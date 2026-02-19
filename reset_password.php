<?php
include 'config.php';

$errors = [];
$message = '';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($token === '') {
    $errors[] = 'Invalid token.';
} else {
    // lookup token
    $stmt = $conn->prepare('SELECT pr.id AS pr_id, pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (strtotime($row['expires_at']) < time()) {
            $errors[] = 'Token has expired.';
        } else {
            $pr_id = $row['pr_id'];
            $user_id = $row['user_id'];
        }
    } else {
        $errors[] = 'Invalid token.';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($password === '' || $password !== $confirm) {
        $errors[] = 'Passwords must match and not be empty.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $upd->bind_param('si', $hash, $user_id);
        if ($upd->execute()) {
            // delete all password resets for user
            $del = $conn->prepare('DELETE FROM password_resets WHERE user_id = ?');
            $del->bind_param('i', $user_id);
            $del->execute();
            $del->close();

            $message = 'Password updated. You may now <a href="login.php">log in</a>.';
        } else {
            $errors[] = 'Failed to update password.';
        }
        $upd->close();
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div style="max-width:480px;margin:48px auto;padding:18px">
    <div class="card">
      <h3>Choose a new password</h3>
      <?php if (!empty($errors)): ?>
        <div class="error"><?php foreach($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
      <?php endif; ?>
      <?php if ($message): ?>
        <div style="background:#ecfeff;padding:10px;border-radius:6px;margin-bottom:10px"><?php echo $message; ?></div>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <label>New password</label>
        <input name="password" type="password" required>
        <label>Confirm password</label>
        <input name="confirm_password" type="password" required>
        <div style="margin-top:10px">
          <button class="btn" type="submit">Save new password</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
