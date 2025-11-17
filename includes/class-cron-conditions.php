<?php

class ACM_Cron_Conditions {

    public function __construct() {
        // WordPress cron sistemine entegre ol
        add_filter('schedule_event', array($this, 'intercept_schedule_event'), 10, 1);
        
        // Cron çalışmadan önce koşulları kontrol et
        add_action('acm_before_cron_execution', array($this, 'check_conditions'), 10, 2);
        
        // Retry mekanizması için hook
        add_action('acm_cron_failed', array($this, 'handle_retry'), 10, 3);
        
        // Bağımlılık kontrolü
        add_action('acm_check_dependencies', array($this, 'check_dependencies'), 10, 2);
        
        // Cron başlatma ve bitirme hook'ları
        add_action('wp_loaded', array($this, 'maybe_intercept_crons'));
    }

    /**
     * DOING_CRON durumunda cron hook'larını dinle
     */
    public function maybe_intercept_crons() {
        // Sadece cron çalıştırma sırasında
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }
        
        $settings = get_option('acm_settings', array());
        
        // Koşullu çalıştırma kapalıysa atla
        if (!isset($settings['enable_conditions']) || !$settings['enable_conditions']) {
            return;
        }

        // Tüm kayıtlı cron hook'larını al
        $crons = _get_cron_array();
        if (empty($crons)) {
            return;
        }

