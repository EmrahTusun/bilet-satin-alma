<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die(" Bu sayfaya erişim yetkiniz yok! <br><br><a href='../index.php'>Ana Sayfaya Dön</a>");
}

// İstatistikler
$total_users = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_companies = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$total_trips = $db->query("SELECT COUNT(*) FROM trips")->fetchColumn();
$total_tickets = $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'active'")->fetchColumn();
$total_revenue = $db->query("SELECT SUM(price) FROM tickets WHERE status != 'cancelled'")->fetchColumn() ?? 0;

$page_title = 'Admin Panel';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>⚙️ Admin Paneli</h2>
    <p style="color: #7f8c8d;">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!</p>
</div>

<!-- İstatistikler -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="background: linear-gradient(135deg, #f64711ff 0%, #e6961eff 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Kullanıcı</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?php echo $total_users; ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #f64711ff 0%, #e6961eff 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Firma</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?php echo $total_companies; ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #f64711ff 0%, #e6961eff 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Sefer</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?php echo $total_trips; ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #f64711ff 0%, #e6961eff 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Aktif Bilet</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?php echo $total_tickets; ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #11e718ff 0%, #30a90bff 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Gelir</div>
        <div style="font-size: 32px; font-weight: bold; margin: 10px 0;">
            <?php echo number_format($total_revenue, 2, ',', '.') . ' TL'; ?>
        </div>
    </div>
</div>

<!-- Yönetim Menüsü -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
    <a href="manage-companies.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s;">
        <h3>🏢 Firma Yönetimi</h3>
        <p style="color: #7f8c8d;">Otobüs firmalarını ekle, düzenle veya sil</p>
    </a>

    <a href="manage-users.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s;">
        <h3>👥 Kullanıcı Yönetimi</h3>
        <p style="color: #7f8c8d;">Kullanıcıları görüntüle ve yönet</p>
    </a>

    <a href="manage-coupons.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s;">
        <h3>🎫 Kupon Yönetimi</h3>
        <p style="color: #7f8c8d;">İndirim kuponları oluştur ve yönet</p>
    </a>

    <a href="manage-firm-admins.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s;">
        <h3>🔐 Firma Admin Yönetimi</h3>
        <p style="color: #7f8c8d;">Firma yöneticilerini oluştur ve ata</p>
    </a>
</div>

<!-- Son Biletler -->
<div class="card" style="margin-top: 30px;">
    <h2>🎫 Son Satılan Biletler</h2>
    
    <?php
    $recent_tickets = $db->query("
        SELECT 
            tickets.*,
            users.username,
            trips.from_city,
            trips.to_city,
            companies.name as company_name
        FROM tickets
        INNER JOIN users ON tickets.user_id = users.id
        INNER JOIN trips ON tickets.trip_id = trips.id
        INNER JOIN companies ON trips.company_id = companies.id
        ORDER BY tickets.purchased_at DESC
        LIMIT 10
    ")->fetchAll();
    ?>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Kullanıcı</th>
            <th>Firma</th>
            <th>Güzergah</th>
            <th>Koltuk</th>
            <th>Fiyat</th>
            <th>Durum</th>
            <th>Tarih</th>
        </tr>
        <?php foreach ($recent_tickets as $ticket): ?>
        <tr>
            <td>#<?php echo $ticket['id']; ?></td>
            <td><?php echo htmlspecialchars($ticket['username'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($ticket['company_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($ticket['from_city'] . ' → ' . $ticket['to_city'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $ticket['seat_number']; ?></td>
            <td><?php echo number_format($ticket['price'], 2, ',', '.') . ' TL'; ?></td>
            <td>
                <?php
                $status_colors = ['active' => '#27ae60', 'cancelled' => '#e74c3c'];
                $status_text = ['active' => 'Aktif', 'cancelled' => 'İptal'];
                ?>
                <span style="background: <?php echo $status_colors[$ticket['status']]; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                    <?php echo $status_text[$ticket['status']]; ?>
                </span>
            </td>
            <td><?php echo date('d.m.Y H:i', strtotime($ticket['purchased_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>