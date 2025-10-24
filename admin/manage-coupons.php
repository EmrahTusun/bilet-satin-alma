<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'firm_admin')) {
    die(" Erişim reddedildi!");
}

$success = '';
$error = '';

$firm_company_id = null;
if ($_SESSION['role'] == 'firm_admin') {
    $stmt = $db->prepare("SELECT id FROM companies WHERE id = 1"); 
    $stmt->execute();
    $company = $stmt->fetch();
    $firm_company_id = $company['id'];
}

// Kupon ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount_percent = (int)$_POST['discount_percent'];
    $usage_limit = (int)$_POST['usage_limit'];
    $expiry_date = $_POST['expiry_date'];
    
    if (empty($code)) {
        $error = "Kupon kodu boş olamaz!";
    } elseif ($discount_percent < 1 || $discount_percent > 100) {
        $error = "İndirim oranı 1-100 arasında olmalı!";
    } else {
        $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
        $stmt->execute([$code]);
        
        if ($stmt->fetch()) {
            $error = "Bu kupon kodu zaten mevcut!";
        } else {
            if ($_SESSION['role'] == 'firm_admin') {
                $stmt = $db->prepare("INSERT INTO coupons (code, discount_percent, usage_limit, expiry_date, company_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount_percent, $usage_limit, $expiry_date, $firm_company_id]);
            } else {
                $stmt = $db->prepare("INSERT INTO coupons (code, discount_percent, usage_limit, expiry_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$code, $discount_percent, $usage_limit, $expiry_date]);
            }
            $success = "Kupon başarıyla eklendi!";
        }
    }
}

// Kupon silme ve aktif/pasif
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($_SESSION['role'] == 'admin') {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Kupon silindi!";
    } elseif ($_SESSION['role'] == 'firm_admin') {
        // Firma admin sadece kendi kuponunu silebilir
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $firm_company_id]);
        $success = "Kupon silindi!";
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    if ($_SESSION['role'] == 'admin') {
        $stmt = $db->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Kupon durumu güncellendi!";
    } elseif ($_SESSION['role'] == 'firm_admin') {
        // Firma admin sadece kendi kuponunu değiştirebilir
        $stmt = $db->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $firm_company_id]);
        $success = "Kupon durumu güncellendi!";
    }
}

// Kuponları listele
if ($_SESSION['role'] == 'admin') {
    $coupons = $db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE company_id = ? ORDER BY created_at DESC");
    $stmt->execute([$firm_company_id]);
    $coupons = $stmt->fetchAll();
}

$page_title = 'Kupon Yönetimi';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>🎫 Kupon Yönetimi</h2>
    <?php if($_SESSION['role'] == 'admin'): ?>
        <a href="dashboard.php" class="btn btn-primary">← Panele Dön</a>
    <?php elseif($_SESSION['role'] == 'firm_admin'): ?>
        <a href="../firm-admin/dashboard.php" class="btn btn-primary">← Panele Dön</a>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<!-- Yeni Kupon Ekle -->
<div class="card">
    <h3>➕ Yeni Kupon Oluştur</h3>
    <form method="POST" action="" style="max-width: 600px;">
        <div class="form-group">
            <label>Kupon Kodu *</label>
            <input type="text" name="code" required placeholder="Örn: YENIYIL25" style="text-transform: uppercase;">
        </div>
        <div class="form-group">
            <label>İndirim Oranı (%) *</label>
            <input type="number" name="discount_percent" min="1" max="100" required placeholder="Örn: 20">
        </div>
        <div class="form-group">
            <label>Kullanım Limiti *</label>
            <input type="number" name="usage_limit" min="1" value="100" required>
        </div>
        <div class="form-group">
            <label>Son Kullanma Tarihi (Opsiyonel)</label>
            <input type="date" name="expiry_date" min="<?php echo date('Y-m-d'); ?>">
        </div>
        <button type="submit" name="add_coupon" class="btn btn-success">Kupon Oluştur</button>
    </form>
</div>

<!-- Kupon Listesi -->
<div class="card">
    <h3>📋 Mevcut Kuponlar</h3>
    <?php if (count($coupons) == 0): ?>
        <p style="color: #7f8c8d;">Henüz kupon oluşturulmamış.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Kod</th>
                <th>İndirim</th>
                <th>Kullanım</th>
                <th>Son Tarih</th>
                <th>Durum</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($coupons as $coupon): ?>
            <?php
            $is_expired = $coupon['expiry_date'] && strtotime($coupon['expiry_date']) < time();
            $is_limit_reached = $coupon['used_count'] >= $coupon['usage_limit'];
            $is_active = $coupon['is_active'] && !$is_expired && !$is_limit_reached;
            ?>
            <tr style="<?php echo $is_active ? '' : 'opacity: 0.5;'; ?>">
                <td><strong><?php echo htmlspecialchars($coupon['code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td>%<?php echo $coupon['discount_percent']; ?></td>
                <td>
                    <?php echo $coupon['used_count']; ?> / <?php echo $coupon['usage_limit']; ?>
                </td>
                <td>
                    <?php 
                    if ($coupon['expiry_date']) {
                        echo date('d.m.Y', strtotime($coupon['expiry_date']));
                        if ($is_expired) echo ' <span style="color: #e74c3c;">(Süresi Doldu)</span>';
                    } else {
                        echo 'Süresiz';
                    }
                    ?>
                </td>
                <td>
                    <?php if ($is_active): ?>
                        <span style="background: #27ae60; color: white; padding: 3px 10px; border-radius: 3px; font-size: 12px;">Aktif</span>
                    <?php else: ?>
                        <span style="background: #95a5a6; color: white; padding: 3px 10px; border-radius: 3px; font-size: 12px;">Pasif</span>
                    <?php endif; ?>
                </td> 
                <td>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'firm_admin'): ?>
                        <a href="?toggle=<?php echo $coupon['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">
                            <?php echo $coupon['is_active'] ? 'Pasif Yap' : 'Aktif Yap'; ?>
                        </a>
                        <a href="?delete=<?php echo $coupon['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Bu kuponu silmek istediğinize emin misiniz?')">
                            Sil
                        </a>
                    <?php endif; ?>
                </td>  
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
