<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch account type for family mode (optional, for future use)
$account_type = 'individual';
$stmt = $conn->prepare('SELECT account_type FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($account_type);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    // $member_id = $_POST['member_id'] ?? null; // For family mode, not yet implemented

    if (!in_array($type, ['income', 'expense'])) {
        $error = 'Invalid transaction type.';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = 'Amount must be a positive number.';
    } elseif (!$category) {
        $error = 'Category is required.';
    } else {
        // Call stored procedure AddTransaction
        $stmt = $conn->prepare('CALL AddTransaction(?, ?, ?, ?, ?)');
        $stmt->bind_param('isdss', $user_id, $type, $amount, $description, $category);
        if ($stmt->execute()) {
            $success = 'Transaction added successfully!';
        } else {
            $error = 'Failed to add transaction.';
        }
        $stmt->close();
        // Clear POST to prevent resubmission
        $_POST = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Transaction - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <h2>Add Transaction</h2>
        <?php if ($success): ?>
            <div style="color: #00e676; margin-bottom: 10px;"> <?= htmlspecialchars($success) ?> </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="color: #e50914; margin-bottom: 10px;"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="post" action="add_transaction.php">
            <label>Type:
                <select name="type" required>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </label><br>
            <label>Amount: <input type="number" name="amount" step="0.01" min="0" required></label><br>
            <label>Description: <input type="text" name="description"></label><br>
            <label>Category: <input type="text" name="category" required></label><br>
            <button type="submit" class="action-btn">Add</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html> 