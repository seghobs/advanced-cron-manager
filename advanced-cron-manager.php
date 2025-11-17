<?php
/**
 * Plugin Name: Advanced Cron Manager
 * Plugin URI: https://example.com/advanced-cron-manager
 * Description: WordPress cron joblarını anlık izleme, durdurma, düzenleme ve yönetme eklentisi. Tüm zamanlanmış görevleri kontrol altına alın.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: advanced-cron-manager
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Sabitler
define('ACM_VERSION', '1.0.0');
define('ACM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Ana sınıfı yükle
require_once ACM_PLUGIN_DIR . 'includes/class-cron-manager.php';
require_once ACM_PLUGIN_DIR . 'includes/class-cron-ajax.php';
require_once ACM_PLUGIN_DIR . 'includes/class-cron-schedule.php';
require_once ACM_PLUGIN_DIR . 'includes/class-cron-conditions.php';
require_once ACM_PLUGIN_DIR . 'includes/class-cron-tags.php';
require_once ACM_PLUGIN_DIR . 'includes/class-cron-webhooks.php';
require_once ACM_PLUGIN_DIR . 'includes/class-cron-debug.php';
require_once ACM_PLUGIN_DIR . 'includes/class-cron-export-import.php';

// Eklentiyi başlat
function acm_init() {
    $cron_manager = new ACM_Cron_Manager();
    $cron_ajax = new ACM_Cron_Ajax();
}
add_action('plugins_loaded', 'acm_init');

// Aktivasyon
register_activation_hook(__FILE__, 'acm_activate');
function acm_activate() {
    // Varsayılan ayarları kaydet
    if (!get_option('acm_settings')) {
        add_option('acm_settings', array(
            'auto_refresh' => true,
            'refresh_interval' => 30,
            'show_system_crons' => true
        ));
    }
}

// Deaktivasyon
register_deactivation_hook(__FILE__, 'acm_deactivate');
function acm_deactivate() {
    // Geçici verileri temizle
    delete_transient('acm_active_users_count');
}

// Kaldırma
register_uninstall_hook(__FILE__, 'acm_uninstall');
function acm_uninstall() {
    // Tüm ayarları ve verileri sil
    delete_option('acm_settings');
    delete_option('acm_paused_crons');
    delete_option('acm_cron_conditions');
    delete_option('acm_retry_configs');
    delete_option('acm_retry_data');
    delete_option('acm_condition_logs');
    delete_option('acm_retry_logs');
    delete_option('acm_dependency_status');
    delete_option('acm_favorites');
    delete_option('acm_cron_tags');
    delete_option('acm_cron_notes');
    delete_option('acm_cron_colors');
    delete_option('acm_tag_colors');
    delete_option('acm_webhooks');
    
    // Backup dizinini sil
    $backup_dir = WP_CONTENT_DIR . '/acm-backups';
    if (file_exists($backup_dir)) {
        $files = glob($backup_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($backup_dir);
    }
}
