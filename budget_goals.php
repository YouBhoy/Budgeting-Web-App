<?php
require_once 'includes/db.php';
require_once 'includes/session.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Handle goal creation
    if ($action === 'create_goal') {
        $name = trim($_POST['goal_name'] ?? '');
        $target_amount = floatval($_POST['target_amount'] ?? 0);
        $deadline = $_POST['deadline'] ?? '';
        $category = trim($_POST['category'] ?? '');
        
        if ($name && $target_amount > 0) {
            $stmt = $conn->prepare('INSERT INTO budget_goals (user_id, name, target_amount, current_amount, deadline, category, created_at) VALUES (?, ?, ?, 0, ?, ?, NOW())');
            $stmt->bind_param('isdss', $user_id, $name, $target_amount, $deadline, $category);
            $stmt->execute();
            $stmt->close();
            
            header('Location: budget_goals.php?success=goal_created');
            exit();
        }
    }
    
    // Handle goal updates
    if ($action === 'update_progress') {
        $goal_id = intval($_POST['goal_id'] ?? 0);
        $current_amount = floatval($_POST['current_amount'] ?? 0);
        
        if ($goal_id > 0) {
            $stmt = $conn->prepare('UPDATE budget_goals SET current_amount = ? WHERE id = ? AND user_id = ?');
            $stmt->bind_param('dii', $current_amount, $goal_id, $user_id);
            $stmt->execute();
            $stmt->close();
            
            header('Location: budget_goals.php?success=progress_updated');
            exit();
        }
    }
    
    // Handle goal deletion
    if ($action === 'delete_goal') {
        $goal_id = intval($_POST['goal_id'] ?? 0);
        
        if ($goal_id > 0) {
            $stmt = $conn->prepare('DELETE FROM budget_goals WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $goal_id, $user_id);
            $stmt->execute();
            $stmt->close();
            
            header('Location: budget_goals.php?success=goal_deleted');
            exit();
        }
    }
}

