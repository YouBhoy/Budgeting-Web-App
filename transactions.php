<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FILTERS ---
$type_filter = $_GET['type'] ?? '';
$category_filter = $_GET['category'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// --- CSV EXPORT ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Type', 'Amount', 'Description', 'Category', 'Date']);
    $query = 'SELECT id, type, amount, description, category, created_at FROM transactions WHERE user_id = ?';
    $params = [$user_id];
    $types = 'i';
    if ($type_filter) { $query .= ' AND type = ?'; $params[] = $type_filter; $types .= 's'; }
    if ($category_filter) { $query .= ' AND category = ?'; $params[] = $category_filter; $types .= 's'; }
    if ($start_date) { $query .= ' AND created_at >= ?'; $params[] = $start_date; $types .= 's'; }
    if ($end_date) { $query .= ' AND created_at <= ?'; $params[] = $end_date; $types .= 's'; }
    $query .= ' ORDER BY created_at DESC';
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($id, $type, $amount, $description, $category, $created_at);
    while ($stmt->fetch()) {
        fputcsv($out, [$type, $amount, $description, $category, $created_at]);
    }
    fclose($out);
    exit();
}

// --- FETCH TRANSACTIONS WITH FILTERS ---
$transactions = [];
$query = 'SELECT id, type, amount, description, category, created_at FROM transactions WHERE user_id = ?';
$params = [$user_id];
$types = 'i';
if ($type_filter) { $query .= ' AND type = ?'; $params[] = $type_filter; $types .= 's'; }
if ($category_filter) { $query .= ' AND category = ?'; $params[] = $category_filter; $types .= 's'; }
if ($start_date) { $query .= ' AND created_at >= ?'; $params[] = $start_date; $types .= 's'; }
if ($end_date) { $query .= ' AND created_at <= ?'; $params[] = $end_date; $types .= 's'; }
$query .= ' ORDER BY created_at DESC';
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($id, $type, $amount, $description, $category, $created_at);
while ($stmt->fetch()) {
    $transactions[] = [
        'id' => $id,
        'type' => $type,
        'amount' => $amount,
        'description' => $description,
        'category' => $category,
        'created_at' => $created_at
    ];
}
$stmt->close();
// Group transactions by category for tabbed display
$transactions_by_category = [];
foreach ($transactions as $t) {
    $cat = $t['category'] ?: 'Uncategorized';
    if (!isset($transactions_by_category[$cat])) {
        $transactions_by_category[$cat] = [];
    }
    $transactions_by_category[$cat][] = $t;
}

// --- CATEGORY LIST FOR FILTERS ---
$categories = [];
$res = $conn->query("SELECT DISTINCT category FROM transactions WHERE user_id = $user_id ORDER BY category ASC");
while ($row = $res->fetch_assoc()) {
    $categories[] = $row['category'];
}
$res->close();

