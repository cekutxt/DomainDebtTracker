<?php
// Bu dosya cron job olarak günde bir kez çalıştırılmalıdır
// Örnek cron: 0 9 * * * /usr/bin/php /path/to/domain-takip/cron/check-notifications.php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// PHPMailer varsa onu kullan, yoksa basit mail fonksiyonunu kullan
if (file_exists(dirname(__DIR__) . '/vendor/PHPMailer/src/PHPMailer.php')) {
    require_once dirname(__DIR__) . '/includes/mail-functions.php';
} else {
    require_once dirname(__DIR__) . '/includes/simple-mail.php';
}

// CLI'den çalıştığını kontrol et
if (php_sapi_name() !== 'cli') {
    die('Bu script sadece komut satırından çalıştırılabilir.');
}

echo "Domain bildirim kontrolü başlatılıyor...\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('-', 50) . "\n";

try {
    // Mail ayarlarını kontrol et
    $settings = getMailSettings($db);
    
    if (!$settings || !$settings['enabled']) {
        echo "Mail bildirimleri devre dışı veya yapılandırılmamış.\n";
        exit;
    }
    
    echo "Mail ayarları bulundu. Alıcı: {$settings['to_email']}\n";
    echo "Bildirim günleri: {$settings['notification_days']}\n\n";
    
    // Bildirimleri kontrol et ve gönder
    $result = checkAndSendNotifications($db);
    
    if ($result['success']) {
        echo "Bildirim kontrolü tamamlandı.\n";
        echo "Gönderilen mail sayısı: {$result['sent']}\n";
    } else {
        echo "Hata: {$result['error']}\n";
    }
    
    // Ek olarak: Süresi bugün dolan domainler için özel kontrol
    $stmt = $db->query("
        SELECT * FROM domains 
        WHERE DATE(expiry_date) = CURDATE()
    ");
    
    $expiringToday = $stmt->fetchAll();
    
    if (count($expiringToday) > 0) {
        echo "\n⚠️ BUGÜN SÜRESİ DOLAN DOMAİNLER:\n";
        foreach ($expiringToday as $domain) {
            echo "- {$domain['domain_name']}\n";
            
            // Acil bildirim gönder
            sendDomainExpiryNotification($domain, 0, $db);
        }
    }
    
    // Log dosyasına yaz
    $logFile = dirname(__DIR__) . '/logs/cron.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = date('Y-m-d H:i:s') . " - Bildirim kontrolü tamamlandı. Gönderilen: {$result['sent']}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    
    // Hata logla
    $errorLog = date('Y-m-d H:i:s') . " - HATA: " . $e->getMessage() . "\n";
    file_put_contents(dirname(__DIR__) . '/logs/cron-errors.log', $errorLog, FILE_APPEND);
}

echo "\nİşlem tamamlandı.\n";
?>