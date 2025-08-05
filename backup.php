<?php
require_once 'includes/db.php';
require_once 'includes/csrf.php';
require_once 'includes/session.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } else {
        try {
            // Get user data
            $stmt = $conn->prepare('SELECT username, email, account_type, currency, created_at FROM users WHERE id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();

            // Get transactions
            $stmt = $conn->prepare('SELECT type, amount, description, category, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $transactions = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Create backup data
            $backup_data = [
                'backup_info' => [
                    'created_at' => date('Y-m-d H:i:s'),
                    'app_version' => '1.0',
                    'user_id' => $user_id
                ],
                'user_data' => $user_data,
                'transactions' => $transactions
            ];

            // Generate filename
            $filename = 'budgetflix_backup_' . date('Y-m-d_H-i-s') . '.json';
            
            // Set headers for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen(json_encode($backup_data, JSON_PRETTY_PRINT)));
            
            // Output backup data
            echo json_encode($backup_data, JSON_PRETTY_PRINT);
            exit();

        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            $error = 'Failed to create backup. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup & Export - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .backup-section {
            background: #181818;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e50914;
        }
        .backup-info {
            background: #222;
            padding: 16px;
            border-radius: 6px;
            margin: 16px 0;
        }
    </style>
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
        <h2>📦 Backup & Export</h2>
        
        <?php if ($success): ?>
            <div style="color: #00e676; margin-bottom: 15px;">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="color: #e50914; margin-bottom: 15px;">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="backup-section">
            <h3>💾 Create Complete Backup</h3>
            <p>Download all your data including transactions, settings, and account information in JSON format.</p>
            
            <div class="backup-info">
                <strong>⚠️ Important for InfinityFree Users:</strong><br>
                • InfinityFree doesn't provide automatic backups<br>
                • Download backups regularly to prevent data loss<br>
                • Store backups securely on your local device<br>
                • Backup includes all your transaction history
            </div>

            <form method="post" style="margin-top: 20px;">
                <?= getCSRFInput() ?>
                <button type="submit" name="create_backup" class="action-btn" style="font-size: 1.1rem; padding: 14px 28px;">
                    📥 Download Complete Backup
                </button>
            </form>
        </div>

        <div class="backup-section">
            <h3>📊 Quick CSV Export</h3>
            <p>For spreadsheet analysis, you can also export just your transactions:</p>
            <div style="margin-top: 15px;">
                <a href="transactions.php?export=csv" class="action-btn" style="background: #2196F3;">
                    📈 Download Transactions CSV
                </a>
            </div>
        </div>

        <div class="backup-section">
            <h3>📋 Backup Instructions</h3>
            <ol style="line-height: 1.8; color: #ccc;">
                <li>Click "Download Complete Backup" to save all your data</li>
                <li>Store the backup file in a safe location (cloud storage recommended)</li>
                <li>Create backups regularly (weekly/monthly)</li>
                <li>Keep multiple backup versions for safety</li>
                <li>Test restore process occasionally</li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="action-btn" style="background: #666;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
