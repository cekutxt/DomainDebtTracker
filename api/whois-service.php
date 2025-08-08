<?php
// Basit Whois API Servisi
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'whois';

if ($action === 'get_logs') {
    // Whois log'larını getir
    $domainId = $_GET['domain_id'] ?? 0;
    
    if (!$domainId) {
        echo json_encode(['error' => 'Domain ID gerekli']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM whois_logs
            WHERE domain_id = ?
            ORDER BY checked_at DESC
            LIMIT 10
        ");
        $stmt->execute([$domainId]);
        $logs = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'logs' => $logs]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
    exit;
}

// Normal whois sorgusu
$domain = $_GET['domain'] ?? '';

if (empty($domain)) {
    echo json_encode(['error' => 'Domain parametresi gerekli']);
    exit;
}

// Domain temizle
$domain = trim($domain);
$domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
$domain = trim($domain, '/');
$domain = strtolower($domain);

// JSONWHOIS.io API kullan (ücretsiz)
$apiUrl = "https://jsonwhois.io/api/v1/whois?domain=" . urlencode($domain);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: Domain-Takip-System/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 && $response) {
    $data = json_decode($response, true);
    
    if ($data && isset($data['result'])) {
        // Parse edilmiş veriyi döndür
        $result = [
            'success' => true,
            'domain' => $domain,
            'registrar' => $data['result']['registrar'] ?? '',
            'creation_date' => $data['result']['created'] ?? '',
            'expiry_date' => $data['result']['expires'] ?? '',
            'updated_date' => $data['result']['changed'] ?? '',
            'status' => $data['result']['status'] ?? '',
            'name_servers' => $data['result']['nameservers'] ?? [],
            'raw' => $data['result']['raw'] ?? ''
        ];
        
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Whois verisi parse edilemedi']);
    }
} else {
    // Alternatif: Doğrudan whois.com.tr kullan
    if (preg_match('/\.tr$/i', $domain)) {
        // Türk domainleri için özel işlem
        $whoisUrl = "https://www.whois.com.tr/whois/" . urlencode($domain);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $whoisUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $html = curl_exec($ch);
        curl_close($ch);
        
        if ($html) {
            // HTML'den bilgileri çıkar
            $result = [
                'success' => true,
                'domain' => $domain,
                'registrar' => '',
                'creation_date' => '',
                'expiry_date' => '',
                'updated_date' => '',
                'status' => '',
                'name_servers' => [],
                'raw' => ''
            ];
            
            // Registrar
            if (preg_match('/Organization Name\s*:\s*([^\n]+)/i', $html, $matches)) {
                $result['registrar'] = trim(strip_tags($matches[1]));
            }
            
            // Created on
            if (preg_match('/Created on\.+:\s*([^\n<]+)/i', $html, $matches)) {
                $date = trim(strip_tags($matches[1]));
                // 2015-Nov-30 formatını düzelt
                if (preg_match('/(\d{4})-(\w+)-(\d{1,2})/', $date, $dateMatch)) {
                    $months = [
                        'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
                        'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
                        'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
                    ];
                    $month = $months[$dateMatch[2]] ?? '01';
                    $result['creation_date'] = $dateMatch[1] . '-' . $month . '-' . str_pad($dateMatch[3], 2, '0', STR_PAD_LEFT);
                }
            }
            
            // Expires on
            if (preg_match('/Expires on\.+:\s*([^\n<]+)/i', $html, $matches)) {
                $date = trim(strip_tags($matches[1]));
                // 2025-Nov-30 formatını düzelt
                if (preg_match('/(\d{4})-(\w+)-(\d{1,2})/', $date, $dateMatch)) {
                    $months = [
                        'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
                        'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
                        'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
                    ];
                    $month = $months[$dateMatch[2]] ?? '01';
                    $result['expiry_date'] = $dateMatch[1] . '-' . $month . '-' . str_pad($dateMatch[3], 2, '0', STR_PAD_LEFT);
                }
            }
            
            echo json_encode($result);
        } else {
            echo json_encode(['error' => 'Whois servisi erişilemedi']);
        }
    } else {
        echo json_encode(['error' => 'Whois servisi erişilemedi']);
    }
}
?>