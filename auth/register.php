<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /bilet-satin-alma/index.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "Tüm alanları doldurun!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir email adresi girin!";
    } elseif ($password != $password_confirm) {
        $error = "Şifreler eşleşmiyor!";
    } else {
        
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = "Bu kullanıcı adı veya email zaten kullanılıyor!";
        } else {
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, balance) 
                                  VALUES (?, ?, ?, ?, 'user', 500)");
            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $full_name
            ]);
            
            $success = "Kayıt başarılı! 500 TL hoş geldin bonusu kazandınız. Giriş yapabilirsiniz.";
        }
    }
}

$page_title = 'Kayıt Ol';
require_once '../includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <h2>📝 Kayıt Ol</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo clean_output($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo clean_output($success); ?>
            <br><br>
            <a href="login.php" class="btn btn-primary">Giriş Yap</a>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Ad Soyad *</label>
                <input type="text" name="full_name" value="<?php echo isset($_POST['full_name']) ? clean_output($_POST['full_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Kullanıcı Adı *</label>
                <input type="text" name="username" value="<?php echo isset($_POST['username']) ? clean_output($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? clean_output($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Şifre *</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Şifre Tekrar *</label>
                <input type="password" name="password_confirm" required>
            </div>
            
            <button type="submit" class="btn btn-success" style="width: 100%;">Kayıt Ol</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>