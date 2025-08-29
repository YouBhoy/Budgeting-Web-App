<?php
/**
 * Migration Script for Recurring Transactions Table
 * 
 * This script will update the existing recurring_transactions table
 * to match the new structure expected by the enhanced features.
 */

// Include database configuration
require_once 'includes/db.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Please log in to run the migration script.');
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

// Function to check if column exists
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result->num_rows > 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recurring Table Migration - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar" role="navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="migrate_recurring_table.php" aria-current="page">Migration</a>
            <a href="help.php">Help</a>
        </div>
    </nav>
    
    <main class="container">
        <header style="text-align:center; margin-bottom:40px;">
            <h1>🔄 Recurring Table Migration</h1>
            <p style="font-size: 1.2rem; color: var(--text-secondary);">
                Updating existing recurring_transactions table structure
            </p>
        </header>

        <?php if ($_POST['action'] === 'migrate'): ?>
            <!-- Migration Process -->
            <div class="card" style="border-left: 5px solid var(--primary-color);">
                <h2 style="color: var(--primary-color);">📋 Migration Progress</h2>
                
                <?php
                // Check current table structure
                $currentColumns = [];
                $result = $conn->query("SHOW COLUMNS FROM recurring_transactions");
                while ($row = $result->fetch_assoc()) {
                    $currentColumns[] = $row['Field'];
                }
                
                // Migration steps
                $migrationSteps = [];
                
                // 1. Rename recurrence to frequency
                if (in_array('recurrence', $currentColumns) && !in_array('frequency', $currentColumns)) {
                    $migrationSteps[] = [
                        'sql' => "ALTER TABLE recurring_transactions CHANGE recurrence frequency ENUM('daily','weekly','monthly','yearly') NOT NULL",
                        'description' => 'Renamed recurrence column to frequency'
                    ];
                }
                
                // 2. Rename next_date to next_due
                if (in_array('next_date', $currentColumns) && !in_array('next_due', $currentColumns)) {
                    $migrationSteps[] = [
                        'sql' => "ALTER TABLE recurring_transactions CHANGE next_date next_due DATE NOT NULL",
                        'description' => 'Renamed next_date column to next_due'
                    ];
                }
                
                // 3. Add is_active column
                if (!in_array('is_active', $currentColumns)) {
                    $migrationSteps[] = [
                        'sql' => "ALTER TABLE recurring_transactions ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER next_due",
                        'description' => 'Added is_active column'
                    ];
                }
                
                // 4. Add updated_at column
                if (!in_array('updated_at', $currentColumns)) {
                    $migrationSteps[] = [
                        'sql' => "ALTER TABLE recurring_transactions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
                        'description' => 'Added updated_at column'
                    ];
                }
                
                // 5. Remove end_date column (not needed in new structure)
                if (in_array('end_date', $currentColumns)) {
                    $migrationSteps[] = [
                        'sql' => "ALTER TABLE recurring_transactions DROP COLUMN end_date",
                        'description' => 'Removed end_date column (not needed)'
                    ];
                }
                
                // Execute migration steps
                foreach ($migrationSteps as $step) {
                    executeSQL($conn, $step['sql'], $step['description']);
                }
                
                // Create indexes after migration
                if (empty($errors)) {
                    $indexes = [
                        "CREATE INDEX idx_recurring_user_next_due ON recurring_transactions(user_id, next_due)" => "recurring user_next_due index",
                        "CREATE INDEX idx_recurring_active ON recurring_transactions(is_active, next_due)" => "recurring active index"
                    ];
                    
                    foreach ($indexes as $sql => $description) {
                        executeSQL($conn, $sql, "Created $description");
                    }
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
                <?php if (empty($errors)): ?>
                    <div style="margin-top: 30px; padding: 20px; background: var(--bg-card); border-radius: var(--border-radius); border-left: 5px solid var(--accent-color);">
                        <h3 style="color: var(--accent-color); margin-top: 0;">🎉 Migration Complete!</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 20px;">
                            Your recurring_transactions table has been successfully updated to support the new features:
                        </p>
                        <ul style="color: var(--text-secondary); margin-bottom: 20px;">
                            <li>🔄 <strong>Enhanced Recurring Transactions</strong> - Better frequency management</li>
                            <li>⏸️ <strong>Pause/Resume Functionality</strong> - Control active status</li>
                            <li>📅 <strong>Improved Due Date Tracking</strong> - Better date management</li>
                            <li>⚡ <strong>Performance Indexes</strong> - Faster queries</li>
                        </ul>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <a href="dashboard.php" class="action-btn btn-success">🚀 Go to Dashboard</a>
                            <a href="recurring.php" class="action-btn btn-warning">🔄 Try Recurring</a>
                            <a href="setup_enhancements.php" class="action-btn btn-info">🔧 Complete Setup</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Migration Instructions -->
            <div class="card" style="border-left: 5px solid var(--primary-color);">
                <h2 style="color: var(--primary-color);">📋 Migration Instructions</h2>
                
                <div style="margin-bottom: 30px;">
                    <h3>What this migration will do:</h3>
                    <ul style="color: var(--text-secondary);">
                        <li>Rename <code>recurrence</code> column to <code>frequency</code></li>
                        <li>Rename <code>next_date</code> column to <code>next_due</code></li>
                        <li>Add <code>is_active</code> column for pause/resume functionality</li>
                        <li>Add <code>updated_at</code> column for better tracking</li>
                        <li>Remove <code>end_date</code> column (not needed)</li>
                        <li>Create performance indexes</li>
                    </ul>
                </div>

                <div style="margin-bottom: 30px; padding: 20px; background: rgba(255,152,0,0.1); border-radius: var(--border-radius); border-left: 5px solid var(--warning-color);">
                    <h3 style="color: var(--warning-color); margin-top: 0;">⚠️ Important Notes</h3>
                    <ul style="color: var(--text-secondary);">
                        <li>This migration will modify your existing recurring_transactions table</li>
                        <li>Make sure to backup your database before running this script</li>
                        <li>Existing data will be preserved during the migration</li>
                        <li>The migration is safe and can be run multiple times</li>
                    </ul>
                </div>

                <!-- Current Table Structure -->
                <div style="margin-bottom: 30px;">
                    <h3>Current Table Structure:</h3>
                    <?php
                    $result = $conn->query("SHOW COLUMNS FROM recurring_transactions");
                    if ($result->num_rows > 0):
                    ?>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr style="background: var(--bg-card);">
                                    <th style="padding: 10px; text-align: left; border: 1px solid var(--border-color);">Column</th>
                                    <th style="padding: 10px; text-align: left; border: 1px solid var(--border-color);">Type</th>
                                    <th style="padding: 10px; text-align: left; border: 1px solid var(--border-color);">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 10px; border: 1px solid var(--border-color);"><?= htmlspecialchars($row['Field']) ?></td>
                                        <td style="padding: 10px; border: 1px solid var(--border-color);"><?= htmlspecialchars($row['Type']) ?></td>
                                        <td style="padding: 10px; border: 1px solid var(--border-color);">
                                            <?php
                                            $status = '';
                                            $color = '';
                                            switch ($row['Field']) {
                                                case 'recurrence':
                                                    $status = 'Will be renamed to frequency';
                                                    $color = 'var(--warning-color)';
                                                    break;
                                                case 'next_date':
                                                    $status = 'Will be renamed to next_due';
                                                    $color = 'var(--warning-color)';
                                                    break;
                                                case 'end_date':
                                                    $status = 'Will be removed';
                                                    $color = 'var(--danger-color)';
                                                    break;
                                                case 'frequency':
                                                case 'next_due':
                                                case 'is_active':
                                                case 'updated_at':
                                                    $status = 'Already exists';
                                                    $color = 'var(--success-color)';
                                                    break;
                                                default:
                                                    $status = 'Will be preserved';
                                                    $color = 'var(--text-secondary)';
                                            }
                                            ?>
                                            <span style="color: <?= $color ?>;"><?= $status ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: var(--text-secondary);">No columns found in recurring_transactions table.</p>
                    <?php endif; ?>
                </div>

                <form method="POST" style="text-align: center;">
                    <input type="hidden" name="action" value="migrate">
                    <button type="submit" class="action-btn btn-success" style="font-size: 1.2rem; padding: 20px 40px;">
                        🔄 Start Migration
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
