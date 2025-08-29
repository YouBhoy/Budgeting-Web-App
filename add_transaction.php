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

// Fetch user's active budget goals for allocation
$active_goals = [];
$stmt = $conn->prepare('SELECT id, name, target_amount, current_amount, (target_amount - current_amount) as remaining FROM budget_goals WHERE user_id = ? AND current_amount < target_amount ORDER BY deadline ASC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($goal_id, $goal_name, $target_amount, $current_amount, $remaining);
while ($stmt->fetch()) {
    $active_goals[] = [
        'id' => $goal_id,
        'name' => $goal_name,
        'target_amount' => $target_amount,
        'current_amount' => $current_amount,
        'remaining' => $remaining
    ];
}
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
        $allocate_to_goals = isset($_POST['allocate_to_goals']) ? 1 : 0;
        $goal_allocations = $_POST['goal_allocations'] ?? [];

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
                // Start transaction
                $conn->begin_transaction();
                
                // Add the main transaction
                $stmt = $conn->prepare('CALL AddTransaction(?, ?, ?, ?, ?)');
                $stmt->bind_param('isdss', $user_id, $type, $amount, $description, $category);
                
                if ($stmt->execute()) {
                    // If this is income and user wants to allocate to goals
                    if ($type === 'income' && $allocate_to_goals && !empty($goal_allocations)) {
                        $total_allocated = 0;
                        
                        // Validate goal allocations
                        foreach ($goal_allocations as $goal_id => $allocation_amount) {
                            $allocation_amount = floatval($allocation_amount);
                            if ($allocation_amount > 0) {
                                $total_allocated += $allocation_amount;
                                
                                // Verify goal exists and belongs to user
                                $stmt2 = $conn->prepare('SELECT id FROM budget_goals WHERE id = ? AND user_id = ?');
                                $stmt2->bind_param('ii', $goal_id, $user_id);
                                $stmt2->execute();
                                $stmt2->store_result();
                                if ($stmt2->num_rows === 0) {
                                    throw new Exception('Invalid goal selected.');
                                }
                                $stmt2->close();
                            }
                        }
                        
                        // Check if total allocation doesn't exceed income amount
                        if ($total_allocated > $amount) {
                            throw new Exception('Total goal allocation cannot exceed income amount.');
                        }
                        
                        // Update goal progress
                        foreach ($goal_allocations as $goal_id => $allocation_amount) {
                            $allocation_amount = floatval($allocation_amount);
                            if ($allocation_amount > 0) {
                                $stmt3 = $conn->prepare('UPDATE budget_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?');
                                $stmt3->bind_param('dii', $allocation_amount, $goal_id, $user_id);
                                $stmt3->execute();
                                $stmt3->close();
                            }
                        }
                    }
                    
                    $conn->commit();
                    $success = 'Transaction added successfully!' . ($allocate_to_goals && !empty($goal_allocations) ? ' Goals updated!' : '');
                    // Clear form data on success
                    $_POST = [];
                } else {
                    throw new Exception('Failed to add transaction.');
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                error_log('Add transaction error: ' . $e->getMessage());
                $error = 'An error occurred: ' . $e->getMessage();
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
    <meta name="description" content="Add income or expense transactions and allocate to goals">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="dashboard.php">My Dashboard</a>
            <a href="transactions.php">View All Transactions</a>
            <a href="add_transaction.php" aria-current="page">Add New Transaction</a>
            <a href="budget_goals.php">Budget Goals</a>
            <a href="recurring.php">Recurring</a>
            <a href="help.php">Help & Tips</a>
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                <span class="icon">☀️</span>
                <span>Theme</span>
            </button>
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>
    
    <main id="main-content" class="container">
        <header style="text-align: center; margin-bottom: 30px;" class="fade-in-up">
            <h1>💰 Add Transaction</h1>
            <p style="font-size: 1.1rem; color: var(--text-secondary);">Record money coming in or going out</p>
        </header>
        
        <?php if ($success): ?>
            <div role="alert" class="alert alert-success fade-in">
                ✅ <?= htmlspecialchars($success) ?>
                <div style="margin-top: 15px;">
                    <a href="dashboard.php" class="action-btn btn-success" style="font-size: 1rem;">
                        📊 Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div role="alert" class="alert alert-danger fade-in">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="card fade-in-up">
            <form method="POST" style="max-width: none; margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label for="type">Transaction Type *</label>
                        <select id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="income" <?= ($_POST['type'] ?? '') === 'income' ? 'selected' : '' ?>>💰 Income</option>
                            <option value="expense" <?= ($_POST['type'] ?? '') === 'expense' ? 'selected' : '' ?>>💸 Expense</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="amount">Amount (₱) *</label>
                        <input type="number" id="amount" name="amount" required min="0" step="0.01" 
                               value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" 
                               placeholder="1000.00">
                    </div>
                    
                    <div>
                        <label for="description">Description *</label>
                        <input type="text" id="description" name="description" required 
                               value="<?= htmlspecialchars($_POST['description'] ?? '') ?>" 
                               placeholder="e.g., Salary, Groceries">
                    </div>
                    
                    <div>
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Salary" <?= ($_POST['category'] ?? '') === 'Salary' ? 'selected' : '' ?>>Salary</option>
                            <option value="Freelance" <?= ($_POST['category'] ?? '') === 'Freelance' ? 'selected' : '' ?>>Freelance</option>
                            <option value="Investment" <?= ($_POST['category'] ?? '') === 'Investment' ? 'selected' : '' ?>>Investment</option>
                            <option value="Groceries" <?= ($_POST['category'] ?? '') === 'Groceries' ? 'selected' : '' ?>>Groceries</option>
                            <option value="Transportation" <?= ($_POST['category'] ?? '') === 'Transportation' ? 'selected' : '' ?>>Transportation</option>
                            <option value="Entertainment" <?= ($_POST['category'] ?? '') === 'Entertainment' ? 'selected' : '' ?>>Entertainment</option>
                            <option value="Utilities" <?= ($_POST['category'] ?? '') === 'Utilities' ? 'selected' : '' ?>>Utilities</option>
                            <option value="Healthcare" <?= ($_POST['category'] ?? '') === 'Healthcare' ? 'selected' : '' ?>>Healthcare</option>
                            <option value="Other" <?= ($_POST['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <!-- Goal Allocation Section (only for income) -->
                <div id="goal-allocation-section" style="display: none; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--border-color);">
                    <h3 style="color: var(--primary-color); margin-bottom: 20px;">🎯 Allocate to Budget Goals</h3>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="allocate_to_goals" name="allocate_to_goals" 
                                   <?= isset($_POST['allocate_to_goals']) ? 'checked' : '' ?>>
                            <span>Allocate this income to my budget goals</span>
                        </label>
                    </div>
                    
                    <div id="goal-allocation-form" style="display: none;">
                        <?php if (empty($active_goals)): ?>
                            <div style="text-align: center; padding: 20px; background: var(--bg-card); border-radius: var(--border-radius-sm);">
                                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                                    No active budget goals found. 
                                    <a href="budget_goals.php" style="color: var(--primary-color);">Create a goal first</a>
                                </p>
                            </div>
                        <?php else: ?>
                            <div style="background: var(--bg-card); padding: 20px; border-radius: var(--border-radius-sm);">
                                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                                    💡 Allocate portions of your income to specific goals. The total allocation cannot exceed your income amount.
                                </p>
                                
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($active_goals as $goal): ?>
                                        <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: var(--bg-primary); border-radius: var(--border-radius-sm);">
                                            <div style="flex: 1;">
                                                <div style="font-weight: bold; color: var(--text-primary);">
                                                    <?= htmlspecialchars($goal['name']) ?>
                                                </div>
                                                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                                    ₱<?= number_format($goal['current_amount'], 2) ?> / ₱<?= number_format($goal['target_amount'], 2) ?>
                                                    (₱<?= number_format($goal['remaining'], 2) ?> remaining)
                                                </div>
                                            </div>
                                            <div style="min-width: 150px;">
                                                <input type="number" 
                                                       name="goal_allocations[<?= $goal['id'] ?>]" 
                                                       class="goal-allocation-input"
                                                       min="0" 
                                                       max="<?= $goal['remaining'] ?>" 
                                                       step="0.01" 
                                                       placeholder="0.00"
                                                       value="<?= htmlspecialchars($_POST['goal_allocations'][$goal['id']] ?? '') ?>"
                                                       style="width: 100%;">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div style="margin-top: 20px; padding: 15px; background: var(--primary-color); color: white; border-radius: var(--border-radius-sm);">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>Total Allocated:</span>
                                        <span id="total-allocated">₱0.00</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                        <span>Remaining:</span>
                                        <span id="remaining-amount">₱0.00</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="action-btn btn-success" style="margin-top: 20px; width: 100%;">
                    💾 Add Transaction
                </button>
            </form>
        </div>
    </main>

    <script>
        // Show/hide goal allocation section based on transaction type
        document.getElementById('type').addEventListener('change', function() {
            const goalSection = document.getElementById('goal-allocation-section');
            const allocateCheckbox = document.getElementById('allocate_to_goals');
            
            if (this.value === 'income') {
                goalSection.style.display = 'block';
            } else {
                goalSection.style.display = 'none';
                allocateCheckbox.checked = false;
                document.getElementById('goal-allocation-form').style.display = 'none';
            }
        });
        
        // Show/hide goal allocation form based on checkbox
        document.getElementById('allocate_to_goals').addEventListener('change', function() {
            const form = document.getElementById('goal-allocation-form');
            form.style.display = this.checked ? 'block' : 'none';
        });
        
        // Calculate total allocation and remaining amount
        function updateAllocationTotals() {
            const incomeAmount = parseFloat(document.getElementById('amount').value) || 0;
            const allocationInputs = document.querySelectorAll('.goal-allocation-input');
            let totalAllocated = 0;
            
            allocationInputs.forEach(input => {
                totalAllocated += parseFloat(input.value) || 0;
            });
            
            const remaining = Math.max(0, incomeAmount - totalAllocated);
            
            document.getElementById('total-allocated').textContent = '₱' + totalAllocated.toFixed(2);
            document.getElementById('remaining-amount').textContent = '₱' + remaining.toFixed(2);
            
            // Highlight if over-allocated
            if (totalAllocated > incomeAmount) {
                document.getElementById('total-allocated').style.color = 'var(--danger-color)';
            } else {
                document.getElementById('total-allocated').style.color = 'white';
            }
        }
        
        // Add event listeners for allocation inputs
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('goal-allocation-input') || e.target.id === 'amount') {
                updateAllocationTotals();
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateAllocationTotals();
            
            // Show goal section if type is already income
            if (document.getElementById('type').value === 'income') {
                document.getElementById('goal-allocation-section').style.display = 'block';
                if (document.getElementById('allocate_to_goals').checked) {
                    document.getElementById('goal-allocation-form').style.display = 'block';
                }
            }
        });
    </script>
</body>
</html> 