<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

$domainId = $_GET['id'] ?? null;
$message = '';
$error = '';

if (!$domainId) {
    header('Location: index.php');
    exit;
}

// Domain bilgilerini al
$stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
$stmt->execute([$domainId]);
$domain = $stmt->fetch();

if (!$domain) {
    header('Location: index.php');
    exit;
}

// Form gönderilmişse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expiryDate = $_POST['expiry_date'] ?? '';
    $creationDate = $_POST['creation_date'] ?? '';
    $registrar = $_POST['registrar'] ?? '';
    $customerName = $_POST['customer_name'] ?? '';
    
    try {
        $stmt = $db->prepare("
            UPDATE domains SET
                expiry_date = ?,
                creation_date = ?,
                registrar = ?,
                notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $expiryDate ?: null,
            $creationDate ?: null,
            $registrar,
            $customerName,
            $domainId
        ]);
        
        $message = 'Domain bilgileri güncellendi.';
        
        // Güncel bilgileri yeniden yükle
        $stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
        $stmt->execute([$domainId]);
        $domain = $stmt->fetch();
        
        // Mail bildirimi kontrolü
        if ($expiryDate) {
            $daysRemaining = calculateDaysRemaining($expiryDate);
            
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
                    // Bugün için mail log'u sil (yeniden gönderebilmek için)
                    $stmt = $db->prepare("
                        DELETE FROM mail_logs
                        WHERE domain_id = ?
                        AND days_remaining = ?
                        AND DATE(sent_at) = CURDATE()
                    ");
                    $stmt->execute([$domainId, $daysRemaining]);
                    
                    // Mail gönder
                    if (sendDomainExpiryNotification($domain, $daysRemaining, $db)) {
                        $message .= ' Süre uyarı maili gönderildi!';
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        $error = 'Güncelleme hatası: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Düzenle - cekutxt Domain Takip</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container" style="margin-top: 2rem; max-width: 800px;">
        <a href="index.php" class="btn btn-secondary" style="margin-bottom: 1rem;">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </a>
        
        <div class="domain-panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i class="fas fa-edit"></i> 
                    <?php echo htmlspecialchars($domain['domain_name']); ?> - Düzenle
                </h2>
            </div>
            
            <div style="padding: 2rem;">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Domain Adı</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($domain['domain_name']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="registrar">Kayıt Firması</label>
                        <input type="text" 
                               id="registrar" 
                               name="registrar" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($domain['registrar'] ?? ''); ?>"
                               placeholder="Örn: NIC Handle">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="creation_date">Oluşturma Tarihi</label>
                        <input type="date" 
                               id="creation_date" 
                               name="creation_date" 
                               class="form-control" 
                               value="<?php echo $domain['creation_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="expiry_date">Bitiş Tarihi</label>
                        <input type="date" 
                               id="expiry_date" 
                               name="expiry_date" 
                               class="form-control" 
                               value="<?php echo $domain['expiry_date'] ?? ''; ?>">
                        <div class="form-text">
                            <?php if ($domain['expiry_date']): ?>
                                <?php 
                                    $days = calculateDaysRemaining($domain['expiry_date']);
                                    $statusText = getStatusText($days);
                                ?>
                                Durum: <?php echo $statusText; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="customer_name">Müşteri İsmi</label>
                        <input type="text"
                               id="customer_name"
                               name="customer_name"
                               class="form-control"
                               value="<?php echo htmlspecialchars($domain['customer_name'] ?? $domain['notes'] ?? ''); ?>"
                               placeholder="Müşteri adını girin...">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Son Kontrol</label>
                        <input type="text" 
                               class="form-control" 
                               value="<?php echo $domain['last_checked'] ? date('d.m.Y H:i', strtotime($domain['last_checked'])) : 'Henüz kontrol edilmedi'; ?>" 
                               disabled>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Kaydet
                        </button>
                        
                        <button type="button" 
                                class="btn btn-success" 
                                onclick="if(confirm('Whois bilgilerini güncellemek istiyor musunuz?')) { checkAndUpdate(<?php echo $domainId; ?>); }">
                            <i class="fas fa-sync"></i> Whois Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .alert {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
    
    <script>
        async function checkAndUpdate(domainId) {
            try {
                const response = await fetch(`../api/check-domain.php?id=${domainId}`);
                const result = await response.json();
                
                if (result.success) {
                    alert('Whois bilgileri güncellendi!');
                    location.reload();
                } else {
                    alert(result.message || 'Whois kontrolü başarısız oldu.');
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        }
    </script>
</body>
</html>