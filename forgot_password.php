<?php
include 'config.php';

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email.';
    } else {
        // create token and store in password_resets
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        // optional: ensure users exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user_id = $row['id'];
            $stmt->close();

            // ensure password_resets table exists (assume caller will run SQL if not)
            $ins = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $ins->bind_param('iss', $user_id, $token, $expires);
            if ($ins->execute()) {
                // send email with reset link - for local dev, we will output link on screen
                $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . $token;
                $message = 'Password reset link (for dev): <a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a>';
            } else {
                $errors[] = 'Failed to create reset token.';
            }
            $ins->close();
        } else {
            $errors[] = 'No account with that email was found.';
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div style="max-width:480px;margin:48px auto;padding:18px">
    <div class="card">
      <h3>Reset your password</h3>
      <?php if (!empty($errors)): ?>
        <div class="error"><?php foreach($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
      <?php endif; ?>
      <?php if ($message): ?>
        <div style="background:#ecfeff;padding:10px;border-radius:6px;margin-bottom:10px"><?php echo $message; ?></div>
      <?php endif; ?>
      <form method="POST">
        <label>Email</label>
        <input name="email" type="email" required>
        <div style="margin-top:10px">
          <button class="btn" type="submit">Send reset link</button>
          <a href="login.php" style="margin-left:10px">Back</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
