<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'firm_admin') {
    die(" Bu sayfaya erişim yetkiniz yok! <br><br><a href='../index.php'>Ana Sayfaya Dön</a>");
}

// Şehirler
$cities = ['İstanbul', 'Ankara', 'İzmir', 'Antalya', 'Batman', 'Adana', 'Gaziantep', 'Konya', 'Eskişehir', 'Van'];

$firm_company_id = 1;

$success = '';
$error = '';
$edit_trip = null;

// Düzenleme 
if (isset($_GET['edit'])) {
    $trip_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $firm_company_id]);
    $edit_trip = $stmt->fetch();
    
    if (!$edit_trip) {
        $error = "Bu seferi düzenleme yetkiniz yok!";
    }
}

// Sefer ekleme-güncelleme alanı
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_trip'])) {
    $from_city = trim($_POST['from_city']);
    $to_city = trim($_POST['to_city']);
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'];
    $price = (float)$_POST['price'];
    $total_seats = (int)$_POST['total_seats'];
    $trip_id = (int)($_POST['trip_id'] ?? 0);
    
    if (empty($from_city) || empty($to_city) || empty($departure_date) || empty($departure_time)) {
        $error = "Tüm alanları doldurun!";
    } elseif ($from_city == $to_city) {
        $error = "Kalkış ve varış noktası aynı olamaz!";
    } elseif ($price <= 0) {
        $error = "Fiyat 0'dan büyük olmalı!";
    } elseif ($total_seats < 1 || $total_seats > 20) {
        $error = "Koltuk sayısı 1-20 arasında olmalı!";
    } else {
        if ($trip_id > 0) {
            // Güncelleme
            $stmt = $db->prepare("
                UPDATE trips 
                SET from_city = ?, to_city = ?, departure_date = ?, departure_time = ?, price = ?, total_seats = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$from_city, $to_city, $departure_date, $departure_time, $price, $total_seats, $trip_id, $firm_company_id]);
            $success = "Sefer güncellendi!";
            $edit_trip = null;
        } else {
            // Yeni ekleme
            $stmt = $db->prepare("
                INSERT INTO trips (company_id, from_city, to_city, departure_date, departure_time, price, total_seats, available_seats) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$firm_company_id, $from_city, $to_city, $departure_date, $departure_time, $price, $total_seats, $total_seats]);
            $success = "Sefer eklendi!";
        }
    }
}

// Sefer silme
if (isset($_GET['delete'])) {
    $trip_id = (int)$_GET['delete'];
    
    // Bu sefere ait bilet var mı?
    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE trip_id = ? AND status = 'active'");
    $stmt->execute([$trip_id]);
    $ticket_count = $stmt->fetchColumn();
    
    if ($ticket_count > 0) {
        $error = "Bu sefere ait $ticket_count aktif bilet var! Önce biletler iptal edilmeli.";
    } else {
        $stmt = $db->prepare("DELETE FROM trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$trip_id, $firm_company_id]);
        $success = "Sefer silindi!";
    }
}

// Seferleri gösterme yeri
$stmt = $db->prepare("
    SELECT 
        trips.*,
        COUNT(tickets.id) as sold_tickets
    FROM trips
    LEFT JOIN tickets ON trips.id = tickets.trip_id AND tickets.status = 'active'
    WHERE trips.company_id = ?
    GROUP BY trips.id
    ORDER BY trips.departure_date DESC, trips.departure_time DESC
");
$stmt->execute([$firm_company_id]);
$trips = $stmt->fetchAll();

$page_title = 'Sefer Yönetimi';
require_once '../includes/header.php';
?>

<div class="card">
    <h2> Sefer Yönetimi</h2>
    <a href="dashboard.php" class="btn btn-primary">← Panele Dön</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<!-- Sefer Ekleme/Düzenleme Formu -->
<div class="card">
    <h3><?php echo $edit_trip ? '✏️ Sefer Düzenle' : '➕ Yeni Sefer Ekle'; ?></h3>
    
    <form method="POST" action="" style="max-width: 800px;">
        <?php if ($edit_trip): ?>
            <input type="hidden" name="trip_id" value="<?php echo $edit_trip['id']; ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label>Kalkış Noktası *</label>
                <select name="from_city" required>
                    <option value="">Seçin</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city; ?>" <?php echo ($edit_trip && $edit_trip['from_city'] == $city) ? 'selected' : ''; ?>>
                            <?php echo $city; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Varış Noktası *</label>
                <select name="to_city" required>
                    <option value="">Seçin</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city; ?>" <?php echo ($edit_trip && $edit_trip['to_city'] == $city) ? 'selected' : ''; ?>>
                            <?php echo $city; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tarih *</label>
                <input type="date" name="departure_date" required min="<?php echo date('Y-m-d'); ?>" 
                       value="<?php echo $edit_trip ? $edit_trip['departure_date'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Saat *</label>
                <input type="time" name="departure_time" required 
                       value="<?php echo $edit_trip ? $edit_trip['departure_time'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Fiyat (TL) *</label>
                <input type="number" name="price" step="0.01" min="1" required placeholder="250.00" 
                       value="<?php echo $edit_trip ? $edit_trip['price'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Toplam Koltuk *</label>
                <input type="number" name="total_seats" min="1" max="20" required value="<?php echo $edit_trip ? $edit_trip['total_seats'] : '20'; ?>">
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" name="save_trip" class="btn btn-success">
                <?php echo $edit_trip ? '💾 Güncelle' : '➕ Sefer Ekle'; ?>
            </button>
            <?php if ($edit_trip): ?>
                <a href="manage-trips.php" class="btn" style="background: #95a5a6; color: white;">İptal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Sefer Listesi -->
<div class="card">
    <h3>📋 Mevcut Seferler</h3>
    
    <?php if (count($trips) == 0): ?>
        <p style="color: #7f8c8d;">Henüz sefer eklenmemiş.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Güzergah</th>
                <th>Tarih</th>
                <th>Saat</th>
                <th>Fiyat</th>
                <th>Satılan Bilet</th>
                <th>Boş Koltuk</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($trips as $trip): ?>
            <?php
            $is_past = strtotime($trip['departure_date'] . ' ' . $trip['departure_time']) < time();
            ?>
            <tr style="<?php echo $is_past ? 'opacity: 0.5;' : ''; ?>">
                <td>#<?php echo $trip['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($trip['from_city'] . ' → ' . $trip['to_city'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td><?php echo date('d.m.Y', strtotime($trip['departure_date'])); ?></td>
                <td><?php echo date('H:i', strtotime($trip['departure_time'])); ?></td>
                <td><?php echo number_format($trip['price'], 2, ',', '.'); ?> TL</td>
                <td><?php echo $trip['sold_tickets']; ?></td>
                <td><?php echo $trip['available_seats']; ?> / <?php echo $trip['total_seats']; ?></td>
                <td>
                    <?php if (!$is_past): ?>
                        <a href="?edit=<?php echo $trip['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Düzenle</a>
                        <a href="?delete=<?php echo $trip['id']; ?>" 
                           class="btn btn-danger" 
                           style="padding: 5px 10px; font-size: 12px;"
                           onclick="return confirm('Bu seferi silmek istediğinize emin misiniz?')">
                            Sil
                        </a>
                    <?php else: ?>
                        <span style="color: #95a5a6; font-size: 12px;">Geçmiş Sefer</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>