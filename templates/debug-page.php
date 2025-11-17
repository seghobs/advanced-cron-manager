<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Debug & Test', 'advanced-cron-manager'); ?></h1>

    <div class="acm-debug-container">
        
        <!-- CRON SİMÜLASYONU -->
        <div class="acm-card">
            <h2>🔬 <?php _e('Cron Simülasyonu', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Bir cron job\'u gerçekten çalıştırmadan test edin. Koşulları kontrol eder ve hook\'un tanımlı olup olmadığını gösterir.', 'advanced-cron-manager'); ?></p>
            
            <form id="acm-simulate-form">
                <table class="form-table">
                    <tr>
                        <th><label for="simulate-hook"><?php _e('Cron Hook', 'advanced-cron-manager'); ?></label></th>
                        <td>
                            <select id="simulate-hook" name="hook" required style="width: 100%; max-width: 400px;">
                                <option value="">-- <?php _e('Seçin', 'advanced-cron-manager'); ?> --</option>
                                <?php foreach ($crons as $cron): ?>
                                    <option value="<?php echo esc_attr($cron['hook']); ?>">
                                        <?php echo esc_html($cron['hook']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="simulate-args"><?php _e('Parametreler (JSON)', 'advanced-cron-manager'); ?></label></th>
                        <td>
                            <textarea id="simulate-args" name="args" rows="3" style="width: 100%; max-width: 400px;" placeholder='{"key": "value"}'></textarea>
                        </td>
                    </tr>
                </table>
                
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Simülasyon Başlat', 'advanced-cron-manager'); ?>
                </button>
            </form>
            
            <div id="acm-simulate-result" style="margin-top: 20px;"></div>
        </div>

        <!-- GERÇEK TEST -->
        <div class="acm-card">
            <h2>🧪 <?php _e('Gerçek Test Çalıştırma', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Cron job\'u gerçekten çalıştırır ve sonuçları gösterir. Çalışma süresini ve çıktıları görebilirsiniz.', 'advanced-cron-manager'); ?></p>
            
            <form id="acm-test-run-form">
                <table class="form-table">
                    <tr>
                        <th><label for="test-hook"><?php _e('Cron Hook', 'advanced-cron-manager'); ?></label></th>
                        <td>
                            <select id="test-hook" name="hook" required style="width: 100%; max-width: 400px;">
                                <option value="">-- <?php _e('Seçin', 'advanced-cron-manager'); ?> --</option>
                                <?php foreach ($crons as $cron): ?>
                                    <option value="<?php echo esc_attr($cron['hook']); ?>">
                                        <?php echo esc_html($cron['hook']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="test-args"><?php _e('Parametreler (JSON)', 'advanced-cron-manager'); ?></label></th>
                        <td>
                            <textarea id="test-args" name="args" rows="3" style="width: 100%; max-width: 400px;" placeholder='{"key": "value"}'></textarea>
                        </td>
                    </tr>
                </table>
                
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-play"></span>
                    <?php _e('Test Çalıştır', 'advanced-cron-manager'); ?>
                </button>
            </form>
            
            <div id="acm-test-run-result" style="margin-top: 20px;"></div>
        </div>

        <!-- WP-CRON BİLGİLERİ -->
        <div class="acm-card">
            <h2>ℹ️ <?php _e('WP-Cron Bilgileri', 'advanced-cron-manager'); ?></h2>
            
            <?php
            $doing_cron = defined('DOING_CRON') && DOING_CRON;
            $disable_wp_cron = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
            $alternate_cron = defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON;
            ?>
            
            <table class="widefat">
                <tr>
                    <th style="width: 250px;"><?php _e('DOING_CRON', 'advanced-cron-manager'); ?></th>
                    <td>
                        <span class="acm-status <?php echo $doing_cron ? 'active' : 'inactive'; ?>">
                            <?php echo $doing_cron ? '✅ Aktif' : '❌ İnaktif'; ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('DISABLE_WP_CRON', 'advanced-cron-manager'); ?></th>
                    <td>
                        <span class="acm-status <?php echo $disable_wp_cron ? 'active' : 'inactive'; ?>">
                            <?php echo $disable_wp_cron ? '✅ Evet (Sistem Cron Kullanılıyor)' : '❌ Hayır (WP-Cron Kullanılıyor)'; ?>
                        </span>
                        <?php if (!$disable_wp_cron): ?>
                            <p><em><?php _e('Daha güvenilir çalışma için sistem cron kullanmanız önerilir.', 'advanced-cron-manager'); ?></em></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('ALTERNATE_WP_CRON', 'advanced-cron-manager'); ?></th>
                    <td>
                        <span class="acm-status <?php echo $alternate_cron ? 'active' : 'inactive'; ?>">
                            <?php echo $alternate_cron ? '✅ Aktif' : '❌ İnaktif'; ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('WP-Cron URL', 'advanced-cron-manager'); ?></th>
                    <td>
                        <code><?php echo site_url('wp-cron.php'); ?></code>
                        <button class="button button-small" onclick="navigator.clipboard.writeText('<?php echo site_url('wp-cron.php'); ?>');">
                            <?php _e('Kopyala', 'advanced-cron-manager'); ?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Sunucu Zamanı', 'advanced-cron-manager'); ?></th>
                    <td><?php echo current_time('Y-m-d H:i:s'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('PHP Versiyonu', 'advanced-cron-manager'); ?></th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Max Execution Time', 'advanced-cron-manager'); ?></th>
                    <td><?php echo ini_get('max_execution_time'); ?> saniye</td>
                </tr>
            </table>
        </div>

        <!-- SİSTEM CRON KURULUM TALİMATLARI -->
        <div class="acm-card">
            <h2>⚙️ <?php _e('Sistem Cron Kurulum Talimatları', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Daha güvenilir çalışma için WP-Cron yerine sistem cron kullanın:', 'advanced-cron-manager'); ?></p>
            
            <h3><?php _e('1. wp-config.php dosyasına ekleyin:', 'advanced-cron-manager'); ?></h3>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;">define('DISABLE_WP_CRON', true);</pre>
            
            <h3><?php _e('2. Crontab\'a ekleyin:', 'advanced-cron-manager'); ?></h3>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;">*/5 * * * * curl <?php echo site_url('wp-cron.php'); ?> >/dev/null 2>&1</pre>
            <p><em><?php _e('veya wget kullanarak:', 'advanced-cron-manager'); ?></em></p>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;">*/5 * * * * wget -q -O - <?php echo site_url('wp-cron.php'); ?> >/dev/null 2>&1</pre>
        </div>

    </div>
</div>

<style>
.acm-debug-container {
    margin-top: 20px;
}

.acm-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.acm-card h2 {
    margin-top: 0;
    font-size: 18px;
}

.acm-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
}

.acm-status.active {
    background: #d4edda;
    color: #155724;
}

.acm-status.inactive {
    background: #f8d7da;
    color: #721c24;
}

#acm-simulate-result,
#acm-test-run-result {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    max-height: 500px;
    overflow-y: auto;
}

.acm-log-entry {
    padding: 8px;
    margin-bottom: 5px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
}

.acm-log-entry.info {
    background: #e7f3ff;
    border-left: 4px solid #2196F3;
}

.acm-log-entry.success {
    background: #d4edda;
    border-left: 4px solid #28a745;
}

.acm-log-entry.warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.acm-log-entry.error {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Simulate cron
    $('#acm-simulate-form').on('submit', function(e) {
        e.preventDefault();
        
        var hook = $('#simulate-hook').val();
        var args = $('#simulate-args').val();
        
        $('#acm-simulate-result').html('<p>🔄 Simülasyon çalıştırılıyor...</p>');
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_simulate_cron',
            nonce: acmAjax.nonce,
            hook: hook,
            args: args
        }, function(response) {
            if (response.success) {
                var html = '<h3>✅ Simülasyon Başarılı</h3>';
                html += '<div class="acm-logs">';
                
                response.data.logs.forEach(function(log) {
                    html += '<div class="acm-log-entry ' + log.level + '">';
                    html += '[' + log.time + '] ' + log.message;
                    html += '</div>';
                });
                
                html += '</div>';
                $('#acm-simulate-result').html(html);
            } else {
                var html = '<h3>❌ Simülasyon Başarısız</h3>';
                html += '<p><strong>Sebep:</strong> ' + response.data.reason + '</p>';
                html += '<div class="acm-logs">';
                
                if (response.data.logs) {
                    response.data.logs.forEach(function(log) {
                        html += '<div class="acm-log-entry ' + log.level + '">';
                        html += '[' + log.time + '] ' + log.message;
                        html += '</div>';
                    });
                }
                
                html += '</div>';
                $('#acm-simulate-result').html(html);
            }
        }).fail(function() {
            $('#acm-simulate-result').html('<p class="error">❌ Bir hata oluştu!</p>');
        });
    });
    
    // Test run cron
    $('#acm-test-run-form').on('submit', function(e) {
        e.preventDefault();
        
        var hook = $('#test-hook').val();
        var args = $('#test-args').val();
        
        $('#acm-test-run-result').html('<p>🔄 Test çalıştırılıyor...</p>');
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_test_run_cron',
            nonce: acmAjax.nonce,
            hook: hook,
            args: args
        }, function(response) {
            if (response.success) {
                var html = '<h3>✅ Test Başarılı</h3>';
                html += '<p><strong>Çalışma Süresi:</strong> ' + response.data.execution_time + ' ms</p>';
                
                if (response.data.output) {
                    html += '<p><strong>Çıktı:</strong></p>';
                    html += '<pre>' + response.data.output + '</pre>';
                }
                
                html += '<div class="acm-logs">';
                response.data.logs.forEach(function(log) {
                    html += '<div class="acm-log-entry ' + log.level + '">';
                    html += '[' + log.time + '] ' + log.message;
                    html += '</div>';
                });
                html += '</div>';
                
                $('#acm-test-run-result').html(html);
            } else {
                var html = '<h3>❌ Test Başarısız</h3>';
                html += '<p><strong>Hata:</strong> ' + response.data.error + '</p>';
                html += '<div class="acm-logs">';
                
                if (response.data.logs) {
                    response.data.logs.forEach(function(log) {
                        html += '<div class="acm-log-entry ' + log.level + '">';
                        html += '[' + log.time + '] ' + log.message;
                        html += '</div>';
                    });
                }
                
                html += '</div>';
                $('#acm-test-run-result').html(html);
            }
        }).fail(function() {
            $('#acm-test-run-result').html('<p class="error">❌ Bir hata oluştu!</p>');
        });
    });
});
</script>
