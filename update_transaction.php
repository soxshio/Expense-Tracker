<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$type = $_POST['type'] ?? '';
$amount = $_POST['amount'] ?? '';
$category = trim($_POST['category'] ?? '');
$note = trim($_POST['note'] ?? '');
$date = $_POST['date'] ?? '';

$errors = [];
if (!in_array($type, ['income','expense'])) $errors[] = 'Invalid type';
if (!is_numeric($amount) || $amount <= 0) $errors[] = 'Invalid amount';
if ($date === '') $errors[] = 'Invalid date';

if (!empty($errors)) {
    echo json_encode(['error' => implode('; ', $errors)]);
    exit();
}

$amount = (float)$amount;
$upd = $conn->prepare('UPDATE transactions SET type = ?, amount = ?, category = ?, note = ?, transaction_date = ? WHERE id = ? AND user_id = ?');
$upd->bind_param('sdsssii', $type, $amount, $category, $note, $date, $id, $user_id);
if (!$upd->execute()) {
    echo json_encode(['error' => 'Failed to update']);
    exit();
}
$upd->close();

// Return updated row
$stmt = $conn->prepare('SELECT id, type, amount, category, note, transaction_date FROM transactions WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row) {
    echo json_encode(['ok' => true, 'transaction' => $row]);
} else {
    echo json_encode(['error' => 'Not found after update']);
}

exit();
