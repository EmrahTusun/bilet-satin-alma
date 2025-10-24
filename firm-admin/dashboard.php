<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'firm_admin') {
    die(" Bu sayfaya erişim yetkiniz yok! <br><br><a href='../index.php'>Ana Sayfaya Dön</a>");
}

$firm_company_id = 1; 

// Firma bilgisi
$stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$firm_company_id]);
$company = $stmt->fetch();

// İstatistikler
$stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE company_id = ?");
$stmt->execute([$firm_company_id]);
$total_trips = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) FROM tickets 
    INNER JOIN trips ON tickets.trip_id = trips.id 
    WHERE trips.company_id = ? AND tickets.status = 'active'
");
$stmt->execute([$firm_company_id]);
$total_tickets = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT SUM(tickets.price) FROM tickets 
    INNER JOIN trips ON tickets.trip_id = trips.id 
    WHERE trips.company_id = ? AND tickets.status != 'cancelled'
");
$stmt->execute([$firm_company_id]);
$total_revenue = $stmt->fetchColumn() ?? 0;

$page_title = 'Firma Admin Paneli';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>🏢 <?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?> - Yönetim Paneli</h2>
    <p style="color: #7f8c8d;">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!</p>
</div>

<!-- İstatistikler -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="background: linear-gradient(135deg, #4386e9ff 0%, #38f9d7 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Sefer</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?php echo $total_trips; ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #4386e9ff 0%, #38f9d7 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Satılan Bilet</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?php echo $total_tickets; ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #4386e9ff 0%, #38f9d7 100%); color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Gelir</div>
        <div style="font-size: 32px; font-weight: bold; margin: 10px 0;">
            <?php echo number_format($total_revenue, 2, ',', '.') . ' TL'; ?>
        </div>
    </div>
</div>

<!-- Menü -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
    <a href="manage-trips.php" class="card" style="text-decoration: none; color: inherit;">
        <h3> Sefer Yönetimi</h3>
        <p style="color: #7f8c8d;">Seferleri ekle, düzenle veya sil</p>
    </a>
     <a href="../admin/manage-coupons.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.3s;">
        <h3>🎫 Kupon Yönetimi</h3>
        <p style="color: #7f8c8d;">İndirim kuponları oluştur ve yönet</p>
    </a>

    <a href="trip-reports.php" class="card" style="text-decoration: none; color: inherit;">
        <h3> Sefer Raporları</h3>
        <p style="color: #7f8c8d;">Sefer bazlı satış raporları</p>
    </a>
</div>

<!-- Son Seferler -->
<div class="card" style="margin-top: 30px;">
    <h2> Son Eklenen Seferler</h2>
    
    <?php
    $stmt = $db->prepare("
        SELECT * FROM trips 
        WHERE company_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$firm_company_id]);
    $recent_trips = $stmt->fetchAll();
    ?>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Güzergah</th>
            <th>Tarih</th>
            <th>Saat</th>
            <th>Fiyat</th>
            <th>Boş Koltuk</th>
            <th>İşlemler</th>
        </tr>
        <?php foreach ($recent_trips as $trip): ?>
        <tr>
            <td>#<?php echo $trip['id']; ?></td>
            <td><?php echo htmlspecialchars($trip['from_city'] . ' → ' . $trip['to_city'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo date('d.m.Y', strtotime($trip['departure_date'])); ?></td>
            <td><?php echo date('H:i', strtotime($trip['departure_time'])); ?></td>
            <td><?php echo number_format($trip['price'], 2, ',', '.') . ' TL'; ?></td>
            <td><?php echo $trip['available_seats']; ?> / <?php echo $trip['total_seats']; ?></td>
            <td>
                <a href="manage-trips.php?edit=<?php echo $trip['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Düzenle</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>