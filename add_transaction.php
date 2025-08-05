<?php
require_once 'includes/db.php';
require_once 'includes/csrf.php';
require_once 'includes/validation.php';

// Use enhanced session management
require_once 'includes/session.php';

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
    // CSRF Protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $type = $_POST['type'] ?? '';
        $amount = sanitizeInput($_POST['amount'] ?? '', 'float');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');

        // Enhanced validation
        if (!validateTransactionType($type)) {
            $error = 'Invalid transaction type.';
        } elseif (!validateAmount($amount)) {
            $error = 'Amount must be a positive number up to 999,999,999.99.';
        } elseif (empty($category)) {
            $error = 'Category is required.';
        } elseif (strlen($category) > 100) {
            $error = 'Category must be 100 characters or less.';
        } elseif (strlen($description) > 255) {
            $error = 'Description must be 255 characters or less.';
        } else {
            try {
                // Call stored procedure AddTransaction
                $stmt = $conn->prepare('CALL AddTransaction(?, ?, ?, ?, ?)');
                $stmt->bind_param('isdss', $user_id, $type, $amount, $description, $category);
                
                if ($stmt->execute()) {
                    $success = 'Transaction added successfully!';
                    // Clear form data on success
                    $_POST = [];
                } else {
                    $error = 'Failed to add transaction. Please try again.';
                }
                $stmt->close();
            } catch (Exception $e) {
                error_log('Add transaction error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        }
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
            <?= getCSRFInput() ?>
            <label>Type:
                <select name="type" required>
                    <option value="">Select Type</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </label><br>
            <label>Amount: <input type="number" name="amount" step="0.01" min="0.01" max="999999999.99" required></label><br>
            <label>Description: <input type="text" name="description" maxlength="255"></label><br>
            <label>Category: <input type="text" name="category" maxlength="100" required></label><br>
            <button type="submit" class="action-btn">Add</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html> 