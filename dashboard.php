<?php require_once __DIR__ . '/includes/bootstrap.php'; require_login(); $user = current_user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MegaCare Pharmacy System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="container text-center">
            <h1><i class="fas fa-pills"></i> MegaCare Pharmacy System</h1>
            <p class="lead">Complete Registration and Management System</p>
            <div class="mt-2">
                <span class="badge bg-light text-dark me-2"><i class="fas fa-user"></i> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a class="btn btn-outline-light btn-sm me-2" href="users.php"><i class="fas fa-users-cog"></i> User Management</a>
                <?php endif; ?>
                <a class="btn btn-outline-light btn-sm me-2" href="change_password.php"><i class="fas fa-key"></i> Change Password</a>
                <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container my-5">
        <div class="row g-4">
            <!-- Client Registration -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="feature-icon text-primary">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h5>Client Registration</h5>
                    <p class="text-muted">Register new clients with training and employment forms</p>
                    <a href="index.php" class="btn btn-primary mt-auto">Start Registration</a>
                </div>
            </div>

            <!-- Admin Panel -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="feature-icon text-success">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5>Admin Panel</h5>
                    <p class="text-muted">Generate access codes and manage vaccination status</p>
                    <a href="admin_panel.php" class="btn btn-success mt-auto">Admin Panel</a>
                </div>
            </div>

            <!-- View Clients -->
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="feature-icon text-info">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5>Client Management</h5>
                    <p class="text-muted">View all registered clients and their forms</p>
                    <a href="admin_view.php" class="btn btn-info mt-auto">View Clients</a>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> System Features</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-check-circle text-success"></i> Registration System</h6>
                                <ul>
                                    <li>Main registration form</li>
                                    <li>Training form (with 6-digit code verification)</li>
                                    <li>Employment form (with 6-digit code verification)</li>
                                    <li>QR code generation for each registrant</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-cog text-primary"></i> Admin Features</h6>
                                <ul>
                                    <li>Generate 6-digit access codes</li>
                                    <li>Manage vaccination status (3 doses)</li>
                                    <li>View training and employment forms</li>
                                    <li>Export client data as CSV</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-lightbulb"></i> How it Works:</h6>
                    <ol>
                        <li><strong>Admin generates codes:</strong> Use the Admin Panel to generate 6-digit codes for Training or Employment</li>
                        <li><strong>Client registration:</strong> When clients select "Training" or "Employment" as reason, they must enter the valid code</li>
                        <li><strong>Form completion:</strong> After code verification, they fill the additional form (training/employment)</li>
                        <li><strong>Admin management:</strong> View all submissions and manage vaccination status in Admin Panel</li>
                        <li><strong>QR scanning:</strong> Each registrant gets a QR code that shows their details and vaccination status</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 MegaCare Pharmacy. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
