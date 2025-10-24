<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'firm_admin') {
    die(" Bu sayfaya erişim yetkiniz yok! <br><br><a href='../index.php'>Ana Sayfaya Dön</a>");
}

$firm_company_id = 1;

// Firma bilgisi
$stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
$stmt->execute([$firm_company_id]);
$company_name = $stmt->fetchColumn();

// Filtre
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
$filter_date_to = $_GET['date_to'] ?? date('Y-m-t');

// Sefer bazlı raporlar
$stmt = $db->prepare("
    SELECT 
        trips.*,
        COUNT(tickets.id) as total_tickets,
        SUM(CASE WHEN tickets.status = 'active' THEN 1 ELSE 0 END) as active_tickets,
        SUM(CASE WHEN tickets.status = 'active' THEN tickets.price ELSE 0 END) as total_revenue,
        (trips.total_seats - trips.available_seats) as occupied_seats,
        ROUND((trips.total_seats - trips.available_seats) * 100.0 / trips.total_seats, 2) as occupancy_rate
    FROM trips
    LEFT JOIN tickets ON trips.id = tickets.trip_id
    WHERE trips.company_id = ?
    AND trips.departure_date BETWEEN ? AND ?
    GROUP BY trips.id
    ORDER BY trips.departure_date DESC, trips.departure_time DESC
");
$stmt->execute([$firm_company_id, $filter_date_from, $filter_date_to]);
$trip_reports = $stmt->fetchAll();

// Genel istatistikler
$total_trips = count($trip_reports);
$total_revenue = 0;
$total_tickets_sold = 0;
$total_occupancy = 0;

foreach ($trip_reports as $report) {
    $total_revenue += $report['total_revenue'];
    $total_tickets_sold += $report['total_tickets'];
    $total_occupancy += $report['occupancy_rate'];
}

$avg_occupancy = $total_trips > 0 ? $total_occupancy / $total_trips : 0;

$page_title = 'Sefer Raporları';
require_once '../includes/header.php';
?>

<div class="card">
    <h2> Sefer Raporları - <?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') ?></h2>
    <a href="dashboard.php" class="btn btn-primary">← Panele Dön</a>
</div>

<!-- Filtre -->
<div class="card">
    <h3>🔍 Tarih Filtresi</h3>
    <form method="GET" action="" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
        <div class="form-group" style="margin: 0;">
            <label>Başlangıç Tarihi</label>
            <input type="date" name="date_from" value="<?= $filter_date_from ?>" required>
        </div>
        
        <div class="form-group" style="margin: 0;">
            <label>Bitiş Tarihi</label>
            <input type="date" name="date_to" value="<?= $filter_date_to ?>" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Filtrele</button>
        <a href="trip-reports.php" class="btn" style="background: #95a5a6; color: white;">Filtreyi Temizle</a>
    </form>
</div>

<!-- Özet İstatistikler -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="background: linear-gradient(135deg, #e85d04 0%, #dc2f02 100%); color: white; border: none;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Sefer</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?= $total_trips ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%); color: white; border: none;">
        <div style="font-size: 14px; opacity: 0.9;">Satılan Bilet</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            <?= $total_tickets_sold ?>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #ffd60a 0%, #ffc300 100%); color: #1a1a1a; border: none;">
        <div style="font-size: 14px; opacity: 0.9;">Toplam Gelir</div>
        <div style="font-size: 32px; font-weight: bold; margin: 10px 0;">
            <?= number_format($total_revenue, 2, ',', '.') ?> TL
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%); color: white; border: none;">
        <div style="font-size: 14px; opacity: 0.9;">Ortalama Doluluk</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">
            %<?= number_format($avg_occupancy, 1) ?>
        </div>
    </div>
</div>

<!-- Sefer Detayları -->
<div class="card">
    <h3>📋 Sefer Detayları</h3>
    
    <?php if (count($trip_reports) == 0): ?>
        <p style="color: #7f8c8d; text-align: center; padding: 40px;">Seçilen tarih aralığında sefer bulunamadı.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Sefer ID</th>
                <th>Güzergah</th>
                <th>Tarih</th>
                <th>Saat</th>
                <th>Satılan</th>
                <th>Doluluk</th>
                <th>Gelir</th>
            </tr>
            <?php foreach ($trip_reports as $report): ?>
            <?php
            $occupancy_color = $report['occupancy_rate'] >= 80 ? '#27ae60' : ($report['occupancy_rate'] >= 50 ? '#f39c12' : '#e74c3c');
            ?>
            <tr>
                <td><strong>#<?= $report['id'] ?></strong></td>
                <td><?= htmlspecialchars($report['from_city'] . ' → ' . $report['to_city'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= date('d.m.Y', strtotime($report['departure_date'])) ?></td>
                <td><?= date('H:i', strtotime($report['departure_time'])) ?></td>
                <td><?= $report['active_tickets'] ?></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; background: #e0e0e0; height: 8px; border-radius: 4px; min-width: 60px;">
                            <div style="width: <?= $report['occupancy_rate'] ?>%; height: 100%; background: <?= $occupancy_color ?>; border-radius: 4px;"></div>
                        </div>
                        <span style="font-weight: bold; color: <?= $occupancy_color ?>;">
                            %<?= number_format($report['occupancy_rate'], 1) ?>
                        </span>
                    </div>
                    <small style="color: #7f8c8d;">
                        <?= $report['occupied_seats'] ?>/<?= $report['total_seats'] ?> koltuk
                    </small>
                </td>
                <td style="font-weight: bold; color: #27ae60;">
                    <?= number_format($report['total_revenue'], 2, ',', '.') ?> TL
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>