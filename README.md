# 🚀 Advanced Cron Manager for WordPress

[![WordPress Plugin](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)

**Profesyonel WordPress cron job yönetim eklentisi** - Koşullu çalıştırma, retry mekanizması, webhook entegrasyonu ve daha fazlası!

![Advanced Cron Manager](https://via.placeholder.com/800x400/007cba/ffffff?text=Advanced+Cron+Manager)

## ✨ Özellikler

### 🎯 Temel Özellikler
- ✅ **Tüm Cron Jobları Görüntüleme** - WordPress'teki tüm zamanlanmış görevleri tek bir sayfada görün
- ▶️ **Anlık Çalıştırma** - Herhangi bir cron job'u hemen çalıştırın
- ✏️ **Düzenleme** - Mevcut cron joblarını kolayca düzenleyin
- 🗑️ **Silme** - İstenmeyen cron joblarını kaldırın
- ⏸️ **Duraklatma/Devam Ettirme** - Cron joblarını geçici olarak durdurun
- ➕ **Yeni Cron Ekleme** - Özel cron jobları oluşturun
- 🔍 **Arama ve Filtreleme** - Cron jobları arasında hızlıca arayın

### 📊 Gelişmiş Özellikler
- **Anlık İstatistikler** - Toplam job sayısı, durum bilgisi
- **Geri Sayım Sayacı** - Her cron job için kalan süreyi canlı görün
- **Bulk İşlemler** - Toplu silme, duraklatma, devam ettirme
- **JSON Parametre Desteği** - Cron joblara özel parametreler gönderin
- **Sistem Durumu** - WP-Cron durumunu görüntüleyin

### ⚡ Koşullu Çalıştırma & Retry
- **Trafik Kontrolü** - Sadece site trafiği düşükken cron çalıştır
- **Zaman Aralığı** - Belirli saatlerde cron çalıştır (örn: gece 22:00 - 06:00)
- **CPU Yükü Kontrolü** - CPU yükü belirli seviyenin altındayken çalıştır
- **Bağımlılık Yönetimi** - Cron'ları sırayla çalıştır
- **Retry Mekanizması** - Başarısız cron'ları otomatik tekrar dene
- **Exponential Backoff** - Her denemede bekleme süresini artır
- **E-posta Bildirimleri** - Başarısızlık durumunda bildirim gönder
- **Detaylı Loglar** - Koşul kontrolleri ve retry geçmişi

### 🔔 Webhook Entegrasyonları
- **Slack** - Cron çalışma bildirimlerini Slack'e gönder
- **Discord** - Discord kanallarına bildirim gönder
- **Telegram** - Telegram bot ile anlık bildirim
- **Generic Webhook** - Özel webhook URL'lerine POST gönder

### 📦 Export/Import & Backup
- **JSON Export/Import** - Tüm ayarları dışa/içe aktar
- **CSV Export** - Cron listesini CSV olarak indir
- **Otomatik Yedekleme** - Günlük otomatik yedek oluştur
- **Yedek Geri Yükleme** - Önceki duruma kolayca dön

### 🧪 Debug & Test
- **Cron Simülasyonu** - Çalıştırmadan test et
- **Gerçek Test** - Cron'u çalıştır ve sonuçları gör
- **Performans Analizi** - Çalışma süresi ve çıktıları görüntüle
- **WP-Cron Bilgileri** - Sistem durumunu detaylı gör

### 🏷️ Organizasyon
- **Favori Sistemi** - Önemli cron'ları favorilere ekle
- **Etiket Yönetimi** - Cron'ları etiketlerle kategorize et
- **Not Ekleme** - Her cron için özel notlar ekle
- **Renk Kodlama** - Görsel organizasyon

### 📋 Log Yönetimi
- **Koşul Logları** - Koşul kontrolü kayıtları
- **Retry Logları** - Yeniden deneme geçmişi
- **Aktif Retry Durumları** - Bekleyen denemeler
- **Log Temizleme** - Eski logları temizle

## 📥 Kurulum

### Manuel Kurulum
1. Bu repo'yu indirin veya klonlayın:
```bash
git clone https://github.com/seghobs/advanced-cron-manager.git
```

2. `advanced-cron-manager` klasörünü WordPress kurulumunuzun `/wp-content/plugins/` dizinine yükleyin

3. WordPress yönetim panelinden **Eklentiler** menüsüne gidin

4. **Advanced Cron Manager** eklentisini etkinleştirin

5. Sol menüden **Cron Manager** seçeneğine tıklayın

### WordPress.org'dan Kurulum (Yakında)
1. WordPress Yönetim Paneli → Eklentiler → Yeni Ekle
2. "Advanced Cron Manager" araması yapın
3. Kur ve Etkinleştir butonlarına tıklayın

## 🎮 Kullanım

### Temel Kullanım

#### Cron Jobları Görüntüleme
1. WordPress Admin Panel → **Cron Manager**
2. Tüm aktif cron joblarını görebilirsiniz
3. Her job için durum, zamanlama, sonraki çalışma zamanı görüntülenir

#### Yeni Cron Job Ekleme
1. Cron Manager → **Yeni Cron Ekle**
2. Hook adı girin (örn: `my_custom_backup`)
3. Zamanlama türünü seçin
4. Başlangıç zamanını belirleyin
5. İsteğe bağlı JSON parametreleri ekleyin

#### Hook'u Kodunuzda Kullanma
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
}
```

### Koşullu Çalıştırma

#### Koşulları Ayarlama
1. Cron Manager → **Ayarlar**
2. "Koşullu Çalıştırma" kutusunu işaretleyin
3. Ayarları kaydedin
4. **Koşullu Çalıştırma** sayfasına gidin
5. Bir cron job seçin ve koşulları belirleyin:
   - Maksimum aktif kullanıcı sayısı
   - Maksimum CPU yükü
   - Çalışma saatleri (örn: 22:00 - 06:00)
   - Bağımlı cron'lar

#### Retry Mekanizması
1. Koşullu Çalıştırma sayfasında bir cron seçin
2. **Retry Ayarları** bölümüne gidin
3. Ayarları yapın:
   - Maksimum deneme sayısı
   - Başlangıç bekleme süresi
   - Backoff çarpanı
   - E-posta bildirimi

### Webhook Kurulumu

#### Slack Webhook
1. Cron Manager → **Webhooks**
2. Bir cron seçin
3. Webhook tipini "Slack" seçin
4. Slack webhook URL'nizi girin
5. Kaydet ve test et

#### Discord Webhook
1. Webhooks sayfasında cron seçin
2. Tip: "Discord"
3. Discord webhook URL girin
4. Kaydet

#### Telegram Bot
1. Telegram'da BotFather'dan bot oluşturun
2. Bot token'ı alın
3. Chat ID'nizi öğrenin
4. Webhooks sayfasında ayarlayın

## 🔧 Sistem Cron Kullanımı (Önerilen)

Daha güvenilir çalışma için WP-Cron yerine sistem cron kullanın:

### 1. wp-config.php'ye Ekleyin
```php
define('DISABLE_WP_CRON', true);
```

### 2. Crontab'a Ekleyin
```bash
crontab -e
```

Şu satırı ekleyin:
```bash
*/5 * * * * curl http://yourdomain.com/wp-cron.php >/dev/null 2>&1
```

veya

```bash
*/5 * * * * wget -q -O - http://yourdomain.com/wp-cron.php >/dev/null 2>&1
```

## 📋 Gereksinimler

- **WordPress:** 5.0 veya üzeri
- **PHP:** 7.0 veya üzeri
- **MySQL:** 5.6 veya üzeri

## 🤝 Katkıda Bulunma

Katkılarınızı bekliyoruz! İşte nasıl katkıda bulunabilirsiniz:

1. Bu repo'yu fork edin
2. Feature branch'i oluşturun (`git checkout -b feature/AmazingFeature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Branch'inizi push edin (`git push origin feature/AmazingFeature`)
5. Pull Request açın

## 🐛 Hata Bildirimi

Bir hata buldunuz mu? [Issue açın](https://github.com/seghobs/advanced-cron-manager/issues)!

## 📝 Changelog

### 1.0.0 (2024)
- ✨ İlk sürüm
- ✅ Temel cron yönetimi özellikleri
- ✅ Koşullu çalıştırma ve retry mekanizması
- ✅ Webhook entegrasyonları
- ✅ Export/Import fonksiyonları
- ✅ Debug ve test araçları
- ✅ Log yönetimi
- ✅ Tag ve favori sistemi

## 📄 Lisans

Bu proje GPL v2 veya üzeri lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

## 👤 Geliştirici

**seghobs**
- GitHub: [@seghobs](https://github.com/seghobs)

## ⭐ Destek

Bu projeyi beğendiyseniz lütfen bir yıldız ⭐ verin!

## 🔗 Bağlantılar

- [GitHub Repository](https://github.com/seghobs/advanced-cron-manager)
- [Issues](https://github.com/seghobs/advanced-cron-manager/issues)
- [Pull Requests](https://github.com/seghobs/advanced-cron-manager/pulls)

---

**Made with ❤️ for WordPress Community**
