<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Get registrant ID from URL
$registrantId = $_GET['id'] ?? null;

if (!$registrantId) {
    die("No registrant ID provided");
}

// Fetch registrant data
$stmt = $pdo->prepare("SELECT * FROM registrants WHERE id = ?");
$stmt->execute([$registrantId]);
$registrant = $stmt->fetch();

if (!$registrant) {
    die("Registrant not found");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vaccine ID Card</title>
    <style>
        @page { size: 50mm x 50mm; margin: 0; }   /* { size: 85.6mm x 54mm; margin: 0; }   */
        body { 
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    width: 50mm;
    height: 50mm;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

        .card-header {
            text-align: center;
            margin-bottom: 5px;
        }
        .card-body {
            display: flex;
            width: 100%;
            justify-content: space-between;
        }
        .qr-code {
    width: 100%;
    height: 100%;
    object-fit: contain; /* Ensures it scales properly */
}

        .details {
            width: 50mm;
        }
        .card-footer {
            font-size: 10px;
            text-align: center;
            margin-top: 5px;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="card-header">
        <h3 style="margin:0;">MegaCare Pharmacy</h3>
        <h4 style="margin:0;">Hepatitis B Vaccine</h4>
    </div>
    
    <div class="card-body">
        <div class="details">
            <p><strong>Name:</strong> <?= htmlspecialchars($registrant['first_name']) . ' ' . htmlspecialchars($registrant['last_name']) ?></p>
            <p><strong>ID:</strong> <?= htmlspecialchars($registrant['id']) ?></p>
            <p><strong>Date:</strong> <?= date('m/d/Y', strtotime($registrant['created_at'])) ?></p>
        </div>
        <img src="<?= htmlspecialchars($registrant['qr_code_file']) ?>" class="qr-code" alt="Vaccine QR Code">
    </div>
    
    <div class="card-footer">
        Scan QR code to verify vaccination record
    </div>
</body>
</html>
