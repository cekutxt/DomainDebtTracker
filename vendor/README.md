# PHPMailer Kurulumu

Mail gönderimi için PHPMailer kütüphanesi gereklidir.

## Otomatik Kurulum (Composer ile)

Eğer Composer kuruluysa:

```bash
composer require phpmailer/phpmailer
```

## Manuel Kurulum

1. PHPMailer'ı indirin: https://github.com/PHPMailer/PHPMailer/releases
2. İndirdiğiniz dosyaları açın
3. `src` klasörünün içindeki dosyaları `domain-takip/vendor/PHPMailer/src/` klasörüne kopyalayın

Gerekli dosyalar:
- Exception.php
- PHPMailer.php
- SMTP.php

## Klasör Yapısı

```
domain-takip/
└── vendor/
    └── PHPMailer/
        └── src/
            ├── Exception.php
            ├── PHPMailer.php
            └── SMTP.php
```

## Alternatif: CDN Kullanımı

PHPMailer yerine basit mail() fonksiyonu da kullanılabilir, ancak SMTP desteği için PHPMailer önerilir.