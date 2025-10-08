<?php
session_start();

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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permit_type = $_POST['permit_type'];
    $applicant_name = $_POST['applicant_name'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $business_name = $_POST['business_name'] ?? '';
    $address = $_POST['address'];
    $description = $_POST['description'];
    
    // Generate reference number
    $prefix = strtoupper(substr($permit_type, 0, 2));
    $reference_number = $prefix . 'P-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("INSERT INTO permits (user_id, reference_number, permit_type, business_name, business_address, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("issss", $user_id, $reference_number, $permit_type, $business_name, $address);
    
    if ($stmt->execute()) {
        $success = "Application submitted successfully! Reference Number: " . $reference_number;
    } else {
        $error = "Failed to submit application. Please try again.";
    }
}

// Get user's permits for Track Applications tab
$permits = [];
$stmt = $conn->prepare("SELECT * FROM permits WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$permits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Permits - Dagupan City E-Services</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/forms.css">
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
                <a href="permits.php" class="active">e-Permits</a>
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
        <div class="form-container">
            <div class="form-header">
                <h2>Welcome to Dagupan e-Permit System</h2>
                <p>Apply for business, event, and construction permits online</p>
            </div>

            <div class="form-tabs">
                <button class="tab-btn active" onclick="showTab('apply')">Apply for Permit</button>
                <button class="tab-btn" onclick="showTab('track')">Track Applications</button>
                <button class="tab-btn" onclick="showTab('download')">Download Permits</button>
            </div>

            <!-- Apply for Permit Tab -->
            <div id="apply-tab" class="tab-content active">
                <?php if ($success): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="application-form">
                    <div class="form-section">
                        <h3>New Permit Application</h3>
                        <p class="section-subtitle">Submit your permit application online</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="permit_type">Permit Type</label>
                                <select id="permit_type" name="permit_type" required>
                                    <option value="">Select permit type</option>
                                    <option value="business">Business Permit</option>
                                    <option value="event">Event Permit</option>
                                    <option value="construction">Construction Permit</option>
                                    <option value="fishing">Fishing Permit</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="applicant_name">Applicant Name</label>
                                <input type="text" id="applicant_name" name="applicant_name" placeholder="Enter full name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number" placeholder="0912-345-6789" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="email@example.com" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="business_name">Business/Event/Project Name</label>
                            <input type="text" id="business_name" name="business_name" placeholder="Enter name" required>
                        </div>

                        <div class="form-group">
                            <label for="address">Address/Location</label>
                            <textarea id="address" name="address" rows="3" placeholder="Enter complete address" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="description">Description/Purpose</label>
                            <textarea id="description" name="description" rows="4" placeholder="Provide details about your application" required></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Required Documents</h3>
                        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                            <div class="upload-icon">📄</div>
                            <p>Drag and drop files here or click to browse</p>
                            <small>PDF, JPG, PNG files up to 10MB</small>
                            <input type="file" id="fileInput" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-secondary">Clear Form</button>
                        <button type="submit" class="btn-primary">Submit Application</button>
                    </div>
                </form>
            </div>

            <!-- Track Applications Tab -->
            <div id="track-tab" class="tab-content" style="display: none;">
                <div class="application-form">
                    <h3>Your Permit Applications</h3>
                    <?php if (empty($permits)): ?>
                        <div class="empty-state">
                            <p>📄 No permit applications yet</p>
                            <button class="btn-primary" onclick="showTab('apply')">Apply for Your First Permit</button>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Reference No.</th>
                                        <th>Permit Type</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                        <th>Date Applied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($permits as $permit): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($permit['reference_number']); ?></strong></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $permit['permit_type'])); ?></td>
                                            <td><?php echo htmlspecialchars($permit['business_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $permit['status']; ?>">
                                                    <?php echo ucfirst($permit['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($permit['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Download Permits Tab -->
            <div id="download-tab" class="tab-content" style="display: none;">
                <div class="application-form">
                    <h3>Download Approved Permits</h3>
                    <?php
                    $approved = array_filter($permits, function($p) { return $p['status'] === 'approved'; });
                    ?>
                    <?php if (empty($approved)): ?>
                        <div class="empty-state">
                            <p>📥 No approved permits available for download</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Reference No.</th>
                                        <th>Permit Type</th>
                                        <th>Date Approved</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved as $permit): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($permit['reference_number']); ?></strong></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $permit['permit_type'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($permit['approved_at'] ?? $permit['updated_at'])); ?></td>
                                            <td>
                                                <a href="#" class="btn-small btn-success">Download PDF</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').style.display = 'block';
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>