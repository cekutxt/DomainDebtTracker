<?php
// Whois bilgilerini çeken fonksiyon
function getWhoisInfo($domain) {
    // Domain adını temizle
    $domain = trim($domain);
    $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
    $domain = trim($domain, '/');
    $domain = explode('/', $domain)[0];
    $domain = strtolower(trim($domain));
    
    // Domain validasyonu
    if (!preg_match('/^[a-z0-9\-\.]+\.[a-z]{2,}$/i', $domain)) {
        return false;
    }
    
    // Whois API veya socket bağlantısı ile bilgi çekme
    $whoisData = performWhoisLookup($domain);
    
    if ($whoisData) {
        return parseWhoisData($whoisData);
    }
    
    return false;
}

// Whois sorgusu yapan fonksiyon
function performWhoisLookup($domain) {
    // .tr domainleri için özel kontrol
    if (preg_match('/\.tr$/i', $domain)) {
        $whoisServer = 'whois.nic.tr';
    } else {
        // TLD'yi al
        $parts = explode('.', $domain);
        $tld = end($parts);
        
        // TLD'ye göre whois sunucuları
        $whoisServers = [
            'com' => 'whois.verisign-grs.com',
            'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org',
            'info' => 'whois.afilias.net',
            'biz' => 'whois.biz',
            'io' => 'whois.nic.io',
            'co' => 'whois.nic.co',
            'me' => 'whois.nic.me',
            'tv' => 'whois.nic.tv'
        ];
        
        $whoisServer = isset($whoisServers[$tld]) ? $whoisServers[$tld] : 'whois.iana.org';
    }
    
    // Socket bağlantısı
    $fp = @fsockopen($whoisServer, 43, $errno, $errstr, 10);
    if (!$fp) {
        return false;
    }
    
    fputs($fp, $domain . "\r\n");
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 128);
    }
    fclose($fp);
    
    return $response;
}


// Whois verisini parse eden fonksiyon
function parseWhoisData($whoisData) {
    $result = [
        'registrar' => '',
        'creation_date' => '',
        'expiry_date' => '',
        'updated_date' => '',
        'status' => '',
        'name_servers' => []
    ];
    
    // Türkçe domainler için özel kontrol
    $isTurkishDomain = false;
    if (stripos($whoisData, 'nic.tr') !== false || stripos($whoisData, 'Trabis') !== false) {
        $isTurkishDomain = true;
    }
    
    if ($isTurkishDomain) {
        // Türk domainleri için özel parse
        
        // Registrar
        if (preg_match('/Organization Name\s*:\s*(.+)/i', $whoisData, $matches)) {
            $result['registrar'] = trim($matches[1]);
        }
        
        // Creation Date - Farklı formatları dene
        if (preg_match('/Created on[\.\s]*:\s*(.+)/i', $whoisData, $matches) ||
            preg_match('/Oluşturulma Tarihi[\.\s]*:\s*(.+)/i', $whoisData, $matches)) {
            $date = trim($matches[1]);
            $result['creation_date'] = formatWhoisDate($date);
        }
        
        // Expiry Date - Türk domainlerinde farklı formatlar olabilir
        if (preg_match('/Expires on[\.\s]*:\s*(.+)/i', $whoisData, $matches) ||
            preg_match('/Son Kullanma Tarihi[\.\s]*:\s*(.+)/i', $whoisData, $matches) ||
            preg_match('/Bitiş Tarihi[\.\s]*:\s*(.+)/i', $whoisData, $matches) ||
            preg_match('/Expiry Date[\.\s]*:\s*(.+)/i', $whoisData, $matches) ||
            preg_match('/Registry Expiry Date[\.\s]*:\s*(.+)/i', $whoisData, $matches)) {
            $date = trim($matches[1]);
            $result['expiry_date'] = formatWhoisDate($date);
        }
        
        // Eğer hala expiry date bulunamadıysa, Created on'dan 1 yıl ekle (varsayılan)
        if (empty($result['expiry_date']) && !empty($result['creation_date'])) {
            // Debug için - gerçek whois verisinde expiry date olmalı
            error_log("TR Domain Expiry Date bulunamadı: " . substr($whoisData, 0, 500));
        }
        
        // Name Servers
        preg_match_all('/\*\s+(.+\.tr)\.?/i', $whoisData, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $ns) {
                if ($ns = trim($ns)) {
                    $result['name_servers'][] = strtolower($ns);
                }
            }
        }
    } else {
        // Uluslararası domainler için standart parse
        
        // Registrar
        if (preg_match('/Registrar:\s*(.+)/i', $whoisData, $matches)) {
            $result['registrar'] = trim($matches[1]);
        }
        
        // Creation Date
        if (preg_match('/Creation Date:\s*(.+)|Created:\s*(.+)|Created On:\s*(.+)/i', $whoisData, $matches)) {
            $date = trim(array_filter($matches)[1] ?? '');
            $result['creation_date'] = formatWhoisDate($date);
        }
        
        // Expiry Date
        if (preg_match('/Registry Expiry Date:\s*(.+)|Expir\w+ Date:\s*(.+)|Expires:\s*(.+)|Expiration Date:\s*(.+)/i', $whoisData, $matches)) {
            $date = trim(array_filter($matches)[1] ?? '');
            $result['expiry_date'] = formatWhoisDate($date);
        }
        
        // Updated Date
        if (preg_match('/Updated Date:\s*(.+)|Modified:\s*(.+)|Last Updated:\s*(.+)/i', $whoisData, $matches)) {
            $date = trim(array_filter($matches)[1] ?? '');
            $result['updated_date'] = formatWhoisDate($date);
        }
        
        // Status
        if (preg_match('/Status:\s*(.+)/i', $whoisData, $matches)) {
            $result['status'] = trim($matches[1]);
        }
        
        // Name Servers
        preg_match_all('/Name Server:\s*(.+)|NS:\s*(.+)|nserver:\s*(.+)/i', $whoisData, $matches);
        if (!empty($matches[0])) {
            foreach ($matches as $matchGroup) {
                foreach ($matchGroup as $match) {
                    if (preg_match('/(?:Name Server:|NS:|nserver:)\s*(.+)/i', $match, $nsMatch)) {
                        $ns = trim($nsMatch[1]);
                        if ($ns && !in_array(strtolower($ns), $result['name_servers'])) {
                            $result['name_servers'][] = strtolower($ns);
                        }
                    }
                }
            }
        }
    }
    
    return $result;
}

