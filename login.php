<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle logout success message
$success = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'You have been successfully logged out.';
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/config.php';
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, user_type FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                
                // Redirect to dashboard
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Government E-Services</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">
                <div class="logo-icon"></div>
            </div>
            <h1>Government E-Services</h1>
            <p class="subtitle">Digital Government Platform</p>
        </div>

        <div class="auth-card">
            <h2>Access Your Account</h2>
            <p class="card-subtitle">Sign in to continue to your services</p>

            <div class="tab-container">
                <button class="tab active" id="loginTab">Login</button>
                <button class="tab" id="signupTab" onclick="window.location.href='signup.php'">Sign Up</button>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <span id="eyeIcon">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-submit">Sign In</button>
            </form>
        </div>

        <div class="auth-footer">
            <p>By accessing this system, you agree to comply with all applicable laws and regulations.</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '👁️';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>