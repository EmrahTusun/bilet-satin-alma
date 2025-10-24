<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız!']);
    exit;
}

$code = trim($_POST['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Kupon kodu boş olamaz!']);
    exit;
}

// Kupon kontrolüü
$stmt = $db->prepare("
    SELECT * FROM coupons 
    WHERE code = ? 
    AND is_active = 1 
    AND (expiry_date IS NULL OR expiry_date >= date('now'))
    AND used_count < usage_limit
");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if ($coupon) {
    echo json_encode([
        'success' => true,
        'discount_percent' => $coupon['discount_percent']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz veya süresi dolmuş kupon!'
    ]);
}
?>