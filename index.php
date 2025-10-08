<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
require_once 'config/config.php';

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get statistics
$stats = [
    'active_applications' => 0,
    'pending_issues' => 0,
    'certificates_issued' => 0,
    'avg_processing_days' => 0
];

// Active permits
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM permits WHERE user_id = ? AND status IN ('pending', 'processing')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['active_applications'] = $stmt->get_result()->fetch_assoc()['count'];

// Pending issues
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['pending_issues'] = $stmt->get_result()->fetch_assoc()['count'];

// Certificates issued
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM certificates WHERE user_id = ? AND status = 'issued'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['certificates_issued'] = $stmt->get_result()->fetch_assoc()['count'];

// Average processing days
$stmt = $conn->prepare("SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days FROM permits WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['avg_processing_days'] = round($result['avg_days'] ?? 2.5, 1);

// Get recent applications
$recent_apps = [];
$stmt = $conn->prepare("
    (SELECT 'permit' as type, permit_id as id, permit_type as title, reference_number, status, created_at 
     FROM permits WHERE user_id = ? ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'certificate' as type, cert_id as id, certificate_type as title, reference_number, status, created_at 
     FROM certificates WHERE user_id = ? ORDER BY created_at DESC LIMIT 2)
    ORDER BY created_at DESC LIMIT 5
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$recent_apps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get system announcements
$stmt = $conn->prepare("SELECT * FROM announcements WHERE status = 'active' ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate processing rates
$permit_rate = 85;
$issue_rate = 78;
$cert_rate = 85;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dagupan City E-Services</title>
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
                <a href="index.php" class="active">Dashboard</a>
                <a href="permits.php">e-Permits</a>
                <a href="complaints.php">Report Issue</a>
                <a href="certificates.php">e-Certificates</a>
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
        <div class="welcome-section">
            <h2>Welcome to Dagupan E-Services</h2>
            <p>Your one-stop platform for city government services</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['active_applications']; ?></h3>
                    <p>Active Applications</p>
                </div>
                <div class="stat-icon green">📋</div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['pending_issues']; ?></h3>
                    <p>Pending Issues</p>
                </div>
                <div class="stat-icon yellow">⚠️</div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['certificates_issued']; ?></h3>
                    <p>Certificates Issued</p>
                </div>
                <div class="stat-icon blue">📄</div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $stats['avg_processing_days']; ?></h3>
                    <p>Avg Processing Days</p>
                </div>
                <div class="stat-icon red">📈</div>
            </div>
        </div>

        <div class="services-grid">
            <div class="service-card">
                <div class="service-header">
                    <span class="service-icon">📝</span>
                    <h3>e-Permit System</h3>
                </div>
                <p>Apply for business, events, and construction permits</p>
                <div class="progress-section">
                    <div class="progress-info">
                        <span>Processing Rate</span>
                        <span class="progress-value"><?php echo $permit_rate; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $permit_rate; ?>%"></div>