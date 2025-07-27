<?php
require_once 'includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $account_type = $_POST['account_type'] ?? '';

    // Basic validation
    if (!$username || !$email || !$password || !$account_type) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!in_array($account_type, ['individual', 'family'])) {
        $error = 'Invalid account type.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            // Insert user
            $stmt = $conn->prepare('INSERT INTO users (username, email, password, account_type) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $username, $email, $hashed, $account_type);
            if ($stmt->execute()) {
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </div>
    <div class="container">
        <h2>Register</h2>
        <?php if ($error): ?>
            <div style="color: #e50914; margin-bottom: 10px;"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="post" action="register.php">
            <label>Username: <input type="text" name="username" required></label><br>
            <label>Email: <input type="email" name="email" required></label><br>
            <label>Password: <input type="password" name="password" required></label><br>
            <label>Account Type:
                <select name="account_type" required>
                    <option value="individual">Individual</option>
                    <option value="family">Family / Shared</option>
                </select>
            </label><br>
            <button type="submit" class="action-btn">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</body>
</html> 