<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Webhook Yönetimi', 'advanced-cron-manager'); ?></h1>
    
    <p class="description">
        Cron joblarınız çalıştığında otomatik olarak Slack, Discord, Telegram veya özel webhook'larınıza bildirim gönderin.
    </p>

    <div class="acm-section">
        <h2>🔔 Webhook Yapılandırma</h2>
        
        <form id="acm-webhook-form" class="acm-form-container">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="webhook_hook">Cron Seç *</label>
                    </th>
                    <td>
                        <select id="webhook_hook" name="webhook_hook" class="regular-text" required>
                            <option value="">-- Cron Seçin --</option>
                            <?php foreach ($crons as $cron): ?>
                                <option value="<?php echo esc_attr($cron['hook']); ?>">
                                    <?php echo esc_html($cron['hook']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Webhook tetiklenecek cron'u seçin</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="webhook_enabled">Webhook Aktif</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="webhook_enabled" name="webhook_enabled" value="1" />
                            Bu cron için webhook'u aktif et
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="webhook_type">Webhook Tipi *</label>
                    </th>
                    <td>
                        <select id="webhook_type" name="webhook_type" class="regular-text" required>
                            <option value="generic">🔗 Generic Webhook</option>
                            <option value="slack">💬 Slack</option>
                            <option value="discord">🎮 Discord</option>
                            <option value="telegram">📱 Telegram</option>
                        </select>
                    </td>
                </tr>

                <!-- Generic/Slack/Discord için URL -->
                <tr class="webhook-field webhook-url">
                    <th scope="row">
                        <label for="webhook_url">Webhook URL *</label>
                    </th>
                    <td>
                        <input type="url" id="webhook_url" name="webhook_url" class="large-text" 
                               placeholder="https://hooks.slack.com/services/..." />
                        <p class="description">
                            <span class="webhook-help webhook-help-generic">Generic webhook URL'i</span>
                            <span class="webhook-help webhook-help-slack" style="display:none;">Slack Incoming Webhook URL</span>
                            <span class="webhook-help webhook-help-discord" style="display:none;">Discord Webhook URL</span>
                        </p>
                    </td>
                </tr>

                <!-- Telegram için Bot Token ve Chat ID -->
                <tr class="webhook-field webhook-telegram" style="display:none;">
                    <th scope="row">
                        <label for="webhook_bot_token">Bot Token *</label>
                    </th>
                    <td>
                        <input type="text" id="webhook_bot_token" name="webhook_bot_token" class="large-text" 
                               placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz" />
                        <p class="description">Telegram Bot Token'ınız (@BotFather'dan alın)</p>
                    </td>
                </tr>

                <tr class="webhook-field webhook-telegram" style="display:none;">
                    <th scope="row">
                        <label for="webhook_chat_id">Chat ID *</label>
                    </th>
                    <td>
                        <input type="text" id="webhook_chat_id" name="webhook_chat_id" class="regular-text" 
                               placeholder="-1001234567890" />
                        <p class="description">Telegram grup veya kanal ID'si</p>
                    </td>
                </tr>

                <!-- Generic için özel headers -->
                <tr class="webhook-field webhook-generic-headers">
                    <th scope="row">
                        <label for="webhook_headers">Özel Headers (JSON)</label>
                    </th>
                    <td>
                        <textarea id="webhook_headers" name="webhook_headers" class="large-text" rows="3" 
                                  placeholder='{"Authorization": "Bearer YOUR_TOKEN"}'></textarea>
                        <p class="description">Opsiyonel. JSON formatında özel HTTP başlıkları</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">💾 Webhook Kaydet</button>
                <button type="button" id="test-webhook" class="button button-secondary">🧪 Test Gönder</button>
                <button type="button" id="load-webhook" class="button button-secondary">📥 Mevcut Ayarları Yükle</button>
                <button type="button" id="delete-webhook" class="button button-link-delete">🗑️ Webhook Sil</button>
            </p>
        </form>
    </div>

    <!-- Mevcut Webhook'lar -->
    <div class="acm-section">
        <h2>📋 Yapılandırılmış Webhook'lar</h2>
        
        <?php if (empty($all_webhooks)): ?>
            <div class="notice notice-info">
                <p>Henüz webhook yapılandırılmamış.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="30%">Cron Hook</th>
                        <th width="15%">Tip</th>
                        <th width="10%">Durum</th>
                        <th width="30%">URL/Hedef</th>
                        <th width="15%">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_webhooks as $hook => $config): ?>
                        <tr>
                            <td><strong><?php echo esc_html($hook); ?></strong></td>
                            <td>
                                <?php
                                $icons = array(
                                    'generic' => '🔗 Generic',
                                    'slack' => '💬 Slack',
                                    'discord' => '🎮 Discord',
                                    'telegram' => '📱 Telegram'
                                );
                                echo esc_html($icons[$config['type']] ?? $config['type']);
                                ?>
                            </td>
                            <td>
                                <?php if ($config['enabled']): ?>
                                    <span class="acm-status-badge active">✓ Aktif</span>
                                <?php else: ?>
                                    <span class="acm-status-badge paused">⏸️ Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($config['type'] === 'telegram'): ?>
                                    <code>Bot: <?php echo esc_html(substr($config['bot_token'], 0, 15) . '...'); ?></code>
                                <?php else: ?>
                                    <code><?php echo esc_html(substr($config['url'], 0, 50) . '...'); ?></code>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button button-small acm-webhook-load" data-hook="<?php echo esc_attr($hook); ?>">✏️ Düzenle</button>
                                <button class="button button-small acm-webhook-test" data-hook="<?php echo esc_attr($hook); ?>">🧪 Test</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Yardım -->
    <div class="acm-section">
        <h3>💡 Webhook Nasıl Çalışır?</h3>
        <ol>
            <li><strong>Cron seçin:</strong> Hangi cron için bildirim almak istiyorsanız seçin</li>
            <li><strong>Webhook tipini seçin:</strong> Slack, Discord, Telegram veya Generic</li>
            <li><strong>URL/Kimlik bilgilerini girin:</strong> Her servis için gerekli bilgileri girin</li>
            <li><strong>Test edin:</strong> "Test Gönder" butonu ile webhook'un çalıştığından emin olun</li>
            <li><strong>Kaydedin:</strong> Artık cron her çalıştığında bildirim alacaksınız!</li>
        </ol>

        <h3>🔗 URL'leri Nasıl Alırım?</h3>
        <ul>
            <li><strong>Slack:</strong> Workspace → Apps → Incoming Webhooks → Add to Slack</li>
            <li><strong>Discord:</strong> Sunucu Ayarları → Entegrasyonlar → Webhooks → Yeni Webhook</li>
            <li><strong>Telegram:</strong> @BotFather → /newbot → Token al, ardından @userinfobot ile Chat ID bul</li>
            <li><strong>Generic:</strong> Kendi sunucunuzdaki herhangi bir HTTP endpoint</li>
        </ul>

        <h3>📦 Gönderilen Veri Formatı (Generic)</h3>
        <pre><code>{
  "hook": "my_cron_hook",
  "success": true,
  "timestamp": "2024-11-17 20:30:00",
  "site_url": "https://example.com",
  "data": {
    "args": {...},
    "error": null
  }
}</code></pre>
    </div>
</div>
