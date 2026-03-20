<?php
include 'config.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// logged-in user ID
$user_id = $_SESSION['user_id'];
// Detect whether `deleted_at` column exists so totals exclude trashed rows
$dbRowForTotals = $conn->query("SELECT DATABASE()")->fetch_row();
$dbNameForTotals = $dbRowForTotals ? $dbRowForTotals[0] : null;
$hasDeletedAtForTotals = false;
if ($dbNameForTotals) {
    $col_stmt_tot = $conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'deleted_at'");
    $col_stmt_tot->bind_param('s', $dbNameForTotals);
    $col_stmt_tot->execute();
    $col_res_tot = $col_stmt_tot->get_result();
    $col_row_tot = $col_res_tot->fetch_assoc();
    $hasDeletedAtForTotals = !empty($col_row_tot['cnt']);
    $col_stmt_tot->close();
}

// Get total income/expense (exclude trashed rows when possible)
$totals_sql = "SELECT SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as total_income, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as total_expense FROM transactions WHERE user_id=?";
if ($hasDeletedAtForTotals) {
    $totals_sql .= " AND deleted_at IS NULL";
}
$stmt = $conn->prepare($totals_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$income = $row['total_income'] ?? 0;
$expense = $row['total_expense'] ?? 0;
$balance = $income - $expense;

$stmt->close();
?>

<?php
// Fetch recent transactions for display and trashed count (if column exists)
$dbRow = $conn->query("SELECT DATABASE()")->fetch_row();
$dbName = $dbRow ? $dbRow[0] : null;
$hasDeletedAt = false;
if ($dbName) {
    $col_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'deleted_at'");
    $col_stmt->bind_param('s', $dbName);
    $col_stmt->execute();
    $col_res = $col_stmt->get_result();
    $col_row = $col_res->fetch_assoc();
    $hasDeletedAt = !empty($col_row['cnt']);
    $col_stmt->close();
}

$trash_count = 0;
if ($hasDeletedAt) {
    $tx_stmt = $conn->prepare("SELECT id, type, amount, category, note, transaction_date FROM transactions WHERE user_id=? AND deleted_at IS NULL ORDER BY transaction_date DESC LIMIT 20");
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM transactions WHERE user_id = ? AND deleted_at IS NOT NULL");
    $count_stmt->bind_param('i', $user_id);
    $count_stmt->execute();
    $count_res = $count_stmt->get_result();
    $count_row = $count_res->fetch_assoc();
    $trash_count = (int)($count_row['cnt'] ?? 0);
    $count_stmt->close();
} else {
    $tx_stmt = $conn->prepare("SELECT id, type, amount, category, note, transaction_date FROM transactions WHERE user_id=? ORDER BY transaction_date DESC LIMIT 20");
}
$tx_stmt->bind_param("i", $user_id);
$tx_stmt->execute();
$tx_result = $tx_stmt->get_result();
$transactions = $tx_result->fetch_all(MYSQLI_ASSOC);
$tx_stmt->close();

// Group transactions by month
$monthly_income = array_fill(1, 12, 0);
$monthly_expense = array_fill(1, 12, 0);

foreach ($transactions as $t) {
    $month = (int)date('n', strtotime($t['transaction_date']));
    if ($t['type'] === 'income') {
        $monthly_income[$month] += $t['amount'];
    } else {
        $monthly_expense[$month] += $t['amount'];
    }
}
// Monthly balance
$monthly_balance = [];
for ($m = 1; $m <= 12; $m++) {
    $monthly_balance[$m] = $monthly_income[$m] - $monthly_expense[$m];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <script defer src="assets/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js">// For charts</script> 
    <style> .hidden{display:none} </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar collapsed" id="sidebar">
            <div class="top">
                <div class="brand">ExpenseTracker</div>
                <button id="sidebarToggle" class="btn" aria-label="Toggle sidebar" style="padding:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 12h18M3 6h18M3 18h18" stroke="#374151" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <nav>
                <a href="dashboard.php">
                    <span class="icon"><svg width="20" height="20" aria-hidden="true"><use href="assets/icons.svg#icon-home"></use></svg></span>
                    <span class="label">Dashboard</span>
                </a>
                <a href="#" id="sidebarAdd">
                    <span class="icon"><svg width="20" height="20" aria-hidden="true"><use href="assets/icons.svg#icon-add"></use></svg></span>
                    <span class="label">Add</span>
                </a>
                 <a href="#">
                    <span class="icon"><svg width="20" height="20" aria-hidden="true"><use href="assets/icons.svg#icon-settings"></use></svg></span>
                    <span class="label">Type</span>
                </a>
                <a href="trash.php">
                    <span class="icon"><svg width="20" height="20" aria-hidden="true"><use href="assets/icons.svg#icon-trash"></use></svg></span>
                    <span class="label">Deleted</span>
                    <span class="badge" id="trashCount"><?php echo (int)$trash_count; ?></span>
                </a>
                <a href="#">
                    <span class="icon"><svg width="20" height="20" aria-hidden="true"><use href="assets/icons.svg#icon-settings"></use></svg></span>
                    <span class="label">Settings</span>
                </a>
            </nav>
            <div class="sidebar-bottom">
                <a id="sidebarLogout" href="logout.php" class="btn logout-btn" style="background:#ef4444;color:#fff;border-radius:6px;"> 
                    <span class="icon"><svg width="16" height="16" aria-hidden="true"><use href="assets/icons.svg#icon-settings"></use></svg></span>
                    <span class="label">Logout</span>
                </a>
            </div>
        </aside>

        <main class="main">
        <div class="container">
        <div class="header">
            <h1>Dashboard</h1>
            <div>
                <button id="addBtn" class="btn">Add Transaction</button>
            </div>
        </div>

        <div class="stats">
            <div class="card">
                <div class="label">Total Income</div>
                <div class="value" id="totalIncome"><?php echo number_format($income,2); ?></div>
            </div>
            <div class="card">
                <div class="label">Total Expense</div>
                <div class="value" id="totalExpense"><?php echo number_format($expense,2); ?></div>
            </div>
            <div class="card">
                <div class="label">Balance</div>
                <div class="value" id="balance"><?php echo number_format($balance,2); ?></div>
            </div>
        </div>
        <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <h3>Income & Expense with Balance</h3>
            </div>
            <canvas id="comboChart"></canvas>
        </div>
        </div>

        </div>
        <div id="addForm" class="card hidden">
            <form method="POST" action="add_transaction.php" class="add-form">
                <select name="type" required>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
                <input class="input" name="amount" type="number" step="0.01" placeholder="Amount" required>
                <input class="input" name="category" type="text" placeholder="Category">
                <input class="input" name="note" type="text" placeholder="Note">
                <button class="btn" type="submit">Save</button>
            </form>
        </div>

        <div class="transactions card">
            <h3>Recent Transactions</h3>
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Category</th><th>Note</th><th>Amount</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr data-id="<?php echo (int)$t['id']; ?>">
                                <td class="cell-date"><?php echo htmlspecialchars($t['transaction_date']); ?></td>
                                <td class="cell-type"><?php echo htmlspecialchars($t['type']); ?></td>
                                <td class="cell-category"><?php echo htmlspecialchars($t['category']); ?></td>
                                <td class="cell-note"><?php echo htmlspecialchars($t['note'] ?? ''); ?></td>
                                <td class="cell-amount"><?php echo number_format($t['amount'],2); ?></td>
                                <td class="cell-actions">
                                    <button class="btn editBtn" data-id="<?php echo (int)$t['id']; ?>" style="background:#f97316;padding:6px 8px;font-size:13px">Edit</button>
                                    <button class="btn deleteBtn" data-id="<?php echo (int)$t['id']; ?>" aria-label="Delete transaction" style="background:#ef4444;padding:6px 8px;font-size:13px;margin-left:6px">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No transactions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="footer-note">Manage transactions using the form above.</div>
        </div>
        </div>
        </main>
    </div>
</body>

<script>
    const monthlyIncome = <?php echo json_encode(array_values($monthly_income)); ?>;
    const monthlyExpense = <?php echo json_encode(array_values($monthly_expense)); ?>;
    const monthlyBalance = <?php echo json_encode(array_values($monthly_balance)); ?>;

    const ctxCombo = document.getElementById('comboChart').getContext('2d');
    const comboChart = new Chart(ctxCombo, {
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [
                {
                    type: 'bar',
                    label: 'Income',
                    data: monthlyIncome,
                    backgroundColor: 'rgba(16,185,129,0.7)',
                    yAxisID: 'y'
                },
                {
                    type: 'bar',
                    label: 'Expense',
                    data: monthlyExpense,
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    yAxisID: 'y'
                },
                {
                    type: 'line',
                    label: 'Balance',
                    data: monthlyBalance,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',   // show all datasets at hovered index
                intersect: false
            },
            plugins: {
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.formattedValue;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: { display: true, text: 'Income & Expense' }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Balance' }
                }
            }
        }
    });
</script>

</html>




