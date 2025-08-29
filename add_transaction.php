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
    <title>Add Money Transaction - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <nav class="navbar" role="navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="dashboard.php">My Dashboard</a>
            <a href="transactions.php">View All Transactions</a>
            <a href="add_transaction.php" aria-current="page">Add New Transaction</a>
            <a href="settings.php">Account Settings</a>
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>
    
    <main id="main-content" class="container">
        <header style="text-align: center; margin-bottom: 30px;">
            <h1>Add a Money Transaction</h1>
            <p style="font-size: 1.1rem; color: #cccccc;">Record money coming in or going out</p>
        </header>
        
        <?php if ($success): ?>
            <div role="alert" style="color: #4CAF50; margin-bottom: 20px; padding: 20px; background: #1b5e20; border-radius: 8px; text-align: center; font-size: 1.2rem;">
                ✅ <?= htmlspecialchars($success) ?>
                <div style="margin-top: 15px;">
                    <a href="dashboard.php" class="action-btn" style="background: #4CAF50; font-size: 1rem;">
                        📊 Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div role="alert" style="color: #f44336; margin-bottom: 20px; padding: 20px; background: #b71c1c; border-radius: 8px; text-align: center; font-size: 1.1rem;">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="add_transaction.php" role="form">
            <fieldset style="border: 3px solid #444; padding: 30px; border-radius: 12px;">
                <legend style="font-size: 1.3rem; padding: 0 15px; color: #ffffff;">Transaction Details</legend>
                
                <?= getCSRFInput() ?>
                
                <!-- Step 1: Type -->
                <div style="margin-bottom: 30px; padding: 20px; background: #1a1a1a; border-radius: 8px; border-left: 5px solid #ff6b6b;">
                    <h3 style="margin-top: 0; color: #ff6b6b;">Step 1: Is money coming in or going out?</h3>
                    <label for="type" style="font-size: 1.2rem;">💰 Transaction Type:</label>
                    <select id="type" name="type" required style="font-size: 1.2rem; padding: 20px;">
                        <option value="">👆 Choose One Option</option>
                        <option value="income">💰 Money Coming In (Income)</option>
                        <option value="expense">💸 Money Going Out (Expense)</option>
                    </select>
                    <p style="font-size: 0.95rem; color: #999; margin-top: 10px;">
                        Choose "Money Coming In" for salary, gifts, etc. Choose "Money Going Out" for bills, shopping, etc.
                    </p>
                </div>
                
                <!-- Step 2: Amount -->
                <div style="margin-bottom: 30px; padding: 20px; background: #1a1a1a; border-radius: 8px; border-left: 5px solid #4CAF50;">
                    <h3 style="margin-top: 0; color: #4CAF50;">Step 2: How much money?</h3>
                    <label for="amount" style="font-size: 1.2rem;">💵 Amount in Pesos:</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" max="999999999.99" 
                           required style="font-size: 1.3rem; text-align: center;" 
                           placeholder="0.00">
                    <p style="font-size: 0.95rem; color: #999; margin-top: 10px;">
                        Enter the amount in Philippine Pesos (₱). For example: 1500.50
                    </p>
                </div>
                
                <!-- Step 3: Description -->
                <div style="margin-bottom: 30px; padding: 20px; background: #1a1a1a; border-radius: 8px; border-left: 5px solid #2196F3;">
                    <h3 style="margin-top: 0; color: #2196F3;">Step 3: What is this for? (Optional)</h3>
                    <label for="description" style="font-size: 1.2rem;">📝 Description:</label>
                    <input type="text" id="description" name="description" maxlength="255"
                           style="font-size: 1.1rem;" 
                           placeholder="For example: Grocery shopping, Salary payment, etc.">
                    <p style="font-size: 0.95rem; color: #999; margin-top: 10px;">
                        Optional: Add a note to help you remember what this transaction was for
                    </p>
                </div>
                
                <!-- Step 4: Category -->
                <div style="margin-bottom: 30px; padding: 20px; background: #1a1a1a; border-radius: 8px; border-left: 5px solid #FF9800;">
                    <h3 style="margin-top: 0; color: #FF9800;">Step 4: What category?</h3>
                    <label for="category" style="font-size: 1.2rem;">🏷️ Category:</label>
                    <input type="text" id="category" name="category" maxlength="100" required
                           style="font-size: 1.1rem;"
                           placeholder="For example: Food, Bills, Salary, etc."
                           list="category-suggestions">
                    <datalist id="category-suggestions">
                        <option value="Food & Groceries">
                        <option value="Transportation">
                        <option value="Bills & Utilities">
                        <option value="Healthcare">
                        <option value="Entertainment">
                        <option value="Salary">
                        <option value="Pension">
                        <option value="Gifts Received">
                        <option value="Other Income">
                    </datalist>
                    <p style="font-size: 0.95rem; color: #999; margin-top: 10px;">
                        Required: Choose a category to help organize your money tracking
                    </p>
                </div>
                
                <!-- Submit Button -->
                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="action-btn" style="font-size: 1.4rem; padding: 25px 50px; background: #4CAF50; border-color: #4CAF50;">
                        ✅ Save This Transaction
                    </button>
                </div>
            </fieldset>
        </form>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="action-btn" style="background: #666; border-color: #666;">
                ← Back to Dashboard
            </a>
        </div>
    </main>
</body>
</html> 