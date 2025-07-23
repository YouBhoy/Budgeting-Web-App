<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Placeholder for settings logic
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Budgeting App</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <h2>Settings</h2>
    <!-- Settings form will go here -->
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html> 