<?php
include 'config.php';
if (isset($_SESSION['user_id'])) {
	header('Location: dashboard.php');
	exit();
}
$errors = [];
$username = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$confirm = $_POST['confirm_password'] ?? '';

	if ($username === '' || $email === '' || $password === '') {
		$errors[] = 'Please fill in all required fields.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Please enter a valid email address.';
	} elseif ($password !== $confirm) {
		$errors[] = 'Passwords do not match.';
	} elseif (strlen($password) < 6) {
		$errors[] = 'Password must be at least 6 characters.';
	}
	if (empty($errors)) {
		$check = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
		$check->bind_param('ss', $username, $email);
	$check->execute();
	$check->store_result();

	if ($check->num_rows > 0) {
			$errors[] = 'Username or email already in use.';
			$check->close();
		} else {
			$check->close();

			$hash = password_hash($password, PASSWORD_DEFAULT);
			$ins = $conn->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
			$ins->bind_param('sss', $username, $email, $hash);

			if ($ins->execute()) {
				$user_id = $conn->insert_id;
				$_SESSION['user_id'] = $user_id;
				$ins->close();
				header('Location: dashboard.php');
				exit();
			} else {
				$errors[] = 'Failed to create account. Please try again.';
				$ins->close();
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Register — Expense Tracker</title>
	<link rel="stylesheet" href="assets/style.css">
	<script defer src="assets/app.js"></script>
	<style>
	.auth-wrap{max-width:420px;margin:48px auto;padding:18px}
	.auth-card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 1px 3px rgba(16,24,40,0.06)}
	.field{margin-bottom:10px}
	.field label{display:block;font-size:13px;color:#374151;margin-bottom:6px}
	.field input{width:100%;padding:10px;border:1px solid #e6e9ef;border-radius:6px}
	.error{background:#fee2e2;color:#991b1b;padding:8px;border-radius:6px;margin-bottom:12px}
		.muted{font-size:13px;color:#6b7280;margin-top:8px}
	</style>
</head>
<body>
	<div class="auth-wrap">
		<div class="auth-card">
			<h2 style="margin-top:0">Create an account</h2>

			<?php if (!empty($errors)): ?>
				<div class="error">
					<?php foreach ($errors as $e): ?>
						<div><?php echo htmlspecialchars($e); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<form method="POST" action="register.php" novalidate>
				<div class="field">
					<label for="username">Username</label>
					<input id="username" name="username" type="text" required value="<?php echo htmlspecialchars($username); ?>">
				</div>

				<div class="field">
					<label for="email">Email</label>
					<input id="email" name="email" type="email" required value="<?php echo htmlspecialchars($email); ?>">
				</div>

				<div class="field">
					<label for="password">Password</label>
					<input id="password" name="password" type="password" required>
				</div>

				<div class="field">
					<label for="confirm_password">Confirm Password</label>
					<input id="confirm_password" name="confirm_password" type="password" required>
				</div>

				<div style="display:flex;gap:8px;align-items:center">
					<button class="btn" type="submit">Register</button>
					<a href="login.php" style="color:#374151;text-decoration:none" class="muted">Already have an account? Log in</a>
				</div>
			</form>

		</div>
	</div>
</body>
</html>
x