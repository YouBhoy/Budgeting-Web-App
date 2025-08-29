<?php
require_once 'includes/db.php';
require_once 'includes/csrf.php';
require_once 'includes/validation.php';

// Start session for CSRF protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '', 'email');
        $password = $_POST['password'] ?? '';
        
        // Rate limiting
        if (!rateLimitCheck('login_' . $_SERVER['REMOTE_ADDR'])) {
            $error = 'Too many login attempts. Please try again later.';
        } elseif (!$email || !$password) {
            $error = 'Email and password are required.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $username, $hashed);
                $stmt->fetch();
                
                if (password_verify($password, $hashed)) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $id;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                    $_SESSION['login_time'] = time();
                    
                    // Clear rate limit on successful login
                    unset($_SESSION['rate_limits']['login_' . $_SERVER['REMOTE_ADDR']]);
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Incorrect password.';
                }
            } else {
                $error = 'No account found with that email.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <nav class="navbar" role="navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="index.php">Home</a>
            <a href="login.php" aria-current="page">Sign In</a>
            <a href="register.php">Create Account</a>
        </div>
    </nav>
    
    <main id="main-content" class="container">
        <header style="text-align: center; margin-bottom: 30px;">
            <h1>Sign In to Your Account</h1>
            <p style="font-size: 1.1rem; color: #cccccc;">Enter your information below to access your budget</p>
        </header>
        
        <?php if (isset($_GET['registered'])): ?>
            <div style="color: #4CAF50; margin-bottom: 20px; padding: 15px; background: #1b5e20; border-radius: 8px; text-align: center; font-size: 1.1rem;">
                ✅ Account created successfully! Please sign in below.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logged_out'])): ?>
            <div style="color: #2196F3; margin-bottom: 20px; padding: 15px; background: #0d47a1; border-radius: 8px; text-align: center; font-size: 1.1rem;">
                👋 You have been signed out successfully.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div role="alert" style="color: #f44336; margin-bottom: 20px; padding: 15px; background: #b71c1c; border-radius: 8px; text-align: center; font-size: 1.1rem;">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php" role="form" aria-labelledby="signin-form">
            <fieldset>
                <legend id="signin-form" style="font-size: 1.3rem; margin-bottom: 20px; color: #ffffff;">Your Sign In Information</legend>
                
                <?= getCSRFInput() ?>
                
                <label for="email">📧 Your Email Address:</label>
                <input type="email" id="email" name="email" required 
                       aria-describedby="email-help"
                       placeholder="example@email.com">
                <div id="email-help" style="font-size: 0.9rem; color: #999; margin-bottom: 15px;">
                    Enter the email address you used when creating your account
                </div>
                
                <label for="password">🔒 Your Password:</label>
                <input type="password" id="password" name="password" required
                       aria-describedby="password-help"
                       placeholder="Enter your password">
                <div id="password-help" style="font-size: 0.9rem; color: #999; margin-bottom: 25px;">
                    Enter the password you created for your account
                </div>
                
                <button type="submit" class="action-btn" style="width: 100%; font-size: 1.3rem;">
                    🚪 Sign In to My Account
                </button>
            </fieldset>
        </form>
        
        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #2d2d2d; border-radius: 8px;">
            <p style="font-size: 1.1rem;">Don't have an account yet?</p>
            <a href="register.php" class="action-btn" style="background: #4CAF50; border-color: #4CAF50;">
                ➕ Create New Account
            </a>
        </div>
    </main>
</body>
</html> 