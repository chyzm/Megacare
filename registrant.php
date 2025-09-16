<?php
require_once 'includes/config.php';

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify database connection
try {
    $pdo->query("SELECT 1")->fetch();
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

$registrantId = isset($_GET['id']) ? trim($_GET['id']) : null;

// Debug: Log the incoming ID
error_log("Searching for ID: " . $registrantId);

if (!$registrantId) {
    die(json_encode(['error' => 'No ID provided']));
}

// More flexible ID pattern
if (!preg_match('/^reg-[a-z0-9]+$/i', $registrantId)) {
    die(json_encode(['error' => 'Invalid ID format']));
}

try {
    // Debug: Count all records
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    error_log("Total records in database: " . $totalRecords);
    
    // Case-sensitive search
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE BINARY id = ?");
    $stmt->execute([$registrantId]);
    $registrant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registrant) {
        // Debug: Find similar IDs
        $similar = $pdo->prepare("SELECT id FROM clients WHERE id LIKE ? LIMIT 5");
        $similar->execute(["%$registrantId%"]);
        $similarIds = $similar->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("No exact match found. Similar IDs: " . implode(', ', $similarIds));
        die(json_encode(['error' => 'Registrant not found']));
    }
    
    // Get vaccination status
    $stmt = $pdo->prepare("SELECT * FROM vaccination_status WHERE client_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$registrant['id']]);
    $vaccination = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die(json_encode(['error' => 'Database error']));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrant Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            flex-wrap: wrap;
        }
        .detail-label {
            font-weight: bold;
            min-width: 150px;
            color: #3498db;
        }
        .detail-value {
            flex: 1;
            min-width: 200px;
        }
        .qr-code-container {
            margin-top: 30px;
            text-align: center;
        }
        @media print {
            body * { visibility: hidden; }
            .container, .container * { visibility: visible; }
            .container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registrant Information</h1>
        <div id="details-container">
            <div class="detail-item">
                <span class="detail-label">First Name:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['first_name']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Last Name:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['last_name']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Date of Birth:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['selected_date']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Reason:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['reason']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['email']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">WhatsApp Number:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['mobile']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Job Title:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['job_title']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Company:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['company']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">City:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['city']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Country:</span>
                <span class="detail-value"><?= htmlspecialchars($registrant['country']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Submission Time:</span>
                <span class="detail-value"><?= date('F j, Y g:i a', strtotime($registrant['created_at'])) ?></span>
            </div>
            
            <!-- Vaccination Status -->
            <?php if ($vaccination): ?>
            <div class="detail-item" style="border-top: 2px solid #28a745; margin-top: 20px; padding-top: 15px;">
                <span class="detail-label">Vaccination Status:</span>
                <div class="vaccination-status">
                    <?php if ($vaccination['first_dose']): ?>
                        <span class="badge bg-success me-1"><i class="fas fa-check"></i> First Dose</span>
                        <div class="small text-muted ms-2">Taken: <?= htmlspecialchars($vaccination['first_dose_date_taken'] ?? '') ?><?= ($vaccination['first_dose_next_date'] ? ' · Next: ' . htmlspecialchars($vaccination['first_dose_next_date']) : '') ?></div>
                    <?php endif; ?>
                    <?php if ($vaccination['second_dose']): ?>
                        <span class="badge bg-success me-1"><i class="fas fa-check"></i> Second Dose</span>
                        <div class="small text-muted ms-2">Taken: <?= htmlspecialchars($vaccination['second_dose_date_taken'] ?? '') ?><?= ($vaccination['second_dose_next_date'] ? ' · Next: ' . htmlspecialchars($vaccination['second_dose_next_date']) : '') ?></div>
                    <?php endif; ?>
                    <?php if ($vaccination['final_dose']): ?>
                        <span class="badge bg-success me-1"><i class="fas fa-check"></i> Vaccination Complete</span>
                        <div class="small text-muted ms-2">Taken: <?= htmlspecialchars($vaccination['final_dose_date_taken'] ?? '') ?></div>
                    <?php endif; ?>
                    <?php if (!$vaccination['first_dose'] && !$vaccination['second_dose'] && !$vaccination['final_dose']): ?>
                        <span class="badge bg-secondary">No vaccinations recorded</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="qr-code-container" class="qr-code-container">
            <h3>Your Registration QR Code</h3>
            <div id="qrcode">
                <img src="https://api.qrcode-monkey.com/qr/custom?size=200&data=<?= urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" 
                     alt="Registration QR Code" class="img-fluid">
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary flex-grow-1" onclick="printQRCode()">
                    <i class="fas fa-print me-2"></i>Print QR Code
                </button>
            </div>
        </div>
    </div>

    <script>
        function printQRCode() {
            const qrCodeImage = document.querySelector('#qrcode img');
            const firstName = '<?= htmlspecialchars($registrant["first_name"]) ?>';
            const lastName = '<?= htmlspecialchars($registrant["last_name"]) ?>';

            if (!qrCodeImage) {
                alert("QR code not found!");
                return;
            }

            const printWindow = window.open('', '', 'width=600,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print QR Code</title>
                    <style>
                        @page { 
                            size: auto; 
                            margin: 10mm;
                        }
                        body { 
                            text-align: center; 
                            padding: 20px;
                            font-family: Arial, sans-serif;
                        }
                        .qr-container {
                            margin: 0 auto;
                            width: 70mm;
                            text-align: center;
                        }
                        img { 
                            width: 100%; 
                            height: auto;
                            margin-bottom: 15px;
                        }
                        .name {
                            font-size: 24px;
                            font-weight: bold;
                            margin-top: 10px;
                            text-transform: uppercase;
                        }
                    </style>
                </head>
                <body>
                    <div class="qr-container">
                        <img src="${qrCodeImage.src}" alt="QR Code">
                        <div class="name">${firstName} ${lastName}</div>
                    </div>
                    <script>
                        window.onload = function() { 
                            setTimeout(function() {
                                window.print(); 
                                window.close(); 
                            }, 300);
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>
