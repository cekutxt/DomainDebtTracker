<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

$message = '';
$error = '';

// Form gönderilmişse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    try {
        // Mevcut kullanıcının bilgilerini al
        $stmt = $db->prepare("SELECT password FROM admin_users WHERE username = ?");
        $stmt->execute([$_SESSION['admin_username']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Kullanıcı bulunamadı.');
        }
        
        // Mevcut şifreyi kontrol et
        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception('Mevcut şifre yanlış.');
        }
        
        // Yeni şifreleri kontrol et
        if (empty($newPassword)) {
            throw new Exception('Yeni şifre boş olamaz.');
        }
        
        if (strlen($newPassword) < 5) {
            throw new Exception('Yeni şifre en az 5 karakter olmalıdır.');
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new Exception('Yeni şifreler eşleşmiyor.');
        }
        
        // Şifreyi güncelle
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $_SESSION['admin_username']]);
        
        $message = 'Şifreniz başarıyla güncellendi!';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Değiştir - cekutxt Domain Takip</title>
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
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container" style="max-width: 600px;">
            <div class="domain-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="fas fa-key"></i> Şifre Değiştir
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
                            <label class="form-label" for="current_password">Mevcut Şifre</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="new_password">Yeni Şifre</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   required>
                            <div class="form-text">En az 5 karakter olmalıdır</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Yeni Şifre (Tekrar)</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Şifreyi Güncelle
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Geri Dön
                            </a>
                        </div>
                    </form>
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
    </style>
</body>
</html>