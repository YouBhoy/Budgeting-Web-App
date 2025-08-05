<?php
// Enhanced session security
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Session hijacking protection
if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
} elseif ($_SESSION['user_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    session_destroy();
    header('Location: login.php?error=session_invalid');
    exit();
}

// Load theme if not set
if (!isset($_SESSION['theme'])) {
    require_once 'db.php';
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT theme FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($theme);
    if ($stmt->fetch()) {
        $_SESSION['theme'] = $theme ?? 'dark';
    }
    $stmt->close();
} 