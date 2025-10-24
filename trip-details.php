<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Giriş kontrolü
require_login();

$trip_id = $_GET['id'] ?? 0;

// Seferi getir
$stmt = $db->prepare("
    SELECT trips.*, companies.name as company_name 
    FROM trips 
    INNER JOIN companies ON trips.company_id = companies.id 
    WHERE trips.id = ?
");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    die("Sefer bulunamadı!");
}

// Dolu koltuklar
$stmt = $db->prepare("SELECT seat_number FROM tickets WHERE trip_id = ? AND status = 'active'");
$stmt->execute([$trip_id]);
$occupied_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Kullanıcı
$user = get_user_info($db, $_SESSION['user_id']);

$page_title = 'Bilet Satın Al';
require_once 'includes/header.php';
?>

<div class="card">
    <h2> Sefer Detayları</h2>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div>
                <strong>Firma:</strong><br>
                <?php echo clean_output($trip['company_name']); ?>
            </div>
            <div>
                <strong>Güzergah:</strong><br>
                <?php echo clean_output($trip['from_city']); ?> → <?php echo clean_output($trip['to_city']); ?>
            </div>
            <div>
                <strong>Tarih:</strong><br>
                <?php echo format_date($trip['departure_date']); ?>
            </div>
            <div>
                <strong>Saat:</strong><br>
                <?php echo format_time($trip['departure_time']); ?>
            </div>
            <div>
                <strong>Fiyat:</strong><br>
                <span style="font-size: 20px; color: #27ae60; font-weight: bold;">
                    <?php echo format_money($trip['price']); ?>
                </span>
            </div>
        </div>
    </div>
    
    <?php if ($trip['available_seats'] == 0): ?>
        <div class="alert alert-error">
             Bu sefer için boş koltuk kalmamıştır!
        </div>
        <a href="index.php" class="btn btn-primary">Başka Sefer Ara</a>
    <?php else: ?>
        <h3 style="margin-top: 30px;">💺 Koltuk Seçimi</h3>
        <p style="color: #7f8c8d; margin-bottom: 20px;">
            Lütfen bir koltuk seçin. Dolu koltuklar seçilemez.
        </p>
        
        <form method="POST" action="user/buy-ticket.php" id="seatForm">
            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
            <input type="hidden" name="seat_number" id="selectedSeat" value="">
            <input type="hidden" name="price" value="<?php echo $trip['price']; ?>">
            
            <!-- Koltuk Haritası -->
            <div style="background: #f8f9fa; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <span style="display: inline-block; padding: 5px 15px; background: #fff; border: 2px solid #ddd; border-radius: 5px; margin: 0 10px;">
                        ⚪ Boş
                    </span>
                    <span style="display: inline-block; padding: 5px 15px; background: #3498db; color: white; border-radius: 5px; margin: 0 10px;">
                        🔵 Seçili
                    </span>
                    <span style="display: inline-block; padding: 5px 15px; background: #e74c3c; color: white; border-radius: 5px; margin: 0 10px;">
                        🔴 Dolu
                    </span>
                </div>
                
                <!-- Şoför -->
                <div style="text-align: left; margin-bottom: 20px; padding-left: 40px;">
                    <div style="width: 50px; height: 50px; background: #34495e; border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                        🚗
                    </div>
                </div>
                
                <!-- Koltuklar (20 koltuk, 2-2 dizilim) -->
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
                    <?php for ($i = 1; $i <= $trip['total_seats']; $i++): ?>
                        <?php 
                        $is_occupied = in_array($i, $occupied_seats);
                        $disabled = $is_occupied ? 'disabled' : '';
                        $bg_color = $is_occupied ? '#e74c3c' : '#fff';
                        $cursor = $is_occupied ? 'not-allowed' : 'pointer';
                        ?>
                        
                        <button type="button" 
                                class="seat-btn" 
                                data-seat="<?php echo $i; ?>"
                                <?php echo $disabled; ?>
                                style="padding: 15px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo $bg_color; ?>; cursor: <?php echo $cursor; ?>; font-weight: bold; transition: all 0.3s;">
                            <?php echo $i; ?>
                        </button>
                        
                        <?php if ($i % 4 == 0): ?>
                            <div style="width: 20px;"></div> 
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Kupon Kodu -->
            <div style="max-width: 600px; margin: 30px auto;">
                <h3>🎫 İndirim Kuponu (Opsiyonel)</h3>
                <div style="display: flex; gap: 10px;">
                    <input type="text" 
                           name="coupon_code" 
                           id="couponCode"
                           placeholder="Kupon kodunu girin"
                           style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    <button type="button" 
                            id="applyCoupon" 
                            class="btn btn-primary">
                        Uygula
                    </button>
                </div>
                <div id="couponMessage" style="margin-top: 10px;"></div>
                <input type="hidden" name="discount" id="discountAmount" value="0">
            </div>
            
            <!-- Özet -->
            <div style="max-width: 600px; margin: 30px auto; background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h3> Ödeme Özeti</h3>
                <div style="display: flex; justify-content: space-between; margin: 10px 0;">
                    <span>Bilet Fiyatı:</span>
                    <strong id="originalPrice"><?php echo format_money($trip['price']); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin: 10px 0; color: #27ae60;" id="discountRow" hidden>
                    <span>İndirim:</span>
                    <strong id="discountText">0 TL</strong>
                </div>
                <hr>
                <div style="display: flex; justify-content: space-between; margin: 10px 0; font-size: 20px;">
                    <span>Toplam:</span>
                    <strong style="color: #27ae60;" id="finalPrice"><?php echo format_money($trip['price']); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin: 10px 0;">
                    <span>Bakiyeniz:</span>
                    <strong><?php echo format_money($user['balance']); ?></strong>
                </div>
            </div>
            
            <div style="max-width: 600px; margin: 0 auto; text-align: center;">
                <button type="submit" class="btn btn-success" style="width: 100%; font-size: 18px;" id="buyBtn" disabled>
                    Bileti Satın Al
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// --- KOLTUK SEÇME ---
const seatButtons = document.querySelectorAll('.seat-btn:not([disabled])');
const selectedSeatInput = document.getElementById('selectedSeat');
const buyBtn = document.getElementById('buyBtn');

