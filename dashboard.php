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

    // Fetch user's goals for dashboard summary
    $goals_summary = [];
    $stmt = $conn->prepare('SELECT id, name, target_amount, current_amount, deadline FROM budget_goals WHERE user_id = ? AND current_amount < target_amount ORDER BY deadline ASC LIMIT 3');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($goal_id, $goal_name, $goal_target, $goal_current, $goal_deadline);
    while ($stmt->fetch()) {
        $progress = $goal_target > 0 ? ($goal_current / $goal_target) * 100 : 0;
        $days_left = $goal_deadline ? max(0, (strtotime($goal_deadline) - time()) / 86400) : null;
        $goals_summary[] = [
            'id' => $goal_id,
            'name' => $goal_name,
            'target' => $goal_target,
            'current' => $goal_current,
            'progress' => $progress,
            'days_left' => $days_left
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
    <title>My Budget Dashboard - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your personal budget dashboard - track income, expenses, and savings">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/app.js" defer></script>
</head>
<body>
    <!-- Skip to main content for screen readers -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="dashboard.php" aria-current="page">My Dashboard</a>
            <a href="transactions.php">View All Transactions</a>
            <a href="add_transaction.php">Add New Transaction</a>
            <a href="budget_goals.php">Budget Goals</a>
            <a href="recurring.php">Recurring</a>
            <a href="help.php">Help & Tips</a>
            <a href="settings.php">Account Settings</a>
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                <span class="icon">☀️</span>
                <span>Theme</span>
            </button>
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>
    
    <main id="main-content" class="container">
        <header style="text-align:center; margin-bottom:40px;">
            <h1>Welcome Back, <?= htmlspecialchars($username) ?>!</h1>
            <p style="font-size: 1.2rem; color: #cccccc;">Here's your money summary for today</p>
        </header>
        <!-- Money Summary Cards with Clear Labels -->
        <section aria-labelledby="money-summary" style="margin-bottom: 50px;">
            <h2 id="money-summary" style="text-align: center; margin-bottom: 30px;">Your Money Summary</h2>
            <div class="card-grid">
                <div class="card" style="border-left: 5px solid #4CAF50;">
                    <div class="card-label">💰 Total Money Coming In</div>
                    <div class="card-value" style="color: #4CAF50; font-size: 2.5rem;">₱<?= number_format($total_income, 2) ?></div>
                    <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: #888;">All your income</p>
                </div>
                <div class="card" style="border-left: 5px solid #f44336;">
                    <div class="card-label">💸 Total Money Going Out</div>
                    <div class="card-value" style="color: #f44336; font-size: 2.5rem;">₱<?= number_format($total_expense, 2) ?></div>
                    <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: #888;">All your expenses</p>
                </div>
                <div class="card" style="border-left: 5px solid <?= $balance >= 0 ? '#2196F3' : '#ff9800' ?>;">
                    <div class="card-label"><?= $balance >= 0 ? '💵 Money Left Over' : '⚠️ Money Needed' ?></div>
                    <div class="card-value" style="color: <?= $balance >= 0 ? '#2196F3' : '#ff9800' ?>; font-size: 2.5rem;">
                        <?= $balance >= 0 ? '₱' : '-₱' ?><?= number_format(abs($balance), 2) ?>
                    </div>
                    <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: #888;">
                        <?= $balance >= 0 ? 'Your savings' : 'Over budget' ?>
                    </p>
                </div>
            </div>
        </section>
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
            
            <!-- Budget Goals Summary -->
            <?php if (!empty($goals_summary)): ?>
            <div style="margin-bottom:32px; border-left: 6px solid #ff6b6b; background: #232323; border-radius: 10px; box-shadow: 0 2px 8px #0002;">
                <h3 style="margin:0; padding: 16px 0 8px 20px; font-size:1.3em; background: linear-gradient(90deg, #ff6b6b 0 10%, transparent 60%); color:#fff; border-radius:10px 10px 0 0; letter-spacing:0.5px;">
                    🎯 Active Budget Goals
                </h3>
                <div style="padding: 20px; background:#222; border-radius:0 0 10px 10px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($goals_summary as $goal): ?>
                            <div style="background: #1a1a1a; padding: 15px; border-radius: 8px; border-left: 4px solid #ff6b6b;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #fff; font-size: 1.1rem;"><?= htmlspecialchars($goal['name']) ?></h4>
                                    <span style="color: #ff6b6b; font-weight: bold;"><?= number_format($goal['progress'], 1) ?>%</span>
                                </div>
                                <div style="background: #333; border-radius: 10px; height: 12px; overflow: hidden; margin-bottom: 10px;">
                                    <div style="background: linear-gradient(90deg, #ff6b6b, #ff8e8e); height: 100%; width: <?= min(100, $goal['progress']) ?>%; transition: width 0.5s ease;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #ccc;">
                                    <span>₱<?= number_format($goal['current'], 2) ?> / ₱<?= number_format($goal['target'], 2) ?></span>
                                    <?php if ($goal['days_left'] !== null): ?>
                                        <span><?= round($goal['days_left']) ?> days left</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="budget_goals.php" class="action-btn" style="background: #ff6b6b; border-color: #ff6b6b; font-size: 0.9rem;">
                            🎯 View All Goals
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
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
        <!-- Easy Action Buttons -->
        <section aria-labelledby="quick-actions" style="margin: 50px 0;">
            <h2 id="quick-actions" style="text-align: center; margin-bottom: 30px;">What would you like to do?</h2>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="add_transaction.php" class="action-btn" style="background: #4CAF50; border-color: #4CAF50;">
                    💰 Add Money Transaction
                </a>
                <a href="transactions.php" class="action-btn" style="background: #2196F3; border-color: #2196F3;">
                    📋 See All My Transactions
                </a>
                <a href="backup.php" class="action-btn" style="background: #FF9800; border-color: #FF9800;">
                    � Save My Data
                </a>
                <a href="settings.php" class="action-btn" style="background: #9C27B0; border-color: #9C27B0;">
                    ⚙️ Change My Settings
                </a>
            </div>
        </section>
    </div>
</body>
</html> 