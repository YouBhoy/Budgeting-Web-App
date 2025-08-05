<?php
// Enhanced logout with better security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Update last login time if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'includes/db.php';
        $stmt = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log('Logout update error: ' . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any potential cached pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header('Location: login.php?logged_out=1');
exit(); 