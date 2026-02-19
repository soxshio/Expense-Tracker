<?php
include 'config.php';

if (isset($_SESSION['user_id'])) {
	header('Location: dashboard.php');
	exit();
}

$errors = [];
$identifier = '';

// Auto-login via remember cookie
if (!isset($_SESSION['user_id']) && empty($_COOKIE['remember_token'])) {
	// no-op
}
elseif (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
	$token = $_COOKIE['remember_token'];
	$stmt = $conn->prepare('SELECT id FROM users WHERE remember_token = ? LIMIT 1');
	$stmt->bind_param('s', $token);
	$stmt->execute();
	$res = $stmt->get_result();
	if ($row = $res->fetch_assoc()) {
		$_SESSION['user_id'] = $row['id'];
		header('Location: dashboard.php');
		exit();
	}
	$stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$identifier = trim($_POST['identifier'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($identifier === '' || $password === '') {
		$errors[] = 'Please enter your username/email and password.';
	} else {
		$stmt = $conn->prepare('SELECT id, password FROM users WHERE username = ? OR email = ? LIMIT 1');
		$stmt->bind_param('ss', $identifier, $identifier);
		$stmt->execute();
		$res = $stmt->get_result();

		if ($row = $res->fetch_assoc()) {
			if (password_verify($password, $row['password'])) {
				$_SESSION['user_id'] = $row['id'];
				// handle remember me
				if (!empty($_POST['remember'])) {
					$token = bin2hex(random_bytes(32));
					$upd = $conn->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
					$upd->bind_param('si', $token, $row['id']);
					$upd->execute();
					$upd->close();
					setcookie('remember_token', $token, time()+60*60*24*30, '/', '', false, true);
				}
				header('Location: dashboard.php');
				exit();
			} else {
				$errors[] = 'Invalid credentials.';
			}
		} else {
			$errors[] = 'Invalid credentials.';
		}

		$stmt->close();
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Login — Expense Tracker</title>
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
			<h2 style="margin-top:0">Sign in</h2>

			<?php if (!empty($errors)): ?>
				<div class="error">
					<?php foreach ($errors as $e): ?>
						<div><?php echo htmlspecialchars($e); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<form method="POST" action="login.php" novalidate>
				<div class="field">
					<label for="identifier">Username or Email</label>
					<input id="identifier" name="identifier" type="text" required value="<?php echo htmlspecialchars($identifier); ?>">
				</div>

				<div class="field">
					<label for="password">Password</label>
					<div style="position:relative">
						<input id="password" name="password" type="password" required>
						<button type="button" id="togglePassword" style="position:absolute;right:8px;top:8px;background:transparent;border:none;cursor:pointer">Show</button>
					</div>
				</div>
				<div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
					<label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="remember"> Remember me</label>
					<a href="forgot_password.php" style="color:#374151;text-decoration:none;margin-left:auto" class="muted">Forgot password?</a>
				</div>

				<div style="display:flex;gap:8px;align-items:center">
					<button class="btn" type="submit">Login</button>
					<a href="register.php" style="color:#374151;text-decoration:none" class="muted">Create an account</a>
				</div>
			</form>

		</div>
	</div>
</body>
</html>
