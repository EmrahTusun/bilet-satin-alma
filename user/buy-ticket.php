<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../index.php");
    exit;
}

$trip_id = (int)$_POST['trip_id'];
$seat_number = (int)$_POST['seat_number'];
$price = (float)$_POST['price'];
$discount = (int)($_POST['discount'] ?? 0);
$coupon_code = trim($_POST['coupon_code'] ?? '');

if ($trip_id <= 0 || $seat_number <= 0) {
    die("Geçersiz istek!");
}

$db->beginTransaction();

try {
    $stmt = $db->prepare("SELECT * FROM trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch();
    
    if (!$trip) {
        throw new Exception("Sefer bulunamadı!");
    }
    
    if ($trip['available_seats'] <= 0) {
        throw new Exception("Bu sefer için boş koltuk kalmamıştır!");
    }
    
    $stmt = $db->prepare("SELECT id FROM tickets WHERE trip_id = ? AND seat_number = ? AND status = 'active'");
    $stmt->execute([$trip_id, $seat_number]);
    
    if ($stmt->fetch()) {
        throw new Exception("Bu koltuk dolu!");
    }
    
    $final_price = $price;
    if ($discount > 0 && !empty($coupon_code)) {
        $final_price = $price - ($price * $discount / 100);
        
        $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
        $stmt->execute([$coupon_code]);
    }
    
    $user = get_user_info($db, $_SESSION['user_id']);
    if ($user['balance'] < $final_price) {
        throw new Exception("Yetersiz bakiye! Bakiyeniz: " . format_money($user['balance']));
    }
    
    $stmt = $db->prepare("
        INSERT INTO tickets (user_id, trip_id, seat_number, price, status) 
        VALUES (?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$_SESSION['user_id'], $trip_id, $seat_number, $final_price]);
    
    $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$final_price, $_SESSION['user_id']]);
    
    $stmt = $db->prepare("UPDATE trips SET available_seats = available_seats - 1 WHERE id = ?");
    $stmt->execute([$trip_id]);
    
    $db->commit();
    
    header("Location: tickets.php?success=1");
    exit;
    
} catch (Exception $e) {
    $db->rollBack();
    die("Hata: " . $e->getMessage() . "<br><a href='../index.php'>Ana Sayfaya Dön</a>");
}
?>