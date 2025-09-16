<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

// Pagination setup
$records_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Search functionality
$search_conditions = [];
$search_params = [];
$search_query = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    
    // Check which search type is selected
    $search_type = $_GET['search_type'] ?? 'name'; // Default to name search
    
    switch ($search_type) {
        case 'id':
            $search_conditions[] = "c.id LIKE :search";
            break;
        case 'reason':
            $search_conditions[] = "c.reason LIKE :search";
            break;
        case 'mobile':
            $search_conditions[] = "c.mobile LIKE :search";
            break;
        case 'name':
        default:
            $search_conditions[] = "(c.first_name LIKE :search OR c.last_name LIKE :search)";
            break;
    }
    
    $search_params[':search'] = $search_term;
}

// Build the WHERE clause if we have search conditions
$where_clause = '';
if (!empty($search_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $search_conditions);
}

// Get total count for pagination (with search if applicable)
$count_sql = "SELECT COUNT(*) as total FROM clients c $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($search_params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Handle CSV export (export ALL records, not just current page, with search if applicable)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    if (ob_get_level()) { ob_end_clean(); }
    header('Content-Description: File Transfer');
    header('Content-Type: application/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=clients_export_' . date('Y-m-d') . '.csv');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Name','DOB','Reason','Email','Mobile','Job Title','Company','Location','Registered']);

    // Export ALL records (no LIMIT for CSV export) with search if applicable
    $export_sql = "SELECT * FROM clients c $where_clause ORDER BY c.created_at DESC";
    $export_stmt = $pdo->prepare($export_sql);
    $export_stmt->execute($search_params);
    
    while ($client = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $client['id'],
            $client['first_name'] . ' ' . $client['last_name'],
            $client['selected_date'],
            $client['reason'],
            $client['email'],
            $client['mobile'],
            $client['job_title'],
            $client['company'],
            $client['city'] . ', ' . $client['country'],
            date('Y-m-d H:i:s', strtotime($client['created_at']))
        ]);
    }
    fclose($output);
    exit();
}

// Fetch paginated clients with additional form data (PDO) and search if applicable
$query = "SELECT c.*,
                 (SELECT COUNT(*) FROM training_forms tf WHERE tf.client_id = c.id) AS has_training,
                 (SELECT COUNT(*) FROM employment_forms ef WHERE ef.client_id = c.id) AS has_employment
          FROM clients c
          $where_clause
          ORDER BY c.created_at DESC 
          LIMIT :offset, :limit";
$stmt = $pdo->prepare($query);

