<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
checkAdmin();

// İstatistikleri çek
try {
    // Toplam domain sayısı
    $stmt = $db->query("SELECT COUNT(*) FROM domains");
    $totalDomains = $stmt->fetchColumn();
    
    // Aktif domain sayısı (süresi dolmamış)
    $stmt = $db->query("SELECT COUNT(*) FROM domains WHERE expiry_date > CURDATE()");
    $activeDomains = $stmt->fetchColumn();
    
    // 30 gün içinde dolacak domainler
    $stmt = $db->query("SELECT COUNT(*) FROM domains WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $expiringDomains = $stmt->fetchColumn();
    
    // Süresi dolmuş domainler
    $stmt = $db->query("SELECT COUNT(*) FROM domains WHERE expiry_date < CURDATE()");
    $expiredDomains = $stmt->fetchColumn();
    
    // Domain listesini çek
    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? 'all';
    
    $query = "SELECT * FROM domains WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND domain_name LIKE ?";
        $params[] = "%$search%";
    }
    
    switch ($filter) {
        case 'active':
            $query .= " AND expiry_date > CURDATE()";
            break;
        case 'expiring':
            $query .= " AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'expired':
            $query .= " AND expiry_date < CURDATE()";
            break;
    }
    
    $query .= " ORDER BY expiry_date ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $domains = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cekutxt Domain Takip Sistemi</title>
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
        <div class="container">
            <!-- İstatistik Kartları -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3 class="stat-card-title">Toplam Domain</h3>
                        <div class="stat-card-icon primary">
                            <i class="fas fa-globe"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalDomains; ?></div>
                    <div class="stat-card-desc">Kayıtlı domain sayısı</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3 class="stat-card-title">Aktif Domain</h3>
                        <div class="stat-card-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $activeDomains; ?></div>
                    <div class="stat-card-desc">Süresi dolmamış</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3 class="stat-card-title">Yakında Dolacak</h3>
                        <div class="stat-card-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $expiringDomains; ?></div>
                    <div class="stat-card-desc">30 gün içinde</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3 class="stat-card-title">Süresi Dolmuş</h3>
                        <div class="stat-card-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $expiredDomains; ?></div>
                    <div class="stat-card-desc">Yenilenmesi gereken</div>
                </div>
            </div>

            <!-- Domain Paneli -->
            <div class="domain-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Domain Listesi</h2>
                    <div class="panel-actions">
                        <form method="GET" class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text"
                                   name="search"
                                   placeholder="Domain ara..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        </form>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; width: 100%;">
                            <button class="btn btn-primary" onclick="showAddModal()">
                                <i class="fas fa-plus"></i> Yeni Domain
                            </button>
                            
                            <button class="btn btn-success" onclick="checkAllDomains(event)">
                                <i class="fas fa-sync"></i> Tümünü Kontrol Et
                            </button>
                            
                            <a href="borc-takip.php" class="btn btn-secondary" title="Borç Takip">
                                <i class="fas fa-money-bill-wave"></i> Borç Takip
                            </a>
                            
                            <a href="mail-settings.php" class="btn btn-secondary" title="Mail ayarları">
                                <i class="fas fa-envelope"></i> Mail Ayarları
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filtre Butonları -->
                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-200);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.5rem;">
                        <a href="?filter=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                           class="btn btn-sm <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Tümü (<?php echo $totalDomains; ?>)
                        </a>
                        <a href="?filter=active<?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                           class="btn btn-sm <?php echo $filter == 'active' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Aktif (<?php echo $activeDomains; ?>)
                        </a>
                        <a href="?filter=expiring<?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                           class="btn btn-sm <?php echo $filter == 'expiring' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Yakında Dolacak (<?php echo $expiringDomains; ?>)
                        </a>
                        <a href="?filter=expired<?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                           class="btn btn-sm <?php echo $filter == 'expired' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Süresi Dolmuş (<?php echo $expiredDomains; ?>)
                        </a>
                    </div>
                </div>

                <!-- Domain Tablosu -->
                <?php if (empty($domains)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Domain bulunamadı</h3>
                        <p>Henüz kayıtlı domain bulunmuyor veya arama kriterlerinize uygun sonuç yok.</p>
                        <button class="btn btn-primary" onclick="showAddModal()">
                            <i class="fas fa-plus"></i> İlk Domaini Ekle
                        </button>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                    <table class="domain-table">
                        <thead>
                            <tr>
                                <th>Domain Adı / Müşteri</th>
                                <th>Kayıt Firması</th>
                                <th class="hide-mobile">Oluşturma</th>
                                <th>Bitiş Tarihi</th>
                                <th>Durum</th>
                                <th class="hide-mobile">Son Kontrol</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($domains as $domain): ?>
                                <?php 
                                    $days = calculateDaysRemaining($domain['expiry_date']);
                                    $statusColor = getStatusColor($days);
                                    $statusText = getStatusText($days);
                                ?>
                                <tr>
                                    <td>
                                        <div class="domain-name"><?php echo htmlspecialchars($domain['domain_name']); ?></div>
                                        <?php
                                            $customerName = $domain['customer_name'] ?? $domain['notes'] ?? '';
                                            if ($customerName):
                                        ?>
                                            <div class="domain-registrar">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($customerName); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($domain['registrar'] ?: 'Bilinmiyor'); ?></td>
                                    <td class="hide-mobile">
                                        <?php echo $domain['creation_date'] ? date('d.m.Y', strtotime($domain['creation_date'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php echo $domain['expiry_date'] ? date('d.m.Y', strtotime($domain['expiry_date'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusColor; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td class="hide-mobile">
                                        <?php if ($domain['last_checked']): ?>
                                            <span class="text-muted">
                                                <?php echo date('d.m.Y H:i', strtotime($domain['last_checked'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Kontrol edilmedi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-action"
                                                    onclick="showWhoisInfo(<?php echo $domain['id']; ?>, event)"
                                                    title="Whois Bilgileri">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            <button class="btn-action"
                                                    onclick="editDomain(<?php echo $domain['id']; ?>, event)"
                                                    title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action danger"
                                                    onclick="deleteDomain(<?php echo $domain['id']; ?>, event)"
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

    <!-- Domain Ekleme Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Yeni Domain Ekle</h3>
                <button class="modal-close" onclick="closeModal('addModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addDomainForm" onsubmit="addDomain(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="domain_name">Domain Adı</label>
                        <input type="text" 
                               id="domain_name" 
                               name="domain_name" 
                               class="form-control" 
                               placeholder="ornek.com"
                               required>
                        <div class="form-text">Sadece domain adını girin (http:// veya www olmadan)</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="customer_name">Müşteri İsmi</label>
                        <input type="text"
                               id="customer_name"
                               name="customer_name"
                               class="form-control"
                               placeholder="Müşteri adını girin..."
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" id="auto_check" name="auto_check" checked>
                            Ekledikten sonra otomatik whois kontrolü yap
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Domain Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Whois Bilgileri Modal -->
    <div id="whoisModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Whois Bilgileri</h3>
                <button class="modal-close" onclick="closeModal('whoisModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="whoisContent">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                    <p style="margin-top: 1rem;">Whois bilgileri yükleniyor...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('whoisModal')">Kapat</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>