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
    $reporter_name = $_POST['reporter_name'];
    $contact_number = $_POST['contact_number'];
    $issue_type = $_POST['issue_type'];
    $priority = $_POST['priority'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    
    // Generate reference number
    $reference_number = 'CMP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("INSERT INTO complaints (user_id, reference_number, category, title, description, location, barangay, status, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
    $title = ucfirst(str_replace('_', ' ', $issue_type));
    $barangay = $location; // Simplified for now
    $stmt->bind_param("isssssss", $user_id, $reference_number, $issue_type, $title, $description, $location, $barangay, $priority);
    
    if ($stmt->execute()) {
        $success = "Issue reported successfully! Reference Number: " . $reference_number;
    } else {
        $error = "Failed to submit report. Please try again.";
    }
}

// Get user's complaints
$complaints = [];
$stmt = $conn->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$complaints = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Issue - Dagupan City E-Services</title>
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
                <a href="permits.php">e-Permits</a>
                <a href="complaints.php" class="active">Report Issue</a>
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
                <h2>Citizen Request & Complaint Tracker</h2>
                <p>Report concerns and track progress on city services</p>
            </div>

            <div class="form-tabs">
                <button class="tab-btn active" onclick="showTab('report')">Report Issue</button>
                <button class="tab-btn" onclick="showTab('track')">Track Reports</button>
            </div>

            <!-- Report Issue Tab -->
            <div id="report-tab" class="tab-content active">
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
                        <h3>Report a New Issue</h3>
                        <p class="section-subtitle">Submit your concerns to the appropriate city department</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="reporter_name">Your Name</label>
                                <input type="text" id="reporter_name" name="reporter_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number" placeholder="0912-345-6789" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="issue_type">Issue Type</label>
                                <select id="issue_type" name="issue_type" required>
                                    <option value="">Select issue type</option>
                                    <option value="waste_management">Waste Management</option>
                                    <option value="road_repair">Road Repair</option>
                                    <option value="flood">Flood</option>
                                    <option value="streetlight">Streetlight Issue</option>
                                    <option value="water">Water Supply</option>
                                    <option value="traffic">Traffic</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="priority">Priority Level</label>
                                <select id="priority" name="priority" required>
                                    <option value="">Select priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">Location</label>
                            <div class="location-input">
                                <input type="text" id="location" name="location" placeholder="Enter specific location/address" required>
                                <button type="button" class="location-btn">📍</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description/Purpose</label>
                            <textarea id="description" name="description" rows="5" placeholder="Provide detailed description of the issue" required></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Attach Photo/Evidence</h3>
                        <div class="upload-area" onclick="document.getElementById('photoInput').click()">
                            <div class="upload-icon">📷</div>
                            <p>Upload photos of the issue</p>
                            <small>JPG, PNG files up to 5MB each</small>
                            <input type="file" id="photoInput" name="photos[]" multiple accept=".jpg,.jpeg,.png" style="display: none;">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-secondary">Clear Form</button>
                        <button type="submit" class="btn-primary">Submit Application</button>
                    </div>
                </form>
            </div>

            <!-- Track Reports Tab -->
            <div id="track-tab" class="tab-content" style="display: none;">
                <div class="application-form">
                    <h3>Your Reported Issues</h3>
                    <?php if (empty($complaints)): ?>
                        <div class="empty-state">
                            <p>📋 No issues reported yet</p>
                            <button class="btn-primary" onclick="showTab('report')">Report Your First Issue</button>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Reference No.</th>
                                        <th>Issue Type</th>
                                        <th>Location</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date Reported</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($complaint['reference_number']); ?></strong></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $complaint['category'])); ?></td>
                                            <td><?php echo htmlspecialchars($complaint['location']); ?></td>
                                            <td>
                                                <span class="badge badge-priority-<?php echo $complaint['priority']; ?>">
                                                    <?php echo ucfirst($complaint['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $complaint['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
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