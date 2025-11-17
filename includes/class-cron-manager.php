<?php

class ACM_Cron_Manager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Cron Manager', 'advanced-cron-manager'),
            __('Cron Manager', 'advanced-cron-manager'),
            'manage_options',
            'advanced-cron-manager',
            array($this, 'render_main_page'),
            'dashicons-clock',
            80
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Tüm Cron Joblar', 'advanced-cron-manager'),
            __('Tüm Cron Joblar', 'advanced-cron-manager'),
            'manage_options',
            'advanced-cron-manager',
            array($this, 'render_main_page')
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Yeni Cron Ekle', 'advanced-cron-manager'),
            __('Yeni Cron Ekle', 'advanced-cron-manager'),
            'manage_options',
            'acm-add-cron',
            array($this, 'render_add_page')
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Koşullu Çalıştırma & Retry', 'advanced-cron-manager'),
            __('Koşullu Çalıştırma', 'advanced-cron-manager'),
            'manage_options',
            'acm-conditions',
            array($this, 'render_conditions_page')
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Webhooks', 'advanced-cron-manager'),
            __('Webhooks', 'advanced-cron-manager'),
            'manage_options',
            'acm-webhooks',
            array($this, 'render_webhooks_page')
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Export/Import', 'advanced-cron-manager'),
            __('Export/Import', 'advanced-cron-manager'),
            'manage_options',
            'acm-export-import',
            array($this, 'render_export_import_page')
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Debug & Test', 'advanced-cron-manager'),
            __('Debug & Test', 'advanced-cron-manager'),
            'manage_options',
            'acm-debug',
            array($this, 'render_debug_page')
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Loglar', 'advanced-cron-manager'),
            __('Loglar', 'advanced-cron-manager'),
            'manage_options',
            'acm-logs',
            array($this, 'render_logs_page')
        );

        add_submenu_page(
            'advanced-cron-manager',
            __('Ayarlar', 'advanced-cron-manager'),
            __('Ayarlar', 'advanced-cron-manager'),
            'manage_options',
            'acm-settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'advanced-cron-manager') === false && strpos($hook, 'acm-') === false) {
            return;
        }

        wp_enqueue_style('acm-admin-style', ACM_PLUGIN_URL . 'assets/css/admin-style.css', array(), ACM_VERSION);
        wp_enqueue_script('acm-admin-script', ACM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), ACM_VERSION, true);
        
        wp_localize_script('acm-admin-script', 'acmAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acm_nonce'),
            'strings' => array(
                'confirm_delete' => __('Bu cron job\'u silmek istediğinizden emin misiniz?', 'advanced-cron-manager'),
                'confirm_run' => __('Bu cron job\'u şimdi çalıştırmak istiyor musunuz?', 'advanced-cron-manager'),
                'success' => __('İşlem başarılı!', 'advanced-cron-manager'),
                'error' => __('Bir hata oluştu!', 'advanced-cron-manager')
            )
        ));
    }

    public function render_main_page() {
        $crons = $this->get_all_cron_jobs();
        include ACM_PLUGIN_DIR . 'templates/main-page.php';
    }

    public function render_add_page() {
        $schedules = wp_get_schedules();
        include ACM_PLUGIN_DIR . 'templates/add-cron.php';
    }

    public function render_conditions_page() {
        $conditions_manager = new ACM_Cron_Conditions();
        $crons = $this->get_all_cron_jobs();
        $condition_logs = $conditions_manager->get_condition_logs(50);
        $retry_logs = $conditions_manager->get_retry_logs(50);
        $retry_status = $conditions_manager->get_retry_status();
        include ACM_PLUGIN_DIR . 'templates/conditions.php';
    }

    public function render_webhooks_page() {
        $webhooks_manager = new ACM_Cron_Webhooks();
        $crons = $this->get_all_cron_jobs();
        $all_webhooks = $webhooks_manager->get_all_webhooks();
        include ACM_PLUGIN_DIR . 'templates/webhooks.php';
    }

    public function render_export_import_page() {
        $export_import_manager = new ACM_Cron_Export_Import();
        $backups = $export_import_manager->list_backups();
        include ACM_PLUGIN_DIR . 'templates/export-import.php';
    }

    public function render_debug_page() {
        $debug_manager = new ACM_Cron_Debug();
        $crons = $this->get_all_cron_jobs();
        include ACM_PLUGIN_DIR . 'templates/debug-page.php';
    }

    public function render_logs_page() {
        $conditions_manager = new ACM_Cron_Conditions();
        $condition_logs = $conditions_manager->get_condition_logs(100);
        $retry_logs = $conditions_manager->get_retry_logs(100);
        include ACM_PLUGIN_DIR . 'templates/logs.php';
    }

    public function render_settings_page() {
        if (isset($_POST['acm_save_settings']) && check_admin_referer('acm_settings_nonce')) {
            $settings = array(
                'auto_refresh' => isset($_POST['auto_refresh']),
                'refresh_interval' => intval($_POST['refresh_interval']),
                'show_system_crons' => isset($_POST['show_system_crons']),
                'enable_conditions' => isset($_POST['enable_conditions']),
                'enable_auto_backup' => isset($_POST['enable_auto_backup']),
                'debug_mode' => isset($_POST['debug_mode'])
            );
            update_option('acm_settings', $settings);
            echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
        }
        
        $settings = get_option('acm_settings');
        include ACM_PLUGIN_DIR . 'templates/settings.php';
    }

    public function get_all_cron_jobs() {
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
                        'hash' => $hash,
                        'next_run' => $timestamp,
                        'next_run_gmt' => get_date_from_gmt(date('Y-m-d H:i:s', $timestamp), 'Y-m-d H:i:s')
                    );
                }
            }
        }

        return $events;
    }

    public function get_cron_info() {
        $doing_cron = defined('DOING_CRON') && DOING_CRON;
        $disable_wp_cron = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        
        return array(
            'total_jobs' => count($this->get_all_cron_jobs()),
            'doing_cron' => $doing_cron,
            'disable_wp_cron' => $disable_wp_cron,
            'alternate_cron' => defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON,
        );
    }
}
