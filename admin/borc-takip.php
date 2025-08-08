<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

// İstatistikleri çek
try {
    // Toplam borç
    $stmt = $db->query("SELECT SUM(amount) FROM simple_debts");
    $totalDebt = $stmt->fetchColumn() ?: 0;
    
    // Borç sayısı
    $stmt = $db->query("SELECT COUNT(*) FROM simple_debts");
    $debtCount = $stmt->fetchColumn();
    
    // Borç listesini çek
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT * FROM simple_debts WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (customer_name LIKE ? OR reason LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $debts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borç Takip - cekutxt Domain Takip</title>
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
                    <a href="borc-takip.php" style="color: white; text-decoration: none; font-weight: 500; padding: 0.5rem 1rem; margin-left: 1rem; border-radius: var(--radius-sm); transition: all 0.3s ease; background: rgba(255,255,255,0.2);">
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
        <div class="container">
            <!-- İstatistik Kartları -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3 class="stat-card-title">Toplam Borç</h3>
                        <div class="stat-card-icon danger">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-card-value">₺<?php echo number_format($totalDebt, 2, ',', '.'); ?></div>
                    <div class="stat-card-desc">Toplam borç miktarı</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3 class="stat-card-title">Borç Sayısı</h3>
                        <div class="stat-card-icon info">
                            <i class="fas fa-list"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $debtCount; ?></div>
                    <div class="stat-card-desc">Kayıtlı borç adedi</div>
                </div>
            </div>

            <!-- Borç Paneli -->
            <div class="domain-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Borç Listesi</h2>
                    <div class="panel-actions">
                        <form method="GET" class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="search" 
                                   placeholder="Müşteri veya neden ara..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                        
                        <button class="btn btn-primary" onclick="showAddDebtModal()">
                            <i class="fas fa-plus"></i> Yeni Borç
                        </button>
                    </div>
                </div>

                <!-- Borç Tablosu -->
                <?php if (empty($debts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Borç kaydı bulunamadı</h3>
                        <p>Henüz kayıtlı borç bulunmuyor veya arama kriterlerinize uygun sonuç yok.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                    <table class="domain-table">
                        <thead>
                            <tr>
                                <th>Müşteri Adı</th>
                                <th>Borç Miktarı</th>
                                <th>Borç Nedeni</th>
                                <th>Eklenme Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($debts as $debt): ?>
                                <tr>
                                    <td>
                                        <div class="domain-name"><?php echo htmlspecialchars($debt['customer_name']); ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--danger-color);">₺<?php echo number_format($debt['amount'], 2, ',', '.'); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($debt['reason']); ?></td>
                                    <td>
                                        <span class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($debt['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-action danger"
                                                    onclick="deleteDebt(<?php echo $debt['id']; ?>)"
                                                    title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Borç Ekleme Modal -->
    <div id="addDebtModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Yeni Borç Ekle</h3>
                <button class="modal-close" onclick="closeModal('addDebtModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addDebtForm" onsubmit="addDebt(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="customer_name">Müşteri Adı</label>
                        <input type="text" 
                               id="customer_name" 
                               name="customer_name" 
                               class="form-control" 
                               placeholder="Örn: Batuhan"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="amount">Borç Miktarı (₺)</label>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               class="form-control" 
                               placeholder="0.00"
                               step="0.01"
                               min="0"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="reason">Borç Nedeni</label>
                        <textarea id="reason" 
                                  name="reason" 
                                  class="form-control" 
                                  rows="3"
                                  placeholder="Neden borçlu? Örn: Domain yenileme ücreti, hosting ödemesi vb."
                                  required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addDebtModal')">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Borç Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal işlemleri
        function showAddDebtModal() {
            document.getElementById('addDebtModal').classList.add('show');
            document.getElementById('customer_name').focus();
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Borç ekleme
        async function addDebt(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            try {
                const response = await fetch('../api/simple-debts.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Borç eklenirken bir hata oluştu.');
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        }

        // Borç silme
        async function deleteDebt(debtId) {
            if (!confirm('Bu borç kaydını silmek istediğinizden emin misiniz?')) {
                return;
            }
            
            try {
                const response = await fetch(`../api/simple-debts.php?id=${debtId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Borç silinemedi.');
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        }

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>