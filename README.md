# Advanced Cron Manager

WordPress cron joblarını anlık olarak izleme, durdurma, düzenleme ve yönetme eklentisi.

## Özellikler

### 🎯 Temel Özellikler
- ✅ **Tüm Cron Jobları Görüntüleme** - WordPress'teki tüm zamanlanmış görevleri tek bir sayfada görün
- ▶️ **Anlık Çalıştırma** - Herhangi bir cron job'u hemen çalıştırın
- ✏️ **Düzenleme** - Mevcut cron joblarını kolayca düzenleyin (hook adı, zamanlama, parametreler)
- 🗑️ **Silme** - İstenmeyen cron joblarını kolaylıkla silin
- ⏸️ **Duraklatma/Devam Ettirme** - Cron joblarını geçici olarak durdurun ve tekrar başlatın
- ➕ **Yeni Cron Ekleme** - Özel cron jobları oluşturun
- 🔍 **Arama ve Filtreleme** - Cron jobları arasında hızlıca arayın

### 📊 Gelişmiş Özellikler
- **Anlık İstatistikler** - Toplam job sayısı, durum bilgisi, duraklatılmış joblar
- **Geri Sayım Sayacı** - Her cron job için kalan süreyi canlı olarak görün
- **Zamanlama Seçenekleri** - 5 dakika, 15 dakika, saatlik, günlük, haftalık, aylık ve daha fazla
- **JSON Parametre Desteği** - Cron joblara özel parametreler gönderin
- **Sistem Durumu** - WP-Cron durumunu ve yapılandırmasını görüntüleyin

### ⚡ Koşullu Çalıştırma & Retry
- **Trafik Kontrolü** - Sadece site trafiği düşükken cron çalıştır
- **Zaman Aralığı** - Belirli saatlerde cron çalıştır (örn: gece 22:00 - 06:00)
- **CPU Yükü Kontrolü** - CPU yükü belirli seviyenin altındayken çalıştır
- **Bağımlılık Yönetimi** - Önce A cron çalışsın, sonra B çalışsın
- **Retry Mekanizması** - Başarısız olan cron'ları otomatik tekrar dene
- **Exponential Backoff** - Her denemede bekleme süresini artır
- **E-posta Bildirimleri** - Tüm denemeler başarısız olursa bildirim gönder
- **Detaylı Loglar** - Koşul kontrolleri ve retry geçmişi

### 🎨 Kullanıcı Deneyimi
- Modern ve temiz arayüz
- Responsive tasarım (mobil uyumlu)
- Emoji destekli görsel göstergeler
- Anlık bildirimler ve geri bildirim

## Kurulum

1. Bu eklentiyi `/wp-content/plugins/advanced-cron-manager/` dizinine yükleyin
2. WordPress yönetim panelinden 'Eklentiler' menüsüne gidin
3. 'Advanced Cron Manager' eklentisini etkinleştirin
4. Sol menüden 'Cron Manager' seçeneğine tıklayın

## Kullanım

### Cron Jobları Görüntüleme
1. WordPress Admin Panel → Cron Manager
2. Tüm aktif cron joblarını görebilirsiniz
3. Her job için durum, zamanlama, sonraki çalışma zamanı ve kalan süre görüntülenir

### Yeni Cron Job Ekleme
1. Cron Manager → Yeni Cron Ekle
2. Hook adı girin (örn: `my_custom_backup`)
3. Zamanlama türünü seçin (tek seferlik, saatlik, günlük, vb.)
4. Başlangıç zamanını belirleyin
5. İsteğe bağlı JSON parametreleri ekleyin

### Hook'u Kodunuzda Kullanma
```php
// functions.php veya özel plugin dosyanızda
add_action('my_custom_backup', 'my_backup_function');

function my_backup_function($args = array()) {
    // Backup işlemleriniz
    error_log('Backup başladı: ' . date('Y-m-d H:i:s'));
    
    // Args kullanımı
    if (!empty($args)) {
        error_log('Parametreler: ' . print_r($args, true));
    }
    
    // İşlemleriniz...
}
```

### Cron Job İşlemleri
- **▶️ Şimdi Çalıştır** - Job'u zamanını beklemeden hemen çalıştırın
- **✏️ Düzenle** - Job'un hook adını, zamanlamasını ve parametrelerini değiştirin
- **⏸️ Duraklat** - Job'u geçici olarak devre dışı bırakın
- **▶️ Devam Ettir** - Duraklatılmış job'u yeniden başlatın
- **🗑️ Sil** - Job'u kalıcı olarak kaldırın

