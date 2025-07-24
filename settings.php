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
    <title>Settings - BudgetFlix</title>
    <link rel="stylesheet" href="assets/style.css">
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
        <h2>Settings</h2>
        <!-- Settings form will go here -->
        <p><a href="dashboard.php" class="action-btn">Back to Dashboard</a></p>
    </div>
</body>
</html> 