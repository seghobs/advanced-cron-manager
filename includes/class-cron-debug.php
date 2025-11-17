<?php

class ACM_Cron_Debug {

    private $debug_logs = array();

    public function __construct() {
        // Debug modu aktif mi kontrol et
        add_action('init', array($this, 'check_debug_mode'));
    }

    public function check_debug_mode() {
        if (defined('ACM_DEBUG') && ACM_DEBUG) {
            add_action('all', array($this, 'log_all_hooks'), 10, 10);
        }
    }

    /**
     * Cron simülasyonu - gerçek çalıştırmadan test et
     */
    public function simulate_cron($hook, $args = array()) {
        $this->debug_logs = array();
        $this->log('info', "Cron simülasyonu başladı: {$hook}");
        
        // Koşulları kontrol et
        $conditions_manager = new ACM_Cron_Conditions();
        $conditions = $conditions_manager->get_cron_conditions($hook);
        
        if (!empty($conditions)) {
            $this->log('info', 'Koşullar kontrol ediliyor...');
            
            // Trafik kontrolü
            if (isset($conditions['max_active_users']) && $conditions['max_active_users'] > 0) {
                $active_users = $this->get_active_users_count();
                $this->log('info', "Aktif kullanıcı sayısı: {$active_users} / {$conditions['max_active_users']}");
                
                if ($active_users > $conditions['max_active_users']) {
                    $this->log('warning', 'Trafik kontrolü başarısız! Cron çalışmayacak.');
                    return array('success' => false, 'reason' => 'traffic', 'logs' => $this->debug_logs);
                }
            }
            
            // Zaman kontrolü
            if (isset($conditions['time_range'])) {
                $current_hour = (int) current_time('H');
                $this->log('info', "Mevcut saat: {$current_hour}:00");
                $this->log('info', "İzin verilen aralık: {$conditions['time_range']['start']}:00 - {$conditions['time_range']['end']}:00");
                
                if (!$this->check_time_range($conditions['time_range'])) {
                    $this->log('warning', 'Zaman aralığı kontrolü başarısız! Cron çalışmayacak.');
                    return array('success' => false, 'reason' => 'time', 'logs' => $this->debug_logs);
                }
            }
            
            // CPU kontrolü
            if (isset($conditions['max_cpu_load']) && $conditions['max_cpu_load'] > 0) {
                if (function_exists('sys_getloadavg')) {
                    $load = sys_getloadavg();
                    $this->log('info', "CPU yükü: {$load[0]} / {$conditions['max_cpu_load']}");
                    
                    if ($load[0] > $conditions['max_cpu_load']) {
                        $this->log('warning', 'CPU yükü çok yüksek! Cron çalışmayacak.');
                        return array('success' => false, 'reason' => 'cpu', 'logs' => $this->debug_logs);
                    }
                }
            }
            
            // Bağımlılık kontrolü
            if (isset($conditions['dependencies']) && !empty($conditions['dependencies'])) {
                $this->log('info', 'Bağımlılıklar kontrol ediliyor: ' . implode(', ', $conditions['dependencies']));
                
                if (!$conditions_manager->check_dependencies($hook, $conditions['dependencies'])) {
                    $this->log('warning', 'Bağımlılıklar karşılanmadı! Cron çalışmayacak.');
                    return array('success' => false, 'reason' => 'dependencies', 'logs' => $this->debug_logs);
                }
            }
        }
        
        // Hook'a bağlı fonksiyonları kontrol et
        global $wp_filter;
        
        if (isset($wp_filter[$hook])) {
            $this->log('success', 'Hook bulundu! Bağlı fonksiyonlar:');
            
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $function_name = $this->get_callback_name($callback['function']);
                    $this->log('info', "  - Öncelik {$priority}: {$function_name}");
                }
            }
        } else {
            $this->log('error', 'Hook bulunamadı! Bu hook için tanımlı fonksiyon yok.');
            return array('success' => false, 'reason' => 'no_hook', 'logs' => $this->debug_logs);
        }
        
        $this->log('success', 'Simülasyon tamamlandı. Tüm kontroller başarılı!');
        return array('success' => true, 'logs' => $this->debug_logs);
    }

    /**
     * Test modunda cron çalıştır
     */
    public function test_run_cron($hook, $args = array()) {
        $this->debug_logs = array();
        $this->log('info', "Test modu başladı: {$hook}");
        
        try {
            // Önce simülasyon yap
            $simulation = $this->simulate_cron($hook, $args);
            
            if (!$simulation['success']) {
                return $simulation;
            }
            
            $this->log('info', 'Gerçek çalıştırma başlıyor...');
            
            // Hook'u çalıştır
            ob_start();
            $start_time = microtime(true);
            
            do_action_ref_array($hook, $args);
            
            $end_time = microtime(true);
            $execution_time = round(($end_time - $start_time) * 1000, 2);
            
            $output = ob_get_clean();
            
            $this->log('success', "Cron başarıyla çalıştırıldı! Süre: {$execution_time}ms");
            
            if (!empty($output)) {
                $this->log('info', "Çıktı: " . substr($output, 0, 500));
            }
            
            return array(
                'success' => true, 
                'execution_time' => $execution_time,
                'output' => $output,
                'logs' => $this->debug_logs
            );
            
        } catch (Exception $e) {
            $this->log('error', 'Hata: ' . $e->getMessage());
            $this->log('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return array(
                'success' => false, 
                'error' => $e->getMessage(),
                'logs' => $this->debug_logs
            );
        }
    }

    /**
     * Log ekle
     */
    private function log($level, $message) {
        $this->debug_logs[] = array(
            'level' => $level,
            'message' => $message,
            'timestamp' => microtime(true),
            'time' => current_time('H:i:s')
        );
    }

    /**
     * Callback adını al
     */
    private function get_callback_name($callback) {
        if (is_string($callback)) {
            return $callback;
        } elseif (is_array($callback)) {
            if (is_object($callback[0])) {
                return get_class($callback[0]) . '::' . $callback[1];
            } else {
                return $callback[0] . '::' . $callback[1];
            }
        } elseif ($callback instanceof Closure) {
            return 'Closure';
        }
        return 'Unknown';
    }

    /**
     * Aktif kullanıcı sayısını al
     */
    private function get_active_users_count() {
        global $wpdb;
        $time_threshold = time() - (5 * 60);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'session_tokens' 
            AND meta_value LIKE %s",
            '%' . $wpdb->esc_like('"login":' . $time_threshold) . '%'
        ));

        return (int) $count;
    }

    /**
     * Zaman aralığını kontrol et
     */
    private function check_time_range($range) {
        $current_hour = (int) current_time('H');
        $start = (int) $range['start'];
        $end = (int) $range['end'];
        
        if ($start <= $end) {
            return ($current_hour >= $start && $current_hour <= $end);
        } else {
            return ($current_hour >= $start || $current_hour <= $end);
        }
    }

    /**
     * Tüm hook'ları logla
     */
    public function log_all_hooks() {
        $hook = current_filter();
        $args = func_get_args();
        
        error_log("[ACM Debug] Hook: {$hook} | Args: " . print_r($args, true));
    }

    /**
     * Debug loglarını al
     */
    public function get_debug_logs() {
        return $this->debug_logs;
    }
}

new ACM_Cron_Debug();
