<?php
include 'config.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// logged-in user ID
$user_id = $_SESSION['user_id'];

// Get total income/expense
$stmt = $conn->prepare(
    "
    SELECT 
        SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as total_expense
    FROM transactions
    WHERE user_id=?
" 
);
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
// Fetch recent transactions for display
$tx_stmt = $conn->prepare("SELECT id, type, amount, category, transaction_date FROM transactions WHERE user_id=? ORDER BY transaction_date DESC LIMIT 20");
$tx_stmt->bind_param("i", $user_id);
$tx_stmt->execute();
$tx_result = $tx_stmt->get_result();
$transactions = $tx_result->fetch_all(MYSQLI_ASSOC);
$tx_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <script defer src="assets/app.js"></script>
    <style> .hidden{display:none} </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dashboard</h1>
            <div>
                <button id="addBtn" class="btn">Add Transaction</button>
                <a id="logoutBtn" href="logout.php" class="btn" style="background:#ef4444">Logout</a>
            </div>
        </div>

        <div class="stats">
            <div class="card">
                <div class="label">Total Income</div>
                <div class="value"><?php echo number_format($income,2); ?></div>
            </div>
            <div class="card">
                <div class="label">Total Expense</div>
                <div class="value"><?php echo number_format($expense,2); ?></div>
            </div>
            <div class="card">
                <div class="label">Balance</div>
                <div class="value"><?php echo number_format($balance,2); ?></div>
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
</body>
</html>




