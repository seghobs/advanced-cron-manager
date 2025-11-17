<?php

class ACM_Cron_Webhooks {

    public function __construct() {
        // Cron çalıştığında webhook tetikle
        add_action('acm_after_cron_execution', array($this, 'trigger_webhook'), 10, 3);
    }

    /**
     * Webhook tetikle
     */
    public function trigger_webhook($hook, $success, $data = array()) {
        $webhooks = get_option('acm_webhooks', array());
        
        if (empty($webhooks[$hook])) {
            return;
        }

        $webhook_config = $webhooks[$hook];
        
        if (!isset($webhook_config['enabled']) || !$webhook_config['enabled']) {
            return;
        }

        // Webhook tipine göre gönder
        $type = isset($webhook_config['type']) ? $webhook_config['type'] : 'generic';
        
        switch ($type) {
            case 'slack':
                $this->send_slack_webhook($hook, $success, $data, $webhook_config);
                break;
                
            case 'discord':
                $this->send_discord_webhook($hook, $success, $data, $webhook_config);
                break;
                
            case 'telegram':
                $this->send_telegram_webhook($hook, $success, $data, $webhook_config);
                break;
                
            case 'generic':
            default:
                $this->send_generic_webhook($hook, $success, $data, $webhook_config);
                break;
        }
    }

    /**
     * Generic webhook gönder
     */
    private function send_generic_webhook($hook, $success, $data, $config) {
        if (empty($config['url'])) {
            return;
        }

        $payload = array(
            'hook' => $hook,
            'success' => $success,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'data' => $data
        );

        $args = array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        );

        // Özel başlıklar varsa ekle
        if (!empty($config['headers'])) {
            $custom_headers = json_decode($config['headers'], true);
            if (is_array($custom_headers)) {
                $args['headers'] = array_merge($args['headers'], $custom_headers);
            }
        }

        wp_remote_post($config['url'], $args);
    }

    /**
     * Slack webhook gönder
     */
    private function send_slack_webhook($hook, $success, $data, $config) {
        if (empty($config['url'])) {
            return;
        }

        $color = $success ? '#36a64f' : '#ff0000';
        $status = $success ? '✅ Başarılı' : '❌ Başarısız';

        $payload = array(
            'attachments' => array(
                array(
                    'color' => $color,
                    'title' => 'Cron Job Bildirim',
                    'fields' => array(
                        array(
                            'title' => 'Hook',
                            'value' => $hook,
                            'short' => true
                        ),
                        array(
                            'title' => 'Durum',
                            'value' => $status,
                            'short' => true
                        ),
                        array(
                            'title' => 'Zaman',
                            'value' => current_time('Y-m-d H:i:s'),
                            'short' => true
                        ),
                        array(
                            'title' => 'Site',
                            'value' => get_site_url(),
                            'short' => true
                        )
                    ),
                    'footer' => 'Advanced Cron Manager',
                    'ts' => time()
                )
            )
        );

        $args = array(
            'body' => json_encode($payload),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 10
        );

        wp_remote_post($config['url'], $args);
    }

    /**
     * Discord webhook gönder
     */
    private function send_discord_webhook($hook, $success, $data, $config) {
        if (empty($config['url'])) {
            return;
        }

        $color = $success ? 3066993 : 15158332; // Yeşil veya kırmızı
        $status = $success ? '✅ Başarılı' : '❌ Başarısız';

        $payload = array(
            'embeds' => array(
                array(
                    'title' => '🔔 Cron Job Bildirim',
                    'color' => $color,
                    'fields' => array(
                        array(
                            'name' => 'Hook',
                            'value' => $hook,
                            'inline' => true
                        ),
                        array(
                            'name' => 'Durum',
                            'value' => $status,
                            'inline' => true
                        ),
                        array(
                            'name' => 'Zaman',
                            'value' => current_time('Y-m-d H:i:s'),
                            'inline' => false
                        )
                    ),
                    'footer' => array(
                        'text' => 'Advanced Cron Manager • ' . get_site_url()
                    ),
                    'timestamp' => date('c')
                )
            )
        );

        $args = array(
            'body' => json_encode($payload),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 10
        );

        wp_remote_post($config['url'], $args);
    }

    /**
     * Telegram webhook gönder
     */
    private function send_telegram_webhook($hook, $success, $data, $config) {
        if (empty($config['bot_token']) || empty($config['chat_id'])) {
            return;
        }

        $status = $success ? '✅ Başarılı' : '❌ Başarısız';
        
        $message = "🔔 <b>Cron Job Bildirim</b>\n\n";
        $message .= "🎯 <b>Hook:</b> <code>{$hook}</code>\n";
        $message .= "📊 <b>Durum:</b> {$status}\n";
        $message .= "⏰ <b>Zaman:</b> " . current_time('Y-m-d H:i:s') . "\n";
        $message .= "🌐 <b>Site:</b> " . get_site_url();

        $url = "https://api.telegram.org/bot{$config['bot_token']}/sendMessage";
        
        $args = array(
            'body' => array(
                'chat_id' => $config['chat_id'],
                'text' => $message,
                'parse_mode' => 'HTML'
            ),
            'timeout' => 10
        );

        wp_remote_post($url, $args);
    }

    /**
     * Webhook yapılandırmasını kaydet
     */
    public function save_webhook_config($hook, $config) {
        $webhooks = get_option('acm_webhooks', array());
        $webhooks[$hook] = $config;
        update_option('acm_webhooks', $webhooks);
    }

    /**
     * Webhook yapılandırmasını al
     */
    public function get_webhook_config($hook) {
        $webhooks = get_option('acm_webhooks', array());
        return isset($webhooks[$hook]) ? $webhooks[$hook] : array();
    }

    /**
     * Webhook'u test et
     */
    public function test_webhook($hook, $config) {
        $test_data = array(
            'message' => 'Bu bir test mesajıdır',
            'test' => true
        );

        $this->trigger_webhook($hook, true, $test_data);
    }

    /**
     * Tüm webhook'ları al
     */
    public function get_all_webhooks() {
        return get_option('acm_webhooks', array());
    }
}

new ACM_Cron_Webhooks();
