<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle signup form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/config.php';
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $agree = isset($_POST['agree']);
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!$agree) {
        $error = 'You must agree to the Terms of Service and Privacy Policy';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type, status, created_at) VALUES (?, ?, ?, 'citizen', 'active', NOW())");
            $stmt->bind_param("sss", $full_name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = 'Account created successfully! Redirecting to login...';
                header('Refresh: 2; URL=login.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Government E-Services</title>
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
                <button class="tab" id="loginTab" onclick="window.location.href='login.php'">Login</button>
                <button class="tab active" id="signupTab">Sign Up</button>
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

            <form method="POST" action="signup.php" id="signupForm">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', 'eyeIcon1')">
                            <span id="eyeIcon1">👁️</span>
                        </button>
                    </div>
                    <small class="form-hint">Must be at least 8 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'eyeIcon2')">
                            <span id="eyeIcon2">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="agree" id="agree" required>
                        <span>I agree to the <a href="terms.php" class="link">Terms of Service</a> and <a href="privacy.php" class="link">Privacy Policy</a></span>
                    </label>
                </div>

                <button type="submit" class="btn-submit">Create Account</button>
            </form>

            <p class="auth-switch">
                Already have an account? <a href="login.php" class="link">Sign in</a>
            </p>
        </div>

        <div class="auth-footer">
            <p>By accessing this system, you agree to comply with all applicable laws and regulations.</p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '👁️';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strength = getPasswordStrength(password);
            // You can add visual feedback here
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            return strength;
        }

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = e.target.value;
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                e.target.setCustomValidity('Passwords do not match');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>