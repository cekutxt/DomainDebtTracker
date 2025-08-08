<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

header('Content-Type: application/json');

// Request method'a göre işlem yap
$method = $_SERVER['REQUEST_METHOD'];
$domainId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            // Tek domain bilgisi getir
            if ($domainId) {
                $stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
                $stmt->execute([$domainId]);
                $domain = $stmt->fetch();
                
                if ($domain) {
                    echo json_encode($domain);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Domain bulunamadı']);
                }
            } else {
                // Tüm domainleri getir
                $stmt = $db->query("SELECT * FROM domains ORDER BY expiry_date ASC");
                $domains = $stmt->fetchAll();
                echo json_encode($domains);
            }
            break;
            
        case 'POST':
            // Yeni domain ekle
            $domainName = trim($_POST['domain_name'] ?? '');
            $customerName = trim($_POST['customer_name'] ?? $_POST['notes'] ?? '');
            
            if (empty($domainName)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Domain adı gereklidir.']);
                exit;
            }
            
            if (empty($customerName)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Müşteri ismi gereklidir.']);
                exit;
            }
            
            // Domain adını temizle - sadece domain kısmını al
            $domainName = str_replace(['http://', 'https://', 'www.'], '', $domainName);
            $domainName = trim($domainName, '/');
            $domainName = explode('/', $domainName)[0];
            $domainName = strtolower(trim($domainName));
            
            // Domain zaten var mı kontrol et
            $stmt = $db->prepare("SELECT id FROM domains WHERE domain_name = ?");
            $stmt->execute([$domainName]);
            
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Bu domain zaten kayıtlı.']);
                exit;
            }
            
            // Domain ekle - hem notes hem customer_name alanlarına ekle
            $stmt = $db->prepare("INSERT INTO domains (domain_name, notes) VALUES (?, ?)");
            $stmt->execute([$domainName, $customerName]);
            
            $domainId = $db->lastInsertId();
            
            // Otomatik whois kontrolü yap
            if (isset($_POST['auto_check']) && $_POST['auto_check'] === 'on') {
                $whoisInfo = getWhoisInfo($domainName);
                
                if ($whoisInfo && $whoisInfo['expiry_date']) {
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
                    
                    // Mail bildirimi kontrolü - süre ne olursa olsun bildirim günlerinde ise mail gönder
                    if ($whoisInfo['expiry_date']) {
                        $daysRemaining = calculateDaysRemaining($whoisInfo['expiry_date']);
                        
                        // Mail fonksiyonlarını yükle
                        if (file_exists('../vendor/PHPMailer/src/PHPMailer.php')) {
                            require_once '../includes/mail-functions.php';
                        } else {
                            require_once '../includes/simple-mail.php';
                        }
                        
                        // Mail ayarlarını kontrol et
                        $settings = getMailSettings($db);
                        if ($settings && $settings['enabled']) {
                            $notificationDays = explode(',', $settings['notification_days']);
                            $notificationDays = array_map('intval', $notificationDays);
                            
                            // Bildirim günlerinden birindeyse mail gönder
                            if (in_array($daysRemaining, $notificationDays)) {
                                // Domain bilgisini al
                                $stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
                                $stmt->execute([$domainId]);
                                $domain = $stmt->fetch();
                                
                                sendDomainExpiryNotification($domain, $daysRemaining, $db);
                            }
                        }
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Domain başarıyla eklendi.',
                'domain_id' => $domainId
            ]);
            break;
            
        case 'PUT':
            // Domain güncelle
            if (!$domainId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Domain ID gereklidir.']);
                exit;
            }
            
            parse_str(file_get_contents('php://input'), $data);
            
            $customerName = trim($data['customer_name'] ?? $data['notes'] ?? '');
            
            $stmt = $db->prepare("UPDATE domains SET notes = ? WHERE id = ?");
            $stmt->execute([$customerName, $domainId]);
            
            echo json_encode(['success' => true, 'message' => 'Domain güncellendi.']);
            break;
            
        case 'DELETE':
            // Domain sil
            if (!$domainId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Domain ID gereklidir.']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM domains WHERE id = ?");
            $stmt->execute([$domainId]);
            
            echo json_encode(['success' => true, 'message' => 'Domain silindi.']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>