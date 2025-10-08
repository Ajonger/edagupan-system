<?php
// Session configuration MUST be before any output
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for default XAMPP
define('DB_NAME', 'edagupan_db');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Application settings
define('APP_NAME', 'Dagupan City E-Services');
define('APP_URL', 'http://localhost/edagupan-system/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>