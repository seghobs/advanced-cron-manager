<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Cron Manager Ayarları', 'advanced-cron-manager'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('acm_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Otomatik Yenileme</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_refresh" value="1" <?php checked($settings['auto_refresh'], true); ?> />
                        Cron listesini otomatik olarak yenile
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">Yenileme Aralığı</th>
                <td>
                    <input type="number" name="refresh_interval" value="<?php echo esc_attr($settings['refresh_interval']); ?>" min="10" max="300" />
                    <p class="description">Saniye cinsinden (10-300 arası)</p>
                </td>
            </tr>

            <tr>
                <th scope="row">Sistem Cron'larını Göster</th>
                <td>
                    <label>
                        <input type="checkbox" name="show_system_crons" value="1" <?php checked($settings['show_system_crons'], true); ?> />
                        WordPress sistem cron joblarını listede göster
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">Koşullu Çalıştırma</th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_conditions" value="1" <?php checked(isset($settings['enable_conditions']) && $settings['enable_conditions'], true); ?> />
                        Koşullu çalıştırma ve retry mekanizmasını aktif et
                    </label>
                    <p class="description">Aktif edildiğinde, cron joblar belirlediğiniz koşullara göre çalışır (trafik, CPU, zaman aralığı vb.)</p>
                </td>
            </tr>

            <tr>
                <th scope="row">Otomatik Yedekleme</th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_auto_backup" value="1" <?php checked(isset($settings['enable_auto_backup']) && $settings['enable_auto_backup'], true); ?> />
                        Günlük otomatik yedek oluştur
                    </label>
                    <p class="description">Her gün otomatik olarak cron ayarlarınızın yedeğini alır</p>
                </td>
            </tr>

            <tr>
                <th scope="row">Debug Modu</th>
                <td>
                    <label>
                        <input type="checkbox" name="debug_mode" value="1" <?php checked(isset($settings['debug_mode']) && $settings['debug_mode'], true); ?> />
                        Debug modunu aktif et
                    </label>
                    <p class="description">Tüm cron çalışmalarını detaylı logla (performansı etkileyebilir)</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="acm_save_settings" class="button button-primary" value="Ayarları Kaydet" />
        </p>
    </form>

    <hr>

    <div class="acm-info-box">
        <h3>🔧 WP-Cron Hakkında</h3>
        <p>WordPress, wp-cron.php dosyasını kullanarak zamanlanmış görevleri yönetir. Bu sistem sayfa ziyaretleri ile tetiklenir.</p>
        
        <h4>Sistem Cron'a Geçiş (Önerilen):</h4>
        <p>Daha güvenilir bir çalışma için sistem cron kullanabilirsiniz:</p>
        
        <ol>
            <li><strong>wp-config.php</strong> dosyanıza şu satırı ekleyin:
                <pre><code>define('DISABLE_WP_CRON', true);</code></pre>
            </li>
            <li>Sistem crontab'a şu komutu ekleyin:
                <pre><code>*/5 * * * * wget -q -O - <?php echo site_url('wp-cron.php'); ?> &>/dev/null</code></pre>
                veya
                <pre><code>*/5 * * * * curl <?php echo site_url('wp-cron.php'); ?> &>/dev/null</code></pre>
            </li>
        </ol>

        <p><em>Bu ayar her 5 dakikada bir WordPress cron'larını çalıştırır.</em></p>
    </div>
</div>
