<?php
ini_set('session.cookie_httponly', 1); 
ini_set('session.cookie_secure', 1);    
ini_set('session.use_strict_mode', 1);   
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';


if (isset($_SESSION['user_id'])) {
    header("Location: /bilet-satin-alma/index.php");
    exit;
}

$error = "";


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Geçersiz istek. Lütfen tekrar deneyin.";
    } else {
       
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        
        if (empty($username) || empty($password)) {
            $error = "Kullanıcı adı ve şifre gerekli!";
        } else {
            
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                
                
                session_regenerate_id(true);

               
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

            
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                
                if ($user['role'] === 'admin') {
                    header("Location: /bilet-satin-alma/admin/dashboard.php");
                } elseif ($user['role'] === 'firm_admin') {
                    header("Location: /bilet-satin-alma/firm-admin/dashboard.php");
                } else {
                    header("Location: /bilet-satin-alma/index.php");
                }
                exit;
            } else {
                $error = "Kullanıcı adı veya şifre hatalı!";
            }
        }
    }
}


$page_title = 'Giriş Yap';
require_once '../includes/header.php';
?>

<div class="card" style="max-width: 450px; margin: 50px auto;">
    <h2>🔐 Güvenli Giriş</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo clean_output($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="form-group">
            <label>Kullanıcı Adı veya Email</label>
            <input type="text" name="username" maxlength="50" required autofocus>
        </div>

        <div class="form-group">
            <label>Şifre</label>
            <input type="password" name="password" maxlength="100" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Giriş Yap</button>
    </form>

    <p style="text-align: center; margin-top: 20px;">
        Hesabın yok mu? <a href="register.php">Kayıt Ol</a>
    </p>

    <hr style="margin: 20px 0;">
</div>

<?php require_once '../includes/footer.php'; ?>
