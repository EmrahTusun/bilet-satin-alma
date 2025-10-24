<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$success = $_GET['success'] ?? 0;
$cancelled = $_GET['cancelled'] ?? 0;

$tickets = get_user_tickets($db, $_SESSION['user_id']);

// Kullanıcı bilgisi
$user = get_user_info($db, $_SESSION['user_id']);

$page_title = 'Biletlerim';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>🎫 Biletlerim</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Bilet başarıyla satın alındı! Bakiyeniz: <?php echo format_money($user['balance']); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($cancelled): ?>
        <div class="alert alert-success">
            ✅ Bilet başarıyla iptal edildi! Para iade edildi. Bakiyeniz: <?php echo format_money($user['balance']); ?>
        </div>
    <?php endif; ?>
    
    <p style="color: #7f8c8d;">
        Toplam <?php echo count($tickets); ?> biletiniz var.
    </p>
</div>

<?php if (count($tickets) == 0): ?>
    <div class="card">
        <div style="text-align: center; padding: 40px;">
            <div style="font-size: 64px;">📭</div>
            <h3>Henüz Biletiniz Yok</h3>
            <p style="color: #7f8c8d;">Hemen bir sefer arayın ve bilet satın alın!</p>
            <a href="../index.php" class="btn btn-primary" style="margin-top: 20px;">Sefer Ara</a>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($tickets as $ticket): ?>
        <?php
        $is_past = strtotime($ticket['departure_date'] . ' ' . $ticket['departure_time']) < time();
        $can_cancel = can_cancel_ticket($ticket['departure_date'], $ticket['departure_time']) && $ticket['status'] == 'active';
        $time_left = get_time_until_departure($ticket['departure_date'], $ticket['departure_time']);

        $status_colors = [
            'active' => '#27ae60',
            'cancelled' => '#e74c3c',
            'used' => '#95a5a6'
        ];
        $status_text = [
            'active' => 'Aktif',
            'cancelled' => 'İptal Edildi',
            'used' => 'Kullanıldı'
        ];
        ?>
        
        <div class="card" style="border-left: 4px solid <?php echo $status_colors[$ticket['status']]; ?>;">
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1;">
                    <div style="display: inline-block; background: <?php echo $status_colors[$ticket['status']]; ?>; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 15px;">
                        <?php echo $status_text[$ticket['status']]; ?>
                    </div>

                    <h3 style="color: #2c3e50; margin-bottom: 10px;">
                         <?php echo clean_output($ticket['company_name']); ?>
                    </h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 15px 0;">
                        <div>
                            <div style="color: #7f8c8d; font-size: 12px;">Güzergah</div>
                            <div style="font-weight: bold;">
                                <?php echo clean_output($ticket['from_city']); ?> → <?php echo clean_output($ticket['to_city']); ?>
                            </div>
                        </div>

                        <div>
                            <div style="color: #7f8c8d; font-size: 12px;">Tarih</div>
                            <div style="font-weight: bold;">
                                <?php echo format_date($ticket['departure_date']); ?>
                            </div>
                        </div>

                        <div>
                            <div style="color: #7f8c8d; font-size: 12px;">Saat</div>
                            <div style="font-weight: bold;">
                                <?php echo format_time($ticket['departure_time']); ?>
                            </div>
                        </div>

                        <div>
                            <div style="color: #7f8c8d; font-size: 12px;">Koltuk No</div>
                            <div style="font-weight: bold; font-size: 24px; color: #3498db;">
                                <?php echo $ticket['seat_number']; ?>
                            </div>
                        </div>

                        <div>
                            <div style="color: #7f8c8d; font-size: 12px;">Fiyat</div>
                            <div style="font-weight: bold; color: #27ae60;">
                                <?php echo format_money($ticket['price']); ?>
                            </div>
                        </div>

                        <?php if ($ticket['status'] == 'active' && !$is_past): ?>
                            <div>
                                <div style="color: #7f8c8d; font-size: 12px;">Kalkışa Kalan</div>
                                <div style="font-weight: bold; color: #e67e22;">
                                    <?php echo $time_left; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="color: #7f8c8d; font-size: 12px; margin-top: 10px;">
                        Satın Alma: <?php echo date('d.m.Y H:i', strtotime($ticket['purchased_at'])); ?>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if ($ticket['status'] == 'active'): ?>
                        <a href="download-ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-primary" target="_blank">
                             PDF İndir
                        </a>

                        <?php if ($can_cancel): ?>
                            <a href="cancel-ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-danger"
                               onclick="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?')">
                                 İptal Et
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
