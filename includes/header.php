<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root_path = '';
if (strpos($_SERVER['SCRIPT_NAME'], '/auth/') !== false || 
    strpos($_SERVER['SCRIPT_NAME'], '/user/') !== false || 
    strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || 
    strpos($_SERVER['SCRIPT_NAME'], '/firm-admin/') !== false) {
    $root_path = '../';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Otobüs Bileti'; ?></title>
    <link rel="stylesheet" href="<?php echo $root_path; ?>assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <a href="<?php echo $root_path; ?>index.php" class="logo">Bilet Satın Al</a>
                
                <ul class="nav-menu">
                    <li><a href="<?php echo $root_path; ?>index.php">Ana Sayfa</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'user'): ?>
                            <li><a href="<?php echo $root_path; ?>user/tickets.php">Biletlerim</a></li>
                            <li><a href="<?php echo $root_path; ?>user/profile.php">Profil</a></li>
                        
                        <?php elseif ($_SESSION['role'] == 'admin'): ?>
                            <li><a href="<?php echo $root_path; ?>admin/dashboard.php">Admin Panel</a></li>
                        <?php elseif ($_SESSION['role'] == 'firm_admin'): ?>
                            <li><a href="<?php echo $root_path; ?>firm-admin/dashboard.php">Firma Panel</a></li>
                        <?php endif; ?>
                        
                        <li>
                            <span>Hoş geldin, <?php echo clean_output($_SESSION['username']); ?>!</span>
                        </li>
                        <li><a href="<?php echo $root_path; ?>auth/logout.php">Çıkış</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $root_path; ?>auth/login.php">Giriş Yap</a></li>
                        <li><a href="<?php echo $root_path; ?>auth/register.php">Kayıt Ol</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    
    <main class="container">