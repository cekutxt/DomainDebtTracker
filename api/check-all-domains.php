<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

header('Content-Type: application/json');

// Zaman aşımını artır
set_time_limit(300); // 5 dakika

try {
    // Tüm domainleri al
    $stmt = $db->query("SELECT id, domain_name FROM domains ORDER BY last_checked ASC");
    $domains = $stmt->fetchAll();
    
    $checked = 0;
    $failed = 0;
    
    foreach ($domains as $domain) {
        // Her domain arasında 2 saniye bekle (rate limiting)
        if ($checked > 0) {
            sleep(2);
        }
        
        // Whois bilgilerini çek
        $whoisInfo = getWhoisInfo($domain['domain_name']);
        
        if ($whoisInfo) {
            // Domain bilgilerini güncelle
            $updateStmt = $db->prepare("
                UPDATE domains SET 
                    registrar = ?,
                    creation_date = ?,
                    expiry_date = ?,
                    updated_date = ?,
                    status = ?,
                    name_servers = ?,
                    last_checked = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $whoisInfo['registrar'],
                $whoisInfo['creation_date'],
                $whoisInfo['expiry_date'],
                $whoisInfo['updated_date'],
                $whoisInfo['status'],
                implode(', ', $whoisInfo['name_servers']),
                $domain['id']
            ]);
            
            // Whois log kaydı ekle
            $logStmt = $db->prepare("INSERT INTO whois_logs (domain_id, whois_data) VALUES (?, ?)");
            $logStmt->execute([$domain['id'], json_encode($whoisInfo)]);
            
            $checked++;
        } else {
            $failed++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Kontrol tamamlandı. Başarılı: $checked, Başarısız: $failed",
        'checked' => $checked,
        'failed' => $failed,
        'total' => count($domains)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>