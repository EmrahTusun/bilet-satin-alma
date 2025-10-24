<?php
$databasePath = realpath(__DIR__ . '/../database/database.sqlite');

if (!$databasePath || !file_exists($databasePath)) {
    error_log("Database file not found: " . ($databasePath ?: 'null'));
    die(" Veritabanı bulunamadı. Lütfen install.php dosyasını çalıştırın.");
}

try {
    $db = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
        PDO::ATTR_EMULATE_PREPARES   => false,        
    ]);
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(500);
    die(" Veritabanı bağlantısı başarısız. Lütfen sistem yöneticisine bildirin.");
}
?>