// Whois tarihini formatla
function formatWhoisDate($date) {
    if (empty($date)) return null;
    
    // Tarih temizleme
    $date = trim($date);
    $date = str_replace(['UTC', 'GMT', '+00:00', 'Z'], '', $date);
    $date = trim($date, ". \t\n\r\0\x0B");
    
    // Özel Türk domain tarih formatı: 30-Nov-2025. veya 30-Nov-2025
    if (preg_match('/^(\d{1,2})-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-(\d{4})/i', $date, $matches)) {
        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
        ];
        
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = $months[ucfirst(strtolower($matches[2]))];
        $year = $matches[3];
        
        if ($year > 1990 && $year < 2100) {
            return "$year-$month-$day";
        }
    }
    
    // YYYY-MMM-DD formatı (2024-Oct-18)
    if (preg_match('/(\d{4})-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-(\d{1,2})/i', $date, $matches)) {
        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
        ];
        
        $year = $matches[1];
        $monthName = $matches[2];
        $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
        
        // Ay ismini kontrol et
        foreach ($months as $eng => $num) {
            if (strcasecmp($eng, $monthName) == 0) {
                $month = $num;
                break;
            }
        }
        
        if (isset($month) && $year > 1990 && $year < 2100) {
            return "$year-$month-$day";
        }
    }
    
    // Türkçe ay isimlerini İngilizce'ye çevir
    $turkishMonths = [
        'Ocak' => 'January', 'Şubat' => 'February', 'Mart' => 'March',
        'Nisan' => 'April', 'Mayıs' => 'May', 'Haziran' => 'June',
        'Temmuz' => 'July', 'Ağustos' => 'August', 'Eylül' => 'September',
        'Ekim' => 'October', 'Kasım' => 'November', 'Aralık' => 'December'
    ];
    
    foreach ($turkishMonths as $tr => $en) {
        $date = str_ireplace($tr, $en, $date);
    }
    
    // Farklı tarih formatlarını dene
    $formats = [
        'Y-m-d\TH:i:s',
        'Y-m-d H:i:s',
        'Y-m-d',
        'd-M-Y',
        'd/m/Y',
        'd.m.Y',
        'd-F-Y',
        'd F Y',
        'F d, Y',
        'Y.m.d',
        'd-M-y'
    ];
    
    foreach ($formats as $format) {
        $parsed = DateTime::createFromFormat($format, $date);
        if ($parsed !== false && $parsed->format('Y') > 1990) {
            return $parsed->format('Y-m-d');
        }
    }
    
    // Son çare olarak strtotime dene
    $timestamp = strtotime($date);
    if ($timestamp !== false && $timestamp > 0) {
        $year = date('Y', $timestamp);
        if ($year > 1990 && $year < 2100) {
            return date('Y-m-d', $timestamp);
        }
    }
    
    return null;
}

// Kalan gün hesapla
function calculateDaysRemaining($expiryDate) {
    if (empty($expiryDate)) return null;
    
    $expiry = new DateTime($expiryDate);
    $today = new DateTime();
    $interval = $today->diff($expiry);
    
    return $interval->invert ? -$interval->days : $interval->days;
}

// Durum rengini belirle
function getStatusColor($days) {
    if ($days === null) return 'secondary';
    if ($days < 0) return 'dark';
    if ($days <= 30) return 'danger';
    if ($days <= 60) return 'warning';
    if ($days <= 90) return 'info';
    return 'success';
}

// Durum metnini belirle
function getStatusText($days) {
    if ($days === null) return 'Bilinmiyor';
    if ($days < 0) return 'Süresi Dolmuş';
    if ($days == 0) return 'Bugün Doluyor';
    if ($days == 1) return 'Yarın Doluyor';
    if ($days <= 30) return $days . ' Gün Kaldı';
    if ($days <= 365) return round($days / 30) . ' Ay Kaldı';
    return round($days / 365, 1) . ' Yıl Kaldı';
}

// Admin kontrolü
function checkAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /domain-takip/admin/login.php');
        exit;
    }
}

// Güvenli veri temizleme
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>