<?php
require_once 'includes/config.php';

$registrantId = isset($_GET['id']) ? trim($_GET['id']) : '';
$error = null;
$registrant = null;
$vaccination = null;

if ($registrantId !== '') {
    if (!preg_match('/^reg-[a-z0-9]+$/i', $registrantId)) {
        $error = 'Invalid registration number format.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE BINARY id = ?");
            $stmt->execute([$registrantId]);
            $registrant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registrant) {
                $stmt = $pdo->prepare("SELECT * FROM vaccination_status WHERE client_id = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$registrant['id']]);
                $vaccination = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'No patient was found with that registration number.';
            }
        } catch (PDOException $e) {
            error_log('Certificate lookup error: ' . $e->getMessage());
            $error = 'Unable to load certificate right now.';
        }
    }
}

function doseDone($vaccination, $field) {
    return $vaccination && !empty($vaccination[$field]);
}

function formatCertDate($value) {
    return $value ? date('F j, Y', strtotime($value)) : 'Pending';
}

function certValue($value, $fallback = 'Not recorded') {
    $value = trim((string)$value);
    return $value !== '' ? $value : $fallback;
}

function doseBatch($vaccination, $field) {
    return $vaccination && !empty($vaccination[$field]) ? $vaccination[$field] : 'Pending';
}

$displayVaccineType = $vaccination['vaccination_type'] ?? $registrant['reason'] ?? '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/megacare_phamacy/vaccination_certificate.php';
$verificationUrl = $registrant
    ? $scheme . '://' . $host . $scriptPath . '?id=' . rawurlencode($registrant['id'])
    : '';
