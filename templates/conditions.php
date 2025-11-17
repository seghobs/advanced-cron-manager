<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Koşullu Çalıştırma & Retry Yönetimi', 'advanced-cron-manager'); ?></h1>

    <div class="acm-tabs">
        <button class="acm-tab-btn active" data-tab="conditions">⚡ Koşullar</button>
        <button class="acm-tab-btn" data-tab="retry">🔄 Retry Ayarları</button>
        <button class="acm-tab-btn" data-tab="logs">📝 Loglar</button>
        <button class="acm-tab-btn" data-tab="status">📊 Durum</button>
    </div>

    <!-- Koşullar Sekmesi -->
    <div class="acm-tab-content active" id="tab-conditions">
        <div class="acm-section">
            <h2>Cron için Koşul Tanımla</h2>
            <p>Bir cron job'un hangi koşullarda çalışacağını belirleyin.</p>

            <form id="acm-condition-form" class="acm-form-container">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="condition_hook">Cron Seç *</label>
                        </th>
                        <td>
                            <select id="condition_hook" name="condition_hook" class="regular-text" required>
                                <option value="">-- Cron Seçin --</option>
                                <?php foreach ($crons as $cron): ?>
                                    <option value="<?php echo esc_attr($cron['hook']); ?>">
                                        <?php echo esc_html($cron['hook']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_active_users">Maksimum Aktif Kullanıcı</label>
                        </th>
                        <td>
                            <input type="number" id="max_active_users" name="max_active_users" min="0" value="0" />
                            <p class="description">0 = kontrol yapma. Sitede bu sayıdan fazla kullanıcı varsa cron çalışmaz.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label>Çalışma Saatleri</label>
                        </th>
                        <td>
                            <label>Başlangıç: 
                                <input type="number" id="time_start" name="time_start" min="0" max="23" value="0" style="width: 80px;" />
                            </label>
                            <label style="margin-left: 20px;">Bitiş: 
                                <input type="number" id="time_end" name="time_end" min="0" max="23" value="23" style="width: 80px;" />
                            </label>
                            <p class="description">Cron sadece bu saat aralığında çalışır (0-23 arası, örn: 22-6 = gece 10'dan sabah 6'ya)</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_cpu_load">Maksimum CPU Yükü</label>
                        </th>
                        <td>
                            <input type="number" id="max_cpu_load" name="max_cpu_load" min="0" step="0.1" value="0" />
                            <p class="description">0 = kontrol yapma. CPU yükü bu değeri aşarsa cron çalışmaz (örn: 2.5)</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="dependencies">Bağımlılıklar</label>
                        </th>
                        <td>
                            <select id="dependencies" name="dependencies[]" class="regular-text" multiple size="5">
                                <?php foreach ($crons as $cron): ?>
                                    <option value="<?php echo esc_attr($cron['hook']); ?>">
                                        <?php echo esc_html($cron['hook']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Bu cron çalışmadan önce seçili cron'lar son 24 saat içinde başarıyla çalışmış olmalı</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">💾 Koşulları Kaydet</button>
                    <button type="button" id="load-conditions" class="button button-secondary">📥 Mevcut Koşulları Yükle</button>
                </p>
            </form>
        </div>

        <div class="acm-section">
            <h3>💡 Koşul Örnekleri</h3>
            <ul>
                <li><strong>Gece Çalışan Backup:</strong> Saat aralığı: 22-06, Max kullanıcı: 5</li>
                <li><strong>Düşük Trafikli Temizlik:</strong> Max kullanıcı: 10, Max CPU: 1.5</li>
                <li><strong>Sıralı İşlemler:</strong> Önce "data_import" sonra "data_process" bağımlılık ekle</li>
            </ul>
        </div>
    </div>

    <!-- Retry Sekmesi -->
    <div class="acm-tab-content" id="tab-retry">
        <div class="acm-section">
            <h2>Retry (Tekrar Deneme) Ayarları</h2>
            <p>Cron başarısız olduğunda otomatik tekrar deneme yapılandırması.</p>

            <form id="acm-retry-form" class="acm-form-container">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="retry_hook">Cron Seç *</label>
                        </th>
                        <td>
                            <select id="retry_hook" name="retry_hook" class="regular-text" required>
                                <option value="">-- Cron Seçin --</option>
                                <?php foreach ($crons as $cron): ?>
                                    <option value="<?php echo esc_attr($cron['hook']); ?>">
                                        <?php echo esc_html($cron['hook']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="retry_enabled">Retry Aktif</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="retry_enabled" name="retry_enabled" value="1" />
                                Bu cron için retry mekanizmasını aktif et
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_attempts">Maksimum Deneme</label>
                        </th>
                        <td>
                            <input type="number" id="max_attempts" name="max_attempts" min="1" max="10" value="3" />
                            <p class="description">Başarısız olduğunda kaç kez tekrar denenecek (1-10 arası)</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="retry_delay">İlk Bekleme Süresi</label>
                        </th>
                        <td>
                            <input type="number" id="retry_delay" name="retry_delay" min="60" value="300" />
                            <p class="description">İlk tekrar denemeden önce beklenecek süre (saniye cinsinden, varsayılan: 300 = 5 dakika)</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="backoff_multiplier">Bekleme Çarpanı</label>
                        </th>
                        <td>
                            <input type="number" id="backoff_multiplier" name="backoff_multiplier" min="1" step="0.1" value="2" />
                            <p class="description">Her denemede bekleme süresinin kaç katına çıkacağı (Exponential Backoff)</p>
                            <small>Örnek: Delay=300s, Çarpan=2 → 1. deneme: 300s, 2. deneme: 600s, 3. deneme: 1200s</small>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">💾 Retry Ayarlarını Kaydet</button>
                    <button type="button" id="load-retry" class="button button-secondary">📥 Mevcut Ayarları Yükle</button>
                </p>
            </form>
        </div>

        <div class="acm-section">
            <h3>🔍 Retry Nasıl Çalışır?</h3>
            <ol>
                <li>Cron çalışır ve başarısız olur</li>
                <li>Sistem belirlediğiniz süre sonra tekrar dener</li>
                <li>Her denemede bekleme süresi çarpanla artar (Exponential Backoff)</li>
                <li>Maksimum deneme sayısına ulaşılırsa admin'e e-posta gönderilir</li>
                <li>Başarılı olduğunda retry verisi temizlenir</li>
            </ol>
        </div>
    </div>

    <!-- Loglar Sekmesi -->
    <div class="acm-tab-content" id="tab-logs">
        <div class="acm-section">
            <h2>📋 Koşul Kontrol Logları</h2>
            <p>Koşullar nedeniyle çalışmayan cron'ların geçmişi.</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="25%">Cron Hook</th>
                        <th width="35%">Başarısızlık Nedeni</th>
                        <th width="20%">Tarih/Saat</th>
                        <th width="20%">Zaman Önce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($condition_logs)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Henüz log yok.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_reverse($condition_logs) as $log): ?>
                            <tr>
                                <td><strong><?php echo esc_html($log['hook']); ?></strong></td>
                                <td><?php echo esc_html($log['reason']); ?></td>
                                <td><?php echo esc_html($log['date']); ?></td>
                                <td><?php echo human_time_diff($log['timestamp'], current_time('timestamp')) . ' önce'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="acm-section" style="margin-top: 30px;">
            <h2>🔄 Retry Logları</h2>
            <p>Başarısız olup tekrar denenen cron'lar.</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="25%">Cron Hook</th>
                        <th width="15%">Deneme</th>
                        <th width="25%">Sonraki Deneme</th>
                        <th width="20%">Tarih/Saat</th>
                        <th width="15%">Zaman Önce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retry_logs)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Henüz log yok.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_reverse($retry_logs) as $log): ?>
                            <tr>
                                <td><strong><?php echo esc_html($log['hook']); ?></strong></td>
                                <td><?php echo esc_html($log['attempt']); ?>. deneme</td>
                                <td><?php echo esc_html($log['next_retry_date']); ?></td>
                                <td><?php echo esc_html($log['date']); ?></td>
                                <td><?php echo human_time_diff($log['timestamp'], current_time('timestamp')) . ' önce'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Durum Sekmesi -->
    <div class="acm-tab-content" id="tab-status">
        <div class="acm-section">
            <h2>📊 Aktif Retry Durumları</h2>
            <p>Şu anda retry bekleyen cron'lar.</p>

            <?php if (empty($retry_status)): ?>
                <div class="notice notice-success">
                    <p>✅ Şu anda retry bekleyen cron yok. Tüm cron'lar başarılı çalışıyor!</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="25%">Cron Hook</th>
                            <th width="15%">Deneme Sayısı</th>
                            <th width="20%">İlk Hata</th>
                            <th width="20%">Son Deneme</th>
                            <th width="20%">Hata Mesajı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($retry_status as $hook => $data): ?>
                            <tr>
                                <td><strong><?php echo esc_html($hook); ?></strong></td>
                                <td>
                                    <span class="acm-status-badge overdue">
                                        <?php echo esc_html($data['attempts']); ?> deneme
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i:s', $data['first_failure']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', $data['last_attempt']); ?></td>
                                <td><code><?php echo esc_html($data['error']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
