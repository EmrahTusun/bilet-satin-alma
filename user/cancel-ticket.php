<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$ticket_id = $_GET['id'] ?? 0;

if ($ticket_id <= 0) {
    die("Geçersiz bilet!");
}

$db->beginTransaction();

try {
    $stmt = $db->prepare("
        SELECT tickets.*, trips.departure_date, trips.departure_time 
        FROM tickets 
        INNER JOIN trips ON tickets.trip_id = trips.id
        WHERE tickets.id = ? AND tickets.user_id = ? AND tickets.status = 'active'
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        throw new Exception("Bilet bulunamadı veya zaten iptal edilmiş!");
    }
    
    // iptal etme saatini kontrol etme 
    if (!can_cancel_ticket($ticket['departure_date'], $ticket['departure_time'])) {
        throw new Exception("Bu bilet artık iptal edilemez! Kalkışa 1 saatten az kaldı.");
    }
    
    // iptal etme
    $stmt = $db->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$ticket_id]);
    
    // para iadesi
    $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$ticket['price'], $_SESSION['user_id']]);
    
    // boş koltuk yapma
    $stmt = $db->prepare("UPDATE trips SET available_seats = available_seats + 1 WHERE id = ?");
    $stmt->execute([$ticket['trip_id']]);
    
    $db->commit();
    
    header("Location: tickets.php?cancelled=1");
    exit;
    
} catch (Exception $e) {
    $db->rollBack();
    die(" Hata: " . $e->getMessage() . "<br><br><a href='tickets.php'>Biletlerime Dön</a>");
}
?>