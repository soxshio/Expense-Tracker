<?php
include 'config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['count'=>0]); exit(); }
$user_id = $_SESSION['user_id'];

// Check if deleted_at exists
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

if (!$hasDeletedAt) {
    echo json_encode(['count'=>0]);
    exit();
}

$count_stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM transactions WHERE user_id = ? AND deleted_at IS NOT NULL');
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$res = $count_stmt->get_result();
$row = $res->fetch_assoc();
$count = (int)($row['cnt'] ?? 0);
$count_stmt->close();

echo json_encode(['count'=>$count]);
exit();
?>
