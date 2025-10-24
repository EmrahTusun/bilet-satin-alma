<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die(" Erişim reddedildi!");
}

$success = '';
$error = '';

// Firma ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_company'])) {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO companies (name) VALUES (?)");
        $stmt->execute([$name]);
        $success = "Firma başarıyla eklendi!";
    } else {
        $error = "Firma adı boş olamaz!";
    }
}

// Firma silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Önce bu firmaya ait sefer var mı kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE company_id = ?");
    $stmt->execute([$id]);
    $trip_count = $stmt->fetchColumn();
    
    if ($trip_count > 0) {
        $error = "Bu firmaya ait $trip_count sefer var! Önce seferleri silin.";
    } else {
        $stmt = $db->prepare("DELETE FROM companies WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Firma silindi!";
    }
}

// Firmaları listele
$companies = $db->query("
    SELECT 
        companies.*,
        COUNT(trips.id) as trip_count
    FROM companies
    LEFT JOIN trips ON companies.id = trips.company_id
    GROUP BY companies.id
    ORDER BY companies.name
")->fetchAll();

$page_title = 'Firma Yönetimi';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>🏢 Firma Yönetimi</h2>
    <a href="dashboard.php" class="btn btn-primary">← Admin Panele Dön</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<!-- Yeni Firma Ekle -->
<div class="card">
    <h3>➕ Yeni Firma Ekle</h3>
    <form method="POST" action="" style="max-width: 500px;">
        <div class="form-group">
            <label>Firma Adı</label>
            <input type="text" name="name" required placeholder="Örn: Metro Turizm">
        </div>
        <button type="submit" name="add_company" class="btn btn-success">Firma Ekle</button>
    </form>
</div>

<!-- Firma Listesi -->
<div class="card">
    <h3>📋 Mevcut Firmalar</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Firma Adı</th>
            <th>Sefer Sayısı</th>
            <th>Kayıt Tarihi</th>
            <th>İşlemler</th>
        </tr>
        <?php foreach ($companies as $company): ?>
        <tr>
            <td><?php echo $company['id']; ?></td>
            <td><strong><?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
            <td><?php echo $company['trip_count']; ?> sefer</td>
            <td><?php echo date('d.m.Y', strtotime($company['created_at'])); ?></td>
            <td>
                <a href="?delete=<?php echo $company['id']; ?>" 
                   class="btn btn-danger" 
                   style="padding: 5px 10px; font-size: 12px;"
                   onclick="return confirm('Bu firmayı silmek istediğinize emin misiniz?')">
                    Sil
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p style="margin-top: 20px; color: #7f8c8d;">
        Toplam <?php echo count($companies); ?> firma bulundu.
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>