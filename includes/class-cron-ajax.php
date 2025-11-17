<?php

class ACM_Cron_Ajax {

    public function __construct() {
        // AJAX aksiyonları
        add_action('wp_ajax_acm_run_cron', array($this, 'run_cron_now'));
        add_action('wp_ajax_acm_delete_cron', array($this, 'delete_cron'));
        add_action('wp_ajax_acm_pause_cron', array($this, 'pause_cron'));
        add_action('wp_ajax_acm_resume_cron', array($this, 'resume_cron'));
        add_action('wp_ajax_acm_add_cron', array($this, 'add_cron'));
        add_action('wp_ajax_acm_edit_cron', array($this, 'edit_cron'));
        add_action('wp_ajax_acm_get_crons', array($this, 'get_crons_ajax'));
        
        // Koşullu çalıştırma ve retry aksiyonları
        add_action('wp_ajax_acm_save_conditions', array($this, 'save_conditions'));
        add_action('wp_ajax_acm_load_conditions', array($this, 'load_conditions'));
        add_action('wp_ajax_acm_save_retry_config', array($this, 'save_retry_config'));
        add_action('wp_ajax_acm_load_retry_config', array($this, 'load_retry_config'));
        
        // Favori ve etiket aksiyonları
        add_action('wp_ajax_acm_toggle_favorite', array($this, 'toggle_favorite'));
        add_action('wp_ajax_acm_add_tag', array($this, 'add_tag'));
        add_action('wp_ajax_acm_remove_tag', array($this, 'remove_tag'));
        add_action('wp_ajax_acm_save_note', array($this, 'save_note'));
        add_action('wp_ajax_acm_get_tags', array($this, 'get_tags'));
        
        // Webhook aksiyonları
        add_action('wp_ajax_acm_save_webhook', array($this, 'save_webhook'));
        add_action('wp_ajax_acm_test_webhook', array($this, 'test_webhook'));
        add_action('wp_ajax_acm_delete_webhook', array($this, 'delete_webhook'));
        
        // Bulk işlemler
        add_action('wp_ajax_acm_bulk_delete', array($this, 'bulk_delete'));
        add_action('wp_ajax_acm_bulk_pause', array($this, 'bulk_pause'));
        add_action('wp_ajax_acm_bulk_resume', array($this, 'bulk_resume'));
        
        // Export/Import aksiyonları
        add_action('wp_ajax_acm_import_json', array($this, 'import_json'));
        add_action('wp_ajax_acm_create_backup', array($this, 'create_backup'));
        add_action('wp_ajax_acm_restore_backup', array($this, 'restore_backup'));
        add_action('wp_ajax_acm_delete_backup', array($this, 'delete_backup'));
        
        // Debug aksiyonları
        add_action('wp_ajax_acm_simulate_cron', array($this, 'simulate_cron'));
        add_action('wp_ajax_acm_test_run_cron', array($this, 'test_run_cron'));
        
        // Log aksiyonları
        add_action('wp_ajax_acm_clear_logs', array($this, 'clear_logs'));
    }

    public function run_cron_now() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $timestamp = intval($_POST['timestamp']);
        $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();

        // Cron'u şimdi çalıştır
        $result = spawn_cron();
        
        $success = true;
        $error = null;
        
        try {
            // Hook'u tetikle
            do_action_ref_array($hook, $args);
        } catch (Exception $e) {
            $success = false;
            $error = $e->getMessage();
        }
        
        // Webhook tetikle
        do_action('acm_after_cron_execution', $hook, $success, array(
            'args' => $args,
            'error' => $error,
            'manually_triggered' => true
        ));

