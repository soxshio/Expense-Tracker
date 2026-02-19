<?php
include 'config.php';

// Clear session and remember cookie
if (isset($_SESSION['user_id'])) {
    // remove remember token from DB
    $stmt = $conn->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

$_SESSION = [];
session_destroy();
setcookie('remember_token', '', time()-3600, '/', '', false, true);
header('Location: login.php');
exit();

?>