// --- MONTHLY SUMMARY ---
$summary = [];
$summary_month = $_POST['summary_month'] ?? date('m');
$summary_year = $_POST['summary_year'] ?? date('Y');
if (isset($_POST['show_summary'])) {
    $stmt = $conn->prepare('CALL GetMonthlySummary(?, ?, ?)');
    $stmt->bind_param('iii', $user_id, $summary_month, $summary_year);
    $stmt->execute();
    $stmt->bind_result($sum_type, $sum_category, $sum_total);
    while ($stmt->fetch()) {
        $summary[] = [
            'type' => $sum_type,
            'category' => $sum_category,
            'total' => $sum_total
        ];
    }
    $stmt->close();
    $conn->next_result();
}
// Group summary by category for tabbed display
$summary_by_category = [];
foreach ($summary as $s) {
    $cat = $s['category'] ?: 'Uncategorized';
    if (!isset($summary_by_category[$cat])) {
        $summary_by_category[$cat] = [];
    }
    $summary_by_category[$cat][] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .tab-bar { display: flex; gap: 8px; margin-bottom: 20px; }
        .tab-btn {
            background: #181818; color: #fff; border: none; padding: 8px 18px; border-radius: 6px 6px 0 0;
            cursor: pointer; font-weight: bold; outline: none; transition: background 0.2s;
        }
        .tab-btn.active { background: #e50914; color: #fff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
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
        <div class="section-card" style="background:#181818; border-radius:12px; box-shadow:0 2px 8px #0002; padding:32px 24px 24px 24px; margin-bottom:40px;">
        <h2 style="margin-top:0;">Your Transactions</h2>
        <div class="tab-bar">
            <button class="tab-btn active" data-tab="tab-all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="tab-btn" data-tab="tab-<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '', $cat)) ?>">
                    <?= htmlspecialchars($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <!-- TRANSACTIONS TABLES BY TAB -->
        <div id="tab-all" class="tab-content active">
            <?php if (empty($transactions)): ?>
                <p>No transactions found.</p>
            <?php else: ?>
            <table style="border-collapse: collapse; width: 100%; background:#222; color:#fff; border-radius:8px; overflow:hidden;">
                <thead>
                    <tr style="background:#181818;">
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr style="border-bottom:1px solid #333;">
                            <td><?= htmlspecialchars($t['type']) ?></td>
                            <td><?= number_format($t['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($t['description']) ?></td>
                            <td><?= htmlspecialchars($t['category']) ?></td>
                            <td><?= htmlspecialchars($t['created_at']) ?></td>
                            <td>
                                <a href="edit_transaction.php?id=<?= $t['id'] ?>" class="action-btn" style="padding:2px 10px; font-size:0.95em; background:#00bcd4;">Edit</a>
                                <a href="delete_transaction.php?id=<?= $t['id'] ?>" class="action-btn" style="padding:2px 10px; font-size:0.95em; background:#e50914;" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php foreach ($categories as $cat): ?>
        <div id="tab-<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '', $cat)) ?>" class="tab-content">
            <?php $cat_key = $cat ?: 'Uncategorized'; ?>
            <?php if (empty($transactions_by_category[$cat_key])): ?>
                <p>No transactions found in this category.</p>
            <?php else: ?>
            <table style="border-collapse: collapse; width: 100%; background:#222; color:#fff; border-radius:8px; overflow:hidden;">
                <thead>
                    <tr style="background:#181818;">
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions_by_category[$cat_key] as $t): ?>
                        <tr style="border-bottom:1px solid #333;">
                            <td><?= htmlspecialchars($t['type']) ?></td>
                            <td><?= number_format($t['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($t['description']) ?></td>
                            <td><?= htmlspecialchars($t['category']) ?></td>
                            <td><?= htmlspecialchars($t['created_at']) ?></td>
                            <td>
                                <a href="edit_transaction.php?id=<?= $t['id'] ?>" class="action-btn" style="padding:2px 10px; font-size:0.95em; background:#00bcd4;">Edit</a>
                                <a href="delete_transaction.php?id=<?= $t['id'] ?>" class="action-btn" style="padding:2px 10px; font-size:0.95em; background:#e50914;" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="section-card" style="background:#181818; border-radius:12px; box-shadow:0 2px 8px #0002; padding:32px 24px 24px 24px; margin-bottom:40px;">
        <h3 style="margin-top:0;">Monthly Summary</h3>
        <form method="post" action="transactions.php" style="margin-bottom: 20px;">
            <label>Month:
                <select name="summary_month">
                    <?php for ($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>"<?= $summary_month==$m?' selected':''; ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>Year:
                <select name="summary_year">
                    <?php for ($y=date('Y')-5; $y<=date('Y'); $y++): ?>
                        <option value="<?= $y ?>"<?= $summary_year==$y?' selected':''; ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <button type="submit" name="show_summary" value="1" class="action-btn">Show Summary</button>
        </form>
        <?php if (!empty($summary)): ?>
        <div class="tab-bar summary-tab-bar">
            <button class="tab-btn summary-tab-btn active" data-tab="summary-tab-all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="tab-btn summary-tab-btn" data-tab="summary-tab-<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '', $cat)) ?>">
                    <?= htmlspecialchars($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div id="summary-tab-all" class="tab-content summary-tab-content active">
            <table style="border-collapse: collapse; width: 100%; background:#222; color:#fff; border-radius:8px; overflow:hidden;">
                <thead>
                    <tr style="background:#181818;">
                        <th>Type</th>
                        <th>Category</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $s): ?>
                        <tr style="border-bottom:1px solid #333;">
                            <td><?= htmlspecialchars($s['type']) ?></td>
                            <td><?= htmlspecialchars($s['category']) ?></td>
                            <td><?= number_format($s['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php foreach ($categories as $cat): ?>
        <div id="summary-tab-<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '', $cat)) ?>" class="tab-content summary-tab-content">
            <?php $cat_key = $cat ?: 'Uncategorized'; ?>
            <?php if (empty($summary_by_category[$cat_key])): ?>
                <p>No summary data found for this category in this month.</p>
            <?php else: ?>
            <table style="border-collapse: collapse; width: 100%; background:#222; color:#fff; border-radius:8px; overflow:hidden;">
                <thead>
                    <tr style="background:#181818;">
                        <th>Type</th>
                        <th>Category</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary_by_category[$cat_key] as $s): ?>
                        <tr style="border-bottom:1px solid #333;">
                            <td><?= htmlspecialchars($s['type']) ?></td>
                            <td><?= htmlspecialchars($s['category']) ?></td>
                            <td><?= number_format($s['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php elseif (isset($_POST['show_summary'])): ?>
            <p>No summary data found for this month.</p>
        <?php endif; ?>
        </div>
    </div>
</body>
</html> 