### Cron Job Düzenleme
1. Cron Manager sayfasında düzenlemek istediğiniz job'un yanındaki **✏️** butonuna tıklayın
2. Açılan modal pencerede istediğiniz değişiklikleri yapın:
   - Hook adını değiştirin
   - Zamanlama türünü değiştirin (tek seferlik, saatlik, günlük, vb.)
   - Başlangıç zamanını ayarlayın
   - JSON parametrelerini güncelleyin
3. **💾 Değişiklikleri Kaydet** butonuna tıklayın
4. Değişiklikler hemen uygulanır ve sayfa yenilenir

## Özel Zamanlama Periyotları

Eklenti varsayılan WordPress zamanlamalarına ek olarak şu periyotları ekler:

- Her 5 Dakikada
- Her 15 Dakikada
- Her 30 Dakikada
- Her 2 Saatte
- Her 3 Saatte
- Her 6 Saatte
- Her 12 Saatte
- Haftada Bir
- Ayda Bir

## Ayarlar

### Otomatik Yenileme
Cron listesinin otomatik olarak yenilenmesini aktif/pasif yapın.

### Yenileme Aralığı
Otomatik yenileme aralığını saniye cinsinden ayarlayın (10-300 arası).

### Sistem Cron'larını Göster
WordPress sistem cron joblarını listede göster/gizle.

## WP-Cron'dan Sistem Cron'a Geçiş

Daha güvenilir çalışma için sistem cron kullanmanız önerilir:

### 1. WP-Cron'u Devre Dışı Bırakın
`wp-config.php` dosyanıza ekleyin:
```php
define('DISABLE_WP_CRON', true);
```

### 2. Sistem Crontab'a Ekleyin
```bash
crontab -e
```

Şu satırı ekleyin:
```
*/5 * * * * curl http://yourdomain.com/wp-cron.php >/dev/null 2>&1
```

veya

```
*/5 * * * * wget -q -O - http://yourdomain.com/wp-cron.php >/dev/null 2>&1
```

## Teknik Bilgiler

### Gereksinimler
- WordPress 5.0 veya üzeri
- PHP 7.0 veya üzeri
- MySQL 5.6 veya üzeri

### Dosya Yapısı
```
advanced-cron-manager/
├── advanced-cron-manager.php   # Ana eklenti dosyası
├── includes/
│   ├── class-cron-manager.php  # Ana yönetici sınıfı
│   ├── class-cron-ajax.php     # AJAX işlemleri
│   └── class-cron-schedule.php # Özel zamanlamalar
├── templates/
│   ├── main-page.php           # Ana liste sayfası
│   ├── add-cron.php            # Cron ekleme sayfası
│   └── settings.php            # Ayarlar sayfası
├── assets/
│   ├── css/
│   │   └── admin-style.css     # Stil dosyası
│   └── js/
│       └── admin-script.js     # JavaScript dosyası
└── README.md                   # Bu dosya
```

### AJAX Aksiyonları
- `acm_run_cron` - Cron'u şimdi çalıştır
- `acm_edit_cron` - Cron'u düzenle
- `acm_delete_cron` - Cron'u sil
- `acm_pause_cron` - Cron'u duraklat
- `acm_resume_cron` - Cron'u devam ettir
- `acm_add_cron` - Yeni cron ekle
- `acm_get_crons` - Cron listesini al

## Güvenlik

- Tüm AJAX istekleri nonce ile doğrulanır
- Sadece `manage_options` yetkisine sahip kullanıcılar erişebilir
- Tüm girişler sanitize edilir
- XSS koruması için çıkışlar escape edilir

## Sık Sorulan Sorular

**S: Eklenti mevcut cron joblarımı etkiler mi?**
C: Hayır, eklenti sadece görüntüleme ve yönetim sağlar. Mevcut joblarınız normal şekilde çalışmaya devam eder.

**S: Duraklatılmış cron'lar nerede saklanır?**
C: WordPress options tablosunda `acm_paused_crons` anahtarı altında saklanır.

**S: Eklentiyi kaldırırsam ne olur?**
C: Eklenti kaldırıldığında ayarlar silinir ancak WordPress cron jobları etkilenmez.

**S: Hangi cron joblarını görüyorum?**
C: WordPress'in `_get_cron_array()` fonksiyonu ile alınan tüm zamanlanmış görevleri görürsünüz.

## Destek

Sorunlar veya öneriler için:
- GitHub Issues: [Proje Sayfası]
- Email: support@example.com

## Lisans

GPL v2 veya üzeri

## Geliştirici

Your Name - [Website]

## Changelog

### 1.0.0 (2024)
- İlk sürüm
- Temel cron yönetimi özellikleri
- Anlık izleme ve geri sayım
- Duraklatma/devam ettirme
- Özel zamanlama periyotları
- Modern ve responsive arayüz
