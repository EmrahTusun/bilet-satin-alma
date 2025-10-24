<?php
// Giriş kontrolü
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . get_base_url() . "auth/login.php");
        exit;
    }
}

// Rol kontrolü
function require_role($role) {
    require_login();
    if ($_SESSION['role'] != $role) {
        die(" Bu sayfaya erişim yetkiniz yok! <br><br><a href='" . get_base_url() . "index.php'>Ana Sayfaya Dön</a>");
    }
}

// Admin kontrolü
function require_admin() {
    require_role('admin');
}

// Firma admin kontrolü
function require_firm_admin() {
    require_role('firm_admin');
}
//dosya bulma
function get_base_url() {
    $script_name = $_SERVER['SCRIPT_NAME'];
    
    if (strpos($script_name, '/auth/') !== false || 
        strpos($script_name, '/user/') !== false || 
        strpos($script_name, '/admin/') !== false || 
        strpos($script_name, '/firm-admin/') !== false) {
        return '../';
    }
    
    return '';
}

// Kullanıcı bilgisini çekme
function get_user_info($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Şehir listesi
function get_cities() {
    return [
        'İstanbul', 
        'Ankara', 
        'İzmir', 
        'Antalya', 
        'Batman', 
        'Adana', 
        'Gaziantep', 
        'Konya',
        'Eskişehir',
        'Van'
    ];
}

// Tarih
function format_date($date) {
    if (empty($date)) return '-';
    return date('d.m.Y', strtotime($date));
}

// Saat
function format_time($time) {
    if (empty($time)) return '-';
    return date('H:i', strtotime($time));
}

// Para
function format_money($amount) {
    return number_format($amount, 2, ',', '.') . ' TL';
}

// xss
function clean_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Alert mesajı
function show_alert($message, $type = 'info') {
    $colors = [
        'success' => '#d4edda',
        'error' => '#f8d7da',
        'warning' => '#fff3cd',
        'info' => '#d1ecf1'
    ];
    
    $text_colors = [
        'success' => '#155724',
        'error' => '#721c24',
        'warning' => '#856404',
        'info' => '#0c5460'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    $text_color = $text_colors[$type] ?? $text_colors['info'];
    
    echo "<div style='background: {$color}; color: {$text_color}; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid {$text_color};'>";
    echo clean_output($message);
    echo "</div>";
}

// Sefer bilgisi
function get_trip_info($db, $trip_id) {
    $stmt = $db->prepare("
        SELECT trips.*, companies.name as company_name 
        FROM trips 
        INNER JOIN companies ON trips.company_id = companies.id 
        WHERE trips.id = ?
    ");
    $stmt->execute([$trip_id]);
    return $stmt->fetch();
}

// Kullanıcının biletleri
function get_user_tickets($db, $user_id) {
    $stmt = $db->prepare("
        SELECT 
            tickets.*,
            trips.from_city,
            trips.to_city,
            trips.departure_date,
            trips.departure_time,
            companies.name as company_name
        FROM tickets
        INNER JOIN trips ON tickets.trip_id = trips.id
        INNER JOIN companies ON trips.company_id = companies.id
        WHERE tickets.user_id = ?
        ORDER BY trips.departure_date DESC, trips.departure_time DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Bilet iptal edilebilir mi kontrol etme fonksiyonu
function can_cancel_ticket($departure_date, $departure_time) {
    $departure_datetime = strtotime($departure_date . ' ' . $departure_time);
    $now = time();
    $time_diff = $departure_datetime - $now;
    
    // 1 saatten fazla varsa iptal edilebilir
    return $time_diff > 3600;
}

// Tarih ve saat farkı
function get_time_until_departure($departure_date, $departure_time) {
    $departure_datetime = strtotime($departure_date . ' ' . $departure_time);
    $now = time();
    $diff = $departure_datetime - $now;
    
    if ($diff <= 0) {
        return "Sefer geçti";
    }
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($hours > 24) {
        $days = floor($hours / 24);
        return $days . " gün";
    } elseif ($hours > 0) {
        return $hours . " saat " . $minutes . " dakika";
    } else {
        return $minutes . " dakika";
    }
}
?>