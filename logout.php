<?php
include 'config.php';

// Clear session and remember cookie
if (isset($_SESSION['user_id'])) {
    // remove remember token from DB if the column exists
    $colStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'remember_token'");
    if ($colStmt) {
        $colStmt->execute();
        $res = $colStmt->get_result();
        $row = $res->fetch_assoc();
        $colStmt->close();
        if (!empty($row['cnt'])) {
            $stmt = $conn->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

$_SESSION = [];
session_destroy();
setcookie('remember_token', '', time()-3600, '/', '', false, true);
header('Location: login.php');
exit();

?>
