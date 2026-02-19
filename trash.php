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
        <div class="header trash-header" style="align-items:center;gap:12px;">
            <h1>Deleted</h1>
            <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
                <a href="dashboard.php" class="btn" style="background:#6b7280">Back</a>
                <?php if (count($rows) > 0): ?>
                    <button id="purgeAllBtn" class="btn" style="background:#ef4444">Purge All</button>
                <?php endif; ?>
            </div>
        </div>

        <main>
            <div class="card">
                <p style="margin:0 0 8px 0;color:#6b7280">Transactions in trash are kept for 30 days before permanent deletion.</p>
                <?php if (count($rows) === 0): ?>
                    <div class="card" style="padding:18px;text-align:center;color:#6b7280">No trashed transactions.</div>
                <?php else: ?>
                    <div style="overflow:auto">
                    <table class="table" style="min-width:800px">
                        <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Note</th>
                                        <th class="amount-col">Amount</th>
                                        <th>Deleted At</th>
                                        <th>Expires In</th>
                                        <th>Actions</th>
                                    </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                    $deleted_ts = strtotime($r['deleted_at']);
                                    $seconds_passed = time() - $deleted_ts;
                                    $days_passed = floor($seconds_passed / 86400);
                                    $days_left = max(0, 30 - $days_passed);
                                    $expires_label = $days_left > 1 ? $days_left . ' days' : ($days_left === 1 ? '1 day' : 'Due');
                                ?>
                                <tr data-id="<?=htmlspecialchars($r['id'])?>">
                                    <td><?=htmlspecialchars($r['transaction_date'])?></td>
                                    <td><?=htmlspecialchars(ucfirst($r['type']))?></td>
                                    <td><?=htmlspecialchars($r['category'])?></td>
                                    <td><?=htmlspecialchars($r['note'])?></td>
                                    <td class="amount-col"><?php echo number_format($r['amount'],2); ?></td>
                                    <td><?=htmlspecialchars($r['deleted_at'])?></td>
                                    <td class="days-left"><?=htmlspecialchars($expires_label)?></td>
                                    <td>
                                        <button class="btn restore-btn" style="background:#10b981;padding:6px 8px">Restore</button>
                                        <button class="btn purge-btn" style="background:#ef4444;padding:6px 8px;margin-left:6px">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
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
                credentials: 'same-origin',
                body: 'id=' + encodeURIComponent(id)
            }).then(r=>r.json()).then(res=>{
                if (res.ok) row.remove(); else alert('Failed to restore');
            });
            return;
        }
        if (e.target.matches('.purge-btn')){
            if (!confirm('Permanently delete this transaction? This cannot be undone.')) return;
            const row = e.target.closest('tr');
            const id = row.getAttribute('data-id');
            fetch('purge_transaction.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                credentials: 'same-origin',
                body: 'id=' + encodeURIComponent(id)
            }).then(r=>r.json()).then(res=>{
                if (res.ok) row.remove(); else alert('Failed to delete');
            });
            return;
        }
        if (e.target && e.target.id === 'purgeAllBtn'){
            if (!confirm('Permanently delete ALL trashed transactions for your account? This cannot be undone.')) return;
            fetch('purge_all_trash.php', { method: 'POST', credentials: 'same-origin' })
                .then(r=>r.json()).then(json=>{
                    if (json && json.ok) {
                        alert('Purged ' + (json.deleted||0) + ' trashed transactions.');
                        location.reload();
                    } else {
                        alert('Purge failed');
                    }
                }).catch(()=>{ alert('Failed to purge'); });
            return;
        }
    });
    </script>
</body>
</html>
