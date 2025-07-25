<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_SESSION['theme'])) {
    require_once 'db.php';
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT theme FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($theme);
    if ($stmt->fetch()) {
        $_SESSION['theme'] = $theme;
    }
    $stmt->close();
} 