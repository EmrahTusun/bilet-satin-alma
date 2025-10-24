<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die(" Erişim reddedildi!");
}

$success = '';
$error = '';

// Kullanıcı silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if ($id == $_SESSION['user_id']) {
        $error = "Kendi hesabınızı silemezsiniz!";
    } else {
        // Aktif bilet kontrolü
        $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$id]);
        $active_tickets = $stmt->fetchColumn();
        
        if ($active_tickets > 0) {
            $error = "Bu kullanıcının $active_tickets aktif bileti var! Önce biletler iptal edilmeli.";
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Kullanıcı silindi!";
        }
    }
}

// Kullanıcıları listele
$users = $db->query("
    SELECT 
        users.*,
        COUNT(tickets.id) as ticket_count,
        SUM(CASE WHEN tickets.status = 'active' THEN 1 ELSE 0 END) as active_tickets
    FROM users
    LEFT JOIN tickets ON users.id = tickets.user_id
    WHERE users.role != 'admin'
    GROUP BY users.id
    ORDER BY users.created_at DESC
")->fetchAll();

$page_title = 'Kullanıcı Yönetimi';
require_once '../includes/header.php';
?>

<div class="card">
    <h2>👥 Kullanıcı Yönetimi</h2>
    <a href="dashboard.php" class="btn btn-primary">← Admin Panele Dön</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<!-- Kullanıcı Listesi -->
<div class="card">
    <h3>📋 Kayıtlı Kullanıcılar</h3>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Ad Soyad</th>
            <th>Kullanıcı Adı</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Toplam Bilet</th>
            <th>Aktif Bilet</th>
            <th>Kayıt Tarihi</th>
            <th>İşlemler</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <?php
                $roles = ['user' => 'Kullanıcı', 'firm_admin' => 'Firma Admin'];
                ?>
                <span style="background: #3498db; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                    <?= $roles[$user['role']] ?? $user['role'] ?>
                </span>
            </td>
            <td><?= $user['ticket_count'] ?></td>
            <td><?= $user['active_tickets'] ?></td>
            <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
            <td>
                <a href="?delete=<?= $user['id'] ?>" 
                   class="btn btn-danger" 
                   style="padding: 5px 10px; font-size: 12px;"
                   onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                    Sil
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p style="margin-top: 20px; color: #7f8c8d;">
        Toplam <?= count($users) ?> kullanıcı bulundu.
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>