$qrUrl = $verificationUrl
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=8&data=' . rawurlencode($verificationUrl)
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Vaccination - MegaCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --teal: #078f91;
            --teal-dark: #047073;
            --ink: #10192b;
            --muted: #506071;
            --line: #d8e3e7;
            --soft: #eaf8f7;
        }
        body {
            margin: 0;
            min-height: 100vh;
            background: #eef3f5;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
        }
        .topbar {
            background: #0b7784;
            color: #fff;
            padding: 18px 0;
        }
        .lookup-card {
            max-width: 680px;
            margin: 48px auto;
            padding: 28px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 18px 40px rgba(16, 25, 43, .12);
        }
        .actions {
            max-width: 760px;
            margin: 22px auto 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-brand {
            background: #078f91;
            border-color: #078f91;
            color: #fff;
        }
        .btn-brand:hover {
            background: #047073;
            border-color: #047073;
            color: #fff;
        }
        .certificate {
            width: min(760px, calc(100% - 28px));
            margin: 22px auto 44px;
            background: #fff;
            box-shadow: 0 20px 48px rgba(16, 25, 43, .15);
        }
        .cert-sheet {
            min-height: 1000px;
            padding: 44px 64px 34px;
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(7,143,145,.08) 0 12%, transparent 12% 100%),
                linear-gradient(315deg, rgba(7,143,145,.08) 0 14%, transparent 14% 100%),
                #fff;
        }
        .cert-sheet::before,
        .cert-sheet::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(7, 143, 145, .08);
            pointer-events: none;
        }
        .cert-sheet::before {
            inset: 20px 30px;
        }
        .cert-sheet::after {
            inset: 52px 66px auto auto;
            width: 140px;
            height: 86px;
            background: rgba(10, 125, 130, .04);
        }
        .logo-wrap {
            text-align: center;
            margin-bottom: 34px;
        }
        .logo-wrap img {
            width: 178px;
            height: auto;
            object-fit: contain;
        }
        .cert-title {
            margin: 0;
            text-align: center;
            color: #111b30;
            font-size: 42px;
            line-height: 1.05;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
        }
        .cert-subtitle {
            margin-top: 12px;
            color: var(--teal);
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .rule {
            width: 82%;
            height: 22px;
            margin: 12px auto 28px;
            position: relative;
            border-top: 2px solid rgba(7, 143, 145, .55);
        }
        .rule i {
            position: absolute;
            left: 50%;
            top: -13px;
            transform: translateX(-50%);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--teal);
            border: 5px solid #fff;
            font-size: 13px;
        }
        .attestation {
            max-width: 620px;
            margin: 0 auto 22px;
            color: #323b4a;
            font-size: 16px;
            line-height: 1.55;
            text-align: center;
        }
        .patient-name {
            margin: 0 0 28px;
            color: #10192b;
            text-align: center;
            font-size: 34px;
            line-height: 1.15;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .fact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 58px;
            row-gap: 24px;
            max-width: 560px;
            margin: 0 auto 28px;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--line);
        }
        .fact {
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 14px;
            align-items: center;
        }
        .fact-icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--soft);
            color: var(--teal);
            font-size: 19px;
        }
        .fact-label {
            color: #344155;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .fact-value {
            margin-top: 5px;
            color: #253041;
            font-size: 16px;
            line-height: 1.25;
        }
        .section-label {
            margin: 0 0 10px;
            color: var(--teal);
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .dose-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 7px;
            font-size: 15px;
        }
        .dose-table th {
            padding: 14px 18px;
            background: var(--teal);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .dose-table td {
            padding: 15px 18px;
            color: #273244;
            border-top: 1px solid var(--line);
            border-right: 1px solid var(--line);
            background: rgba(255,255,255,.9);
        }
        .dose-table td:last-child,
        .dose-table th:last-child {
            border-right: 0;
        }
        .dose-table tbody tr:first-child td {
            border-top: 0;
        }
        .cert-bottom {
            display: grid;
            grid-template-columns: 1fr 158px 1fr;
            gap: 34px;
            align-items: end;
            margin-top: 52px;
        }
        .signature-block,
        .issued-block {
            text-align: center;
        }
        .issued-block {
            padding-bottom: 45px;
        }
        .line-content {
            height: 72px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .issued-content {
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 13px;
        }
        .signature-mark {
            width: 158px;
            height: 66px;
            display: block;
            object-fit: contain;
            mix-blend-mode: multiply;
        }
        .line {
            border-top: 2px solid #9eb9bf;
            padding-top: 10px;
        }
        .line-below {
            border-top: 2px solid #9eb9bf;
            height: 1px;
        }
        .fine-label {
            color: var(--teal);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .fine-value {
            margin-top: 5px;
            color: #263245;
            font-size: 13px;
            line-height: 1.35;
        }
        .qr-block {
            text-align: center;
        }
        .qr-block img {
            width: 138px;
            height: 138px;
            object-fit: contain;
        }
        @media (max-width: 720px) {
            .cert-sheet {
                padding: 32px 24px;
            }
            .cert-title {
                font-size: 32px;
            }
            .fact-grid,
            .cert-bottom {
                grid-template-columns: 1fr;
            }
            .cert-bottom {
                gap: 22px;
            }
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }
            .topbar,
            .actions,
            .lookup-card {
                display: none !important;
            }
            body {
                background: #fff;
            }
            .certificate {
                width: 194mm;
                margin: 0 auto;
                box-shadow: none;
            }
            .cert-sheet {
                box-sizing: border-box;
                width: 194mm;
                height: 281mm;
                min-height: 0;
                padding: 12mm 17mm 10mm;
            }
            .cert-title {
                font-size: 34px;
            }
            .logo-wrap {
                margin-bottom: 24px;
            }
            .logo-wrap img {
                width: 150px;
            }
            .attestation {
                font-size: 13px;
            }
            .patient-name {
                font-size: 29px;
                margin-bottom: 22px;
            }
            .fact-grid {
                row-gap: 18px;
                margin-bottom: 22px;
            }
            .fact-value,
            .dose-table {
                font-size: 12px;
            }
            .dose-table th,
            .dose-table td {
                padding: 10px 13px;
            }
            .cert-bottom {
                margin-top: 36px;
            }
            .qr-block img {
                width: 118px;
                height: 118px;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h4 mb-1"><i class="fas fa-certificate me-2"></i>Vaccination Certificate</h1>
                <div class="opacity-75">MegaCare Pharmacy official vaccination record</div>
            </div>
            <a class="btn btn-outline-light" href="index.php"><i class="fas fa-house me-2"></i>Home</a>
        </div>
    </header>

    <?php if (!$registrant): ?>
        <main class="container">
            <section class="lookup-card">
                <h2 class="h4 mb-2">View Patient Certificate</h2>
                <p class="text-muted">Enter the unique registration number issued after registration.</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="id">Registration Number</label>
                        <input class="form-control form-control-lg" id="id" name="id" placeholder="reg-xxxxxxxxxxxxx" value="<?= htmlspecialchars($registrantId) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-brand btn-lg w-100" type="submit">
                            <i class="fas fa-search me-2"></i>View Certificate
                        </button>
                    </div>
                </form>
            </section>
        </main>
    <?php else: ?>
        <div class="actions">
            <button class="btn btn-brand" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Certificate</button>
            <a class="btn btn-outline-secondary" href="registrant.php?id=<?= htmlspecialchars($registrant['id']) ?>"><i class="fas fa-user me-2"></i>Patient Record</a>
            <a class="btn btn-outline-primary" href="vaccination_certificate.php"><i class="fas fa-search me-2"></i>Lookup Another</a>
        </div>

        <main class="certificate">
            <div class="cert-sheet">
                <div class="logo-wrap">
                    <img src="assets/img/logo.png" alt="MegaCare Pharmacy logo">
                </div>

                <h2 class="cert-title">Certificate of Vaccination</h2>
                <div class="cert-subtitle">Official Patient Vaccination Record</div>
                <div class="rule"><i class="fas fa-shield-alt"></i></div>

                <p class="attestation">
                    MegaCare Pharmacy hereby certifies that the undermentioned individual, has received
                    <strong>the vaccination listed below</strong>
                </p>

                <h3 class="patient-name"><?= htmlspecialchars($registrant['first_name'] . ' ' . $registrant['last_name']) ?></h3>

                <section class="fact-grid">
                    <div class="fact">
                        <div class="fact-icon"><i class="fas fa-syringe"></i></div>
                        <div>
                            <div class="fact-label">Vaccine Type</div>
                            <div class="fact-value"><?= htmlspecialchars(certValue($displayVaccineType)) ?></div>
                        </div>
                    </div>
                    <div class="fact">
                        <div class="fact-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="fact-label">Date of Birth</div>
                            <div class="fact-value"><?= htmlspecialchars(certValue($registrant['selected_date'] ?? '')) ?></div>
                        </div>
                    </div>
                    <div class="fact">
                        <div class="fact-icon"><i class="fas fa-calendar-check"></i></div>
                        <div>
                            <div class="fact-label">Registration Date</div>
                            <div class="fact-value"><?= htmlspecialchars(formatCertDate($registrant['created_at'] ?? null)) ?></div>
                        </div>
                    </div>
                    <div class="fact">
                        <div class="fact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="fact-label">Location</div>
                            <div class="fact-value"><?= htmlspecialchars(certValue(($registrant['city'] ?? '') . ', ' . ($registrant['country'] ?? ''), 'Not recorded')) ?></div>
                        </div>
                    </div>
                </section>

                <h4 class="section-label">Vaccination Record</h4>
                <table class="dose-table">
                    <thead>
                        <tr>
                            <th>Dose</th>
                            <th>Batch No</th>
                            <th>Vaccine Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>First Dose</td>
                            <td><?= htmlspecialchars(doseDone($vaccination, 'first_dose') ? doseBatch($vaccination, 'first_dose_batch_no') : 'Pending') ?></td>
                            <td><?= htmlspecialchars(doseDone($vaccination, 'first_dose') ? formatCertDate($vaccination['first_dose_date_taken'] ?? null) : 'Pending') ?></td>
                        </tr>
                        <tr>
                            <td>Second Dose</td>
                            <td><?= htmlspecialchars(doseDone($vaccination, 'second_dose') ? doseBatch($vaccination, 'second_dose_batch_no') : 'Pending') ?></td>
                            <td><?= htmlspecialchars(doseDone($vaccination, 'second_dose') ? formatCertDate($vaccination['second_dose_date_taken'] ?? null) : 'Pending') ?></td>
                        </tr>
                        <tr>
                            <td>Final Dose</td>
                            <td><?= htmlspecialchars(doseDone($vaccination, 'final_dose') ? doseBatch($vaccination, 'final_dose_batch_no') : 'Pending') ?></td>
                            <td><?= htmlspecialchars(doseDone($vaccination, 'final_dose') ? formatCertDate($vaccination['final_dose_date_taken'] ?? null) : 'Pending') ?></td>
                        </tr>
                    </tbody>
                </table>

                <section class="cert-bottom">
                    <div class="signature-block">
                        <div class="line-content">
                            <img class="signature-mark" src="assets/img/signature.png" alt="Authorized officer signature">
                        </div>
                        <div class="line">
                            <div class="fine-value">Authorized Officer<br>MegaCare Pharmacy</div>
                        </div>
                    </div>
                    <div class="qr-block">
                        <img src="<?= htmlspecialchars($qrUrl) ?>" alt="Verification QR code">
                    </div>
                    <div class="issued-block">
                        <div class="line-content issued-content">
                            <div class="fine-label">Date Issued</div>
                            <div class="fine-value"><?= date('F j, Y') ?></div>
                        </div>
                        <div class="line-below"></div>
                    </div>
                </section>
            </div>
        </main>
    <?php endif; ?>
</body>
</html>
