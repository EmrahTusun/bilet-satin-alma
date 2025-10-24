<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die(" Bu sayfaya erişim yetkiniz yok! <br><br><a href='../index.php'>Ana Sayfaya Dön</a>");
}

$success = '';
$error = '';

// Firma admin ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_firm_admin'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $company_id = (int)$_POST['company_id'];
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Tüm alanları doldurun!';
    } elseif (strlen($username) < 3) {
        $error = 'Kullanıcı adı en az 3 karakter olmalı!';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalı!';
    } else {
        // Kullanıcı adı veya email kontrolü
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Bu kullanıcı adı veya email zaten kullanılıyor!';
        } else {
            // Firma admin oluşturma 
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, balance) VALUES (?, ?, ?, ?, 'firm_admin', 0)");
            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $full_name
            ]);
            
            $success = "Firma admin başarıyla oluşturuldu! Kullanıcı Adı: $username, Şifre: $password";
        }
    }
}

// Firma admini silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'firm_admin'");
    $stmt->execute([$id]);
    $success = 'Firma admin silindi!';
}

// Firmaları göster
$companies = $db->query("SELECT * FROM companies ORDER BY name")->fetchAll();

// Firmaların adminleri
$firm_admins = $db->query("
    SELECT users.*
    FROM users
    WHERE users.role = 'firm_admin'
    ORDER BY users.created_at DESC
")->fetchAll();

$page_title = 'Firma Admin Yönetimi';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>🔐 Firma Admin Yönetimi</h2>
    <a href="dashboard.php" class="btn btn-primary">← Admin Panele Dön</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card">
    <h3>➕ Yeni Firma Admin Oluştur</h3>
    
    <form method="POST" action="" style="max-width: 800px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label>Firma Seç *</label>
                <select name="company_id" required>
                    <option value="">Firma Seçin</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>">
                            <?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #6B7280; font-size: 13px;">Bu admin hangi firmayı yönetecek?</small>
            </div>
            
            <div class="form-group">
                <label>Ad Soyad *</label>
                <input type="text" name="full_name" required placeholder="Örn: Ahmet Yılmaz">
            </div>
            
            <div class="form-group">
                <label>Kullanıcı Adı *</label>
                <input type="text" name="username" required placeholder="Örn: ahmet_admin">
                <small style="color: #6B7280; font-size: 13px;">En az 3 karakter, boşluk kullanmayın</small>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="ornek@mail.com">
            </div>
            
            <div class="form-group">
                <label>Şifre *</label>
                <input type="text" name="password" required placeholder="En az 6 karakter" value="<?= substr(md5(time()), 0, 8) ?>">
                <small style="color: #6B7280; font-size: 13px;">Otomatik şifre oluşturuldu, değiştirebilirsiniz</small>
            </div>
        </div>
        
        <button type="submit" name="add_firm_admin" class="btn btn-success" style="margin-top: 20px;">Firma Admin Oluştur</button>
    </form>
</div>

<div class="card">
    <h3>📋 Mevcut Firma Adminleri</h3>
    
    <?php if (count($firm_admins) == 0): ?>
        <p style="color: #7f8c8d; text-align: center; padding: 40px;">Henüz firma admin oluşturulmamış.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>Email</th>
                <th>Kayıt Tarihi</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($firm_admins as $admin): ?>
            <tr>
                <td><?= $admin['id'] ?></td>
                <td><strong><?= htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td><?= htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= date('d.m.Y', strtotime($admin['created_at'])) ?></td>
                <td>
                    <a href="?delete=<?= $admin['id'] ?>" 
                       class="btn btn-danger" 
                       style="padding: 5px 10px; font-size: 12px;"
                       onclick="return confirm('Bu firma admini silmek istediğinize emin misiniz?')">
                        Sil
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <p style="margin-top: 20px; color: #7f8c8d; text-align: center;">
            Toplam <?= count($firm_admins) ?> firma admin bulundu.
        </p>
    <?php endif; ?>
</div>


<?php require_once '../includes/footer.php'; ?>