// Bind search parameters if they exist
foreach ($search_params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current search values for the form
$current_search = $_GET['search'] ?? '';
$current_search_type = $_GET['search_type'] ?? 'name';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin View - Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-responsive { overflow-x: auto; }
        .qr-modal img { max-width: 100%; height: auto; }
        .action-btns { white-space: nowrap; }
        .export-btn-container { float: right; margin-bottom: 20px; }
        .pagination-info { 
            color: #666; 
            font-size: 14px; 
            margin-bottom: 15px; 
        }
        .pagination .page-link {
            color: #0d6efd;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .search-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a class="btn btn-sm btn-outline-secondary" href="dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
            </div>
            <div>
                <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
                    <a class="btn btn-sm btn-outline-secondary me-2" href="users.php"><i class="fas fa-users-cog"></i> Users</a>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary me-2" href="change_password.php"><i class="fas fa-key"></i> Change Password</a>
                <a class="btn btn-sm btn-outline-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Clients Management</h2>
            <div class="export-btn-container">
               <button class="btn btn-success" id="exportCsvBtn">
                  <i class="fas fa-file-export"></i> Export All as CSV
               </button>
            </div>
        </div>
        
        <!-- Search Form -->
        <div class="search-container">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($current_search) ?>" 
                           placeholder="Enter search term...">
                </div>
                <div class="col-md-3">
                    <label for="search_type" class="form-label">Search By</label>
                    <select class="form-select" id="search_type" name="search_type">
                        <option value="name" <?= $current_search_type === 'name' ? 'selected' : '' ?>>Name</option>
                        <option value="id" <?= $current_search_type === 'id' ? 'selected' : '' ?>>ID</option>
                        <option value="reason" <?= $current_search_type === 'reason' ? 'selected' : '' ?>>Reason</option>
                        <option value="mobile" <?= $current_search_type === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($current_search)): ?>
                        <a href="?" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Pagination Info -->
        <div class="pagination-info">
            Showing <?= ($offset + 1) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
            <?php if (!empty($current_search)): ?>
                <span class="badge bg-info">Filtered results</span>
            <?php endif; ?>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>DOB</th>
                        <th>Reason</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= htmlspecialchars($client['id']) ?></td>
                        <td><?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?></td>
                        <td><?= htmlspecialchars($client['selected_date']) ?></td>
                        <td><?= htmlspecialchars($client['reason']) ?></td>
                        <td><?= htmlspecialchars($client['email']) ?></td>
                        <td><?= htmlspecialchars($client['mobile']) ?></td>
                        <td><?= htmlspecialchars($client['job_title']) ?></td>
                        <td><?= htmlspecialchars($client['company']) ?></td>
                        <td><?= htmlspecialchars($client['city'] . ', ' . $client['country']) ?></td>
                        <td><?= date('M j, Y g:i a', strtotime($client['created_at'])) ?></td>

                        <td class="action-btns">
                            <?php if (!empty($client['qr_code_path'])): ?>
                                <a href="#" onclick="openAndPrintQr('<?= htmlspecialchars($client['qr_code_path']) ?>', '<?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>')" class="btn btn-sm btn-info me-1">
                                    Print QR
                                </a>
                            <?php else: ?>
                                <span class="text-muted me-1"></span>
                            <?php endif; ?>
                            
                            <?php if ($client['has_training'] > 0): ?>
                                <button class="btn btn-sm btn-success me-1" onclick="viewTrainingForm('<?= htmlspecialchars($client['id'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-graduation-cap"></i> Training
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($client['has_employment'] > 0): ?>
                                <button class="btn btn-sm btn-warning me-1" onclick="viewEmploymentForm('<?= htmlspecialchars($client['id'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-briefcase"></i> Employment
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (count($clients) == 0): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <?php if (!empty($current_search)): ?>
                                No clients found matching your search criteria.
                            <?php else: ?>
                                No clients found
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Clients pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous Button -->
                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <!-- Page Numbers -->
                <?php 
                // Calculate page range to show
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                // Show first page if we're not near the beginning
                if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Current page range -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Show last page if we're not near the end -->
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                    </li>
                <?php endif; ?>

                <!-- Next Button -->
                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Jump -->
        <div class="d-flex justify-content-center mt-3">
            <form method="GET" class="d-flex align-items-center">
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key !== 'page'): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <label for="page" class="me-2">Go to page:</label>
                <input type="number" name="page" id="page" class="form-control form-control-sm" 
                       style="width: 80px;" min="1" max="<?= $total_pages ?>" value="<?= $current_page ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary ms-2">Go</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Records Summary -->
        <div class="text-center mt-3 text-muted">
            <small>Page <?= $current_page ?> of <?= $total_pages ?> (<?= $total_records ?> total records)</small>
        </div>
    </div>

<!-- Training Form View Modal -->
<div class="modal fade" id="trainingViewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Training Form Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="trainingFormContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Employment Form View Modal -->
<div class="modal fade" id="employmentViewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employment Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="employmentFormContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center qr-modal">
                <img id="modalQrImage" src="" alt="QR Code" class="img-fluid mb-3">
                <p id="qrClientName" class="fw-bold"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
    
