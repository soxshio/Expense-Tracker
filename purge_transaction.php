<?php
include 'config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit(); }
$user_id = $_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['error'=>'Invalid id']); exit(); }

// Ensure the transaction belongs to this user and is already soft-deleted
$stmt = $conn->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL');
$stmt->bind_param('ii', $id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['error'=>'Failed to delete']);
}
$stmt->close();
exit();
?>
