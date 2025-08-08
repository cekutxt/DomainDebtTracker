<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

header('Content-Type: application/json');

// Request method'a göre işlem yap
$method = $_SERVER['REQUEST_METHOD'];
$debtId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            // Borçları getir
            $stmt = $db->query("SELECT * FROM simple_debts ORDER BY created_at DESC");
            $debts = $stmt->fetchAll();
            echo json_encode($debts);
            break;
            
        case 'POST':
            // Yeni borç ekle
            $customerName = trim($_POST['customer_name'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            
            if (empty($customerName)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Müşteri adı gereklidir.']);
                exit;
            }
            
            if ($amount <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçerli bir borç miktarı giriniz.']);
                exit;
            }
            
            if (empty($reason)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Borç nedeni gereklidir.']);
                exit;
            }
            
            // Borç ekle
            $stmt = $db->prepare("
                INSERT INTO simple_debts (customer_name, amount, reason) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $customerName,
                $amount,
                $reason
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Borç başarıyla eklendi.',
                'debt_id' => $db->lastInsertId()
            ]);
            break;
            
        case 'DELETE':
            // Borç sil
            if (!$debtId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Borç ID gereklidir.']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM simple_debts WHERE id = ?");
            $stmt->execute([$debtId]);
            
            echo json_encode(['success' => true, 'message' => 'Borç silindi.']);
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