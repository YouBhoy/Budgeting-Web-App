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
    $query = 'SELECT type, amount, description, category, created_at FROM transactions WHERE user_id = ?';
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
    $stmt->bind_result($type, $amount, $description, $category, $created_at);
    while ($stmt->fetch()) {
        fputcsv($out, [$type, $amount, $description, $category, $created_at]);
    }
    fclose($out);
    exit();
}

// --- FETCH TRANSACTIONS WITH FILTERS ---
$transactions = [];
$query = 'SELECT type, amount, description, category, created_at FROM transactions WHERE user_id = ?';
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
$stmt->bind_result($type, $amount, $description, $category, $created_at);
while ($stmt->fetch()) {
    $transactions[] = [
        'type' => $type,
        'amount' => $amount,
        'description' => $description,
        'category' => $category,
        'created_at' => $created_at
    ];
}
$stmt->close();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions - BudgetFlix</title>
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
        <h2>Your Transactions</h2>
        <div style="margin-bottom:20px;">
            <a href="add_transaction.php" class="action-btn">Add Transaction</a>
            <a href="dashboard.php" class="action-btn">Back to Dashboard</a>
        </div>
        <!-- FILTER FORM -->
        <form method="get" action="transactions.php" style="margin-bottom: 20px;">
            <label>Type:
                <select name="type">
                    <option value="">All</option>
                    <option value="income"<?= $type_filter==='income'?' selected':''; ?>>Income</option>
                    <option value="expense"<?= $type_filter==='expense'?' selected':''; ?>>Expense</option>
                </select>
            </label>
            <label>Category:
                <select name="category">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"<?= $category_filter===$cat?' selected':''; ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"></label>
            <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>"></label>
            <button type="submit" class="action-btn">Filter</button>
            <a href="transactions.php" class="action-btn">Reset</a>
            <button type="submit" name="export" value="csv" class="action-btn">Export CSV</button>
        </form>
        <!-- TRANSACTIONS TABLE -->
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <!-- MONTHLY SUMMARY FORM -->
        <h3>Monthly Summary</h3>
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
        <?php elseif (isset($_POST['show_summary'])): ?>
            <p>No summary data found for this month.</p>
        <?php endif; ?>
    </div>
</body>
</html> 