<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$ticket_id = $_GET['id'] ?? 0;

// Bileti getirme
$stmt = $db->prepare("
    SELECT 
        tickets.*,
        trips.from_city,
        trips.to_city,
        trips.departure_date,
        trips.departure_time,
        companies.name as company_name,
        users.full_name as passenger_name
    FROM tickets
    INNER JOIN trips ON tickets.trip_id = trips.id
    INNER JOIN companies ON trips.company_id = companies.id
    INNER JOIN users ON tickets.user_id = users.id
    WHERE tickets.id = ? AND tickets.user_id = ?
");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Bilet bulunamadı!");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bilet - <?php echo $ticket['id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .ticket {
            border: 3px solid #2c3e50;
            border-radius: 10px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .ticket-header {
            text-align: center;
            border-bottom: 2px dashed white;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .ticket-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .field {
            margin: 10px 0;
        }
        .field-label {
            font-size: 12px;
            opacity: 0.8;
        }
        .field-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
        }
        .seat-number {
            text-align: center;
            font-size: 48px;
            font-weight: bold;
            background: white;
            color: #667eea;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed white;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 15px 30px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            Yazdır / PDF Olarak Kaydet
        </button>
        <a href="tickets.php" style="display: inline-block; padding: 15px 30px; background: #3498db; color: white; border-radius: 5px; text-decoration: none; margin-left: 10px;">
            ← Biletlerime Dön
        </a>
    </div>

    <div class="ticket">
        <div class="ticket-header">
            <h1 style="margin: 0; font-size: 32px;"> OTOBÜS BİLETİ</h1>
            <p style="margin: 10px 0 0 0;">Bilet No: #<?php echo str_pad($ticket['id'], 5, '0', STR_PAD_LEFT); ?></p>
        </div>

        <div class="ticket-body">
            <div class="field">
                <div class="field-label">Yolcu Adı</div>
                <div class="field-value"><?php echo htmlspecialchars($ticket['passenger_name']); ?></div>
            </div>

            <div class="field">
                <div class="field-label">Firma</div>
                <div class="field-value"><?php echo htmlspecialchars($ticket['company_name']); ?></div>
            </div>

            <div class="field">
                <div class="field-label">Kalkış</div>
                <div class="field-value"><?php echo htmlspecialchars($ticket['from_city']); ?></div>
            </div>

            <div class="field">
                <div class="field-label">Varış</div>
                <div class="field-value"><?php echo htmlspecialchars($ticket['to_city']); ?></div>
            </div>

            <div class="field">
                <div class="field-label">Tarih</div>
                <div class="field-value"><?php echo date('d.m.Y', strtotime($ticket['departure_date'])); ?></div>
            </div>

            <div class="field">
                <div class="field-label">Saat</div>
                <div class="field-value"><?php echo date('H:i', strtotime($ticket['departure_time'])); ?></div>
            </div>

            <div class="field">
                <div class="field-label">Ücret</div>
                <div class="field-value"><?php echo number_format($ticket['price'], 2, ',', '.') . ' TL'; ?></div>
            </div>

            <div class="field">
                <div class="field-label">Satın Alma</div>
                <div class="field-value"><?php echo date('d.m.Y H:i', strtotime($ticket['purchased_at'])); ?></div>
            </div>
        </div>

        <div class="seat-number">
            Koltuk No: <?php echo $ticket['seat_number']; ?>
        </div>

        <div class="qr-code">
            <p style="margin: 0; font-size: 12px; opacity: 0.8;">
                Biletinizi seyahat sırasında gösteriniz
            </p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 30px; color: #7f8c8d;">
        <p><strong>Not:</strong> PDF olarak kaydetmek için "Yazdır" butonuna tıklayın ve hedef olarak "PDF olarak kaydet" seçin.</p>
    </div>
</body>
</html>