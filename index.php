<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Ana Sayfa - Otobüs Bileti';
require_once 'includes/header.php';
?>

<div class="search-box">
    <h1>Otobüs Bileti Ara</h1>
    
    <form method="GET" action="search.php" class="search-form">
        <div class="form-group">
            <label>Nereden</label>
            <select name="from_city" required>
                <option value="">Şehir Seçin</option>
                <?php foreach (get_cities() as $city): ?>
                    <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Nereye</label>
            <select name="to_city" required>
                <option value="">Şehir Seçin</option>
                <?php foreach (get_cities() as $city): ?>
                    <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Tarih</label>
            <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
        </div>
        
        <div class="form-group" style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn btn-success" style="width: 100%;">Sefer Ara</button>
        </div>
    </form>
</div>

<div class="card" style="margin-top: 30px;">
    <h2> Popüler ve Yaklaşan Seferler</h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-top: 15px;">

        <?php
        $trips = $db->query("
            SELECT from_city, to_city, departure_date, departure_time, price
            FROM trips
            WHERE departure_date >= date('now')
            ORDER BY departure_date ASC, departure_time ASC
            LIMIT 4
        ")->fetchAll();

        foreach ($trips as $trip):
        ?>
        <a href="search.php?from_city=<?php echo urlencode($trip['from_city']); ?>&to_city=<?php echo urlencode($trip['to_city']); ?>&date=<?php echo $trip['departure_date']; ?>" 
           style="background: #f5f5f5; padding: 15px; border-radius: 6px; text-decoration: none; color: #333; text-align: center; transition: all 0.2s; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <div style="font-weight: bold; font-size: 16px;"><?php echo $trip['from_city']; ?> → <?php echo $trip['to_city']; ?></div>
            <div style="margin-top: 5px; font-size: 14px; color: #555;">
                <?php echo date('d M Y', strtotime($trip['departure_date'])); ?> | <?php echo $trip['departure_time']; ?>
            </div>
            <div style="margin-top: 5px; font-size: 14px; color: #27ae60; font-weight: bold;">₺<?php echo $trip['price']; ?></div>
        </a>
        <?php endforeach; ?>

    </div>
</div>
<div class="card">
    <h2>✨ Hemen Keşfet</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 15px;">
        <div style="text-align: center;">
            <div style="font-size: 40px;">🗺️</div>
            <p>Güzergahları incele, sana uygun seferi seç</p>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 40px;">⏰</div>
            <p>Hızlıca tarih ve saati ayarla</p>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 40px;">💺</div>
            <p>Koltuklarını seç, rahatını planla</p>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 40px;">📥</div>
            <p>Biletini indir ve yola hazır ol</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>