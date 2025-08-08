// Domain Takip Sistemi JavaScript

// Modal işlemleri
function showAddModal() {
    document.getElementById('addModal').classList.add('show');
    document.getElementById('domain_name').focus();
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Domain ekleme
async function addDomain(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('../api/domains.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (formData.get('auto_check') === 'on' && result.domain_id) {
                // Otomatik whois kontrolü
                await checkDomain(result.domain_id, false);
            }
            location.reload();
        } else {
            alert(result.message || 'Domain eklenirken bir hata oluştu.');
        }
    } catch (error) {
        alert('Bir hata oluştu: ' + error.message);
    }
}

// Domain whois kontrolü
async function checkDomain(domainId, showAlert = true, event = null) {
    const button = event ? (event.currentTarget || event.target) : null;
    const originalContent = button ? button.innerHTML : '';
    if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
    }
    
    try {
        const response = await fetch(`../api/check-domain.php?id=${domainId}`);
        const result = await response.json();
        
        if (result.success) {
            if (showAlert) {
                alert('Whois kontrolü tamamlandı!');
            }
            location.reload();
        } else {
            alert(result.message || 'Whois kontrolü başarısız oldu.');
        }
    } catch (error) {
        alert('Bir hata oluştu: ' + error.message);
    } finally {
        if (button) {
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    }
}

// Tüm domainleri kontrol et
async function checkAllDomains(event = null) {
    if (!confirm('Tüm domainlerin whois kontrolü yapılacak. Bu işlem biraz zaman alabilir. Devam etmek istiyor musunuz?')) {
        return;
    }
    
    const button = event ? (event.currentTarget || event.target) : null;
    const originalContent = button ? button.innerHTML : '';
    if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kontrol ediliyor...';
        button.disabled = true;
    }
    
    try {
        const response = await fetch('../api/check-all-domains.php');
        const result = await response.json();
        
        if (result.success) {
            alert(`${result.checked} domain başarıyla kontrol edildi!`);
            location.reload();
        } else {
            alert(result.message || 'Kontrol işlemi başarısız oldu.');
        }
    } catch (error) {
        alert('Bir hata oluştu: ' + error.message);
    } finally {
        if (button) {
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    }
}

// Domain düzenleme
async function editDomain(domainId, event = null) {
    // Düzenleme sayfasına yönlendir
    window.location.href = `edit-domain.php?id=${domainId}`;
}

// Domain silme
async function deleteDomain(domainId, event = null) {
    if (!confirm('Bu domaini silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    try {
        const response = await fetch(`../api/domains.php?id=${domainId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Domain silinemedi.');
        }
    } catch (error) {
        alert('Bir hata oluştu: ' + error.message);
    }
}

// Whois bilgilerini göster
async function showWhoisInfo(domainId, event = null) {
    // Modal'ı aç
    document.getElementById('whoisModal').classList.add('show');
    
    try {
        // Domain bilgilerini al
        const response = await fetch(`../api/domains.php?id=${domainId}`);
        const domain = await response.json();
        
        if (domain.error) {
            document.getElementById('whoisContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Domain bilgileri alınamadı.
                </div>
            `;
            return;
        }
        
        // Whois log'larını al
        const whoisResponse = await fetch(`../api/whois-service.php?action=get_logs&domain_id=${domainId}`);
        const whoisData = await whoisResponse.json();
        
        let content = `
            <div style="padding: 1rem;">
                <h4 style="margin-bottom: 1rem;">
                    <i class="fas fa-globe"></i> ${domain.domain_name}
                </h4>
                
                <div style="display: grid; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-sm);">
                        <strong>Müşteri:</strong> ${domain.customer_name || domain.notes || 'Belirtilmemiş'}
                    </div>
                    <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-sm);">
                        <strong>Kayıt Firması:</strong> ${domain.registrar || 'Bilinmiyor'}
                    </div>
                    <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-sm);">
                        <strong>Oluşturma Tarihi:</strong> ${domain.creation_date ? new Date(domain.creation_date).toLocaleDateString('tr-TR') : 'Bilinmiyor'}
                    </div>
                    <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-sm);">
                        <strong>Bitiş Tarihi:</strong> ${domain.expiry_date ? new Date(domain.expiry_date).toLocaleDateString('tr-TR') : 'Bilinmiyor'}
                    </div>
                    <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-sm);">
                        <strong>Name Servers:</strong> ${domain.name_servers || 'Bilinmiyor'}
                    </div>
                </div>
        `;
        
        if (whoisData.logs && whoisData.logs.length > 0) {
            const latestLog = whoisData.logs[0];
            content += `
                <h5 style="margin-top: 1.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-file-alt"></i> Son Whois Verisi
                    <small style="color: var(--gray-600); font-weight: normal;">
                        (${new Date(latestLog.checked_at).toLocaleString('tr-TR')})
                    </small>
                </h5>
                <pre style="background: var(--gray-900); color: var(--gray-100); padding: 1rem; border-radius: var(--radius-sm); overflow-x: auto; max-height: 400px; overflow-y: auto; font-size: 0.875rem; line-height: 1.5;">${latestLog.whois_data || 'Whois verisi bulunamadı'}</pre>
            `;
        } else {
            content += `
                <div class="alert alert-warning" style="margin-top: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Bu domain için henüz whois kontrolü yapılmamış.
                </div>
            `;
        }
        
        document.getElementById('whoisContent').innerHTML = content;
        
    } catch (error) {
        document.getElementById('whoisContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                Bir hata oluştu: ${error.message}
            </div>
        `;
    }
}

// Modal dışına tıklandığında kapat
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
}

// Enter tuşu ile arama
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-box input[type="text"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
});