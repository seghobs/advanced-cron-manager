<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Loglar', 'advanced-cron-manager'); ?></h1>

    <div class="acm-logs-container">
        
        <!-- LOG TEMİZLEME BUTONLARI -->
        <div class="acm-log-actions">
            <button id="acm-clear-condition-logs" class="button">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Koşul Loglarını Temizle', 'advanced-cron-manager'); ?>
            </button>
            <button id="acm-clear-retry-logs" class="button">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Retry Loglarını Temizle', 'advanced-cron-manager'); ?>
            </button>
            <button id="acm-clear-all-logs" class="button button-secondary">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Tüm Logları Temizle', 'advanced-cron-manager'); ?>
            </button>
        </div>

        <!-- KOŞUL LOGLARI -->
        <div class="acm-card">
            <h2>📋 <?php _e('Koşul Kontrol Logları', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Cron jobların koşul kontrolü sırasında kaydedilen loglar.', 'advanced-cron-manager'); ?></p>
            
            <?php if (empty($condition_logs)): ?>
                <p><em><?php _e('Henüz log kaydı bulunmuyor.', 'advanced-cron-manager'); ?></em></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped acm-logs-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;"><?php _e('Tarih/Saat', 'advanced-cron-manager'); ?></th>
                            <th style="width: 250px;"><?php _e('Hook', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('Sebep', 'advanced-cron-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($condition_logs) as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['date']); ?></td>
                                <td><code><?php echo esc_html($log['hook']); ?></code></td>
                                <td>
                                    <span class="acm-log-reason warning">
                                        ⚠️ <?php echo esc_html($log['reason']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="acm-log-count">
                    <?php printf(__('Toplam %d kayıt gösteriliyor', 'advanced-cron-manager'), count($condition_logs)); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- RETRY LOGLARI -->
        <div class="acm-card">
            <h2>🔄 <?php _e('Retry (Yeniden Deneme) Logları', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Başarısız cron jobların yeniden deneme kayıtları.', 'advanced-cron-manager'); ?></p>
            
            <?php if (empty($retry_logs)): ?>
                <p><em><?php _e('Henüz log kaydı bulunmuyor.', 'advanced-cron-manager'); ?></em></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped acm-logs-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;"><?php _e('Tarih/Saat', 'advanced-cron-manager'); ?></th>
                            <th style="width: 250px;"><?php _e('Hook', 'advanced-cron-manager'); ?></th>
                            <th style="width: 100px;"><?php _e('Deneme', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('Sonraki Deneme', 'advanced-cron-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($retry_logs) as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['date']); ?></td>
                                <td><code><?php echo esc_html($log['hook']); ?></code></td>
                                <td>
                                    <span class="acm-badge">
                                        <?php echo esc_html($log['attempt']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html($log['next_retry_date']); ?>
                                    <small class="acm-relative-time">
                                        (<?php echo human_time_diff($log['next_retry'], current_time('timestamp')); ?> içinde)
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="acm-log-count">
                    <?php printf(__('Toplam %d kayıt gösteriliyor', 'advanced-cron-manager'), count($retry_logs)); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- AKTIF RETRY DURUMLARI -->
        <div class="acm-card">
            <h2>⏳ <?php _e('Aktif Retry Durumları', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Şu anda yeniden denenmeyi bekleyen cron joblar.', 'advanced-cron-manager'); ?></p>
            
            <?php
            $conditions_manager = new ACM_Cron_Conditions();
            $retry_status = $conditions_manager->get_retry_status();
            ?>
            
            <?php if (empty($retry_status)): ?>
                <p><em><?php _e('Şu anda retry bekleyen cron yok.', 'advanced-cron-manager'); ?></em></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped acm-logs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Hook', 'advanced-cron-manager'); ?></th>
                            <th style="width: 100px;"><?php _e('Deneme', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('İlk Hata', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('Son Deneme', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('Hata Mesajı', 'advanced-cron-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($retry_status as $hook => $status): ?>
                            <tr>
                                <td><code><?php echo esc_html($hook); ?></code></td>
                                <td>
                                    <span class="acm-badge error">
                                        <?php echo esc_html($status['attempts']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i:s', $status['first_failure']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', $status['last_attempt']); ?></td>
                                <td>
                                    <span class="acm-error-message">
                                        <?php echo esc_html($status['error']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
.acm-logs-container {
    margin-top: 20px;
}

.acm-log-actions {
    background: #fff;
    padding: 15px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
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

.acm-logs-table {
    margin-top: 15px;
}

.acm-logs-table code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.acm-log-reason {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
}

.acm-log-reason.warning {
    background: #fff3cd;
    color: #856404;
    border-left: 3px solid #ffc107;
}

.acm-badge {
    display: inline-block;
    padding: 3px 8px;
    background: #007cba;
    color: #fff;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.acm-badge.error {
    background: #dc3545;
}

.acm-relative-time {
    color: #666;
}

.acm-error-message {
    color: #dc3545;
    font-weight: 500;
}

.acm-log-count {
    margin-top: 10px;
    color: #666;
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Clear condition logs
    $('#acm-clear-condition-logs').on('click', function() {
        if (!confirm('Koşul loglarını temizlemek istediğinize emin misiniz?')) {
            return;
        }
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_clear_logs',
            nonce: acmAjax.nonce,
            log_type: 'condition'
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ ' + response.data.message);
            }
        });
    });
    
    // Clear retry logs
    $('#acm-clear-retry-logs').on('click', function() {
        if (!confirm('Retry loglarını temizlemek istediğinize emin misiniz?')) {
            return;
        }
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_clear_logs',
            nonce: acmAjax.nonce,
            log_type: 'retry'
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ ' + response.data.message);
            }
        });
    });
    
    // Clear all logs
    $('#acm-clear-all-logs').on('click', function() {
        if (!confirm('TÜM logları temizlemek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
            return;
        }
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_clear_logs',
            nonce: acmAjax.nonce,
            log_type: 'all'
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ ' + response.data.message);
            }
        });
    });
});
</script>
