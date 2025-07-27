<?php
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare('SELECT username, currency, password FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($username, $currency, $hashed_password);
$stmt->fetch();
$stmt->close();

// Section messages
$msg_username = $msg_currency = $msg_password = '';
$err_username = $err_currency = $err_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Username
    if (isset($_POST['save_username'])) {
        $new_username = trim($_POST['username'] ?? '');
        if (!$new_username) {
            $err_username = 'Username cannot be empty.';
        } else {
            $stmt = $conn->prepare('UPDATE users SET username=? WHERE id=?');
            $stmt->bind_param('si', $new_username, $user_id);
            if ($stmt->execute()) {
                $msg_username = 'Username updated!';
                $_SESSION['username'] = $new_username;
                $username = $new_username;
            } else {
                $err_username = 'Failed to update username.';
            }
            $stmt->close();
        }
    }
    // Update Currency
    if (isset($_POST['save_currency'])) {
        $new_currency = $_POST['currency'] ?? 'PHP';
        $stmt = $conn->prepare('UPDATE users SET currency=? WHERE id=?');
        $stmt->bind_param('si', $new_currency, $user_id);
        if ($stmt->execute()) {
            $msg_currency = 'Currency updated!';
            $currency = $new_currency;
        } else {
            $err_currency = 'Failed to update currency.';
        }
        $stmt->close();
    }
    // Change Password
    if (isset($_POST['save_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if (!password_verify($current_password, $hashed_password)) {
            $err_password = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 6) {
            $err_password = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $err_password = 'New passwords do not match.';
        } else {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password=? WHERE id=?');
            $stmt->bind_param('si', $new_hashed, $user_id);
            if ($stmt->execute()) {
                $msg_password = 'Password updated!';
            } else {
                $err_password = 'Failed to update password.';
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
    <title>Settings - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .settings-form { max-width: 420px; margin: 0 auto; background: #181818; padding: 32px 28px; border-radius: 10px; box-shadow: 0 2px 8px #0002; }
        .settings-form label { font-weight: 500; margin-bottom: 8px; display: block; }
        .settings-form input, .settings-form select { margin-bottom: 18px; }
        .settings-section { margin-bottom: 32px; }
        .settings-section hr { margin: 18px 0; border: 0; border-top: 1px solid #333; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="transactions.php">Transactions</a>
            <a href="add_transaction.php">Add</a>
            <a href="settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="container">
        <h2>Profile & Settings</h2>
        <form method="post" class="settings-form" autocomplete="off">
            <div class="settings-section">
                <label>Username:
                    <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>
                </label>
                <button type="submit" name="save_username" class="action-btn">Save Username</button>
                <?php if ($msg_username): ?><div style="color:#00e676; margin-top:6px;"> <?= htmlspecialchars($msg_username) ?> </div><?php endif; ?>
                <?php if ($err_username): ?><div style="color:#e50914; margin-top:6px;"> <?= htmlspecialchars($err_username) ?> </div><?php endif; ?>
            </div>
            <div class="settings-section">
                <label>Preferred Currency:
                    <select name="currency">
                        <option value="PHP"<?= $currency==='PHP'?' selected':''; ?>>PHP (₱)</option>
                        <option value="USD"<?= $currency==='USD'?' selected':''; ?>>USD ($)</option>
                        <option value="EUR"<?= $currency==='EUR'?' selected':''; ?>>EUR (€)</option>
                        <option value="GBP"<?= $currency==='GBP'?' selected':''; ?>>GBP (£)</option>
                        <option value="JPY"<?= $currency==='JPY'?' selected':''; ?>>JPY (¥)</option>
                    </select>
                </label>
                <button type="submit" name="save_currency" class="action-btn">Save Currency</button>
                <?php if ($msg_currency): ?><div style="color:#00e676; margin-top:6px;"> <?= htmlspecialchars($msg_currency) ?> </div><?php endif; ?>
                <?php if ($err_currency): ?><div style="color:#e50914; margin-top:6px;"> <?= htmlspecialchars($err_currency) ?> </div><?php endif; ?>
            </div>
            <div class="settings-section">
                <label>Change Password:</label>
                <label>Current Password:
                    <input type="password" name="current_password" autocomplete="off">
                </label>
                <label>New Password:
                    <input type="password" name="new_password" autocomplete="off">
                </label>
                <label>Confirm New Password:
                    <input type="password" name="confirm_password" autocomplete="off">
                </label>
                <button type="submit" name="save_password" class="action-btn">Save Password</button>
                <?php if ($msg_password): ?><div style="color:#00e676; margin-top:6px;"> <?= htmlspecialchars($msg_password) ?> </div><?php endif; ?>
                <?php if ($err_password): ?><div style="color:#e50914; margin-top:6px;"> <?= htmlspecialchars($err_password) ?> </div><?php endif; ?>
            </div>
            <a href="dashboard.php" class="action-btn" style="background:#888;">Cancel</a>
        </form>
    </div>
</body>
</html> 