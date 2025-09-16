<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

// Handle code generation
if (isset($_POST['action']) && $_POST['action'] == 'generate_code') {
    $purpose = $_POST['purpose'];
    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    try {
        // Deactivate previous codes for the same purpose
        $stmt = $pdo->prepare("UPDATE admin_codes SET is_active = 0 WHERE purpose = ?");
        $stmt->execute([$purpose]);
        
        // Generate new code
        $stmt = $pdo->prepare("INSERT INTO admin_codes (code, purpose, is_active) VALUES (?, ?, 1)");
        $stmt->execute([$code, $purpose]);
        
        $success_msg = "New $purpose code generated: $code";
    } catch (PDOException $e) {
        $error_msg = "Error generating code: " . $e->getMessage();
    }
}

// Handle clearing codes
if (isset($_POST['action']) && $_POST['action'] == 'clear_codes') {
    try {
        $stmt = $pdo->prepare("UPDATE admin_codes SET is_active = 0");
        $stmt->execute();
        $success_msg = "All codes cleared successfully";
    } catch (PDOException $e) {
        $error_msg = "Error clearing codes: " . $e->getMessage();
    }
}

// Handle vaccination status update
if (isset($_POST['action']) && $_POST['action'] == 'update_vaccination') {
    $client_id = $_POST['client_id'];
    $first_dose = isset($_POST['first_dose']) ? 1 : 0;
    $second_dose = isset($_POST['second_dose']) ? 1 : 0;
    $final_dose = isset($_POST['final_dose']) ? 1 : 0;
    
    try {
        // Insert or update vaccination status
        $stmt = $pdo->prepare("
            INSERT INTO vaccination_status (client_id, first_dose, second_dose, final_dose)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                first_dose = VALUES(first_dose),
                second_dose = VALUES(second_dose),
                final_dose = VALUES(final_dose)
        ");
        $stmt->execute([$client_id, $first_dose, $second_dose, $final_dose]);
        
        $success_msg = "Vaccination status updated successfully";
    } catch (PDOException $e) {
        $error_msg = "Error updating vaccination status: " . $e->getMessage();
    }
}

// Get current active codes
// Pagination for recent codes (max 20)
$codes_per_page = 10;
$page = isset($_GET['code_page']) ? max(1, intval($_GET['code_page'])) : 1;
$offset = ($page - 1) * $codes_per_page;
$stmt_count = $pdo->query("SELECT COUNT(*) FROM admin_codes");
$total_codes = min(20, (int)$stmt_count->fetchColumn());
$total_pages = ceil($total_codes / $codes_per_page);
$stmt = $pdo->query("SELECT *, CASE WHEN is_active = 1 THEN 'Active' WHEN used_at IS NOT NULL THEN 'Used' ELSE 'Inactive' END as status FROM admin_codes ORDER BY created_at DESC LIMIT 20");
$all_codes = $stmt->fetchAll();
$active_codes = array_slice($all_codes, $offset, $codes_per_page);

// Get all clients for vaccination status management (with search functionality)
// Only fetch clients if a search is performed
$clients = [];
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_param = trim($_GET['search']);
    $search_query = "WHERE c.id LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?";
    $search_value = '%' . $search_param . '%';
    $stmt = $pdo->prepare("
        SELECT c.id, c.first_name, c.last_name, c.email, c.mobile,
               COALESCE(v.first_dose, 0) as first_dose,
               COALESCE(v.second_dose, 0) as second_dose,
               COALESCE(v.final_dose, 0) as final_dose
        FROM clients c 
        LEFT JOIN vaccination_status v ON c.id = v.client_id
        $search_query
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$search_value, $search_value, $search_value, $search_value]);
    $clients = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - MegaCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .code-display {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-family: 'Courier New', monospace;
            border-left: 4px solid #007bff;
        }
        .code-text {
            font-weight: bold;
            font-size: 1.1em;
        }
        .vaccination-status {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .status-complete { background-color: #d4edda; color: #155724; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-none { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1><i class="fas fa-shield-alt"></i> Admin Panel</h1>
            <p>Manage codes and vaccination status</p>
            <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                <a class="btn btn-outline-light btn-sm" href="dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
                <span class="badge bg-light text-dark me-2"><i class="fas fa-user"></i> <?= htmlspecialchars(current_user()['name']) ?> (<?= htmlspecialchars(current_user()['role']) ?>)</span>
                <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
                    <a class="btn btn-outline-light btn-sm" href="users.php"><i class="fas fa-users-cog"></i> Users</a>
                <?php endif; ?>
                <a class="btn btn-outline-light btn-sm" href="change_password.php"><i class="fas fa-key"></i> Change Password</a>
                <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Code Generation Section -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-key"></i> Generate Access Codes</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="generate_code">
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <select class="form-select" id="purpose" name="purpose" required>
                                    <option value="">Select Purpose</option>
                                    <option value="training">Training</option>
                                    <option value="employment">Employment</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-random"></i> Generate Code
                                </button>
                                <button type="button" class="btn btn-warning" onclick="clearCodes()">
                                    <i class="fas fa-trash"></i> Clear All
                                </button>
                            </div>
                        </form>

                        <!-- Display Active Codes -->
                        <div class="mt-4">
                            <h6>Recent Codes:</h6>
                            <?php if (empty($active_codes)): ?>
                                <p class="text-muted">No codes generated yet.</p>
                            <?php else: ?>
                                <?php foreach ($active_codes as $code): ?>
                                    <div class="code-display d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-<?= $code['status'] == 'Active' ? 'success' : ($code['status'] == 'Used' ? 'warning' : 'secondary') ?> me-2">
                                                <?= ucfirst($code['purpose']) ?>
                                            </span>
                                            <span class="code-text"><?= $code['code'] ?></span>
                                        </div>
                                        <small class="text-<?= $code['status'] == 'Active' ? 'success' : ($code['status'] == 'Used' ? 'warning' : 'muted') ?>">
                                            <?= $code['status'] ?>
                                            <?= $code['status'] == 'Used' && $code['used_at'] ? '<br><small>' . date('M j, g:i A', strtotime($code['used_at'])) . '</small>' : '' ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                                <!-- Pagination -->
                                <nav aria-label="Recent codes pagination" class="mt-2">
                                    <ul class="pagination pagination-sm justify-content-center">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                                <a class="page-link" href="?code_page=<?= $i ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vaccination Status Management -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-syringe"></i> Vaccination Status</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="startBarcodeScanner()">
                            <i class="fas fa-qrcode"></i> Scan QR
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Search Form -->
                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by ID, name, or scan QR code..." 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                       id="searchInput">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($_GET['search'])): ?>
                                    <a href="admin_panel.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="form-text mt-1">
                                Tip: Handheld barcode scanners are supported. Ensure the page is focused, then scan — search will auto-run.
                            </div>
                        </form>

                        <!-- QR Scanner Modal Trigger (Hidden) -->
                        <div id="qrScannerModal" class="modal fade" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Scan Patient QR Code</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="qr-reader" style="width: 100%; height: 300px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <?php if (isset($_GET['search']) && !empty(trim($_GET['search']))): ?>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Client</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($clients)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    No clients found matching your search.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($clients as $client): ?>
                                            <tr>
                                                <td><small><?= htmlspecialchars($client['id']) ?></small></td>
                                                <td><?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?></td>
                                                <td>
                                                    <small>
                                                        <?= htmlspecialchars($client['email']) ?><br>
                                                        <?= htmlspecialchars($client['mobile']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $total = $client['first_dose'] + $client['second_dose'] + $client['final_dose'];
                                                    if ($total == 3) echo '<span class="vaccination-status status-complete">Complete</span>';
                                                    elseif ($total > 0) echo '<span class="vaccination-status status-partial">Partial (' . $total . '/3)</span>';
                                                    else echo '<span class="vaccination-status status-none">None</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="openVaccinationModal('<?= htmlspecialchars($client['id']) ?>', '<?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>', <?= $client['first_dose'] ?>, <?= $client['second_dose'] ?>, <?= $client['final_dose'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-muted text-center">Search for a client by ID or name to view details.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vaccination Status Modal -->
    <div class="modal fade" id="vaccinationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Vaccination Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_vaccination">
                        <input type="hidden" name="client_id" id="modal_client_id">
                        
                        <h6 id="modal_client_name"></h6>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="first_dose" id="first_dose">
                            <label class="form-check-label" for="first_dose">
                                First Dose <i class="fas fa-check text-success"></i>
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="second_dose" id="second_dose">
                            <label class="form-check-label" for="second_dose">
                                Second Dose <i class="fas fa-check text-success"></i>
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="final_dose" id="final_dose">
                            <label class="form-check-label" for="final_dose">
                                Final Dose (Vaccination Complete) <i class="fas fa-check text-success"></i>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- QR Scanner Modal -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scan Patient ID Barcode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qr-reader" style="width: 100%;"></div>
                    <p class="text-muted mt-3">Position the barcode/QR code within the scanner area</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopScanner()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrCode = null;

        // Handle scanned code from either camera or handheld scanner
        function handleScannedCode(raw) {
            if (!raw) return;
            let code = String(raw).trim();
            // Try to extract ID from URL-like strings
            try {
                const u = new URL(code);
                const id = u.searchParams.get('id');
                if (id) code = id;
                else {
                    const last = u.pathname.split('/').pop();
                    if (last) code = last;
                }
            } catch (e) {
                const m = code.match(/id=([^&]+)/i);
                if (m) code = decodeURIComponent(m[1]);
            }
            const input = document.getElementById('searchInput');
            if (input && input.form) {
                input.value = code;
                input.form.submit();
            }
        }

        // Keyboard-wedge barcode scanner support (captures fast key streams ending with Enter)
        (function enableKeyboardScanner(){
            let buffer = '';
            let lastTime = 0;
            const MAX_INTERVAL = 50; // ms between keystrokes to consider as scanner
            const CLEAR_DELAY = 500; // clear buffer if idle
            let clearTimer = null;

            window.addEventListener('keydown', (e) => {
                const t = e.target;
                const isTyping = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable);
                if (isTyping) return; // don't interfere with normal typing in inputs

                const now = Date.now();
                if (now - lastTime > CLEAR_DELAY) buffer = '';
                lastTime = now;

                if (e.key === 'Enter') {
                    if (buffer.length >= 3) {
                        handleScannedCode(buffer);
                    }
                    buffer = '';
                    if (clearTimer) { clearTimeout(clearTimer); clearTimer = null; }
                    return;
                }
                if (e.key.length === 1) {
                    // If interval is too large, treat as new sequence
                    if (buffer && (now - lastTime) > MAX_INTERVAL) buffer = '';
                    buffer += e.key;
                    if (clearTimer) clearTimeout(clearTimer);
                    clearTimer = setTimeout(() => { buffer = ''; }, CLEAR_DELAY);
                }
            }, true);
        })();

    function clearCodes() {
            if (confirm('Are you sure you want to clear all generated codes? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'clear_codes');
                
                fetch('admin_panel.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to clear codes. Please try again.');
                });
            }
        }

        function startBarcodeScanner() {
            const qrScannerModal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
            qrScannerModal.show();
            
            html5QrCode = new Html5Qrcode("qr-reader");
            
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                (decodedText, decodedResult) => {
                    // Process the scanned text
                    document.querySelector('input[name="search"]').value = decodedText;
                    stopScanner();
                    qrScannerModal.hide();
                    // Submit the search form
                    document.querySelector('form').submit();
                },
                (errorMessage) => {
                    // Handle scan failure, usually better to ignore these
                }
            ).catch(err => {
                console.error("Unable to start scanning", err);
                alert("Camera access required for barcode scanning");
            });
        }

        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    html5QrCode = null;
                }).catch(err => {
                    console.error("Unable to stop scanning", err);
                });
            }
        }
        function openVaccinationModal(clientId, clientName, firstDose, secondDose, finalDose) {
            document.getElementById('modal_client_id').value = clientId;
            document.getElementById('modal_client_name').textContent = clientName;
            document.getElementById('first_dose').checked = firstDose == 1;
            document.getElementById('second_dose').checked = secondDose == 1;
            document.getElementById('final_dose').checked = finalDose == 1;
            
            new bootstrap.Modal(document.getElementById('vaccinationModal')).show();
        }
    </script>
</body>
</html>