// Download QR Code Functionality
function openAndPrintQr(qrPath, clientName) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print QR Code - ${clientName}</title>
            <style>
                body { text-align: center; padding: 20px; font-family: Arial, sans-serif; }
                img { max-width: 100%; height: auto; margin: 20px 0; }
                h2 { margin-bottom: 10px; }
                p { margin-top: 0; }
            </style>
        </head>
        <body>
            <h2>${clientName}</h2>
            <img src="${qrPath}">
            <p>Scan this QR code for registration details</p>
            <script>
                window.onload = function() { window.print(); window.close(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// View training form
function viewTrainingForm(clientId) {
    fetch(`api/get_training_form.php?client_id=${encodeURIComponent(clientId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const content = document.getElementById('trainingFormContent');
                content.innerHTML = formatTrainingForm(data.form);
                new bootstrap.Modal(document.getElementById('trainingViewModal')).show();
            } else {
                alert('Training form not found for this client.');
            }
        })
        .catch(err => {
            console.error('Error fetching training form:', err);
            alert('Failed to load training form.');
        });
}

// View employment form  
function viewEmploymentForm(clientId) {
    fetch(`api/get_employment_form.php?client_id=${encodeURIComponent(clientId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const content = document.getElementById('employmentFormContent');
                content.innerHTML = formatEmploymentForm(data.form);
                new bootstrap.Modal(document.getElementById('employmentViewModal')).show();
            } else {
                alert('Employment form not found for this client.');
            }
        })
        .catch(err => {
            console.error('Error fetching employment form:', err);
            alert('Failed to load employment form.');
        });
}

// Format training form for display
function formatTrainingForm(form) {
    let areas = [];
    try {
        areas = form.training_areas ? JSON.parse(form.training_areas) : [];
    } catch (e) {
        areas = [];
    }
    return `
        <div class="row">
            <div class="col-12"><h6>Section A: Candidate Information</h6></div>
            <div class="col-md-6"><strong>Full Name:</strong> ${form.full_name || 'N/A'}</div>
            <div class="col-md-6"><strong>Date of Birth:</strong> ${form.date_of_birth || 'N/A'}</div>
            <div class="col-md-6"><strong>Age:</strong> ${form.age || 'N/A'}</div>
            <div class="col-md-6"><strong>Gender:</strong> ${form.gender || 'N/A'}</div>
            <div class="col-md-6"><strong>Phone:</strong> ${form.phone_number || 'N/A'}</div>
            <div class="col-md-6"><strong>Email:</strong> ${form.email || 'N/A'}</div>
            <div class="col-12"><strong>Address:</strong> ${form.contact_address || 'N/A'}</div>
            <div class="col-12"><strong>Next of Kin:</strong> ${form.next_of_kin || 'N/A'}</div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12"><h6>Section B: Educational Background</h6></div>
            <div class="col-md-6"><strong>Qualification:</strong> ${form.highest_qualification || 'N/A'}</div>
            <div class="col-md-6"><strong>Institution:</strong> ${form.institution_attended || 'N/A'}</div>
            <div class="col-md-6"><strong>Graduation Year:</strong> ${form.year_of_graduation || 'N/A'}</div>
            <div class="col-md-6"><strong>Certifications:</strong> ${form.relevant_certifications || 'N/A'}</div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12"><h6>Section C: Training Details</h6></div>
            <div class="col-md-6"><strong>Start Date:</strong> ${form.training_start_date || 'N/A'}</div>
            <div class="col-md-6"><strong>Completion Date:</strong> ${form.expected_completion_date || 'N/A'}</div>
            <div class="col-12"><strong>Training Areas:</strong> ${areas.length > 0 ? areas.join(', ') : 'N/A'}</div>
            ${form.other_area ? `<div class="col-12"><strong>Other Area:</strong> ${form.other_area}</div>` : ''}
        </div>
    `;
}

// Format employment form for display
function formatEmploymentForm(form) {
    return `
        <div class="row">
            <div class="col-12"><h6>Personal Information</h6></div>
            <div class="col-md-6"><strong>Full Name:</strong> ${form.full_name || 'N/A'}</div>
            <div class="col-md-6"><strong>Date of Birth:</strong> ${form.date_of_birth || 'N/A'}</div>
            <div class="col-md-6"><strong>Age:</strong> ${form.age || 'N/A'}</div>
            <div class="col-md-6"><strong>Gender:</strong> ${form.gender || 'N/A'}</div>
            <div class="col-md-6"><strong>Phone:</strong> ${form.phone_number || 'N/A'}</div>
            <div class="col-md-6"><strong>Email:</strong> ${form.email || 'N/A'}</div>
            <div class="col-12"><strong>Address:</strong> ${form.contact_address || 'N/A'}</div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12"><h6>Employment Details</h6></div>
            <div class="col-md-6"><strong>Position Applied:</strong> ${form.position_applied || 'N/A'}</div>
            <div class="col-md-6"><strong>Experience Years:</strong> ${form.experience_years || 'N/A'}</div>
            <div class="col-md-6"><strong>Previous Employer:</strong> ${form.previous_employer || 'N/A'}</div>
            <div class="col-md-6"><strong>Expected Salary:</strong> ${form.expected_salary || 'N/A'}</div>
            <div class="col-md-6"><strong>Availability Date:</strong> ${form.availability_date || 'N/A'}</div>
            <div class="col-12"><strong>Qualifications:</strong> ${form.qualifications || 'N/A'}</div>
            <div class="col-12"><strong>Skills:</strong> ${form.skills || 'N/A'}</div>
            <div class="col-12"><strong>References:</strong> ${form.references || 'N/A'}</div>
        </div>
    `;
}

// CSV Export functionality (server-side for all records)
document.getElementById('exportCsvBtn').addEventListener('click', function() {
    if (confirm('Export all <?= $total_records ?> records to CSV?')) {
        // Preserve search parameters in export
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = '?' + params.toString();
    }
});
</script>

</body>
</html>