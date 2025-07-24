<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$total_income = 0;
$total_expense = 0;
$balance = 0;

// Get totals using stored procedure
$stmt = $conn->prepare('CALL GetUserTotals(?)');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($total_income, $total_expense);
if ($stmt->fetch()) {
    $balance = $total_income - $total_expense;
}
$stmt->close();
// Clear results for next query after CALL
$conn->next_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - BudgetFlix</title>
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
        <div style="text-align:center; margin-bottom:32px;">
            <h2>Welcome to Your Dashboard</h2>
            <p>Hello, <?= htmlspecialchars($username) ?>!</p>
        </div>
        <div class="card-grid">
            <div class="card">
                <div class="card-label">Total Income</div>
                <div class="card-value"> ₱<?= number_format($total_income, 2) ?></div>
            </div>
            <div class="card">
                <div class="card-label">Total Expenses</div>
                <div class="card-value"> ₱<?= number_format($total_expense, 2) ?></div>
            </div>
            <div class="card">
                <div class="card-label">Balance</div>
                <div class="card-value"> ₱<?= number_format($balance, 2) ?></div>
            </div>
        </div>
        <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin-bottom:32px;">
            <a href="add_transaction.php" class="action-btn">Add Transaction</a>
            <a href="transactions.php" class="action-btn">View Transactions</a>
            <a href="settings.php" class="action-btn">Settings</a>
            <a href="logout.php" class="action-btn" style="background:#b0060f;">Logout</a>
        </div>
    </div>
</body>
</html> 