<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

header('Content-Type: application/json');

$domainId = $_GET['id'] ?? null;

if (!$domainId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Domain ID gereklidir.']);
    exit;
}

try {
    // Domain bilgisini al
    $stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
    $stmt->execute([$domainId]);
    $domain = $stmt->fetch();
    
    if (!$domain) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Domain bulunamadı.']);
        exit;
    }
    
    // Whois bilgilerini çek
    $whoisInfo = getWhoisInfo($domain['domain_name']);
    
    if ($whoisInfo) {
        // Domain bilgilerini güncelle
        $stmt = $db->prepare("
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
        
        $stmt->execute([
            $whoisInfo['registrar'],
            $whoisInfo['creation_date'],
            $whoisInfo['expiry_date'],
            $whoisInfo['updated_date'],
            $whoisInfo['status'],
            implode(', ', $whoisInfo['name_servers']),
            $domainId
        ]);
        
        // Whois log kaydı ekle
        $stmt = $db->prepare("INSERT INTO whois_logs (domain_id, whois_data) VALUES (?, ?)");
        $stmt->execute([$domainId, json_encode($whoisInfo)]);
        
        // Mail bildirimi kontrolü
        if (file_exists('../vendor/PHPMailer/src/PHPMailer.php')) {
            require_once '../includes/mail-functions.php';
        } else {
            require_once '../includes/simple-mail.php';
        }
        checkNewDomainExpiry($domainId, $db);
        
        echo json_encode([
            'success' => true,
            'message' => 'Whois kontrolü başarıyla tamamlandı.',
            'data' => $whoisInfo
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Whois bilgileri alınamadı. Domain adını kontrol edin veya daha sonra tekrar deneyin.'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
?>