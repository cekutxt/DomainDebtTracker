<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

$message = '';
$error = '';

// Mail tabloları var mı kontrol et
try {
    $stmt = $db->query("SELECT * FROM mail_settings LIMIT 1");
    $settings = $stmt->fetch();
} catch (PDOException $e) {
    // Tablo yoksa oluşturma sayfasına yönlendir
    header('Location: create-mail-tables.php');
    exit;
}

// Form gönderilmişse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $smtp_host = $_POST['smtp_host'] ?? '';
    $smtp_port = $_POST['smtp_port'] ?? 587;
    $smtp_secure = $_POST['smtp_secure'] ?? 'tls';
    $smtp_username = $_POST['smtp_username'] ?? '';
    $smtp_password = $_POST['smtp_password'] ?? '';
    // Gmail uygulama şifresindeki boşlukları temizle
    $smtp_password = str_replace(' ', '', $smtp_password);
    $from_email = $_POST['from_email'] ?? '';
    $from_name = $_POST['from_name'] ?? 'Domain Takip Sistemi';
    $to_email = $_POST['to_email'] ?? '';
    $notification_days = $_POST['notification_days'] ?? '30,15,7,5,4,3,2,1';
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    try {
        if ($settings) {
            // Güncelle
            $stmt = $db->prepare("
                UPDATE mail_settings SET 
                    smtp_host = ?,
                    smtp_port = ?,
                    smtp_secure = ?,
                    smtp_username = ?,
                    smtp_password = ?,
                    from_email = ?,
                    from_name = ?,
                    to_email = ?,
                    notification_days = ?,
                    enabled = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $smtp_host, $smtp_port, $smtp_secure, $smtp_username, 
                $smtp_password, $from_email, $from_name, $to_email, 
                $notification_days, $enabled, $settings['id']
            ]);
        } else {
            // Yeni ekle
            $stmt = $db->prepare("
                INSERT INTO mail_settings 
                (smtp_host, smtp_port, smtp_secure, smtp_username, smtp_password, 
                 from_email, from_name, to_email, notification_days, enabled) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $smtp_host, $smtp_port, $smtp_secure, $smtp_username, 
                $smtp_password, $from_email, $from_name, $to_email, 
                $notification_days, $enabled
            ]);
        }
        
        $message = 'Mail ayarları kaydedildi.';
        
        // Test maili gönder
        if (isset($_POST['send_test'])) {
            // PHPMailer yoksa basit mail fonksiyonunu kullan
            if (file_exists('../vendor/PHPMailer/src/PHPMailer.php')) {
                require_once '../includes/mail-functions.php';
                $sendFunction = 'sendMail';
            } else {
                require_once '../includes/simple-mail.php';
                $sendFunction = 'sendHtmlMail';
            }
            
            $testSubject = "Test Maili - Domain Takip Sistemi";
            $testBody = "
                <h2>Test Maili</h2>
                <p>Bu mail, Domain Takip Sistemi mail ayarlarının doğru yapılandırıldığını test etmek için gönderilmiştir.</p>
                <p>Mail başarıyla alındıysa, ayarlarınız doğru demektir.</p>
                <hr>
                <p><small>Gönderim zamanı: " . date('d.m.Y H:i:s') . "</small></p>
            ";
            
            $result = $sendFunction($testSubject, $testBody, $db);
            
            if ($result['success']) {
                $message .= ' Test maili başarıyla gönderildi!';
            } else {
                $error = 'Test maili gönderilemedi: ' . $result['error'];
            }
        }
        
        // Ayarları yeniden yükle
        $stmt = $db->query("SELECT * FROM mail_settings LIMIT 1");
        $settings = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Ayarları - cekutxt Domain Takip</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-globe"></i>
                    cekutxt Domain Takip Sistemi
                </a>
                <nav style="flex: 1; display: flex; justify-content: center; align-items: center;">
                    <a href="index.php" style="color: white; text-decoration: none; font-weight: 500; padding: 0.5rem 1rem; border-radius: var(--radius-sm); transition: all 0.3s ease;">
                        <i class="fas fa-server"></i> Domain Kontrol
                    </a>
                    <a href="borc-takip.php" style="color: white; text-decoration: none; font-weight: 500; padding: 0.5rem 1rem; margin-left: 1rem; border-radius: var(--radius-sm); transition: all 0.3s ease;">
                        <i class="fas fa-money-bill-wave"></i> Borç Takip
                    </a>
                </nav>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    </div>
                    <a href="change-password.php" class="btn-logout" style="background: rgba(255, 255, 255, 0.1); margin-right: 0.5rem;">
                        <i class="fas fa-key"></i> Şifre Değiştir
                    </a>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container" style="max-width: 800px;">
            <div class="domain-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="fas fa-envelope"></i> Mail Ayarları
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
                    
                    <?php if (!file_exists('../vendor/PHPMailer/src/PHPMailer.php')): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>PHPMailer kurulu değil!</strong> SMTP mail göndermek için PHPMailer gereklidir.
                            <a href="download-phpmailer.php" style="color: var(--warning-color); text-decoration: underline; margin-left: 0.5rem;">
                                PHPMailer'ı İndir
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <h3 style="margin-bottom: 1rem;">SMTP Sunucu Ayarları</h3>
                        
                        <div class="form-group">
                            <label class="form-label" for="smtp_host">SMTP Sunucu</label>
                            <input type="text" 
                                   id="smtp_host" 
                                   name="smtp_host" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>"
                                   placeholder="smtp.gmail.com"
                                   required>
                            <div class="form-text">Örn: smtp.gmail.com, smtp.yandex.com, mail.yourdomain.com</div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" for="smtp_port">Port</label>
                                <input type="number" 
                                       id="smtp_port" 
                                       name="smtp_port" 
                                       class="form-control" 
                                       value="<?php echo $settings['smtp_port'] ?? 587; ?>"
                                       required>
                                <div class="form-text">TLS: 587, SSL: 465</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="smtp_secure">Güvenlik</label>
                                <select id="smtp_secure" name="smtp_secure" class="form-control">
                                    <option value="tls" <?php echo ($settings['smtp_secure'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($settings['smtp_secure'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo ($settings['smtp_secure'] ?? '') == '' ? 'selected' : ''; ?>>Yok</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="smtp_username">SMTP Kullanıcı Adı</label>
                            <input type="text" 
                                   id="smtp_username" 
                                   name="smtp_username" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>"
                                   placeholder="your-email@gmail.com"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="smtp_password">SMTP Şifre</label>
                            <input type="password" 
                                   id="smtp_password" 
                                   name="smtp_password" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>"
                                   placeholder="••••••••"
                                   required>
                            <div class="form-text">Gmail için uygulama şifresi kullanın</div>
                        </div>
                        
                        <hr style="margin: 2rem 0;">
                        
                        <h3 style="margin-bottom: 1rem;">Gönderici ve Alıcı Bilgileri</h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" for="from_email">Gönderici E-posta</label>
                                <input type="email" 
                                       id="from_email" 
                                       name="from_email" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['from_email'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="from_name">Gönderici Adı</label>
                                <input type="text" 
                                       id="from_name" 
                                       name="from_name" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['from_name'] ?? 'Domain Takip Sistemi'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="to_email">Bildirim Alıcı E-posta</label>
                            <input type="email" 
                                   id="to_email" 
                                   name="to_email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['to_email'] ?? ''); ?>"
                                   placeholder="admin@yourdomain.com"
                                   required>
                            <div class="form-text">Domain süre bildirimleri bu adrese gönderilecek</div>
                        </div>
                        
                        <hr style="margin: 2rem 0;">
                        
                        <h3 style="margin-bottom: 1rem;">Bildirim Ayarları</h3>
                        
                        <div class="form-group">
                            <label class="form-label" for="notification_days">Bildirim Günleri</label>
                            <input type="text" 
                                   id="notification_days" 
                                   name="notification_days" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['notification_days'] ?? '30,15,7,5,4,3,2,1'); ?>">
                            <div class="form-text">Virgülle ayrılmış gün sayıları (örn: 30,15,7,5,4,3,2,1)</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" 
                                       name="enabled" 
                                       value="1" 
                                       <?php echo ($settings['enabled'] ?? 1) ? 'checked' : ''; ?>>
                                Mail bildirimleri aktif
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                            
                            <button type="submit" name="send_test" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Test Maili Gönder
                            </button>
                            
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Geri Dön
                            </a>
                        </div>
                    </form>
                    
                    <div style="margin-top: 3rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin-bottom: 1rem;">📧 Popüler SMTP Ayarları</h4>
                        
                        <div style="display: grid; gap: 1rem;">
                            <div>
                                <strong>Gmail:</strong><br>
                                Sunucu: smtp.gmail.com, Port: 587 (TLS) veya 465 (SSL)<br>
                                <small>Not: Gmail için uygulama şifresi oluşturmanız gerekir.</small><br>
                                <a href="gmail-smtp-guide.php" style="color: var(--primary-color); font-size: 0.875rem;">
                                    <i class="fas fa-question-circle"></i> Gmail Ayarları Rehberi
                                </a>
                            </div>
                            
                            <div>
                                <strong>Yandex:</strong><br>
                                Sunucu: smtp.yandex.com, Port: 587 (TLS) veya 465 (SSL)
                            </div>
                            
                            <div>
                                <strong>Outlook/Hotmail:</strong><br>
                                Sunucu: smtp-mail.outlook.com, Port: 587 (TLS)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
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
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
    </style>
</body>
</html>