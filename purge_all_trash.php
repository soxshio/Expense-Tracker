<?php
include 'config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit(); }
$user_id = $_SESSION['user_id'];

// Permanently delete all trashed transactions for this user
$stmt = $conn->prepare('DELETE FROM transactions WHERE user_id = ? AND deleted_at IS NOT NULL');
$stmt->bind_param('i', $user_id);
if ($stmt->execute()) {
    $count = $stmt->affected_rows;
    echo json_encode(['ok' => true, 'deleted' => $count]);
} else {
    echo json_encode(['error' => 'Failed to purge']);
}
$stmt->close();
exit();
?>
