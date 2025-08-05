<?php
require_once 'includes/db.php';
require_once 'includes/session.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$total_income = 0;
$total_expense = 0;
$balance = 0;

try {
    // Get totals using stored procedure
    $stmt = $conn->prepare('CALL GetUserTotals(?)');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($total_income, $total_expense);
    if ($stmt->fetch()) {
        $balance = $total_income - $total_expense;
    }
    $stmt->close();
    // Clear results for next query after CALL
    $conn->next_result();

    // Fetch 5 most recent transactions
    $recent_transactions = [];
    $stmt = $conn->prepare('SELECT type, amount, description, category, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($type, $amount, $description, $category, $created_at);
    while ($stmt->fetch()) {
        $recent_transactions[] = [
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'category' => $category,
            'created_at' => $created_at
        ];
    }
    $stmt->close();

    // Fetch 5 most recent expenses
    $recent_expenses = [];
    $stmt = $conn->prepare("SELECT type, amount, description, category, created_at FROM transactions WHERE user_id = ? AND type = 'expense' ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($type, $amount, $description, $category, $created_at);
    while ($stmt->fetch()) {
        $recent_expenses[] = [
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'category' => $category,
            'created_at' => $created_at
        ];
    }
    $stmt->close();

} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    // Continue with default values
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <div style="text-align:center; margin-bottom:32px;">
            <h2>Welcome to Your Dashboard</h2>
            <p>Hello, <?= htmlspecialchars($username) ?>!</p>
        </div>
        <div class="card-grid">
            <div class="card">
                <div class="card-label">Total Income</div>
                <div class="card-value"> ₱<?= number_format($total_income, 2) ?></div>
            </div>
            <div class="card">
                <div class="card-label">Total Expenses</div>
                <div class="card-value"> ₱<?= number_format($total_expense, 2) ?></div>
            </div>
            <div class="card">
                <div class="card-label">Balance</div>
                <div class="card-value"> ₱<?= number_format($balance, 2) ?></div>
            </div>
        </div>
        <!-- Charts Section -->
        <div style="margin: 40px 0 32px 0; display: flex; flex-wrap: wrap; gap: 32px; justify-content: center;">
            <div style="flex:1 1 320px; min-width:320px; max-width:480px; background:#232323; border-radius:12px; box-shadow:0 2px 8px #0002; padding:24px;">
                <h3 style="margin-top:0; margin-bottom:18px; color:#e50914;">Expenses by Category</h3>
                <canvas id="expensesPieChart"></canvas>
            </div>
            <div style="flex:1 1 420px; min-width:320px; max-width:600px; background:#232323; border-radius:12px; box-shadow:0 2px 8px #0002; padding:24px;">
                <h3 style="margin-top:0; margin-bottom:18px; color:#00e676;">Income vs Expenses (Last 6 Months)</h3>
                <canvas id="incomeExpenseBarChart"></canvas>
            </div>
        </div>
        <?php
        // Prepare data for charts
        // 1. Expenses by Category (Pie)
        $pie_labels = [];
        $pie_data = [];
        $pie_colors = [];
        $res = $conn->query("SELECT category, SUM(amount) as total FROM transactions WHERE user_id = $user_id AND type = 'expense' GROUP BY category ORDER BY total DESC");
        $color_palette = ['#e50914','#ff9800','#00e676','#2196f3','#ab47bc','#ffd600','#ff4081','#8d6e63','#607d8b','#bdbdbd'];
        $color_idx = 0;
        while ($row = $res->fetch_assoc()) {
            $pie_labels[] = $row['category'];
            $pie_data[] = (float)$row['total'];
            $pie_colors[] = $color_palette[$color_idx % count($color_palette)];
            $color_idx++;
        }
        $res->close();
        // 2. Income vs Expenses by Month (Bar)
        $bar_labels = [];
        $bar_income = [];
        $bar_expense = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $bar_labels[] = date('M Y', strtotime($month.'-01'));
            // Income
            $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = 'income' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $stmt->bind_param('is', $user_id, $month);
            $stmt->execute();
            $stmt->bind_result($inc);
            $stmt->fetch();
            $bar_income[] = (float)$inc;
            $stmt->close();
            // Expense
            $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = 'expense' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $stmt->bind_param('is', $user_id, $month);
            $stmt->execute();
            $stmt->bind_result($exp);
            $stmt->fetch();
            $bar_expense[] = (float)$exp;
            $stmt->close();
        }
        ?>
        <script>
        // Helper for pie legend
        function renderPieLegend(labels, data, colors) {
            let total = data.reduce((a, b) => a + b, 0);
            let html = '<ul style="list-style:none; padding:0; margin:0;">';
            for (let i = 0; i < labels.length; i++) {
                let pct = total ? ((data[i] / total) * 100).toFixed(1) : 0;
                html += `<li style="margin-bottom:6px; display:flex; align-items:center;">
                    <span style="display:inline-block; width:16px; height:16px; background:${colors[i]}; border-radius:3px; margin-right:10px;"></span>
                    <span style="min-width:110px; display:inline-block;">${labels[i]}</span>
                    <span style="margin-left:auto;">₱${data[i].toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} (${pct}%)</span>
                </li>`;
            }
            html += '</ul>';
            document.getElementById('pie-legend').innerHTML = html;
        }
        // Expenses by Category Pie Chart
        const pieLabels = <?= json_encode($pie_labels) ?>;
        const pieData = <?= json_encode($pie_data) ?>;
        const pieColors = <?= json_encode($pie_colors) ?>;
        renderPieLegend(pieLabels, pieData, pieColors);
        const pieCtx = document.getElementById('expensesPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: pieColors,
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.chart._metasets[0].total || pieData.reduce((a,b)=>a+b,0);
                                let pct = total ? ((value/total)*100).toFixed(1) : 0;
                                return `${label}: ₱${value.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
        // Income vs Expenses Bar Chart with Net Savings Line
        const barLabels = <?= json_encode($bar_labels) ?>;
        const barIncome = <?= json_encode($bar_income) ?>;
        const barExpense = <?= json_encode($bar_expense) ?>;
        const barNet = barIncome.map((v, i) => v - barExpense[i]);
        const barCtx = document.getElementById('incomeExpenseBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [
                    {
                        label: 'Income',
                        data: barIncome,
                        backgroundColor: '#00e676',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Expenses',
                        data: barExpense,
                        backgroundColor: '#e50914',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Net Savings',
                        data: barNet,
                        type: 'line',
                        borderColor: '#ffd600',
                        backgroundColor: 'rgba(255,214,0,0.15)',
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: '#ffd600',
                        fill: false,
                        yAxisID: 'y',
                        tension: 0.3
                    }
                ]
            },
            options: {
                plugins: {
                    legend: { labels: { color: '#fff', font: { size: 14 } } },
                    title: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                                return `${label}: ₱${value.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;
                            }
                        }
                    }
                },
                responsive: true,
                scales: {
                    x: { ticks: { color: '#fff' } },
                    y: { ticks: { color: '#fff' }, beginAtZero: true }
                }
            }
        });
        </script>
        <!-- Enhanced Pie Chart Legend -->
        <div id="pie-legend" style="margin-top:18px; color:#fff; font-size:1em;"></div>
        <!-- Bar Chart Breakdown Table -->
        <div style="margin-top:18px;">
            <table style="width:100%; background:#181818; color:#fff; border-radius:8px; overflow:hidden; border-collapse:collapse;">
                <thead>
                    <tr style="background:#222;">
                        <th style="padding:6px 8px;">Month</th>
                        <th style="padding:6px 8px; color:#00e676;">Income</th>
                        <th style="padding:6px 8px; color:#e50914;">Expenses</th>
                        <th style="padding:6px 8px; color:#ffd600;">Net Savings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($bar_labels); $i++): ?>
                    <tr style="border-bottom:1px solid #333;">
                        <td style="padding:6px 8px;"> <?= htmlspecialchars($bar_labels[$i]) ?> </td>
                        <td style="padding:6px 8px;"> <?= number_format($bar_income[$i], 2) ?> </td>
                        <td style="padding:6px 8px;"> <?= number_format($bar_expense[$i], 2) ?> </td>
                        <td style="padding:6px 8px;"> <?= number_format($bar_income[$i] - $bar_expense[$i], 2) ?> </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-bottom:32px; border-left: 6px solid #e50914; background: #232323; border-radius: 10px; box-shadow: 0 2px 8px #0002;">
                <h3 style="margin:0; padding: 16px 0 8px 20px; font-size:1.3em; background: linear-gradient(90deg, #e50914 0 10%, transparent 60%); color:#fff; border-radius:10px 10px 0 0; letter-spacing:0.5px;">
                    💸 Recent Expenses
                </h3>
                <table style="border-collapse: separate; border-spacing:0; width: 100%; background:#222; color:#fff; border-radius:0 0 10px 10px; overflow:hidden;">
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
                        <?php if (empty($recent_expenses)): ?>
                            <tr><td colspan="5" style="text-align:center;">No recent expenses.</td></tr>
                        <?php else: foreach ($recent_expenses as $t): ?>
                            <tr style="border-bottom:1px solid #333; transition:background 0.2s;" onmouseover="this.style.background='#292929'" onmouseout="this.style.background='none'">
                                <td><?= htmlspecialchars($t['type']) ?></td>
                                <td><?= number_format($t['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($t['description']) ?></td>
                                <td><?= htmlspecialchars($t['category']) ?></td>
                                <td><?= htmlspecialchars($t['created_at']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-bottom:32px; border-left: 6px solid #00e676; background: #232323; border-radius: 10px; box-shadow: 0 2px 8px #0002;">
                <h3 style="margin:0; padding: 16px 0 8px 20px; font-size:1.3em; background: linear-gradient(90deg, #00e676 0 10%, transparent 60%); color:#fff; border-radius:10px 10px 0 0; letter-spacing:0.5px;">
                    📋 Recent Transactions
                </h3>
                <table style="border-collapse: separate; border-spacing:0; width: 100%; background:#222; color:#fff; border-radius:0 0 10px 10px; overflow:hidden;">
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
                        <?php if (empty($recent_transactions)): ?>
                            <tr><td colspan="5" style="text-align:center;">No recent transactions.</td></tr>
                        <?php else: foreach ($recent_transactions as $t): ?>
                            <tr style="border-bottom:1px solid #333; transition:background 0.2s;" onmouseover="this.style.background='#292929'" onmouseout="this.style.background='none'">
                                <td><?= htmlspecialchars($t['type']) ?></td>
                                <td><?= number_format($t['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($t['description']) ?></td>
                                <td><?= htmlspecialchars($t['category']) ?></td>
                                <td><?= htmlspecialchars($t['created_at']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin-bottom:32px;">
            <a href="add_transaction.php" class="action-btn">Add Transaction</a>
            <a href="transactions.php" class="action-btn">View Transactions</a>
            <a href="settings.php" class="action-btn">Settings</a>
            <a href="backup.php" class="action-btn" style="background:#2196F3;">📦 Backup</a>
            <a href="logout.php" class="action-btn" style="background:#b0060f;">Logout</a>
        </div>
    </div>
</body>
</html> 