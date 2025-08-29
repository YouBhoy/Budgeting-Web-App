<?php
require_once 'includes/db.php';
require_once 'includes/session.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Handle recurring transaction creation
    if ($action === 'create_recurring') {
        $type = $_POST['type'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $frequency = $_POST['frequency'] ?? '';
        $next_due = $_POST['next_due'] ?? '';
        
        if ($description && $amount > 0 && $next_due) {
            $stmt = $conn->prepare('INSERT INTO recurring_transactions (user_id, type, amount, description, category, frequency, next_due) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isdssss', $user_id, $type, $amount, $description, $category, $frequency, $next_due);
            $stmt->execute();
            $stmt->close();
            
            header('Location: recurring.php?success=recurring_created');
            exit();
        }
    }
    
    // Handle recurring transaction updates
    if ($action === 'update_recurring') {
        $recurring_id = intval($_POST['recurring_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $frequency = $_POST['frequency'] ?? '';
        $next_due = $_POST['next_due'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($recurring_id > 0) {
            $stmt = $conn->prepare('UPDATE recurring_transactions SET amount = ?, description = ?, category = ?, frequency = ?, next_due = ?, is_active = ? WHERE id = ? AND user_id = ?');
            $stmt->bind_param('dssssiii', $amount, $description, $category, $frequency, $next_due, $is_active, $recurring_id, $user_id);
            $stmt->execute();
            $stmt->close();
            
            header('Location: recurring.php?success=recurring_updated');
            exit();
        }
    }
    
    // Handle recurring transaction deletion
    if ($action === 'delete_recurring') {
        $recurring_id = intval($_POST['recurring_id'] ?? 0);
        
        if ($recurring_id > 0) {
            $stmt = $conn->prepare('DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $recurring_id, $user_id);
            $stmt->execute();
            $stmt->close();
            
            header('Location: recurring.php?success=recurring_deleted');
            exit();
        }
    }
    
    // Handle mark as paid
    if ($action === 'mark_paid') {
        $recurring_id = intval($_POST['recurring_id'] ?? 0);
        
        if ($recurring_id > 0) {
            // Get recurring transaction details
            $stmt = $conn->prepare('SELECT type, amount, description, category, frequency, next_due FROM recurring_transactions WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $recurring_id, $user_id);
            $stmt->execute();
            $stmt->bind_result($type, $amount, $description, $category, $frequency, $next_due);
            if ($stmt->fetch()) {
                // Add to transactions table
                $stmt2 = $conn->prepare('INSERT INTO transactions (user_id, type, amount, description, category, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt2->bind_param('isdss', $user_id, $type, $amount, $description, $category);
                $stmt2->execute();
                $stmt2->close();
                
                // Calculate next due date
                $next_due_date = date('Y-m-d', strtotime($next_due));
                switch ($frequency) {
                    case 'daily':
                        $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +1 day'));
                        break;
                    case 'weekly':
                        $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +1 week'));
                        break;
                    case 'monthly':
                        $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +1 month'));
                        break;
                    case 'yearly':
                        $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +1 year'));
                        break;
                }
                
                // Update next due date
                $stmt3 = $conn->prepare('UPDATE recurring_transactions SET next_due = ? WHERE id = ? AND user_id = ?');
                $stmt3->bind_param('sii', $next_due_date, $recurring_id, $user_id);
                $stmt3->execute();
                $stmt3->close();
            }
            $stmt->close();
            
            header('Location: recurring.php?success=marked_paid');
            exit();
        }
    }
}

// Fetch user's recurring transactions
$recurring_transactions = [];
$stmt = $conn->prepare('SELECT id, type, amount, description, category, frequency, next_due, is_active, created_at FROM recurring_transactions WHERE user_id = ? ORDER BY next_due ASC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($id, $type, $amount, $description, $category, $frequency, $next_due, $is_active, $created_at);
while ($stmt->fetch()) {
    $recurring_transactions[] = [
        'id' => $id,
        'type' => $type,
        'amount' => $amount,
        'description' => $description,
        'category' => $category,
        'frequency' => $frequency,
        'next_due' => $next_due,
        'is_active' => $is_active,
        'created_at' => $created_at
    ];
}
$stmt->close();

// Calculate summary
$total_recurring_income = 0;
$total_recurring_expense = 0;
$due_soon = 0;

foreach ($recurring_transactions as $transaction) {
    if ($transaction['is_active']) {
        if ($transaction['type'] === 'income') {
            $total_recurring_income += $transaction['amount'];
        } else {
            $total_recurring_expense += $transaction['amount'];
        }
        
        $days_until_due = (strtotime($transaction['next_due']) - time()) / 86400;
        if ($days_until_due <= 7 && $days_until_due >= 0) {
            $due_soon++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recurring Transactions - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your recurring income and expenses">
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
            <a href="add_transaction.php">Add New Transaction</a>
            <a href="budget_goals.php">Budget Goals</a>
            <a href="recurring.php" aria-current="page">Recurring</a>
            <a href="help.php">Help & Tips</a>
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                <span class="icon">☀️</span>
                <span>Theme</span>
            </button>
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>
    
    <main id="main-content" class="container">
        <header style="text-align:center; margin-bottom:40px;" class="fade-in-up">
            <h1>🔄 Recurring Transactions</h1>
            <p style="font-size: 1.2rem; color: var(--text-secondary);">Manage your regular income and expenses</p>
        </header>

        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success fade-in">
                <?php
                switch ($_GET['success']) {
                    case 'recurring_created': echo '✅ Recurring transaction created successfully!'; break;
                    case 'recurring_updated': echo '📝 Recurring transaction updated successfully!'; break;
                    case 'recurring_deleted': echo '🗑️ Recurring transaction deleted successfully!'; break;
                    case 'marked_paid': echo '💰 Transaction marked as paid!'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <section style="margin-bottom: 40px;" class="fade-in-up">
            <div class="card-grid">
                <div class="card" style="border-left: 5px solid var(--success-color);">
                    <div class="card-label">💰 Monthly Recurring Income</div>
                    <div class="card-value" style="color: var(--success-color);">₱<?= number_format($total_recurring_income, 2) ?></div>
                    <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: var(--text-muted);">Regular money coming in</p>
                </div>
                <div class="card" style="border-left: 5px solid var(--danger-color);">
                    <div class="card-label">💸 Monthly Recurring Expenses</div>
                    <div class="card-value" style="color: var(--danger-color);">₱<?= number_format($total_recurring_expense, 2) ?></div>
                    <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: var(--text-muted);">Regular money going out</p>
                </div>
                <div class="card" style="border-left: 5px solid var(--warning-color);">
                    <div class="card-label">⚠️ Due This Week</div>
                    <div class="card-value" style="color: var(--warning-color);"><?= $due_soon ?></div>
                    <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: var(--text-muted);">Transactions due soon</p>
                </div>
            </div>
        </section>

        <!-- Create New Recurring Transaction -->
        <section style="margin-bottom: 40px;" class="fade-in-up">
            <div class="card" style="border-left: 5px solid var(--accent-color);">
                <h2 style="margin-bottom: 20px; color: var(--accent-color);">➕ Create Recurring Transaction</h2>
                <form method="POST" style="max-width: none; margin: 0;">
                    <input type="hidden" name="action" value="create_recurring">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div>
                            <label for="type">Type *</label>
                            <select id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="income">💰 Income</option>
                                <option value="expense">💸 Expense</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="amount">Amount (₱) *</label>
                            <input type="number" id="amount" name="amount" required min="0" step="0.01" placeholder="1000">
                        </div>
                        
                        <div>
                            <label for="description">Description *</label>
                            <input type="text" id="description" name="description" required placeholder="e.g., Salary, Rent">
                        </div>
                        
                        <div>
                            <label for="category">Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Salary">Salary</option>
                                <option value="Rent">Rent</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Groceries">Groceries</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Entertainment">Entertainment</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Investment">Investment</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="frequency">Frequency *</label>
                            <select id="frequency" name="frequency" required>
                                <option value="">Select Frequency</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="next_due">Next Due Date *</label>
                            <input type="date" id="next_due" name="next_due" required min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="action-btn btn-success" style="margin-top: 20px;">
                        🔄 Create Recurring Transaction
                    </button>
                </form>
            </div>
        </section>

        <!-- Recurring Transactions List -->
        <section class="fade-in-up">
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">📋 Your Recurring Transactions</h2>
            
            <?php if (empty($recurring_transactions)): ?>
                <div class="card" style="text-align: center; border-left: 5px solid var(--warning-color);">
                    <div style="font-size: 3rem; margin-bottom: 20px;">🔄</div>
                    <h3 style="color: var(--warning-color);">No Recurring Transactions</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Create your first recurring transaction to automate your regular income and expenses!
                    </p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($recurring_transactions as $transaction): ?>
                        <?php
                        $days_until_due = (strtotime($transaction['next_due']) - time()) / 86400;
                        $is_overdue = $days_until_due < 0;
                        $is_due_soon = $days_until_due <= 7 && $days_until_due >= 0;
                        ?>
                        
                        <div class="card" style="border-left: 5px solid <?= $transaction['type'] === 'income' ? 'var(--success-color)' : 'var(--danger-color)' ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                <h3 style="margin: 0; color: var(--text-primary);"><?= htmlspecialchars($transaction['description']) ?></h3>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="editRecurring(<?= $transaction['id'] ?>)" class="action-btn btn-info" style="padding: 8px 12px; font-size: 0.9rem;">
                                        ✏️
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this recurring transaction?')">
                                        <input type="hidden" name="action" value="delete_recurring">
                                        <input type="hidden" name="recurring_id" value="<?= $transaction['id'] ?>">
                                        <button type="submit" class="action-btn btn-danger" style="padding: 8px 12px; font-size: 0.9rem;">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <div>
                                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Amount</div>
                                    <div style="color: <?= $transaction['type'] === 'income' ? 'var(--success-color)' : 'var(--danger-color)' ?>; font-weight: bold; font-size: 1.1rem;">
                                        <?= $transaction['type'] === 'income' ? '+' : '-' ?>₱<?= number_format($transaction['amount'], 2) ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Frequency</div>
                                    <div style="color: var(--text-primary); font-weight: bold; font-size: 1.1rem;">
                                        <?= ucfirst($transaction['frequency']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="color: var(--text-secondary); font-size: 0.9rem;">Category</div>
                                <div style="color: var(--text-primary); font-weight: bold;">
                                    📂 <?= htmlspecialchars($transaction['category']) ?>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="color: var(--text-secondary); font-size: 0.9rem;">Next Due</div>
                                <div style="color: <?= $is_overdue ? 'var(--danger-color)' : ($is_due_soon ? 'var(--warning-color)' : 'var(--text-primary)') ?>; font-weight: bold;">
                                    <?= date('M j, Y', strtotime($transaction['next_due'])) ?>
                                    <?php if ($days_until_due !== null): ?>
                                        <span style="font-size: 0.9rem; color: var(--text-secondary);">
                                            (<?= $is_overdue ? abs(round($days_until_due)) . ' days overdue' : round($days_until_due) . ' days left' ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <?php if ($transaction['is_active']): ?>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="recurring_id" value="<?= $transaction['id'] ?>">
                                        <button type="submit" class="action-btn btn-success" style="width: 100%;">
                                            💰 Mark as Paid
                                        </button>
                                    </form>
                                    <button onclick="toggleActive(<?= $transaction['id'] ?>)" class="action-btn btn-warning" style="flex: 1;">
                                        ⏸️ Pause
                                    </button>
                                <?php else: ?>
                                    <button onclick="toggleActive(<?= $transaction['id'] ?>)" class="action-btn btn-success" style="width: 100%;">
                                        ▶️ Resume
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$transaction['is_active']): ?>
                                <div style="text-align: center; padding: 10px; background: var(--warning-color); color: white; border-radius: var(--border-radius-sm); font-weight: bold; margin-top: 10px;">
                                    ⏸️ Paused
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function editRecurring(recurringId) {
            // Redirect to edit page or show edit modal
            alert('Edit functionality coming soon!');
        }
        
        function toggleActive(recurringId) {
            // Toggle active status
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_recurring">
                <input type="hidden" name="recurring_id" value="${recurringId}">
                <input type="hidden" name="is_active" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Auto-fill next due date based on frequency
        document.getElementById('frequency').addEventListener('change', function() {
            const frequency = this.value;
            const nextDueInput = document.getElementById('next_due');
            const today = new Date();
            
            if (frequency) {
                let nextDue = new Date(today);
                switch (frequency) {
                    case 'daily':
                        nextDue.setDate(today.getDate() + 1);
                        break;
                    case 'weekly':
                        nextDue.setDate(today.getDate() + 7);
                        break;
                    case 'monthly':
                        nextDue.setMonth(today.getMonth() + 1);
                        break;
                    case 'yearly':
                        nextDue.setFullYear(today.getFullYear() + 1);
                        break;
                }
                nextDueInput.value = nextDue.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>