        // Her cron hook için dinleyici ekle
        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $details) {
                // WordPress iç hook'larını atla
                if (strpos($hook, 'wp_') === 0 || strpos($hook, 'acm_') === 0) {
                    continue;
                }
                
                // Bu hook için koşullar var mı?
                $conditions = $this->get_cron_conditions($hook);
                if (!empty($conditions)) {
                    // Koşul varsa hook'u dinle
                    add_action($hook, array($this, 'check_before_execution'), 1, 10);
                }
            }
        }
    }

    /**
     * Cron çalışmadan önce koşulları kontrol et
     */
    public function check_before_execution() {
        $hook = current_filter();
        $args = func_get_args();
        
        // Koşulları kontrol et
        if (!$this->check_conditions($hook, $args)) {
            // Koşullar karşılanmadı, cron'u durdur
            $this->log_condition_fail($hook, 'Koşullar karşılanmadı');
            
            // Cron'u yeniden zamanla (5 dakika sonra)
            wp_schedule_single_event(time() + 300, $hook, $args);
            
            // Mevcut çalışmayı durdur
            wp_die('', '', array('response' => 200, 'back_link' => false));
        }

        // Koşullar karşılandı, başarılı olarak işaretle
        $this->mark_success($hook);
    }

    /**
     * Cron planlamasını yakala
     */
    public function intercept_schedule_event($event) {
        // Event objesini geri döndür (değişiklik yapmadan)
        return $event;
    }

    /**
     * Cron için koşulları kontrol et
     */
    public function check_conditions($hook, $args = array()) {
        $conditions = $this->get_cron_conditions($hook);
        
        if (empty($conditions)) {
            return true;
        }

        // Site trafiği kontrolü
        if (isset($conditions['max_active_users']) && $conditions['max_active_users'] > 0) {
            if (!$this->check_traffic_condition($conditions['max_active_users'])) {
                $this->log_condition_fail($hook, 'Trafik çok yüksek');
                return false;
            }
        }

        // Zaman aralığı kontrolü
        if (isset($conditions['time_range'])) {
            if (!$this->check_time_range($conditions['time_range'])) {
                $this->log_condition_fail($hook, 'Zaman aralığı uygun değil');
                return false;
            }
        }

        // CPU kullanımı kontrolü
        if (isset($conditions['max_cpu_load']) && $conditions['max_cpu_load'] > 0) {
            if (!$this->check_cpu_load($conditions['max_cpu_load'])) {
                $this->log_condition_fail($hook, 'CPU yükü çok yüksek');
                return false;
            }
        }

        // Bağımlılık kontrolü
        if (isset($conditions['dependencies']) && !empty($conditions['dependencies'])) {
            if (!$this->check_dependencies($hook, $conditions['dependencies'])) {
                $this->log_condition_fail($hook, 'Bağımlılıklar karşılanmadı');
                return false;
            }
        }

        return true;
    }

    /**
     * Trafik durumunu kontrol et
     */
    private function check_traffic_condition($max_users) {
        // Aktif kullanıcı sayısını kontrol et (WordPress transient kullanarak)
        $active_users = get_transient('acm_active_users_count');
        
        if ($active_users === false) {
            $active_users = $this->count_active_users();
            set_transient('acm_active_users_count', $active_users, 60); // 1 dakika cache
        }

        return $active_users <= $max_users;
    }

    /**
     * Aktif kullanıcı sayısını say
     */
    private function count_active_users() {
        global $wpdb;
        
        // Son 5 dakikada aktif olan kullanıcılar
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
        
        if (isset($range['start']) && isset($range['end'])) {
            $start = (int) $range['start'];
            $end = (int) $range['end'];
            
            // Aynı gün içinde
            if ($start <= $end) {
                return ($current_hour >= $start && $current_hour <= $end);
            } 
            // Gece yarısını geçen aralık (örn: 22:00 - 06:00)
            else {
                return ($current_hour >= $start || $current_hour <= $end);
            }
        }

        return true;
    }

    /**
     * CPU yükünü kontrol et
     */
    private function check_cpu_load($max_load) {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $current_load = $load[0]; // 1 dakikalık ortalama
            
            return $current_load <= $max_load;
        }
        
        return true; // CPU yükü ölçülemiyorsa çalıştır
    }

    /**
     * Bağımlılıkları kontrol et
     */
    public function check_dependencies($hook, $dependencies) {
        if (empty($dependencies)) {
            return true;
        }

        $dependency_status = get_option('acm_dependency_status', array());

        foreach ($dependencies as $dep_hook) {
            // Bağımlı cron son 24 saat içinde başarıyla çalıştı mı?
            if (!isset($dependency_status[$dep_hook])) {
                return false;
            }

            $last_success = $dependency_status[$dep_hook];
            $time_limit = time() - (24 * 3600); // 24 saat

            if ($last_success < $time_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retry mekanizması
     */
    public function handle_retry($hook, $error, $args = array()) {
        $retry_config = $this->get_retry_config($hook);
        
        if (empty($retry_config) || !isset($retry_config['enabled']) || !$retry_config['enabled']) {
            return;
        }

        $max_attempts = isset($retry_config['max_attempts']) ? (int) $retry_config['max_attempts'] : 3;
        $retry_delay = isset($retry_config['retry_delay']) ? (int) $retry_config['retry_delay'] : 300; // 5 dakika
        $backoff_multiplier = isset($retry_config['backoff_multiplier']) ? (float) $retry_config['backoff_multiplier'] : 2;

        // Mevcut deneme sayısını al
        $retry_data = get_option('acm_retry_data', array());
        
        if (!isset($retry_data[$hook])) {
            $retry_data[$hook] = array(
                'attempts' => 0,
                'first_failure' => time(),
                'last_attempt' => time(),
                'error' => $error
            );
        }

        $retry_data[$hook]['attempts']++;
        $retry_data[$hook]['last_attempt'] = time();
        $retry_data[$hook]['error'] = $error;

        // Max deneme sayısına ulaşıldı mı?
        if ($retry_data[$hook]['attempts'] >= $max_attempts) {
            // E-posta bildirimi gönder
            $this->send_failure_notification($hook, $retry_data[$hook]);
            
            // Retry verisini temizle
            unset($retry_data[$hook]);
            update_option('acm_retry_data', $retry_data);
            
            return;
        }

        // Exponential backoff hesapla
        $delay = $retry_delay * pow($backoff_multiplier, $retry_data[$hook]['attempts'] - 1);
        
        // Retry için yeni cron planla
        $next_retry = time() + $delay;
        wp_schedule_single_event($next_retry, $hook, $args);

        update_option('acm_retry_data', $retry_data);

        $this->log_retry($hook, $retry_data[$hook]['attempts'], $next_retry);
    }

    /**
     * Cron başarılı çalıştığında bağımlılık durumunu güncelle
     */
    public function mark_success($hook) {
        $dependency_status = get_option('acm_dependency_status', array());
        $dependency_status[$hook] = time();
        update_option('acm_dependency_status', $dependency_status);

        // Retry verisini temizle
        $retry_data = get_option('acm_retry_data', array());
        if (isset($retry_data[$hook])) {
            unset($retry_data[$hook]);
            update_option('acm_retry_data', $retry_data);
        }
    }

    /**
     * Koşul başarısızlığını logla
     */
    private function log_condition_fail($hook, $reason) {
        $logs = get_option('acm_condition_logs', array());
        
        $logs[] = array(
            'hook' => $hook,
            'reason' => $reason,
            'timestamp' => time(),
            'date' => current_time('mysql')
        );

        // Son 100 log'u sakla
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('acm_condition_logs', $logs);
    }

    /**
     * Retry'yi logla
     */
    private function log_retry($hook, $attempt, $next_retry) {
        $logs = get_option('acm_retry_logs', array());
        
        $logs[] = array(
            'hook' => $hook,
            'attempt' => $attempt,
            'next_retry' => $next_retry,
            'next_retry_date' => date('Y-m-d H:i:s', $next_retry),
            'timestamp' => time(),
            'date' => current_time('mysql')
        );

        // Son 100 log'u sakla
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('acm_retry_logs', $logs);
    }

    /**
     * Başarısızlık bildirimi gönder
     */
    private function send_failure_notification($hook, $retry_data) {
        $admin_email = get_option('admin_email');
        $subject = '[' . get_bloginfo('name') . '] Cron Job Başarısız: ' . $hook;
        
        $message = "Cron Job: {$hook}\n\n";
        $message .= "Durum: Tüm denemeler başarısız\n";
        $message .= "Deneme Sayısı: {$retry_data['attempts']}\n";
        $message .= "İlk Hata: " . date('Y-m-d H:i:s', $retry_data['first_failure']) . "\n";
        $message .= "Son Deneme: " . date('Y-m-d H:i:s', $retry_data['last_attempt']) . "\n";
        $message .= "Hata Mesajı: {$retry_data['error']}\n\n";
        $message .= "Lütfen sorunu kontrol edin.\n\n";
        $message .= "Site: " . get_bloginfo('url');

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Belirli bir cron için koşulları al
     */
    public function get_cron_conditions($hook) {
        $all_conditions = get_option('acm_cron_conditions', array());
        return isset($all_conditions[$hook]) ? $all_conditions[$hook] : array();
    }

    /**
     * Cron için koşulları kaydet
     */
    public function save_cron_conditions($hook, $conditions) {
        $all_conditions = get_option('acm_cron_conditions', array());
        $all_conditions[$hook] = $conditions;
        update_option('acm_cron_conditions', $all_conditions);
    }

    /**
     * Retry yapılandırmasını al
     */
    public function get_retry_config($hook) {
        $all_configs = get_option('acm_retry_configs', array());
        return isset($all_configs[$hook]) ? $all_configs[$hook] : array();
    }

    /**
     * Retry yapılandırmasını kaydet
     */
    public function save_retry_config($hook, $config) {
        $all_configs = get_option('acm_retry_configs', array());
        $all_configs[$hook] = $config;
        update_option('acm_retry_configs', $all_configs);
    }

    /**
     * Tüm koşul loglarını al
     */
    public function get_condition_logs($limit = 50) {
        $logs = get_option('acm_condition_logs', array());
        return array_slice($logs, -$limit);
    }

    /**
     * Tüm retry loglarını al
     */
    public function get_retry_logs($limit = 50) {
        $logs = get_option('acm_retry_logs', array());
        return array_slice($logs, -$limit);
    }

    /**
     * Retry durumunu al
     */
    public function get_retry_status() {
        return get_option('acm_retry_data', array());
    }
}

// Sınıfı başlat
new ACM_Cron_Conditions();
