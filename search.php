<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$from_city = $_GET['from_city'] ?? '';
$to_city = $_GET['to_city'] ?? '';
$date = $_GET['date'] ?? '';

$page_title = 'Sefer Arama Sonuçları';
require_once 'includes/header.php';

if (empty($from_city) || empty($to_city) || empty($date)) {
    echo '<div class="alert alert-error">Lütfen tüm alanları doldurun!</div>';
    echo '<a href="index.php" class="btn btn-primary">Geri Dön</a>';
    require_once 'includes/footer.php';
    exit;
}

if ($from_city == $to_city) {
    echo '<div class="alert alert-error">Kalkış ve varış noktası aynı olamaz!</div>';
    echo '<a href="index.php" class="btn btn-primary">Geri Dön</a>';
    require_once 'includes/footer.php';
    exit;
}

$stmt = $db->prepare("
    SELECT trips.*, companies.name as company_name 
    FROM trips 
    INNER JOIN companies ON trips.company_id = companies.id 
    WHERE trips.from_city = ? 
    AND trips.to_city = ? 
    AND trips.departure_date = ? 
    AND trips.available_seats > 0
    ORDER BY trips.departure_time
");
$stmt->execute([$from_city, $to_city, $date]);
$trips = $stmt->fetchAll();
?>

<div class="card">
    <h2>🔍 Arama Sonuçları</h2>
    <p style="color: #7f8c8d;">
        <strong><?php echo clean_output($from_city); ?></strong> → 
        <strong><?php echo clean_output($to_city); ?></strong> | 
        <?php echo format_date($date); ?>
    </p>
    <a href="index.php" class="btn btn-primary" style="margin-top: 10px;">Yeni Arama</a>
</div>

<?php if (count($trips) == 0): ?>
    <div class="card">
        <div style="text-align: center; padding: 40px;">
            <div style="font-size: 64px;">😔</div>
            <h3>Sefer Bulunamadı</h3>
            <p style="color: #7f8c8d;">Bu güzergah için sefer bulunmamaktadır.</p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Farklı Arama Yap</a>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($trips as $trip): ?>
        <div class="card" style="border-left: 4px solid #3498db;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1;">
                    <h3 style="color: #2c3e50; margin-bottom: 10px;">
                        🚌 <?php echo clean_output($trip['company_name']); ?>
                    </h3>
                    <div style="display: flex; align-items: center; gap: 20px; margin: 10px 0;">
                        <div>
                            <div style="font-size: 24px; font-weight: bold; color: #2c3e50;">
                                <?php echo format_time($trip['departure_time']); ?>
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">
                                <?php echo clean_output($trip['from_city']); ?>
                            </div>
                        </div>
                        
                        <div style="flex: 1; text-align: center;">
                            <div style="color: #7f8c8d;">━━━━━━━━→</div>
                        </div>
                        
                        <div style="text-align: right;">
                            <div style="font-size: 24px; font-weight: bold; color: #2c3e50;">
                                ~<?php echo format_time(date('H:i', strtotime($trip['departure_time']) + 3600 * 5)); ?>
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">
                                <?php echo clean_output($trip['to_city']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <span style="background: #ecf0f1; padding: 5px 10px; border-radius: 3px; font-size: 14px;">
                            💺 <?php echo $trip['available_seats']; ?>/<?php echo $trip['total_seats']; ?> Boş Koltuk
                        </span>
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <div style="font-size: 32px; font-weight: bold; color: #27ae60; margin-bottom: 10px;">
                        <?php echo format_money($trip['price']); ?>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="trip-details.php?id=<?php echo $trip['id']; ?>" class="btn btn-success">
                            Bilet Al
                        </a>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-primary">
                            Giriş Yapın
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="card">
        <p style="text-align: center; color: #7f8c8d;">
            Toplam <strong><?php echo count($trips); ?></strong> sefer bulundu
        </p>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>