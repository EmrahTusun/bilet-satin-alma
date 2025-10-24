<?php
if (!file_exists('database')) {
    mkdir('database', 0777, true);
}

$db_file = 'database/database.sqlite';

if (file_exists($db_file)) {
    die("Veritabanı zaten mevcut! Silmek için 'database' klasörünü manuel olarak silin.");
}

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Veritabanı oluşturuluyor...</h2>";

    // ==== USERS TABLOSU ====
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            full_name TEXT,
            role TEXT DEFAULT 'user',
            balance REAL DEFAULT 0,
            company_id INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
        )
    ");

    // ==== COMPANIES TABLOSU ====
    $db->exec("
        CREATE TABLE IF NOT EXISTS companies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // ==== TRIPS TABLOSU ====
    $db->exec("
        CREATE TABLE IF NOT EXISTS trips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_id INTEGER NOT NULL,
            from_city TEXT NOT NULL,
            to_city TEXT NOT NULL,
            departure_date DATE NOT NULL,
            departure_time TIME NOT NULL,
            price REAL NOT NULL,
            total_seats INTEGER DEFAULT 20,
            available_seats INTEGER DEFAULT 20,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )
    ");

    // ==== TICKETS TABLOSU ====
    $db->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            trip_id INTEGER NOT NULL,
            seat_number INTEGER NOT NULL,
            price REAL NOT NULL,
            status TEXT DEFAULT 'active',
            purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
        )
    ");

    // ==== COUPONS TABLOSU ====
    $db->exec("
        CREATE TABLE IF NOT EXISTS coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            discount_percent INTEGER NOT NULL,
            usage_limit INTEGER DEFAULT 500,
            used_count INTEGER DEFAULT 0,
            expiry_date DATE,
            is_active INTEGER DEFAULT 1,
            company_id INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
        )
    ");

    echo "Tablolar oluşturuldu.<br>";

    // ==== ÖRNEK FİRMALAR ====
    $companies = ['Metro Turizm', 'Pamukkale', 'Kamil Koç', 'Ulusoy', 'Nilüfer'];
    $stmt = $db->prepare("INSERT INTO companies (name) VALUES (?)");
    foreach ($companies as $company) {
        $stmt->execute([$company]);
    }
    echo count($companies) . " firma eklendi.<br>";

    // ==== ADMIN KULLANICISI ====
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, full_name, role, balance)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'admin',
        'admin@example.com',
        password_hash('Dejavu123', PASSWORD_DEFAULT),
        'Admin User',
        'admin',
        0
    ]);
    echo "Admin kullanıcısı oluşturuldu (admin / Dejavu123)<br>";

    // ==== FİRMA ADMINLERİ ====
    $firm_admins = [
        ['metro_admin', 'metro@example.com', 'Metro Yönetici', 1],
        ['pamukkale_admin', 'pamukkale@example.com', 'Pamukkale Yönetici', 2],
        ['kamil_admin', 'kamil@example.com', 'Kamil Koç Yönetici', 3],
        ['ulusoy_admin', 'ulusoy@example.com', 'Ulusoy Yönetici', 4],
        ['nilufer_admin', 'nilufer@example.com', 'Nilüfer Yönetici', 5],
    ];

    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, full_name, role, balance, company_id)
        VALUES (?, ?, ?, ?, 'firm_admin', 0, ?)
    ");

    foreach ($firm_admins as $admin) {
        [$username, $email, $full_name, $company_id] = $admin;
        $stmt->execute([
            $username,
            $email,
            password_hash('123456', PASSWORD_DEFAULT),
            $full_name,
            $company_id
        ]);
    }
    echo count($firm_admins) . " firma yöneticisi oluşturuldu (şifre: 123456).<br>";

    // ==== KUPONLAR ====
    $stmt = $db->prepare("
        INSERT INTO coupons (code, discount_percent, usage_limit, expiry_date, company_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute(['SIBERVATAN', 25, 100, '2026-12-31', 1]);
    $stmt->execute(['YAVUZLAR', 50, 50, '2026-12-31', 2]);
    $stmt->execute(['ALTAYLAR', 90, 10, '2026-12-31', 3]);
    echo "3 örnek kupon eklendi.<br><br>";

    echo "<h2>Kurulum Tamamlandı ✅</h2>";
    echo "<p><a href='index.php'>Ana Sayfaya Git</a></p>";

} catch(PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>