// Fetch user's goals
$goals = [];
$stmt = $conn->prepare('SELECT id, name, target_amount, current_amount, deadline, category, created_at FROM budget_goals WHERE user_id = ? ORDER BY deadline ASC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($id, $name, $target_amount, $current_amount, $deadline, $category, $created_at);
while ($stmt->fetch()) {
    $goals[] = [
        'id' => $id,
        'name' => $name,
        'target_amount' => $target_amount,
        'current_amount' => $current_amount,
        'deadline' => $deadline,
        'category' => $category,
        'created_at' => $created_at
    ];
}
$stmt->close();

// Calculate total progress
$total_target = array_sum(array_column($goals, 'target_amount'));
$total_current = array_sum(array_column($goals, 'current_amount'));
$overall_progress = $total_target > 0 ? ($total_current / $total_target) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Goals - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Set and track your financial goals">
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
            <a href="budget_goals.php" aria-current="page">Budget Goals</a>
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
        <header style="text-align:center; margin-bottom:40px;" class="fade-in-up">
            <h1>🎯 Budget Goals</h1>
            <p style="font-size: 1.2rem; color: var(--text-secondary);">Set and track your financial goals</p>
        </header>

        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success fade-in">
                <?php
                switch ($_GET['success']) {
                    case 'goal_created': echo '✅ Goal created successfully!'; break;
                    case 'progress_updated': echo '📈 Progress updated successfully!'; break;
                    case 'goal_deleted': echo '🗑️ Goal deleted successfully!'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Overall Progress -->
        <section style="margin-bottom: 40px;" class="fade-in-up">
            <div class="card" style="text-align: center; border-left: 5px solid var(--primary-color);">
                <h2 style="margin-bottom: 20px; color: var(--primary-color);">📊 Overall Progress</h2>
                <div style="font-size: 3rem; font-weight: bold; color: var(--primary-color); margin-bottom: 10px;">
                    <?= number_format($overall_progress, 1) ?>%
                </div>
                <div style="background: var(--bg-card); border-radius: 10px; height: 20px; margin: 20px 0; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); height: 100%; width: <?= $overall_progress ?>%; transition: width 0.5s ease;"></div>
                </div>
                <p style="color: var(--text-secondary);">
                    ₱<?= number_format($total_current, 2) ?> of ₱<?= number_format($total_target, 2) ?> saved
                </p>
            </div>
        </section>

        <!-- Create New Goal -->
        <section style="margin-bottom: 40px;" class="fade-in-up">
            <div class="card" style="border-left: 5px solid var(--success-color);">
                <h2 style="margin-bottom: 20px; color: var(--success-color);">➕ Create New Goal</h2>
                <form method="POST" style="max-width: none; margin: 0;">
                    <input type="hidden" name="action" value="create_goal">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div>
                            <label for="goal_name">Goal Name *</label>
                            <input type="text" id="goal_name" name="goal_name" required placeholder="e.g., Emergency Fund">
                        </div>
                        
                        <div>
                            <label for="target_amount">Target Amount (₱) *</label>
                            <input type="number" id="target_amount" name="target_amount" required min="0" step="0.01" placeholder="10000">
                        </div>
                        
                        <div>
                            <label for="deadline">Target Date</label>
                            <input type="date" id="deadline" name="deadline" min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div>
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">Select Category</option>
                                <option value="Emergency Fund">Emergency Fund</option>
                                <option value="Vacation">Vacation</option>
                                <option value="Home">Home</option>
                                <option value="Car">Car</option>
                                <option value="Education">Education</option>
                                <option value="Investment">Investment</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="action-btn btn-success" style="margin-top: 20px;">
                        🎯 Create Goal
                    </button>
                </form>
            </div>
        </section>

        <!-- Goals List -->
        <section class="fade-in-up">
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">📋 Your Goals</h2>
            
            <?php if (empty($goals)): ?>
                <div class="card" style="text-align: center; border-left: 5px solid var(--warning-color);">
                    <div style="font-size: 3rem; margin-bottom: 20px;">🎯</div>
                    <h3 style="color: var(--warning-color);">No Goals Yet</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Create your first budget goal to start tracking your progress!
                    </p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($goals as $goal): ?>
                        <?php
                        $progress = $goal['target_amount'] > 0 ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
                        $days_left = $goal['deadline'] ? max(0, (strtotime($goal['deadline']) - time()) / 86400) : null;
                        $is_overdue = $days_left !== null && $days_left < 0;
                        $is_completed = $progress >= 100;
                        ?>
                        
                        <div class="card" style="border-left: 5px solid <?= $is_completed ? 'var(--success-color)' : ($is_overdue ? 'var(--danger-color)' : 'var(--primary-color)') ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                <h3 style="margin: 0; color: var(--text-primary);"><?= htmlspecialchars($goal['name']) ?></h3>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="editGoal(<?= $goal['id'] ?>)" class="action-btn btn-info" style="padding: 8px 12px; font-size: 0.9rem;">
                                        ✏️
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this goal?')">
                                        <input type="hidden" name="action" value="delete_goal">
                                        <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                                        <button type="submit" class="action-btn btn-danger" style="padding: 8px 12px; font-size: 0.9rem;">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if ($goal['category']): ?>
                                <p style="color: var(--text-secondary); margin-bottom: 10px; font-size: 0.9rem;">
                                    📂 <?= htmlspecialchars($goal['category']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="color: var(--text-secondary);">Progress</span>
                                    <span style="color: var(--text-primary); font-weight: bold;">
                                        <?= number_format($progress, 1) ?>%
                                    </span>
                                </div>
                                <div style="background: var(--bg-card); border-radius: 10px; height: 15px; overflow: hidden;">
                                    <div style="background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); height: 100%; width: <?= min(100, $progress) ?>%; transition: width 0.5s ease;"></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <div>
                                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Current</div>
                                    <div style="color: var(--text-primary); font-weight: bold; font-size: 1.1rem;">
                                        ₱<?= number_format($goal['current_amount'], 2) ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Target</div>
                                    <div style="color: var(--text-primary); font-weight: bold; font-size: 1.1rem;">
                                        ₱<?= number_format($goal['target_amount'], 2) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($goal['deadline']): ?>
                                <div style="margin-bottom: 15px;">
                                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Deadline</div>
                                    <div style="color: <?= $is_overdue ? 'var(--danger-color)' : 'var(--text-primary)' ?>; font-weight: bold;">
                                        <?= date('M j, Y', strtotime($goal['deadline'])) ?>
                                        <?php if ($days_left !== null): ?>
                                            <span style="font-size: 0.9rem; color: var(--text-secondary);">
                                                (<?= $is_overdue ? abs(round($days_left)) . ' days overdue' : round($days_left) . ' days left' ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$is_completed): ?>
                                <button onclick="updateProgress(<?= $goal['id'] ?>, <?= $goal['current_amount'] ?>, <?= $goal['target_amount'] ?>)" class="action-btn btn-success" style="width: 100%;">
                                    💰 Update Progress
                                </button>
                            <?php else: ?>
                                <div style="text-align: center; padding: 10px; background: var(--success-color); color: white; border-radius: var(--border-radius-sm); font-weight: bold;">
                                    🎉 Goal Completed!
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Progress Update Modal -->
    <div id="progressModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: var(--bg-secondary); padding: 30px; border-radius: var(--border-radius); max-width: 400px; width: 90%;">
            <h3 style="margin-top: 0; color: var(--primary-color);">Update Progress</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_progress">
                <input type="hidden" name="goal_id" id="modalGoalId">
                
                <label for="current_amount">Current Amount (₱)</label>
                <input type="number" id="modalCurrentAmount" name="current_amount" required min="0" step="0.01">
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="action-btn btn-success">💾 Save</button>
                    <button type="button" onclick="closeModal()" class="action-btn btn-danger">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateProgress(goalId, currentAmount, targetAmount) {
            document.getElementById('modalGoalId').value = goalId;
            document.getElementById('modalCurrentAmount').value = currentAmount;
            document.getElementById('progressModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('progressModal').style.display = 'none';
        }
        
        function editGoal(goalId) {
            // Redirect to edit page or show edit modal
            alert('Edit functionality coming soon!');
        }
        
        // Close modal when clicking outside
        document.getElementById('progressModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
