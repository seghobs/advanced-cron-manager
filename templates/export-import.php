<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Export / Import', 'advanced-cron-manager'); ?></h1>

    <div class="acm-export-import-container">
        
        <!-- EXPORT BÖLÜMÜ -->
        <div class="acm-card">
            <h2>📤 <?php _e('Export', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Tüm cron joblarınızı, ayarlarınızı, koşullarınızı ve webhook yapılandırmalarınızı dışa aktarın.', 'advanced-cron-manager'); ?></p>
            
            <div class="acm-export-buttons">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                    <input type="hidden" name="action" value="acm_export_crons">
                    <?php wp_nonce_field('acm_export_nonce'); ?>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('JSON Olarak İndir', 'advanced-cron-manager'); ?>
                    </button>
                </form>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-left: 10px;">
                    <input type="hidden" name="action" value="acm_export_csv">
                    <?php wp_nonce_field('acm_export_nonce'); ?>
                    <button type="submit" class="button">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <?php _e('CSV Olarak İndir', 'advanced-cron-manager'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- IMPORT BÖLÜMÜ -->
        <div class="acm-card">
            <h2>📥 <?php _e('Import', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Daha önce dışa aktarılmış JSON dosyasını içe aktarın.', 'advanced-cron-manager'); ?></p>
            
            <div class="acm-import-form">
                <form id="acm-import-form" enctype="multipart/form-data">
                    <input type="file" name="import_file" id="acm-import-file" accept=".json" required>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('JSON İçe Aktar', 'advanced-cron-manager'); ?>
                    </button>
                </form>
                
                <div id="acm-import-result" style="margin-top: 15px;"></div>
            </div>
            
            <div class="acm-import-warning">
                <p><strong>⚠️ <?php _e('Uyarı:', 'advanced-cron-manager'); ?></strong> <?php _e('İçe aktarma işlemi mevcut cron joblarınızı etkilemez, sadece yeni joblar ekler.', 'advanced-cron-manager'); ?></p>
            </div>
        </div>

        <!-- OTOMATIK YEDEKLEME -->
        <div class="acm-card">
            <h2>💾 <?php _e('Otomatik Yedekleme', 'advanced-cron-manager'); ?></h2>
            <p><?php _e('Anlık yedek oluşturun veya mevcut yedekleri yönetin.', 'advanced-cron-manager'); ?></p>
            
            <button id="acm-create-backup" class="button button-primary">
                <span class="dashicons dashicons-backup"></span>
                <?php _e('Şimdi Yedek Oluştur', 'advanced-cron-manager'); ?>
            </button>
            
            <div id="acm-backup-result" style="margin-top: 15px;"></div>
        </div>

        <!-- YEDEKLER LİSTESİ -->
        <div class="acm-card">
            <h2>📦 <?php _e('Mevcut Yedekler', 'advanced-cron-manager'); ?></h2>
            
            <?php if (empty($backups)): ?>
                <p><?php _e('Henüz yedek bulunmuyor.', 'advanced-cron-manager'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Dosya Adı', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('Tarih', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('Boyut', 'advanced-cron-manager'); ?></th>
                            <th><?php _e('İşlemler', 'advanced-cron-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo esc_html($backup['filename']); ?></td>
                                <td><?php echo esc_html($backup['date']); ?></td>
                                <td><?php echo size_format($backup['size']); ?></td>
                                <td>
                                    <button class="button button-small acm-restore-backup" data-filename="<?php echo esc_attr($backup['filename']); ?>">
                                        <span class="dashicons dashicons-backup"></span>
                                        <?php _e('Geri Yükle', 'advanced-cron-manager'); ?>
                                    </button>
                                    <button class="button button-small acm-delete-backup" data-filename="<?php echo esc_attr($backup['filename']); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('Sil', 'advanced-cron-manager'); ?>
                                    </button>
                                    <a href="<?php echo content_url('acm-backups/' . $backup['filename']); ?>" class="button button-small" download>
                                        <span class="dashicons dashicons-download"></span>
                                        <?php _e('İndir', 'advanced-cron-manager'); ?>
                                    </a>
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
.acm-export-import-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.acm-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.acm-card h2 {
    margin-top: 0;
    font-size: 18px;
}

.acm-export-buttons {
    margin-top: 15px;
}

.acm-import-form input[type="file"] {
    display: block;
    margin-bottom: 10px;
    width: 100%;
}

.acm-import-warning {
    margin-top: 15px;
    padding: 10px;
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 4px;
}

.acm-import-warning p {
    margin: 0;
}

#acm-backup-result,
#acm-import-result {
    padding: 10px;
    border-radius: 4px;
}

.notice-success {
    background: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
}

.notice-error {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Import form
    $('#acm-import-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData();
        formData.append('action', 'acm_import_json');
        formData.append('nonce', acmAjax.nonce);
        formData.append('import_file', $('#acm-import-file')[0].files[0]);
        
        $('#acm-import-result').html('<p>⏳ Yükleniyor...</p>');
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#acm-import-result').html(
                        '<div class="notice-success"><p>✅ ' + response.data.message + '</p></div>'
                    );
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#acm-import-result').html(
                        '<div class="notice-error"><p>❌ ' + response.data.message + '</p></div>'
                    );
                }
            },
            error: function() {
                $('#acm-import-result').html(
                    '<div class="notice-error"><p>❌ Bir hata oluştu!</p></div>'
                );
            }
        });
    });
    
    // Create backup
    $('#acm-create-backup').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Oluşturuluyor...');
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_create_backup',
            nonce: acmAjax.nonce
        }, function(response) {
            if (response.success) {
                $('#acm-backup-result').html(
                    '<div class="notice-success"><p>✅ ' + response.data.message + '</p></div>'
                );
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                $('#acm-backup-result').html(
                    '<div class="notice-error"><p>❌ ' + response.data.message + '</p></div>'
                );
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Şimdi Yedek Oluştur');
            }
        });
    });
    
    // Restore backup
    $(document).on('click', '.acm-restore-backup', function() {
        if (!confirm('Bu yedeği geri yüklemek istediğinize emin misiniz?')) {
            return;
        }
        
        var filename = $(this).data('filename');
        var $btn = $(this);
        $btn.prop('disabled', true).text('Geri Yükleniyor...');
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_restore_backup',
            nonce: acmAjax.nonce,
            filename: filename
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ ' + response.data.message);
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Geri Yükle');
            }
        });
    });
    
    // Delete backup
    $(document).on('click', '.acm-delete-backup', function() {
        if (!confirm('Bu yedeği silmek istediğinize emin misiniz?')) {
            return;
        }
        
        var filename = $(this).data('filename');
        var $row = $(this).closest('tr');
        
        $.post(acmAjax.ajaxurl, {
            action: 'acm_delete_backup',
            nonce: acmAjax.nonce,
            filename: filename
        }, function(response) {
            if (response.success) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert('❌ ' + response.data.message);
            }
        });
    });
});
</script>
