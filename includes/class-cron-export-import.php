<?php

class ACM_Cron_Export_Import {

    public function __construct() {
        add_action('admin_post_acm_export_crons', array($this, 'export_crons'));
        add_action('admin_post_acm_export_csv', array($this, 'export_csv'));
    }

    /**
     * Tüm cron'ları JSON formatında export et
     */
    public function export_crons() {
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok!');
        }

        check_admin_referer('acm_export_nonce');

        $export_data = array(
            'version' => ACM_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'crons' => $this->get_all_crons_data(),
            'conditions' => get_option('acm_cron_conditions', array()),
            'retry_configs' => get_option('acm_retry_configs', array()),
            'webhooks' => get_option('acm_webhooks', array()),
            'settings' => get_option('acm_settings', array())
        );

        $filename = 'cron-manager-backup-' . date('Y-m-d-His') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * CSV formatında export et
     */
    public function export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok!');
        }

        check_admin_referer('acm_export_nonce');

        $crons = $this->get_all_crons_data();
        $filename = 'cron-manager-export-' . date('Y-m-d-His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM ekle (Türkçe karakterler için)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Başlıklar
        fputcsv($output, array('Hook', 'Zamanlama', 'Periyot (saniye)', 'Sonraki Çalışma', 'Parametreler'));
        
        // Veriler
        foreach ($crons as $cron) {
            fputcsv($output, array(
                $cron['hook'],
                $cron['schedule'],
                $cron['interval'],
                date('Y-m-d H:i:s', $cron['next_run']),
                json_encode($cron['args'])
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * JSON'dan import et
     */
    public function import_from_json($json_string) {
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('success' => false, 'message' => 'Geçersiz JSON formatı!');
        }

        if (!isset($data['crons'])) {
            return array('success' => false, 'message' => 'Cron verisi bulunamadı!');
        }

        $imported = 0;
        $skipped = 0;
        $errors = array();

        // Cron'ları import et
        foreach ($data['crons'] as $cron) {
            try {
                $timestamp = isset($cron['next_run']) ? $cron['next_run'] : time() + 300;
                $args = isset($cron['args']) ? $cron['args'] : array();
                
                if ($cron['schedule'] === 'single') {
                    $result = wp_schedule_single_event($timestamp, $cron['hook'], $args);
                } else {
                    $result = wp_schedule_event($timestamp, $cron['schedule'], $cron['hook'], $args);
                }
                
                if ($result !== false) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (Exception $e) {
                $errors[] = "Hata ({$cron['hook']}): " . $e->getMessage();
                $skipped++;
            }
        }

        // Koşulları import et
        if (isset($data['conditions']) && !empty($data['conditions'])) {
            update_option('acm_cron_conditions', $data['conditions']);
        }

        // Retry ayarlarını import et
        if (isset($data['retry_configs']) && !empty($data['retry_configs'])) {
            update_option('acm_retry_configs', $data['retry_configs']);
        }

        // Webhook'ları import et
        if (isset($data['webhooks']) && !empty($data['webhooks'])) {
            update_option('acm_webhooks', $data['webhooks']);
        }

        return array(
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "{$imported} cron başarıyla import edildi, {$skipped} atlandı."
        );
    }

    /**
     * Tüm cron verilerini al
     */
    private function get_all_crons_data() {
        $crons = _get_cron_array();
        $events = array();

        if (empty($crons)) {
            return $events;
        }

        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $details) {
                foreach ($details as $hash => $data) {
                    $events[] = array(
                        'hook' => $hook,
                        'timestamp' => $timestamp,
                        'schedule' => isset($data['schedule']) ? $data['schedule'] : 'single',
                        'interval' => isset($data['interval']) ? $data['interval'] : 0,
                        'args' => isset($data['args']) ? $data['args'] : array(),
                        'next_run' => $timestamp
                    );
                }
            }
        }

        return $events;
    }

    /**
     * Yedekleme oluştur
     */
    public function create_backup() {
        $backup_data = array(
            'version' => ACM_VERSION,
            'backup_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'crons' => $this->get_all_crons_data(),
            'conditions' => get_option('acm_cron_conditions', array()),
            'retry_configs' => get_option('acm_retry_configs', array()),
            'webhooks' => get_option('acm_webhooks', array()),
            'settings' => get_option('acm_settings', array()),
            'dependency_status' => get_option('acm_dependency_status', array()),
            'paused_crons' => get_option('acm_paused_crons', array())
        );

        // Yedekleme dizini
        $backup_dir = WP_CONTENT_DIR . '/acm-backups';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
            
            // .htaccess ekle (güvenlik için)
            file_put_contents($backup_dir . '/.htaccess', 'Deny from all');
        }

        $filename = $backup_dir . '/backup-' . date('Y-m-d-His') . '.json';
        file_put_contents($filename, json_encode($backup_data, JSON_PRETTY_PRINT));

        // Eski yedekleri temizle (son 10 yedekleme dışında)
        $this->cleanup_old_backups($backup_dir);

        return $filename;
    }

    /**
     * Eski yedekleri temizle
     */
    private function cleanup_old_backups($backup_dir, $keep = 10) {
        $files = glob($backup_dir . '/backup-*.json');
        
        if (count($files) > $keep) {
            // Tarihe göre sırala
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // İlk 10'u tut, geri kalanını sil
            $files_to_delete = array_slice($files, $keep);
            foreach ($files_to_delete as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Yedekleri listele
     */
    public function list_backups() {
        $backup_dir = WP_CONTENT_DIR . '/acm-backups';
        
        if (!file_exists($backup_dir)) {
            return array();
        }

        $files = glob($backup_dir . '/backup-*.json');
        $backups = array();

        foreach ($files as $file) {
            $backups[] = array(
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            );
        }

        // Yeniden eskiye sırala
        usort($backups, function($a, $b) {
            return strcmp($b['filename'], $a['filename']);
        });

        return $backups;
    }

    /**
     * Yedekten geri yükle
     */
    public function restore_from_backup($backup_file) {
        if (!file_exists($backup_file)) {
            return array('success' => false, 'message' => 'Yedek dosyası bulunamadı!');
        }

        $json_string = file_get_contents($backup_file);
        return $this->import_from_json($json_string);
    }
}

new ACM_Cron_Export_Import();
