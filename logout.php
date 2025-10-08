<?php
// Start session
session_start();

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the logout activity if user is logged in and database is available
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'config/config.php';
        
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Insert logout activity log
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, module, ip_address, user_agent, created_at) VALUES (?, 'logout', 'user', ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
        
        if (isset($conn)) {
            $conn->close();
        }
    } catch (Exception $e) {
        // Silently fail - logout should still work even if logging fails
        // echo "Error logging activity: " . $e->getMessage(); // Uncomment for debugging
    }
}

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Debug: Check if we're about to redirect
// echo "About to redirect to login.php"; exit(); // Uncomment to test

// Redirect to login page with logout message
header('Location: login.php?logout=success');
exit();
?>