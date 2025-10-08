<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/config.php';

$user_id = $_SESSION['user_id'];
$user = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's certificates
$certificates = [];
$stmt = $conn->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'pending' => 0,
    'processing' => 0,
    'issued' => 0,
    'rejected' => 0
];

foreach ($certificates as $cert) {
    if (isset($stats[$cert['status']])) {
        $stats[$cert['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Certificates - Dagupan City E-Services</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <nav>
        <div class="nav-content">
            <div class="logo-section">
                <div class="logo-placeholder"></div>
                <div>
                    <h1>Dagupan City E-Services</h1>
                    <p>Digital Government Platform</p>
                </div>
            </div>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="permits.php">e-Permits</a>
                <a href="complaints.php">Report Issue</a>
                <a href="certificates.php" class="active">e-Certificates</a>
            </div>
            <div class="user-section">
                <span class="notification-icon">🔔</span>
                <span class="user-icon">👤</span>
                <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="page-header">
            <div>
                <h2>e-Certificates</h2>
                <p>Request barangay and city-level certificates</p>
            </div>
            <a href="certificates_request.php" class="btn-primary">Request New Certificate</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-icon yellow">⏳</div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['processing']; ?></h3>
                    <p>Processing</p>
                </div>
                <div class="stat-icon blue">🔄</div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['issued']; ?></h3>
                    <p>Issued</p>
                </div>
                <div class="stat-icon green">✅</div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['rejected']; ?></h3>
                    <p>Rejected</p>
                </div>
                <div class="stat-icon red">❌</div>
            </div>
        </div>

        <div class="content-card">
            <h3>Your Certificate Requests</h3>
            
            <?php if (empty($certificates)): ?>
                <div class="empty-state">
                    <p>📜 No certificate requests yet</p>
                    <a href="certificates_request.php" class="btn-primary">Request Your First Certificate</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference No.</th>
                                <th>Certificate Type</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Valid Until</th>
                                <th>Date Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $cert): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cert['reference_number']); ?></strong></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $cert['certificate_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($cert['purpose']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $cert['status']; ?>">
                                            <?php echo ucfirst($cert['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $cert['valid_until'] ? date('M d, Y', strtotime($cert['valid_until'])) : 'N/A'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($cert['created_at'])); ?></td>
                                    <td>
                                        <a href="certificates_view.php?id=<?php echo $cert['cert_id']; ?>" class="btn-small">View</a>
                                        <?php if ($cert['status'] === 'issued'): ?>
                                            <a href="certificates_download.php?id=<?php echo $cert['cert_id']; ?>" class="btn-small btn-success">Download</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>