seatButtons.forEach(btn => {
    btn.addEventListener('click', function() {
        seatButtons.forEach(b => {
            b.style.background = '#fff';
            b.style.color = '#000';
        });

        this.style.background = '#3498db';
        this.style.color = '#fff';
        
        selectedSeatInput.value = this.dataset.seat;
        buyBtn.disabled = false;
    });
});

// --- KUPON KISMI ---
const applyCouponBtn = document.getElementById('applyCoupon');
const couponCodeInput = document.getElementById('couponCode');
const couponMessage = document.getElementById('couponMessage');
const originalPrice = <?php echo $trip['price']; ?>;
let currentDiscount = 0;

applyCouponBtn.addEventListener('click', function() {
    const code = couponCodeInput.value.trim();
    
    if (!code) {
        showCouponMessage('Lütfen bir kupon kodu girin!', 'error');
        return;
    }
    
    fetch('user/check-coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'code=' + encodeURIComponent(code)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentDiscount = data.discount_percent;
            const discountAmount = (originalPrice * currentDiscount) / 100;
            const finalPrice = originalPrice - discountAmount;

            const priceInput = document.querySelector('input[name="price"]');
            const couponHiddenInput = document.querySelector('input[name="coupon_code"]');
            if (priceInput) priceInput.value = finalPrice.toFixed(2);
            if (couponHiddenInput) couponHiddenInput.value = code;

            document.getElementById('discountAmount').value = currentDiscount;
            document.getElementById('discountRow').hidden = false;
            document.getElementById('discountText').textContent =
                '-' + discountAmount.toFixed(2).replace('.', ',') + ' TL (-%' + currentDiscount + ')';
            document.getElementById('finalPrice').textContent =
                finalPrice.toFixed(2).replace('.', ',') + ' TL';
            
            showCouponMessage('✅ Kupon uygulandı! %' + currentDiscount + ' indirim!', 'success');
            couponCodeInput.disabled = true;
            applyCouponBtn.disabled = true;
        } else {
            showCouponMessage('❌ ' + data.message, 'error');
        }
    })
    .catch(() => {
        showCouponMessage('❌ Bir hata oluştu!', 'error');
    });
});

function showCouponMessage(message, type) {
    const colors = { success: '#d4edda', error: '#f8d7da' };
    couponMessage.innerHTML = '<div style="padding: 10px; background: ' + colors[type] +
        '; border-radius: 5px;">' + message + '</div>';
}
</script>



<?php require_once 'includes/footer.php'; ?>