        wp_send_json_success(array(
            'message' => 'Cron job başarıyla çalıştırıldı!',
            'hook' => $hook
        ));
    }

    public function delete_cron() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $timestamp = intval($_POST['timestamp']);
        $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();

        $result = wp_unschedule_event($timestamp, $hook, $args);

        if ($result === false) {
            wp_send_json_error(array('message' => 'Cron job silinemedi!'));
        }

        wp_send_json_success(array('message' => 'Cron job başarıyla silindi!'));
    }

    public function pause_cron() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $timestamp = intval($_POST['timestamp']);
        $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();

        // Mevcut cron'u sil ve duraklatılmış olarak işaretle
        wp_unschedule_event($timestamp, $hook, $args);
        
        // Duraklatılmış cron'ları sakla
        $paused = get_option('acm_paused_crons', array());
        $paused[] = array(
            'hook' => $hook,
            'timestamp' => $timestamp,
            'args' => $args,
            'paused_at' => current_time('timestamp')
        );
        update_option('acm_paused_crons', $paused);

        wp_send_json_success(array('message' => 'Cron job duraklatıldı!'));
    }

    public function resume_cron() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $paused = get_option('acm_paused_crons', array());
        
        foreach ($paused as $key => $cron) {
            if ($cron['hook'] === $hook) {
                // Cron'u yeniden planla
                $schedule = wp_get_schedule($hook);
                if ($schedule) {
                    wp_schedule_event(time(), $schedule, $hook, $cron['args']);
                } else {
                    wp_schedule_single_event(time() + 300, $hook, $cron['args']);
                }
                
                unset($paused[$key]);
                update_option('acm_paused_crons', array_values($paused));
                
                wp_send_json_success(array('message' => 'Cron job devam ettirildi!'));
                return;
            }
        }

        wp_send_json_error(array('message' => 'Duraklatılmış cron bulunamadı!'));
    }

    public function add_cron() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $schedule = sanitize_text_field($_POST['schedule']);
        $timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : time() + 300;
        $args = isset($_POST['args']) && !empty($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();

        if (empty($hook)) {
            wp_send_json_error(array('message' => 'Hook adı gerekli!'));
        }

        if ($schedule === 'single') {
            $result = wp_schedule_single_event($timestamp, $hook, $args);
        } else {
            $result = wp_schedule_event($timestamp, $schedule, $hook, $args);
        }

        if ($result === false) {
            wp_send_json_error(array('message' => 'Cron job eklenemedi!'));
        }

        wp_send_json_success(array('message' => 'Cron job başarıyla eklendi!'));
    }

    public function edit_cron() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        // Önce eski cron'u sil
        $old_hook = sanitize_text_field($_POST['old_hook']);
        $old_timestamp = intval($_POST['old_timestamp']);
        $old_args = isset($_POST['old_args']) ? json_decode(stripslashes($_POST['old_args']), true) : array();
        
        wp_unschedule_event($old_timestamp, $old_hook, $old_args);

        // Yeni cron'u ekle
        $hook = sanitize_text_field($_POST['hook']);
        $schedule = sanitize_text_field($_POST['schedule']);
        $timestamp = intval($_POST['timestamp']);
        $args = isset($_POST['args']) && !empty($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();

        if ($schedule === 'single') {
            wp_schedule_single_event($timestamp, $hook, $args);
        } else {
            wp_schedule_event($timestamp, $schedule, $hook, $args);
        }

        wp_send_json_success(array('message' => 'Cron job güncellendi!'));
    }

    public function get_crons_ajax() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $manager = new ACM_Cron_Manager();
        $crons = $manager->get_all_cron_jobs();
        $info = $manager->get_cron_info();

        wp_send_json_success(array(
            'crons' => $crons,
            'info' => $info,
            'current_time' => current_time('timestamp')
        ));
    }

    public function save_conditions() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $conditions = array(
            'max_active_users' => isset($_POST['max_active_users']) ? intval($_POST['max_active_users']) : 0,
            'max_cpu_load' => isset($_POST['max_cpu_load']) ? floatval($_POST['max_cpu_load']) : 0,
            'time_range' => array(
                'start' => isset($_POST['time_start']) ? intval($_POST['time_start']) : 0,
                'end' => isset($_POST['time_end']) ? intval($_POST['time_end']) : 23
            ),
            'dependencies' => isset($_POST['dependencies']) ? $_POST['dependencies'] : array()
        );

        $conditions_manager = new ACM_Cron_Conditions();
        $conditions_manager->save_cron_conditions($hook, $conditions);

        wp_send_json_success(array('message' => 'Koşullar başarıyla kaydedildi!'));
    }

    public function load_conditions() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $conditions_manager = new ACM_Cron_Conditions();
        $conditions = $conditions_manager->get_cron_conditions($hook);

        wp_send_json_success(array(
            'conditions' => $conditions,
            'message' => 'Koşullar yüklendi'
        ));
    }

    public function save_retry_config() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $config = array(
            'enabled' => isset($_POST['enabled']) && $_POST['enabled'] == '1',
            'max_attempts' => isset($_POST['max_attempts']) ? intval($_POST['max_attempts']) : 3,
            'retry_delay' => isset($_POST['retry_delay']) ? intval($_POST['retry_delay']) : 300,
            'backoff_multiplier' => isset($_POST['backoff_multiplier']) ? floatval($_POST['backoff_multiplier']) : 2
        );

        $conditions_manager = new ACM_Cron_Conditions();
        $conditions_manager->save_retry_config($hook, $config);

        wp_send_json_success(array('message' => 'Retry ayarları başarıyla kaydedildi!'));
    }

    public function load_retry_config() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $conditions_manager = new ACM_Cron_Conditions();
        $config = $conditions_manager->get_retry_config($hook);

        wp_send_json_success(array(
            'config' => $config,
            'message' => 'Retry ayarları yüklendi'
        ));
    }

    public function toggle_favorite() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $tags_manager = new ACM_Cron_Tags();
        $result = $tags_manager->toggle_favorite($hook);

        $message = $result['action'] === 'added' ? 'Favorilere eklendi!' : 'Favorilerden çıkarıldı!';
        
        wp_send_json_success(array(
            'message' => $message,
            'action' => $result['action'],
            'is_favorite' => $result['action'] === 'added'
        ));
    }

    public function add_tag() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $tag = sanitize_text_field($_POST['tag']);
        
        if (empty($tag)) {
            wp_send_json_error(array('message' => 'Etiket boş olamaz!'));
        }

        $tags_manager = new ACM_Cron_Tags();
        $tags_manager->add_tag($hook, $tag);
        
        $tags = $tags_manager->get_cron_tags($hook);

        wp_send_json_success(array(
            'message' => 'Etiket eklendi!',
            'tags' => $tags
        ));
    }

    public function remove_tag() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $tag = sanitize_text_field($_POST['tag']);

        $tags_manager = new ACM_Cron_Tags();
        $tags_manager->remove_tag($hook, $tag);
        
        $tags = $tags_manager->get_cron_tags($hook);

        wp_send_json_success(array(
            'message' => 'Etiket kaldırıldı!',
            'tags' => $tags
        ));
    }

    public function save_note() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $note = sanitize_textarea_field($_POST['note']);

        $tags_manager = new ACM_Cron_Tags();
        $tags_manager->save_note($hook, $note);

        wp_send_json_success(array('message' => 'Not kaydedildi!'));
    }

    public function get_tags() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $tags_manager = new ACM_Cron_Tags();
        $all_tags = $tags_manager->get_all_tags();

        wp_send_json_success(array(
            'tags' => $all_tags
        ));
    }

    public function save_webhook() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] == '1';
        $type = sanitize_text_field($_POST['type']);
        $url = esc_url_raw($_POST['url']);
        $headers = isset($_POST['headers']) ? sanitize_textarea_field($_POST['headers']) : '';
        $bot_token = isset($_POST['bot_token']) ? sanitize_text_field($_POST['bot_token']) : '';
        $chat_id = isset($_POST['chat_id']) ? sanitize_text_field($_POST['chat_id']) : '';

        $config = array(
            'enabled' => $enabled,
            'type' => $type,
            'url' => $url,
            'headers' => $headers,
            'bot_token' => $bot_token,
            'chat_id' => $chat_id
        );

        $webhooks_manager = new ACM_Cron_Webhooks();
        $webhooks_manager->save_webhook_config($hook, $config);

        wp_send_json_success(array('message' => 'Webhook ayarları kaydedildi!'));
    }

    public function test_webhook() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        
        $webhooks_manager = new ACM_Cron_Webhooks();
        $config = $webhooks_manager->get_webhook_config($hook);
        
        if (empty($config)) {
            wp_send_json_error(array('message' => 'Bu cron için webhook yapılandırması bulunamadı!'));
        }
        
        $webhooks_manager->test_webhook($hook, $config);

        wp_send_json_success(array('message' => 'Test webhook’u gönderildi!'));
    }

    public function delete_webhook() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        
        $webhooks = get_option('acm_webhooks', array());
        if (isset($webhooks[$hook])) {
            unset($webhooks[$hook]);
            update_option('acm_webhooks', $webhooks);
        }

        wp_send_json_success(array('message' => 'Webhook silindi!'));
    }

    // BULK İŞLEMLER
    public function bulk_delete() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hooks = isset($_POST['hooks']) ? $_POST['hooks'] : array();
        $deleted = 0;

        foreach ($hooks as $hook_data) {
            $hook = sanitize_text_field($hook_data['hook']);
            $timestamp = intval($hook_data['timestamp']);
            $args = isset($hook_data['args']) ? json_decode(stripslashes($hook_data['args']), true) : array();

            if (wp_unschedule_event($timestamp, $hook, $args) !== false) {
                $deleted++;
            }
        }

        wp_send_json_success(array(
            'message' => "{$deleted} cron job başarıyla silindi!",
            'deleted' => $deleted
        ));
    }

    public function bulk_pause() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hooks = isset($_POST['hooks']) ? $_POST['hooks'] : array();
        $paused = get_option('acm_paused_crons', array());
        $count = 0;

        foreach ($hooks as $hook_data) {
            $hook = sanitize_text_field($hook_data['hook']);
            $timestamp = intval($hook_data['timestamp']);
            $args = isset($hook_data['args']) ? json_decode(stripslashes($hook_data['args']), true) : array();

            wp_unschedule_event($timestamp, $hook, $args);
            
            $paused[] = array(
                'hook' => $hook,
                'timestamp' => $timestamp,
                'args' => $args,
                'paused_at' => current_time('timestamp')
            );
            $count++;
        }

        update_option('acm_paused_crons', $paused);

        wp_send_json_success(array(
            'message' => "{$count} cron job duraklatıldı!",
            'paused' => $count
        ));
    }

    public function bulk_resume() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hooks = isset($_POST['hooks']) ? $_POST['hooks'] : array();
        $paused = get_option('acm_paused_crons', array());
        $count = 0;

        foreach ($hooks as $hook_name) {
            foreach ($paused as $key => $cron) {
                if ($cron['hook'] === $hook_name) {
                    $schedule = wp_get_schedule($hook_name);
                    if ($schedule) {
                        wp_schedule_event(time(), $schedule, $hook_name, $cron['args']);
                    } else {
                        wp_schedule_single_event(time() + 300, $hook_name, $cron['args']);
                    }
                    
                    unset($paused[$key]);
                    $count++;
                }
            }
        }

        update_option('acm_paused_crons', array_values($paused));

        wp_send_json_success(array(
            'message' => "{$count} cron job devam ettirildi!",
            'resumed' => $count
        ));
    }

    // EXPORT/IMPORT
    public function import_json() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        if (!isset($_FILES['import_file'])) {
            wp_send_json_error(array('message' => 'Dosya yüklenmedi!'));
        }

        $file = $_FILES['import_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'Dosya yükleme hatası!'));
        }

        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($file['type'] !== 'application/json' && strtolower($file_ext) !== 'json') {
            wp_send_json_error(array('message' => 'Sadece JSON dosyaları yüklenebilir!'));
        }

        $json_string = file_get_contents($file['tmp_name']);
        
        $export_import_manager = new ACM_Cron_Export_Import();
        $result = $export_import_manager->import_from_json($json_string);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    public function create_backup() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $export_import_manager = new ACM_Cron_Export_Import();
        $filename = $export_import_manager->create_backup();

        if ($filename) {
            wp_send_json_success(array(
                'message' => 'Yedek başarıyla oluşturuldu!',
                'filename' => basename($filename)
            ));
        } else {
            wp_send_json_error(array('message' => 'Yedek oluşturulamadı!'));
        }
    }

    public function restore_backup() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $filename = sanitize_file_name($_POST['filename']);
        $backup_file = WP_CONTENT_DIR . '/acm-backups/' . $filename;

        $export_import_manager = new ACM_Cron_Export_Import();
        $result = $export_import_manager->restore_from_backup($backup_file);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    public function delete_backup() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $filename = sanitize_file_name($_POST['filename']);
        $backup_file = WP_CONTENT_DIR . '/acm-backups/' . $filename;

        if (file_exists($backup_file) && @unlink($backup_file)) {
            wp_send_json_success(array('message' => 'Yedek silindi!'));
        } else {
            wp_send_json_error(array('message' => 'Yedek silinemedi!'));
        }
    }

    // DEBUG
    public function simulate_cron() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();

        $debug_manager = new ACM_Cron_Debug();
        $result = $debug_manager->simulate_cron($hook, $args);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    public function test_run_cron() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $hook = sanitize_text_field($_POST['hook']);
        $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();

        $debug_manager = new ACM_Cron_Debug();
        $result = $debug_manager->test_run_cron($hook, $args);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    // LOG TEMIZLEME
    public function clear_logs() {
        check_ajax_referer('acm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }

        $log_type = sanitize_text_field($_POST['log_type']);

        switch ($log_type) {
            case 'condition':
                update_option('acm_condition_logs', array());
                wp_send_json_success(array('message' => 'Koşul logları temizlendi!'));
                break;
                
            case 'retry':
                update_option('acm_retry_logs', array());
                wp_send_json_success(array('message' => 'Retry logları temizlendi!'));
                break;
                
            case 'all':
                update_option('acm_condition_logs', array());
                update_option('acm_retry_logs', array());
                wp_send_json_success(array('message' => 'Tüm loglar temizlendi!'));
                break;
                
            default:
                wp_send_json_error(array('message' => 'Geçersiz log tipi!'));
        }
    }
}
