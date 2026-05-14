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
    return $value ? date('F j, Y', strtotime($value)) : 'Not recorded';
}

$completedDoses = (int)doseDone($vaccination, 'first_dose') + (int)doseDone($vaccination, 'second_dose') + (int)doseDone($vaccination, 'final_dose');
$isComplete = $completedDoses === 3;
$displayVaccineType = $vaccination['vaccination_type'] ?? $registrant['reason'] ?? '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/megacare_phamacy/vaccination_certificate.php';
$verificationUrl = $registrant
    ? $scheme . '://' . $host . $scriptPath . '?id=' . rawurlencode($registrant['id'])
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Certificate - MegaCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand: #1a9bb5;
            --brand-dark: #1a3a5c;
            --brand-mid: #137f96;
            --brand-light: #e6f5f9;
            --line: #b7d7e1;
            --ink: #1f2937;
            --muted: #64748b;
            --soft: #f8fafc;
        }
        body {
            background: linear-gradient(180deg, #f8fbfd 0%, #eef4f7 100%);
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .topbar {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-mid), var(--brand));
            color: #fff;
            padding: 24px 0;
        }
        .lookup-card,
        .certificate {
            background: #fff;
            border: 1px solid rgba(26, 155, 181, .16);
            border-radius: 8px;
            box-shadow: 0 16px 40px rgba(26, 58, 92, .10);
        }
        .lookup-card {
            max-width: 680px;
            margin: 48px auto;
            padding: 28px;
        }
        .certificate {
            max-width: 980px;
            margin: 28px auto 48px;
            padding: 18px;
        }
        .cert-frame {
            min-height: 760px;
            border: 8px double var(--brand-dark);
            padding: 28px;
            position: relative;
            background:
                linear-gradient(135deg, rgba(230,245,249,.9), transparent 24%),
                linear-gradient(315deg, rgba(232,237,244,.95), transparent 24%),
                #fff;
        }
        .cert-frame::before {
            content: "";
            position: absolute;
            inset: 14px;
            border: 1px solid var(--line);
            pointer-events: none;
        }
        .cert-header {
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 8px 24px 20px;
            border-bottom: 2px solid var(--line);
        }
        .brand-mark {
            width: 150px;
            min-height: 78px;
            border-radius: 10px;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            box-shadow: 0 10px 28px rgba(26, 58, 92, .12);
            margin: 0 auto 14px;
            padding: 10px 14px;
        }
        .brand-mark img {
            display: block;
            max-width: 126px;
            max-height: 64px;
            object-fit: contain;
        }
        .cert-kicker {
            color: var(--brand-mid);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .2em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .cert-title {
            color: var(--brand-dark);
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(34px, 5vw, 58px);
            font-weight: 700;
            line-height: 1;
            margin: 0;
            letter-spacing: .02em;
        }
        .cert-subtitle {
            color: var(--muted);
            font-size: 17px;
            margin-top: 10px;
        }
        .cert-body {
            padding: 28px 24px 14px;
            position: relative;
            z-index: 1;
        }
        .attestation {
            color: var(--muted);
            font-size: 17px;
            line-height: 1.75;
            margin: 0 auto 18px;
            max-width: 780px;
            text-align: center;
        }
        .patient-name {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 700;
            color: var(--brand-dark);
            margin: 8px auto 10px;
            text-align: center;
            border-bottom: 2px solid var(--line);
            max-width: 720px;
            padding-bottom: 8px;
        }
        .patient-id {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--soft);
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 8px 14px;
            color: var(--muted);
            font-family: Consolas, "Courier New", monospace;
        }
        .cert-summary {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 700;
        }
        .status-complete {
            background: #e6f5f9;
            color: #137f96;
        }
        .status-progress {
            background: #e8edf4;
            color: #1a3a5c;
        }
        .meta-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            background: rgba(255,255,255,.78);
        }
        .meta-row strong {
            color: var(--muted);
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 22px 0 24px;
        }
        .dose-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 12px;
            border: 1px solid var(--line);
        }
        .dose-table th {
            background: linear-gradient(180deg, #e6f5f9 0%, #d9eef4 100%);
            color: var(--brand-dark);
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 12px;
            border: 1px solid var(--line);
        }
        .dose-table td {
            padding: 13px 12px;
            border: 1px solid var(--line);
            color: var(--ink);
            vertical-align: top;
        }
        .dose-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: var(--brand-dark);
        }
        .seal-row {
            display: grid;
            grid-template-columns: 1fr 160px 1fr;
            gap: 22px;
            align-items: end;
            margin-top: 36px;
        }
        .signature-line {
            border-top: 2px solid var(--brand-dark);
            padding-top: 8px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }
        .seal {
            width: 142px;
            height: 142px;
            border-radius: 999px;
            border: 4px double var(--brand-dark);
            display: grid;
            place-items: center;
            text-align: center;
            color: var(--brand-dark);
            font-weight: 800;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: radial-gradient(circle, #fff 0%, #fff 50%, var(--brand-light) 51%, #d9eef4 100%);
            margin: 0 auto;
        }
        .cert-footer {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            position: relative;
            z-index: 1;
        }
        .verification-link {
            display: block;
            margin-top: 6px;
            color: var(--brand-dark);
            font-weight: 700;
            overflow-wrap: anywhere;
            text-decoration: none;
        }
        .verification-link:hover {
            color: var(--brand-mid);
            text-decoration: underline;
        }
        .actions {
            max-width: 920px;
            margin: 24px auto 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-brand {
            background: linear-gradient(180deg, #2fb0c8 0%, #1a9bb5 58%, #137f96 100%);
            border-color: #137f96;
            color: #fff;
        }
        .btn-brand:hover {
            background: linear-gradient(180deg, #259fb7 0%, #168da5 58%, #116f83 100%);
            border-color: #116f83;
            color: #fff;
        }
        @media (max-width: 768px) {
            .cert-header {
                text-align: center;
            }
            .detail-grid,
            .seal-row,
            .meta-row {
                grid-template-columns: 1fr;
            }
            .cert-frame {
                padding: 16px;
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
                margin: 0;
            }
            .certificate {
                box-shadow: none;
                margin: 0;
                max-width: none;
                padding: 0;
                border: none;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .cert-frame {
                box-sizing: border-box;
                width: 194mm;
                height: 281mm;
                min-height: 0;
                padding: 12mm 11mm 8mm;
                border-width: 5px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .cert-frame::before {
                inset: 9px;
            }
            .cert-header {
                padding: 4px 18px 12px;
            }
            .brand-mark {
                width: 120px;
                min-height: 58px;
                padding: 7px 10px;
                margin-bottom: 8px;
            }
            .brand-mark img {
                max-width: 100px;
                max-height: 46px;
            }
            .cert-kicker {
                font-size: 10px;
                margin-bottom: 5px;
            }
            .cert-title {
                font-size: 38px;
            }
            .cert-subtitle {
                font-size: 14px;
                margin-top: 6px;
            }
            .cert-body {
                padding: 16px 14px 8px;
                flex: 1 1 auto;
            }
            .attestation {
                font-size: 14px;
                line-height: 1.5;
                margin-bottom: 10px;
            }
            .patient-name {
                font-size: 34px;
                margin: 4px auto 8px;
                padding-bottom: 6px;
            }
            .cert-summary {
                margin-bottom: 14px;
            }
            .detail-grid {
                gap: 7px;
                margin: 14px 0 14px;
            }
            .meta-row {
                padding: 8px 10px;
                font-size: 12px;
            }
            .dose-table {
                margin-top: 8px;
                font-size: 12px;
            }
            .dose-table th {
                padding: 8px;
                font-size: 10px;
            }
            .dose-table td {
                padding: 8px;
            }
            .seal-row {
                margin-top: 22px;
                gap: 16px;
            }
            .seal {
                width: 104px;
                height: 104px;
                font-size: 10px;
            }
            .signature-line,
            .cert-footer {
                font-size: 11px;
            }
            .cert-footer {
                flex: 0 0 auto;
                margin-top: 8px;
                padding-top: 8px;
                font-size: 10px;
                line-height: 1.35;
            }
            .verification-link {
                display: block;
                max-width: 100%;
                margin-top: 3px;
                color: var(--brand-dark) !important;
                font-size: 9px;
                line-height: 1.25;
                overflow-wrap: anywhere;
                word-break: break-word;
                text-decoration: none;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-certificate me-2"></i>Vaccination Certificate</h1>
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
            <div class="cert-frame">
                <section class="cert-header">
                    <div class="brand-mark">
                        <img src="assets/img/logo.png" alt="MegaCare Pharmacy logo">
                    </div>
                    <div class="cert-kicker">MegaCare Pharmacy</div>
                    <h2 class="cert-title">Certificate of Vaccination</h2>
                    <div class="cert-subtitle">Official Patient Vaccination Record</div>
                </section>

                <section class="cert-body">
                    <p class="attestation">
                        This is to certify that the individual named below is registered with MegaCare Pharmacy and has the following vaccination record on file.
                    </p>

                    <div class="patient-name"><?= htmlspecialchars($registrant['first_name'] . ' ' . $registrant['last_name']) ?></div>

                    <div class="cert-summary">
                        <div class="patient-id"><i class="fas fa-barcode"></i><?= htmlspecialchars($registrant['id']) ?></div>
                        <div class="status-pill <?= $isComplete ? 'status-complete' : 'status-progress' ?>">
                            <i class="fas <?= $isComplete ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                            <?= $isComplete ? 'Vaccination Complete' : $completedDoses . '/3 Doses Recorded' ?>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div class="meta-row"><strong>Vaccine Type</strong><span><?= htmlspecialchars($displayVaccineType) ?></span></div>
                        <div class="meta-row"><strong>Date of Birth</strong><span><?= htmlspecialchars($registrant['selected_date']) ?></span></div>
                        <div class="meta-row"><strong>Registration Date</strong><span><?= date('F j, Y', strtotime($registrant['created_at'])) ?></span></div>
                        <div class="meta-row"><strong>Location</strong><span><?= htmlspecialchars($registrant['city'] . ', ' . $registrant['country']) ?></span></div>
                    </div>

                    <table class="dose-table">
                        <thead>
                            <tr>
                                <th>Dose</th>
                                <th>Status</th>
                                <th>Vaccine Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>First Dose</td>
                                <td><span class="dose-status"><?= doseDone($vaccination, 'first_dose') ? '<i class="fas fa-check-circle"></i>Completed' : 'Pending' ?></span></td>
                                <td><?= doseDone($vaccination, 'first_dose') ? formatCertDate($vaccination['first_dose_date_taken'] ?? null) : 'Pending' ?></td>
                            </tr>
                            <tr>
                                <td>Second Dose</td>
                                <td><span class="dose-status"><?= doseDone($vaccination, 'second_dose') ? '<i class="fas fa-check-circle"></i>Completed' : 'Pending' ?></span></td>
                                <td><?= doseDone($vaccination, 'second_dose') ? formatCertDate($vaccination['second_dose_date_taken'] ?? null) : 'Pending' ?></td>
                            </tr>
                            <tr>
                                <td>Final Dose</td>
                                <td><span class="dose-status"><?= doseDone($vaccination, 'final_dose') ? '<i class="fas fa-check-circle"></i>Completed' : 'Pending' ?></span></td>
                                <td><?= doseDone($vaccination, 'final_dose') ? formatCertDate($vaccination['final_dose_date_taken'] ?? null) : 'Pending' ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="seal-row">
                        <div class="signature-line">
                            Authorized Officer<br>
                            MegaCare Pharmacy
                        </div>
                        <div class="seal">
                            Official<br>Record<br><?= date('Y') ?>
                        </div>
                        <div class="signature-line">
                            Date Issued<br>
                            <?= date('F j, Y') ?>
                        </div>
                    </div>
                </section>

                <footer class="cert-footer">
                    <strong>Verification:</strong> <?= htmlspecialchars($registrant['id']) ?> &nbsp; | &nbsp;
                    Generated <?= date('F j, Y g:i A') ?><br>
                    <strong>Verify online:</strong>
                    <a class="verification-link" href="<?= htmlspecialchars($verificationUrl) ?>">
                        <?= htmlspecialchars($verificationUrl) ?>
                    </a>
                    This certificate is valid only when the registration number matches MegaCare Pharmacy records.
                </footer>
            </div>
        </main>
    <?php endif; ?>
</body>
</html>
