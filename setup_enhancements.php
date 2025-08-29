<?php
/**
 * BudgetFlix Enhancements Setup Script
 * 
 * This script will:
 * 1. Create new database tables for budget goals and recurring transactions
 * 2. Add stored procedures for enhanced functionality
 * 3. Create database indexes for better performance
 * 4. Verify the installation
 */

// Include database configuration
require_once 'includes/db.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Please log in to run the setup script.');
}

$errors = [];
$success = [];

// Function to execute SQL and handle errors
function executeSQL($conn, $sql, $description) {
    global $errors, $success;
    
    try {
        if ($conn->query($sql)) {
            $success[] = "✅ $description - Success";
            return true;
        } else {
            $errors[] = "❌ $description - Failed: " . $conn->error;
            return false;
        }
    } catch (Exception $e) {
        $errors[] = "❌ $description - Error: " . $e->getMessage();
        return false;
    }
}

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to check if procedure exists
function procedureExists($conn, $procedureName) {
    $result = $conn->query("SHOW PROCEDURE STATUS WHERE Name = '$procedureName'");
    return $result->num_rows > 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BudgetFlix Enhancements Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar" role="navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="setup_enhancements.php" aria-current="page">Setup</a>
            <a href="help.php">Help</a>
        </div>
    </nav>
    
    <main class="container">
        <header style="text-align:center; margin-bottom:40px;">
            <h1>🚀 BudgetFlix Enhancements Setup</h1>
            <p style="font-size: 1.2rem; color: var(--text-secondary);">
                Installing new features and database improvements
            </p>
        </header>

        <?php if ($_POST['action'] === 'install'): ?>
            <!-- Installation Process -->
            <div class="card" style="border-left: 5px solid var(--primary-color);">
                <h2 style="color: var(--primary-color);">📋 Installation Progress</h2>
                
                <?php
                // 1. Create budget_goals table
                if (!tableExists($conn, 'budget_goals')) {
                    $sql = "CREATE TABLE `budget_goals` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `name` varchar(255) NOT NULL,
                        `target_amount` decimal(10,2) NOT NULL,
                        `current_amount` decimal(10,2) DEFAULT 0.00,
                        `deadline` date NULL,
                        `category` varchar(100) NULL,
                        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `user_id` (`user_id`),
                        KEY `deadline` (`deadline`),
                        KEY `category` (`category`),
                        CONSTRAINT `fk_budget_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    
                    executeSQL($conn, $sql, "Created budget_goals table");
                } else {
                    $success[] = "✅ budget_goals table already exists";
                }

                // 2. Create recurring_transactions table
                if (!tableExists($conn, 'recurring_transactions')) {
                    $sql = "CREATE TABLE `recurring_transactions` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `type` enum('income','expense') NOT NULL,
                        `amount` decimal(10,2) NOT NULL,
                        `description` varchar(255) NOT NULL,
                        `category` varchar(100) NOT NULL,
                        `frequency` enum('daily','weekly','monthly','yearly') NOT NULL,
                        `next_due` date NOT NULL,
                        `is_active` tinyint(1) DEFAULT 1,
                        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `user_id` (`user_id`),
                        KEY `next_due` (`next_due`),
                        KEY `is_active` (`is_active`),
                        CONSTRAINT `fk_recurring_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    
                    executeSQL($conn, $sql, "Created recurring_transactions table");
                } else {
                    $success[] = "✅ recurring_transactions table already exists";
                }

                // 3. Create stored procedures
                if (!procedureExists($conn, 'GetBudgetGoalsSummary')) {
                    $sql = "CREATE PROCEDURE GetBudgetGoalsSummary(IN user_id_param INT)
                    BEGIN
                        SELECT 
                            COUNT(*) as total_goals,
                            COUNT(CASE WHEN current_amount >= target_amount THEN 1 END) as completed_goals,
                            SUM(target_amount) as total_target,
                            SUM(current_amount) as total_current,
                            AVG(CASE WHEN current_amount < target_amount THEN (current_amount / target_amount) * 100 END) as avg_progress
                        FROM budget_goals 
                        WHERE user_id = user_id_param;
                    END";
                    
                    executeSQL($conn, $sql, "Created GetBudgetGoalsSummary stored procedure");
                } else {
                    $success[] = "✅ GetBudgetGoalsSummary procedure already exists";
                }

                if (!procedureExists($conn, 'GetRecurringDueSoon')) {
                    $sql = "CREATE PROCEDURE GetRecurringDueSoon(IN user_id_param INT, IN days_ahead INT)
                    BEGIN
                        SELECT 
                            id,
                            type,
                            amount,
                            description,
                            category,
                            frequency,
                            next_due,
                            DATEDIFF(next_due, CURDATE()) as days_until_due
                        FROM recurring_transactions 
                        WHERE user_id = user_id_param 
                        AND is_active = 1 
                        AND next_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL days_ahead DAY)
                        ORDER BY next_due ASC;
                    END";
                    
                    executeSQL($conn, $sql, "Created GetRecurringDueSoon stored procedure");
                } else {
                    $success[] = "✅ GetRecurringDueSoon procedure already exists";
                }

                // 4. Create additional indexes
                $indexes = [
                    "CREATE INDEX idx_budget_goals_user_deadline ON budget_goals(user_id, deadline)" => "budget_goals user_deadline index",
                    "CREATE INDEX idx_recurring_user_next_due ON recurring_transactions(user_id, next_due)" => "recurring user_next_due index",
                    "CREATE INDEX idx_recurring_active ON recurring_transactions(is_active, next_due)" => "recurring active index"
                ];

                foreach ($indexes as $sql => $description) {
                    executeSQL($conn, $sql, "Created $description");
                }
                ?>

                <!-- Results -->
                <div style="margin-top: 30px;">
                    <h3 style="color: var(--success-color);">✅ Successful Operations</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($success as $msg): ?>
                            <li style="margin: 5px 0; padding: 10px; background: var(--bg-card); border-radius: var(--border-radius-sm);">
                                <?= htmlspecialchars($msg) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!empty($errors)): ?>
                        <h3 style="color: var(--danger-color); margin-top: 20px;">❌ Errors</h3>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($errors as $error): ?>
                                <li style="margin: 5px 0; padding: 10px; background: rgba(244,67,54,0.1); border-radius: var(--border-radius-sm); color: var(--danger-color);">
                                    <?= htmlspecialchars($error) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Next Steps -->
                <div style="margin-top: 30px; padding: 20px; background: var(--bg-card); border-radius: var(--border-radius); border-left: 5px solid var(--accent-color);">
                    <h3 style="color: var(--accent-color); margin-top: 0;">🎉 Installation Complete!</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Your BudgetFlix application has been enhanced with new features:
                    </p>
                    <ul style="color: var(--text-secondary); margin-bottom: 20px;">
                        <li>🎯 <strong>Budget Goals</strong> - Set and track financial targets</li>
                        <li>🔄 <strong>Recurring Transactions</strong> - Automate regular income and expenses</li>
                        <li>🌙 <strong>Theme Toggle</strong> - Switch between dark and light modes</li>
                        <li>📱 <strong>Enhanced Mobile Experience</strong> - Better responsive design</li>
                        <li>⚡ <strong>Performance Improvements</strong> - Faster loading and better UX</li>
                    </ul>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="dashboard.php" class="action-btn btn-success">🚀 Go to Dashboard</a>
                        <a href="budget_goals.php" class="action-btn btn-info">🎯 Try Budget Goals</a>
                        <a href="recurring.php" class="action-btn btn-warning">🔄 Try Recurring</a>
                        <a href="help.php" class="action-btn">📖 View Help</a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Setup Instructions -->
            <div class="card" style="border-left: 5px solid var(--primary-color);">
                <h2 style="color: var(--primary-color);">📋 Setup Instructions</h2>
                
                <div style="margin-bottom: 30px;">
                    <h3>What this setup will do:</h3>
                    <ul style="color: var(--text-secondary);">
                        <li>Create new database tables for budget goals and recurring transactions</li>
                        <li>Add stored procedures for enhanced functionality</li>
                        <li>Create database indexes for better performance</li>
                        <li>Verify the installation and provide status updates</li>
                    </ul>
                </div>

                <div style="margin-bottom: 30px; padding: 20px; background: rgba(255,152,0,0.1); border-radius: var(--border-radius); border-left: 5px solid var(--warning-color);">
                    <h3 style="color: var(--warning-color); margin-top: 0;">⚠️ Important Notes</h3>
                    <ul style="color: var(--text-secondary);">
                        <li>This setup requires database administrator privileges</li>
                        <li>Make sure to backup your database before running this script</li>
                        <li>The script will only create tables that don't already exist</li>
                        <li>No existing data will be modified or deleted</li>
                    </ul>
                </div>

                <form method="POST" style="text-align: center;">
                    <input type="hidden" name="action" value="install">
                    <button type="submit" class="action-btn btn-success" style="font-size: 1.2rem; padding: 20px 40px;">
                        🚀 Start Installation
                    </button>
                </form>
            </div>

            <!-- Current Status -->
            <div class="card" style="border-left: 5px solid var(--accent-color); margin-top: 30px;">
                <h2 style="color: var(--accent-color);">📊 Current Database Status</h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="padding: 15px; background: var(--bg-card); border-radius: var(--border-radius-sm);">
                        <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">budget_goals table</h4>
                        <p style="margin: 0; color: <?= tableExists($conn, 'budget_goals') ? 'var(--success-color)' : 'var(--warning-color)' ?>;">
                            <?= tableExists($conn, 'budget_goals') ? '✅ Exists' : '❌ Missing' ?>
                        </p>
                    </div>
                    
                    <div style="padding: 15px; background: var(--bg-card); border-radius: var(--border-radius-sm);">
                        <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">recurring_transactions table</h4>
                        <p style="margin: 0; color: <?= tableExists($conn, 'recurring_transactions') ? 'var(--success-color)' : 'var(--warning-color)' ?>;">
                            <?= tableExists($conn, 'recurring_transactions') ? '✅ Exists' : '❌ Missing' ?>
                        </p>
                    </div>
                    
                    <div style="padding: 15px; background: var(--bg-card); border-radius: var(--border-radius-sm);">
                        <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">GetBudgetGoalsSummary procedure</h4>
                        <p style="margin: 0; color: <?= procedureExists($conn, 'GetBudgetGoalsSummary') ? 'var(--success-color)' : 'var(--warning-color)' ?>;">
                            <?= procedureExists($conn, 'GetBudgetGoalsSummary') ? '✅ Exists' : '❌ Missing' ?>
                        </p>
                    </div>
                    
                    <div style="padding: 15px; background: var(--bg-card); border-radius: var(--border-radius-sm);">
                        <h4 style="margin: 0 0 10px 0; color: var(--text-primary);">GetRecurringDueSoon procedure</h4>
                        <p style="margin: 0; color: <?= procedureExists($conn, 'GetRecurringDueSoon') ? 'var(--success-color)' : 'var(--warning-color)' ?>;">
                            <?= procedureExists($conn, 'GetRecurringDueSoon') ? '✅ Exists' : '❌ Missing' ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
