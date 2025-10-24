<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user = get_user_info($db, $_SESSION['user_id']);
$success = $_GET['success'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_balance'])) {
    $amount = (float)$_POST['amount'];
    
    if ($amount > 0 && $amount <= 10000) {
        $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $_SESSION['user_id']]);
        
        header("Location: profile.php?success=1");
        exit;
    }
}

$page_title = 'Profilim';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>Profil Bilgileri</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Bakiye başarıyla yüklendi!
        </div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <div style="color: #7f8c8d; font-size: 14px;">Ad Soyad</div>
            <div style="font-size: 18px; font-weight: bold; margin-top: 5px;">
                <?php echo clean_output($user['full_name']); ?>
            </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <div style="color: #7f8c8d; font-size: 14px;">Kullanıcı Adı</div>
            <div style="font-size: 18px; font-weight: bold; margin-top: 5px;">
                <?php echo clean_output($user['username']); ?>
            </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <div style="color: #7f8c8d; font-size: 14px;">Email</div>
            <div style="font-size: 18px; font-weight: bold; margin-top: 5px;">
                <?php echo clean_output($user['email']); ?>
            </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <div style="color: #7f8c8d; font-size: 14px;">Rol</div>
            <div style="font-size: 18px; font-weight: bold; margin-top: 5px;">
                <?php 
                $roles = ['user' => 'Kullanıcı', 'admin' => 'Admin', 'firm_admin' => 'Firma Yöneticisi'];
                echo $roles[$user['role']] ?? $user['role'];
                ?>
            </div>
        </div>
        
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white;">
            <div style="font-size: 14px; opacity: 0.9;">Bakiye</div>
            <div style="font-size: 32px; font-weight: bold; margin-top: 5px;">
                <?php echo format_money($user['balance']); ?>
            </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <div style="color: #7f8c8d; font-size: 14px;">Kayıt Tarihi</div>
            <div style="font-size: 18px; font-weight: bold; margin-top: 5px;">
                <?php echo format_date(date('Y-m-d', strtotime($user['created_at']))); ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <h2>💳 Bakiye Yükle</h2>
<form method="POST" action="" style="max-width: 500px;">
        <div class="form-group">
            <label>Yüklenecek Miktar (TL)</label>
            <input type="number" name="amount" step="0.01" min="1" max="10000" required placeholder="Örn: 500">
            <small>Minimum: 1 TL, Maksimum: 10,000 TL</small>
        </div>
        
        <button type="submit" name="add_balance" class="btn btn-success">
            💰 Bakiye Yükle
        </button>
    </form>
</div>

<div class="card">
    <h2> İstatistiklerim</h2>
    
    <?php
    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_tickets = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $active_tickets = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT SUM(price) FROM tickets WHERE user_id = ? AND status != 'cancelled'");
    $stmt->execute([$_SESSION['user_id']]);
    $total_spent = $stmt->fetchColumn() ?? 0;
    ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #27ae60;">
                <?php echo $total_tickets; ?>
            </div>
            <div style="color: #7f8c8d;">Toplam Bilet</div>
        </div>
        
        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #3498db;">
                <?php echo $active_tickets; ?>
            </div>
            <div style="color: #7f8c8d;">Aktif Bilet</div>
        </div>
        
        <div style="background: #fff3e0; padding: 20px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #e67e22;">
                <?php echo format_money($total_spent); ?>
            </div>
            <div style="color: #7f8c8d;">Toplam Harcama</div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
