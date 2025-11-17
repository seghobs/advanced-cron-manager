<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Yeni Cron Job Ekle', 'advanced-cron-manager'); ?></h1>

    <div class="acm-form-container">
        <form id="acm-add-cron-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cron_hook">Hook Adı *</label>
                    </th>
                    <td>
                        <input type="text" id="cron_hook" name="cron_hook" class="regular-text" required />
                        <p class="description">Çalıştırılacak hook'un adı (örn: my_custom_cron_job)</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cron_schedule">Zamanlama Türü *</label>
                    </th>
                    <td>
                        <select id="cron_schedule" name="cron_schedule" class="regular-text">
                            <option value="single">Tek Seferlik</option>
                            <?php foreach ($schedules as $key => $schedule): ?>
                                <option value="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($schedule['display']); ?> 
                                    (<?php echo esc_html(ACM_Cron_Schedule::get_interval_in_words($schedule['interval'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Cron'un ne sıklıkla çalışacağını seçin</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cron_timestamp">Başlangıç Zamanı</label>
                    </th>
                    <td>
                        <input type="datetime-local" id="cron_timestamp" name="cron_timestamp" class="regular-text" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime('+5 minutes')); ?>" />
                        <p class="description">Cron'un ilk çalışma zamanı (boş bırakırsanız 5 dakika sonra başlar)</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cron_args">Parametreler (JSON)</label>
                    </th>
                    <td>
                        <textarea id="cron_args" name="cron_args" class="large-text" rows="4" placeholder='{"key": "value"}'></textarea>
                        <p class="description">Hook'a gönderilecek parametreler (JSON formatında, opsiyonel)</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Cron Job Ekle</button>
                <a href="<?php echo admin_url('admin.php?page=advanced-cron-manager'); ?>" class="button button-secondary">İptal</a>
            </p>
        </form>
    </div>

    <div class="acm-info-box">
        <h3>📝 Nasıl Kullanılır?</h3>
        <ol>
            <li><strong>Hook Adı:</strong> Benzersiz bir hook adı girin (örn: <code>my_daily_backup</code>)</li>
            <li><strong>Zamanlama:</strong> Cron'un ne sıklıkla çalışacağını seçin</li>
            <li><strong>Başlangıç Zamanı:</strong> İlk çalışma zamanını belirleyin</li>
            <li><strong>Parametreler:</strong> Gerekirse JSON formatında parametre ekleyin</li>
        </ol>

        <h4>Örnek Hook Kullanımı:</h4>
        <pre><code>// functions.php veya plugin dosyanızda
add_action('my_daily_backup', 'my_backup_function');

function my_backup_function($args) {
    // Backup işlemleriniz
    error_log('Backup çalıştırıldı: ' . print_r($args, true));
}</code></pre>
    </div>
</div>
