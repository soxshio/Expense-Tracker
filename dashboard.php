<?php
include 'config.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<?php
// Fetch recent transactions for display
$tx_stmt = $conn->prepare("SELECT id, type, amount, category, note, date FROM transactions WHERE user_id=? ORDER BY date DESC LIMIT 20");
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
                    <tr><th>Date</th><th>Type</th><th>Category</th><th>Note</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['date']); ?></td>
                                <td><?php echo htmlspecialchars($t['type']); ?></td>
                                <td><?php echo htmlspecialchars($t['category']); ?></td>
                                <td><?php echo htmlspecialchars($t['note']); ?></td>
                                <td><?php echo number_format($t['amount'],2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No transactions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="footer-note">Manage transactions using the form above.</div>
        </div>
    </div>
</body>
</html>




