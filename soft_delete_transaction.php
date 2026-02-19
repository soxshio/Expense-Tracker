<?php
include 'config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit(); }
$user_id = $_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['error'=>'Invalid id']); exit(); }

// set deleted_at to now
$stmt = $conn->prepare('UPDATE transactions SET deleted_at = NOW() WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['error'=>'Failed to delete']);
}
$stmt->close();
exit();
?>
