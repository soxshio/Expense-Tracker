<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$type = $_POST['type'] ?? '';
$amount = $_POST['amount'] ?? '';
$category = trim($_POST['category'] ?? '');
$note = trim($_POST['note'] ?? '');

$errors = [];
if (!in_array($type, ['income', 'expense'])) $errors[] = 'Invalid type.';
if (!is_numeric($amount) || $amount <= 0) $errors[] = 'Invalid amount.';

if (!empty($errors)) {
    // For simplicity redirect back; in real app you may show errors
    header('Location: dashboard.php');
    exit();
}

$stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, category, note, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())');
$stmt->bind_param('isdss', $user_id, $type, $amount, $category, $note);
if (!$stmt->execute()) {
    // log error in real app
}
$stmt->close();

header('Location: dashboard.php');
exit();

?>
