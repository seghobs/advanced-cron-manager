jQuery(document).ready(function($) {
    
    // Cron listesini yenile
    $('#acm-refresh').on('click', function() {
        location.reload();
    });

    // Cron'u şimdi çalıştır
    $(document).on('click', '.acm-run-now', function() {
        if (!confirm(acmAjax.strings.confirm_run)) {
            return;
        }

        var $btn = $(this);
        var hook = $btn.data('hook');
        var timestamp = $btn.data('timestamp');
        var args = $btn.data('args');

        $btn.prop('disabled', true).text('⏳');

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_run_cron',
                nonce: acmAjax.nonce,
                hook: hook,
                timestamp: timestamp,
                args: JSON.stringify(args)
            },
            success: function(response) {
                if (response.success) {
                    alert(acmAjax.strings.success + '\n' + response.data.message);
                    location.reload();
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text('▶️');
            }
        });
    });

    // Cron'u sil
    $(document).on('click', '.acm-delete', function() {
        if (!confirm(acmAjax.strings.confirm_delete)) {
            return;
        }

        var $btn = $(this);
        var hook = $btn.data('hook');
        var timestamp = $btn.data('timestamp');
        var args = $btn.data('args');

        $btn.prop('disabled', true).text('⏳');

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_delete_cron',
                nonce: acmAjax.nonce,
                hook: hook,
                timestamp: timestamp,
                args: JSON.stringify(args)
            },
            success: function(response) {
                if (response.success) {
                    $btn.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                    alert(response.data.message);
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                    $btn.prop('disabled', false).text('🗑️');
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
                $btn.prop('disabled', false).text('🗑️');
            }
        });
    });

    // Cron'u duraklat
    $(document).on('click', '.acm-pause', function() {
        var $btn = $(this);
        var hook = $btn.data('hook');
        var timestamp = $btn.data('timestamp');
        var args = $btn.data('args');

        $btn.prop('disabled', true).text('⏳');

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_pause_cron',
                nonce: acmAjax.nonce,
                hook: hook,
                timestamp: timestamp,
                args: JSON.stringify(args)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text('⏸️');
            }
        });
    });

    // Cron'u devam ettir
    $(document).on('click', '.acm-resume', function() {
        var $btn = $(this);
        var hook = $btn.data('hook');

        $btn.prop('disabled', true).text('⏳');

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_resume_cron',
                nonce: acmAjax.nonce,
                hook: hook
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text('▶️');
            }
        });
    });

    // Düzenle butonuna tıklama
    $(document).on('click', '.acm-edit', function() {
        var $btn = $(this);
        var hook = $btn.data('hook');
        var timestamp = $btn.data('timestamp');
        var schedule = $btn.data('schedule');
        var args = $btn.data('args');
        
        // Modal'ı aç
        $('#acm-edit-modal').fadeIn();
        
        // Formu doldur
        $('#edit_cron_hook').val(hook);
        $('#edit_cron_schedule').val(schedule);
        
        // Timestamp'i datetime-local formatına çevir
        var date = new Date(timestamp * 1000);
        var dateStr = date.getFullYear() + '-' + 
                     String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                     String(date.getDate()).padStart(2, '0') + 'T' + 
                     String(date.getHours()).padStart(2, '0') + ':' + 
                     String(date.getMinutes()).padStart(2, '0');
        $('#edit_cron_timestamp').val(dateStr);
        
        // Args'ı doldur
        if (args && Object.keys(args).length > 0) {
            $('#edit_cron_args').val(JSON.stringify(args, null, 2));
        } else {
            $('#edit_cron_args').val('');
        }
        
        // Eski değerleri sakla
        $('#edit_old_hook').val(hook);
        $('#edit_old_timestamp').val(timestamp);
        $('#edit_old_args').val(JSON.stringify(args || {}));
    });
    
    // Modal kapat butonları
    $('.acm-modal-close, .acm-modal-cancel').on('click', function() {
        $('#acm-edit-modal').fadeOut();
    });
    
    // Modal dışına tıklama
    $(window).on('click', function(e) {
        if ($(e.target).is('#acm-edit-modal')) {
            $('#acm-edit-modal').fadeOut();
        }
    });
    
    // Düzenleme formu gönder
    $('#acm-edit-cron-form').on('submit', function(e) {
        e.preventDefault();
        
        var hook = $('#edit_cron_hook').val();
        var schedule = $('#edit_cron_schedule').val();
        var timestampStr = $('#edit_cron_timestamp').val();
        var args = $('#edit_cron_args').val();
        
        var old_hook = $('#edit_old_hook').val();
        var old_timestamp = $('#edit_old_timestamp').val();
        var old_args = $('#edit_old_args').val();
        
        // Tarihi timestamp'e çevir
        var timestamp = timestampStr ? Math.floor(new Date(timestampStr).getTime() / 1000) : Math.floor(Date.now() / 1000) + 300;
        
        // JSON doğrulama
        if (args) {
            try {
                JSON.parse(args);
            } catch (e) {
                alert('Geçersiz JSON formatı!');
                return;
            }
        }
        
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Kaydediliyor...');
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_edit_cron',
                nonce: acmAjax.nonce,
                hook: hook,
                schedule: schedule,
                timestamp: timestamp,
                args: args,
                old_hook: old_hook,
                old_timestamp: old_timestamp,
                old_args: old_args
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#acm-edit-modal').fadeOut();
                    location.reload();
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                    $submitBtn.prop('disabled', false).text('💾 Değişiklikleri Kaydet');
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
                $submitBtn.prop('disabled', false).text('💾 Değişiklikleri Kaydet');
            }
        });
    });

    // Yeni cron ekle formu
    $('#acm-add-cron-form').on('submit', function(e) {
        e.preventDefault();

        var hook = $('#cron_hook').val();
        var schedule = $('#cron_schedule').val();
        var timestampStr = $('#cron_timestamp').val();
        var args = $('#cron_args').val();

        // Tarihi timestamp'e çevir
        var timestamp = timestampStr ? Math.floor(new Date(timestampStr).getTime() / 1000) : 0;

        // JSON doğrulama
        if (args) {
            try {
                JSON.parse(args);
            } catch (e) {
                alert('Geçersiz JSON formatı!');
                return;
            }
        }

        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Ekleniyor...');

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_add_cron',
                nonce: acmAjax.nonce,
                hook: hook,
                schedule: schedule,
                timestamp: timestamp,
                args: args
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = acmAjax.ajaxurl.replace('/admin-ajax.php', '') + '/admin.php?page=advanced-cron-manager';
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                    $submitBtn.prop('disabled', false).text('Cron Job Ekle');
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
                $submitBtn.prop('disabled', false).text('Cron Job Ekle');
            }
        });
    });

    // Arama fonksiyonu
    $('#acm-search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#acm-cron-list tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Tüm cron'ları çalıştır
    $('#acm-run-all').on('click', function() {
        if (!confirm('Tüm cron jobları şimdi çalıştırmak istiyor musunuz?')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Çalıştırılıyor...');
        
        var totalCrons = $('.acm-run-now').length;
        var completed = 0;

        $('.acm-run-now').each(function() {
            var $cronBtn = $(this);
            var hook = $cronBtn.data('hook');
            var timestamp = $cronBtn.data('timestamp');
            var args = $cronBtn.data('args');
            
            $cronBtn.prop('disabled', true).text('⏳');
            
            $.ajax({
                url: acmAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'acm_run_cron',
                    nonce: acmAjax.nonce,
                    hook: hook,
                    timestamp: timestamp,
                    args: JSON.stringify(args)
                },
                success: function(response) {
                    $cronBtn.prop('disabled', false).text('✓');
                },
                error: function() {
                    $cronBtn.prop('disabled', false).text('✗');
                },
                complete: function() {
                    completed++;
                    if (completed === totalCrons) {
                        alert('Tüm cron jobları çalıştırıldı!');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                }
            });
        });
    });

    // Otomatik yenileme (eğer ayarlarda aktifse)
    var autoRefreshInterval = 30000; // 30 saniye
    
    function updateCountdowns() {
        $('.acm-countdown').each(function() {
            var $this = $(this);
            var timestamp = parseInt($this.data('timestamp'));
            var now = Math.floor(Date.now() / 1000);
            var diff = timestamp - now;

            if (diff < 0) {
                $this.text('Gecikmiş!').css('color', 'red');
            } else {
                var minutes = Math.floor(diff / 60);
                var hours = Math.floor(minutes / 60);
                var days = Math.floor(hours / 24);

                if (days > 0) {
                    $this.text(days + ' gün');
                } else if (hours > 0) {
                    $this.text(hours + ' saat');
                } else if (minutes > 0) {
                    $this.text(minutes + ' dakika');
                } else {
                    $this.text(diff + ' saniye');
                }
            }
        });
    }

    // Her saniye geri sayımları güncelle
    setInterval(updateCountdowns, 1000);
    updateCountdowns();
    
    // ===== FAVORİ VE ETİKET ÖZELLİKLERİ =====
    
    // Favori butonu
    $(document).on('click', '.acm-favorite-btn', function() {
        var $btn = $(this);
        var hook = $btn.data('hook');
        var isActive = $btn.hasClass('active');
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_toggle_favorite',
                nonce: acmAjax.nonce,
                hook: hook
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.is_favorite) {
                        $btn.addClass('active').text('⭐').attr('title', 'Favorilerden çıkar');
                        $btn.closest('tr').addClass('is-favorite');
                    } else {
                        $btn.removeClass('active').text('☆').attr('title', 'Favorilere ekle');
                        $btn.closest('tr').removeClass('is-favorite');
                    }
                }
            }
        });
    });
    
    // Etiket ekleme modal aç
    $(document).on('click', '.acm-add-tag-btn', function() {
        var hook = $(this).data('hook');
        $('#tag-hook').val(hook);
        $('#tag-input').val('');
        $('#acm-tag-modal').fadeIn();
    });
    
    // Mevcut etiket seç
    $(document).on('click', '.acm-tag-option', function() {
        var tag = $(this).data('tag');
        $('#tag-input').val(tag);
    });
    
    // Etiket ekleme formu
    $('#acm-tag-form').on('submit', function(e) {
        e.preventDefault();
        
        var hook = $('#tag-hook').val();
        var tag = $('#tag-input').val().trim();
        
        if (!tag) {
            alert('Lütfen bir etiket girin!');
            return;
        }
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_add_tag',
                nonce: acmAjax.nonce,
                hook: hook,
                tag: tag
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#acm-tag-modal').fadeOut();
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Etiket silme
    $(document).on('click', '.acm-tag-remove', function(e) {
        e.stopPropagation();
        
        var hook = $(this).data('hook');
        var tag = $(this).data('tag');
        var $tagEl = $(this).closest('.acm-tag');
        
        if (!confirm('"​' + tag + '" etiketini kaldırmak istiyor musunuz?')) {
            return;
        }
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_remove_tag',
                nonce: acmAjax.nonce,
                hook: hook,
                tag: tag
            },
            success: function(response) {
                if (response.success) {
                    $tagEl.fadeOut(function() {
                        $(this).remove();
                    });
                }
            }
        });
    });
    
    // Not modal aç
    $(document).on('click', '.acm-note-btn', function() {
        var hook = $(this).data('hook');
        var note = $(this).data('note');
        
        $('#note-hook').val(hook);
        $('#note-input').val(note || '');
        $('#acm-note-modal').fadeIn();
    });
    
    // Not kaydetme formu
    $('#acm-note-form').on('submit', function(e) {
        e.preventDefault();
        
        var hook = $('#note-hook').val();
        var note = $('#note-input').val();
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_save_note',
                nonce: acmAjax.nonce,
                hook: hook,
                note: note
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#acm-note-modal').fadeOut();
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Filtre butonları
    $('.acm-filter-btn').on('click', function() {
        var $btn = $(this);
        var filter = $btn.data('filter');
        var tag = $btn.data('tag');
        
        $('.acm-filter-btn').removeClass('active');
        $btn.addClass('active');
        
        if (filter === 'all') {
            $('.acm-cron-row').show();
        } else if (filter === 'favorites') {
            $('.acm-cron-row').hide();
            $('.acm-cron-row.is-favorite').show();
        } else if (filter === 'tag') {
            $('.acm-cron-row').hide();
            $('.acm-cron-row').each(function() {
                var rowTags = $(this).data('tags');
                if (rowTags && rowTags.includes(tag)) {
                    $(this).show();
                }
            });
        }
    });
    
    // ===== WEBHOOK ÖZELLİKLERİ =====
    
    // Webhook tipi değiştir
    $('#webhook_type').on('change', function() {
        var type = $(this).val();
        
        // Tüm alanları gizle
        $('.webhook-field').hide();
        $('.webhook-help').hide();
        
        // Tip'e göre alanları göster
        if (type === 'telegram') {
            $('.webhook-telegram').show();
        } else {
            $('.webhook-url').show();
            $('.webhook-help-' + type).show();
            
            if (type === 'generic') {
                $('.webhook-generic-headers').show();
            }
        }
    });
    
    // Webhook kaydet
    $('#acm-webhook-form').on('submit', function(e) {
        e.preventDefault();
        
        var hook = $('#webhook_hook').val();
        var enabled = $('#webhook_enabled').is(':checked') ? '1' : '0';
        var type = $('#webhook_type').val();
        var url = $('#webhook_url').val();
        var headers = $('#webhook_headers').val();
        var bot_token = $('#webhook_bot_token').val();
        var chat_id = $('#webhook_chat_id').val();
        
        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }
        
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Kaydediliyor...');
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_save_webhook',
                nonce: acmAjax.nonce,
                hook: hook,
                enabled: enabled,
                type: type,
                url: url,
                headers: headers,
                bot_token: bot_token,
                chat_id: chat_id
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    $submitBtn.prop('disabled', false).text('💾 Webhook Kaydet');
                }
            },
            error: function() {
                alert('Bir hata oluştu!');
                $submitBtn.prop('disabled', false).text('💾 Webhook Kaydet');
            }
        });
    });
    
    // Webhook test et
    $('#test-webhook, .acm-webhook-test').on('click', function() {
        var hook = $(this).data('hook') || $('#webhook_hook').val();
        
        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Gönderiliyor...');
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_test_webhook',
                nonce: acmAjax.nonce,
                hook: hook
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Bir hata oluştu!');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Webhook yükle
    $('#load-webhook, .acm-webhook-load').on('click', function() {
        var hook = $(this).data('hook') || $('#webhook_hook').val();
        
        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }
        
        // AJAX ile webhook ayarlarını yükle (sunucudan)
        // Bu basitleştirilmiş versiyon - sayfa yeniden yüklenecek
        $('#webhook_hook').val(hook);
        alert('Webhook seçildi: ' + hook + '. Lütfen sayfa yüklenene kadar bekleyin.');
        location.reload();
    });
    
    // Webhook sil
    $('#delete-webhook').on('click', function() {
        var hook = $('#webhook_hook').val();
        
        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }
        
        if (!confirm('Bu webhook yapılandırmasını silmek istediğinizden emin misiniz?')) {
            return;
        }
        
        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_delete_webhook',
                nonce: acmAjax.nonce,
                hook: hook
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Bir hata oluştu!');
            }
        });
    });

    // ===== KOŞULLU ÇALIŞTIRMA VE RETRY ÖZELLİKLERİ =====

    // Sekme değiştirme
    $('.acm-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.acm-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.acm-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // Koşul formu gönder
    $('#acm-condition-form').on('submit', function(e) {
        e.preventDefault();

        var hook = $('#condition_hook').val();
        var max_active_users = $('#max_active_users').val();
        var time_start = $('#time_start').val();
        var time_end = $('#time_end').val();
        var max_cpu_load = $('#max_cpu_load').val();
        var dependencies = $('#dependencies').val() || [];

        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_save_conditions',
                nonce: acmAjax.nonce,
                hook: hook,
                max_active_users: max_active_users,
                time_start: time_start,
                time_end: time_end,
                max_cpu_load: max_cpu_load,
                dependencies: dependencies
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
            }
        });
    });

    // Mevcut koşulları yükle
    $('#load-conditions').on('click', function() {
        var hook = $('#condition_hook').val();

        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_load_conditions',
                nonce: acmAjax.nonce,
                hook: hook
            },
            success: function(response) {
                if (response.success && response.data.conditions) {
                    var cond = response.data.conditions;
                    
                    if (cond.max_active_users) {
                        $('#max_active_users').val(cond.max_active_users);
                    }
                    
                    if (cond.max_cpu_load) {
                        $('#max_cpu_load').val(cond.max_cpu_load);
                    }
                    
                    if (cond.time_range) {
                        $('#time_start').val(cond.time_range.start || 0);
                        $('#time_end').val(cond.time_range.end || 23);
                    }
                    
                    if (cond.dependencies) {
                        $('#dependencies').val(cond.dependencies);
                    }
                    
                    alert('Koşullar yüklendi!');
                } else {
                    alert('Bu cron için kayıtlı koşul bulunamadı.');
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
            }
        });
    });

    // Retry formu gönder
    $('#acm-retry-form').on('submit', function(e) {
        e.preventDefault();

        var hook = $('#retry_hook').val();
        var enabled = $('#retry_enabled').is(':checked') ? '1' : '0';
        var max_attempts = $('#max_attempts').val();
        var retry_delay = $('#retry_delay').val();
        var backoff_multiplier = $('#backoff_multiplier').val();

        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_save_retry_config',
                nonce: acmAjax.nonce,
                hook: hook,
                enabled: enabled,
                max_attempts: max_attempts,
                retry_delay: retry_delay,
                backoff_multiplier: backoff_multiplier
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(acmAjax.strings.error + '\n' + response.data.message);
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
            }
        });
    });

    // Mevcut retry ayarlarını yükle
    $('#load-retry').on('click', function() {
        var hook = $('#retry_hook').val();

        if (!hook) {
            alert('Lütfen bir cron seçin!');
            return;
        }

        $.ajax({
            url: acmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'acm_load_retry_config',
                nonce: acmAjax.nonce,
                hook: hook
            },
            success: function(response) {
                if (response.success && response.data.config) {
                    var config = response.data.config;
                    
                    $('#retry_enabled').prop('checked', config.enabled || false);
                    $('#max_attempts').val(config.max_attempts || 3);
                    $('#retry_delay').val(config.retry_delay || 300);
                    $('#backoff_multiplier').val(config.backoff_multiplier || 2);
                    
                    alert('Retry ayarları yüklendi!');
                } else {
                    alert('Bu cron için kayıtlı retry ayarı bulunamadı.');
                }
            },
            error: function() {
                alert(acmAjax.strings.error);
            }
        });
    });

});
