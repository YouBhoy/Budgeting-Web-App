<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id || !is_numeric($transaction_id)) {
    header('Location: transactions.php');
    exit();
}
$error = '';
$success = '';
$type = $amount = $description = $category = '';
// Fetch transaction
$stmt = $conn->prepare('SELECT type, amount, description, category FROM transactions WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $transaction_id, $user_id);
$stmt->execute();
$stmt->bind_result($type, $amount, $description, $category);
if (!$stmt->fetch()) {
    $stmt->close();
    header('Location: transactions.php');
    exit();
}
$stmt->close();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    if (!in_array($type, ['income', 'expense'])) {
        $error = 'Invalid transaction type.';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = 'Amount must be a positive number.';
    } elseif (!$category) {
        $error = 'Category is required.';
    } else {
        $stmt = $conn->prepare('UPDATE transactions SET type=?, amount=?, description=?, category=? WHERE id=? AND user_id=?');
        $stmt->bind_param('sdssii', $type, $amount, $description, $category, $transaction_id, $user_id);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: transactions.php?updated=1');
            exit();
        } else {
            $error = 'Failed to update transaction.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Transaction - BudgetFlix</title>
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
        <h2>Edit Transaction</h2>
        <?php if ($error): ?>
            <div style="color: #e50914; margin-bottom: 10px;"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="post" action="edit_transaction.php?id=<?= $transaction_id ?>">
            <label>Type:
                <select name="type" required>
                    <option value="income"<?= $type==='income'?' selected':''; ?>>Income</option>
                    <option value="expense"<?= $type==='expense'?' selected':''; ?>>Expense</option>
                </select>
            </label><br>
            <label>Amount: <input type="number" name="amount" step="0.01" min="0" value="<?= htmlspecialchars($amount) ?>" required></label><br>
            <label>Description: <input type="text" name="description" value="<?= htmlspecialchars($description) ?>"></label><br>
            <label>Category: <input type="text" name="category" value="<?= htmlspecialchars($category) ?>" required></label><br>
            <button type="submit" class="action-btn">Update</button>
            <a href="transactions.php" class="action-btn" style="background:#888;">Cancel</a>
        </form>
    </div>
</body>
</html> 