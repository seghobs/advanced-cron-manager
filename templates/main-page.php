<?php
if (!defined('ABSPATH')) exit;

$info = $this->get_cron_info();
$paused_crons = get_option('acm_paused_crons', array());
$tags_manager = new ACM_Cron_Tags();
$all_tags = $tags_manager->get_all_tags();
?>

<div class="wrap acm-wrap">
    <h1><?php _e('Cron Job Yöneticisi', 'advanced-cron-manager'); ?></h1>

    <div class="acm-stats">
        <div class="acm-stat-box">
            <div class="acm-stat-icon">📊</div>
            <div class="acm-stat-content">
                <div class="acm-stat-number"><?php echo $info['total_jobs']; ?></div>
                <div class="acm-stat-label">Toplam Cron Job</div>
            </div>
        </div>

        <div class="acm-stat-box">
            <div class="acm-stat-icon">⚙️</div>
            <div class="acm-stat-content">
                <div class="acm-stat-number"><?php echo $info['doing_cron'] ? 'Aktif' : 'Pasif'; ?></div>
                <div class="acm-stat-label">Cron Durumu</div>
            </div>
        </div>

        <div class="acm-stat-box">
            <div class="acm-stat-icon">⏸️</div>
            <div class="acm-stat-content">
                <div class="acm-stat-number"><?php echo count($paused_crons); ?></div>
                <div class="acm-stat-label">Duraklatılmış</div>
            </div>
        </div>

        <div class="acm-stat-box <?php echo $info['disable_wp_cron'] ? 'acm-warning' : 'acm-success'; ?>">
            <div class="acm-stat-icon"><?php echo $info['disable_wp_cron'] ? '⚠️' : '✅'; ?></div>
            <div class="acm-stat-content">
                <div class="acm-stat-number"><?php echo $info['disable_wp_cron'] ? 'Devre Dışı' : 'Aktif'; ?></div>
                <div class="acm-stat-label">WP-Cron</div>
            </div>
        </div>
    </div>

    <?php if ($info['disable_wp_cron']): ?>
    <div class="notice notice-warning">
        <p><strong>Uyarı:</strong> DISABLE_WP_CRON aktif. WordPress cron sistemi devre dışı. Sistem cron kullanıyor olabilirsiniz.</p>
    </div>
    <?php endif; ?>

    <div class="acm-actions">
        <button id="acm-refresh" class="button button-secondary">🔄 Yenile</button>
        <a href="<?php echo admin_url('admin.php?page=acm-add-cron'); ?>" class="button button-primary">➕ Yeni Cron Ekle</a>
        <button id="acm-run-all" class="button button-secondary">▶️ Tüm Cron'ları Çalıştır</button>
    </div>

    <div class="acm-filters">
        <div class="acm-search">
            <input type="text" id="acm-search-input" placeholder="Cron ara... (hook adı, zamanlama, vb.)" />
        </div>
        
        <div class="acm-filter-buttons">
            <button class="button acm-filter-btn active" data-filter="all">📊 Tümü</button>
            <button class="button acm-filter-btn" data-filter="favorites">⭐ Favoriler</button>
            <?php foreach ($all_tags as $tag): ?>
                <button class="button acm-filter-btn" data-filter="tag" data-tag="<?php echo esc_attr($tag); ?>">
                    🏷️ <?php echo esc_html($tag); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped acm-cron-table">
        <thead>
            <tr>
                <th width="3%"></th>
                <th width="4%">Durum</th>
                <th width="20%">Hook Adı</th>
                <th width="13%">Etiketler</th>
                <th width="12%">Zamanlama</th>
                <th width="10%">Periyot</th>
                <th width="13%">Sonraki Çalışma</th>
                <th width="10%">Kalan Süre</th>
                <th width="15%">İşlemler</th>
            </tr>
        </thead>
        <tbody id="acm-cron-list">
            <?php if (empty($crons)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Henüz hiç cron job yok.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($crons as $cron): 
                    $time_remaining = $cron['next_run'] - current_time('timestamp');
                    $is_overdue = $time_remaining < 0;
                    $is_paused = false;
                    
                    foreach ($paused_crons as $paused) {
                        if ($paused['hook'] === $cron['hook']) {
                            $is_paused = true;
                            break;
                        }
                    }
                    
                    $is_favorite = $tags_manager->is_favorite($cron['hook']);
                    $cron_tags = $tags_manager->get_cron_tags($cron['hook']);
                    $note = $tags_manager->get_note($cron['hook']);
                ?>
                <tr class="acm-cron-row <?php echo $is_favorite ? 'is-favorite' : ''; ?>" data-hook="<?php echo esc_attr($cron['hook']); ?>" data-tags='<?php echo esc_attr(json_encode($cron_tags)); ?>'>
                    <td>
                        <button class="acm-favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>" 
                                data-hook="<?php echo esc_attr($cron['hook']); ?>" 
                                title="<?php echo $is_favorite ? 'Favorilerden çıkar' : 'Favorilere ekle'; ?>">
                            <?php echo $is_favorite ? '⭐' : '☆'; ?>
                        </button>
                    </td>
                    <td>
                        <span class="acm-status-badge <?php echo $is_paused ? 'paused' : ($is_overdue ? 'overdue' : 'active'); ?>">
                            <?php echo $is_paused ? '⏸️' : ($is_overdue ? '⏰' : '✓'); ?>
                        </span>
                    </td>
                    <td>
                        <strong><?php echo esc_html($cron['hook']); ?></strong>
                        <?php if ($note): ?>
                            <br><small class="acm-note" title="<?php echo esc_attr($note); ?>">📝 <?php echo esc_html(wp_trim_words($note, 5)); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($cron['args'])): ?>
                            <br><small class="acm-args">Args: <?php echo esc_html(json_encode($cron['args'])); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="acm-tags-cell">
                        <?php if (!empty($cron_tags)): ?>
                            <?php foreach ($cron_tags as $tag): ?>
                                <span class="acm-tag" data-tag="<?php echo esc_attr($tag); ?>">
                                    🏷️ <?php echo esc_html($tag); ?>
                                    <span class="acm-tag-remove" data-hook="<?php echo esc_attr($cron['hook']); ?>" data-tag="<?php echo esc_attr($tag); ?>">&times;</span>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <button class="button button-small acm-add-tag-btn" data-hook="<?php echo esc_attr($cron['hook']); ?>" title="Etiket Ekle">+</button>
                    </td>
                    <td><?php echo esc_html(ACM_Cron_Schedule::get_schedule_display_name($cron['schedule'])); ?></td>
                    <td>
                        <?php 
                        if ($cron['interval'] > 0) {
                            echo esc_html(ACM_Cron_Schedule::get_interval_in_words($cron['interval']));
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html(date('Y-m-d H:i:s', $cron['next_run'])); ?></td>
                    <td class="acm-countdown" data-timestamp="<?php echo $cron['next_run']; ?>">
                        <?php echo $is_overdue ? 'Gecikmiş!' : human_time_diff($cron['next_run'], current_time('timestamp')); ?>
                    </td>
                    <td>
                        <button class="button button-small acm-run-now" data-hook="<?php echo esc_attr($cron['hook']); ?>" data-timestamp="<?php echo $cron['timestamp']; ?>" data-args='<?php echo esc_attr(json_encode($cron['args'])); ?>' title="Şimdi Çalıştır">▶️</button>
                        
                        <button class="button button-small acm-edit" data-hook="<?php echo esc_attr($cron['hook']); ?>" data-timestamp="<?php echo $cron['timestamp']; ?>" data-schedule="<?php echo esc_attr($cron['schedule']); ?>" data-args='<?php echo esc_attr(json_encode($cron['args'])); ?>' title="Düzenle">✏️</button>
                        
                        <button class="button button-small acm-note-btn" data-hook="<?php echo esc_attr($cron['hook']); ?>" data-note="<?php echo esc_attr($note); ?>" title="Not Ekle/Düzenle">📝</button>
                        
                        <?php if ($is_paused): ?>
                            <button class="button button-small acm-resume" data-hook="<?php echo esc_attr($cron['hook']); ?>" title="Devam Ettir">▶️</button>
                        <?php else: ?>
                            <button class="button button-small acm-pause" data-hook="<?php echo esc_attr($cron['hook']); ?>" data-timestamp="<?php echo $cron['timestamp']; ?>" data-args='<?php echo esc_attr(json_encode($cron['args'])); ?>' title="Duraklat">⏸️</button>
                        <?php endif; ?>
                        
                        <button class="button button-small acm-delete" data-hook="<?php echo esc_attr($cron['hook']); ?>" data-timestamp="<?php echo $cron['timestamp']; ?>" data-args='<?php echo esc_attr(json_encode($cron['args'])); ?>' title="Sil">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="acm-info-box">
        <h3>ℹ️ Bilgiler</h3>
        <ul>
            <li><strong>Mevcut Sunucu Zamanı:</strong> <?php echo current_time('mysql'); ?></li>
            <li><strong>WordPress Zamanı:</strong> <?php echo date('Y-m-d H:i:s', current_time('timestamp')); ?></li>
            <li><strong>Timezone:</strong> <?php echo wp_timezone_string(); ?></li>
            <li><strong>WP-Cron URL:</strong> <code><?php echo site_url('wp-cron.php'); ?></code></li>
        </ul>
    </div>
</div>

<!-- Etiket Ekleme Modal -->
<div id="acm-tag-modal" class="acm-modal" style="display:none;">
    <div class="acm-modal-content">
        <span class="acm-modal-close">&times;</span>
        <h2>🏷️ Etiket Ekle</h2>
        <form id="acm-tag-form">
            <p>
                <label for="tag-input"><strong>Yeni Etiket:</strong></label><br>
                <input type="text" id="tag-input" class="regular-text" placeholder="Örn: critical, backup, daily" />
            </p>
            <p class="description">Veya mevcut etiketlerden birini seçin:</p>
            <div id="existing-tags" class="acm-tag-list">
                <?php foreach ($all_tags as $tag): ?>
                    <span class="acm-tag-option" data-tag="<?php echo esc_attr($tag); ?>">
                        🏷️ <?php echo esc_html($tag); ?>
                    </span>
                <?php endforeach; ?>
                <?php if (empty($all_tags)): ?>
                    <p><em>Henüz etiket yok</em></p>
                <?php endif; ?>
            </div>
            
            <input type="hidden" id="tag-hook" />
            
            <p class="submit">
                <button type="submit" class="button button-primary">💾 Etiket Ekle</button>
                <button type="button" class="button acm-modal-cancel">❌ İptal</button>
            </p>
        </form>
    </div>
</div>

<!-- Not Ekleme Modal -->
<div id="acm-note-modal" class="acm-modal" style="display:none;">
    <div class="acm-modal-content">
        <span class="acm-modal-close">&times;</span>
        <h2>📝 Not Ekle/Düzenle</h2>
        <form id="acm-note-form">
            <p>
                <label for="note-input"><strong>Not:</strong></label><br>
                <textarea id="note-input" class="large-text" rows="5" placeholder="Bu cron hakkında notlarınızı yazın..."></textarea>
            </p>
            
            <input type="hidden" id="note-hook" />
            
            <p class="submit">
                <button type="submit" class="button button-primary">💾 Notu Kaydet</button>
                <button type="button" class="button acm-modal-cancel">❌ İptal</button>
            </p>
        </form>
    </div>
</div>

<!-- Düzenleme Modal -->
<div id="acm-edit-modal" class="acm-modal" style="display:none;">
    <div class="acm-modal-content">
        <span class="acm-modal-close">&times;</span>
        <h2>✏️ Cron Job Düzenle</h2>
        <form id="acm-edit-cron-form">
            <table class="form-table">
                <tr>
                    <th><label for="edit_cron_hook">Hook Adı</label></th>
                    <td>
                        <input type="text" id="edit_cron_hook" name="hook" class="regular-text" required />
                        <p class="description">Cron job'un hook adını değiştirebilirsiniz.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="edit_cron_schedule">Zamanlama</label></th>
                    <td>
                        <select id="edit_cron_schedule" name="schedule" class="regular-text" required>
                            <option value="single">Tek Seferlik</option>
                            <option value="hourly">Her Saat</option>
                            <option value="twicedaily">Günde İki Kez</option>
                            <option value="daily">Günlük</option>
                            <?php
                            $schedules = wp_get_schedules();
                            foreach ($schedules as $key => $schedule) {
                                echo '<option value="' . esc_attr($key) . '">' . esc_html($schedule['display']) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Cron job'un çalışma sıklığını seçin.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="edit_cron_timestamp">Başlangıç Zamanı</label></th>
                    <td>
                        <input type="datetime-local" id="edit_cron_timestamp" name="timestamp" class="regular-text" />
                        <p class="description">Cron job'un ne zaman başlayacağını belirleyin. Boş bırakırsanız hemen başlar.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="edit_cron_args">Parametreler (JSON)</label></th>
                    <td>
                        <textarea id="edit_cron_args" name="args" class="large-text" rows="4" placeholder='{"key": "value"}'></textarea>
                        <p class="description">İsteğe bağlı. Hook fonksiyonuna gönderilecek parametreleri JSON formatında girin.</p>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" id="edit_old_hook" name="old_hook" />
            <input type="hidden" id="edit_old_timestamp" name="old_timestamp" />
            <input type="hidden" id="edit_old_args" name="old_args" />
            
            <p class="submit">
                <button type="submit" class="button button-primary">💾 Değişiklikleri Kaydet</button>
                <button type="button" class="button acm-modal-cancel">❌ İptal</button>
            </p>
        </form>
    </div>
</div>
