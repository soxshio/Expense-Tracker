<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$user_id = $_SESSION['user_id'];

// Fetch soft-deleted transactions for this user
$stmt = $conn->prepare('SELECT id, type, amount, category, note, transaction_date, deleted_at FROM transactions WHERE user_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Trash - Expense Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Trash</h1>
            <a href="dashboard.php">Back to Dashboard</a>
        </header>

        <main>
            <p>Transactions in trash are kept for 30 days before permanent deletion.</p>
            <?php if (count($rows) === 0): ?>
                <p>No trashed transactions.</p>
            <?php else: ?>
                <table class="transactions">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Note</th>
                            <th>Deleted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr data-id="<?=htmlspecialchars($r['id'])?>">
                                <td><?=htmlspecialchars($r['transaction_date'])?></td>
                                <td><?=htmlspecialchars($r['type'])?></td>
                                <td><?=htmlspecialchars($r['category'])?></td>
                                <td><?=htmlspecialchars($r['amount'])?></td>
                                <td><?=htmlspecialchars($r['note'])?></td>
                                <td><?=htmlspecialchars($r['deleted_at'])?></td>
                                <td>
                                    <button class="restore-btn">Restore</button>
                                    <button class="purge-btn">Delete Permanently</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

    <script>
    document.addEventListener('click', function(e){
        if (e.target.matches('.restore-btn')){
            if (!confirm('Restore this transaction?')) return;
            const row = e.target.closest('tr');
            const id = row.getAttribute('data-id');
            fetch('restore_transaction.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            }).then(r=>r.json()).then(res=>{
                if (res.ok) row.remove(); else alert('Failed to restore');
            });
        }
        if (e.target.matches('.purge-btn')){
            if (!confirm('Permanently delete this transaction? This cannot be undone.')) return;
            const row = e.target.closest('tr');
            const id = row.getAttribute('data-id');
            fetch('purge_transaction.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            }).then(r=>r.json()).then(res=>{
                if (res.ok) row.remove(); else alert('Failed to delete');
            });
        }
    });
    </script>
</body>
</html>
