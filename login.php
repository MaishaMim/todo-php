<?php
require_once __DIR__ . '/config.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if (!$user || !password_verify($password, $user['password_hash'])) {
    $errors[] = 'Invalid email or password.';
  } else {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    header('Location: dashboard.php');
    exit;
  }
}
$registered = isset($_GET['registered']);
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Log in • Simple Tasks</title>
    <link rel="stylesheet" href="styles.css">
  </head>
  <body class="auth-wrap">
    <div class="auth-card">
      <h1>Welcome back</h1>
      <?php if ($registered): ?>
        <div style="background:#e7f6e7;border:1px solid #badbcc;color:#0f5132;padding:10px 12px;border-radius:12px;margin-bottom:12px">
          Account created. You can log in now.
        </div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div style="background:#ffe6e6;border:1px solid #f5c2c7;color:#842029;padding:10px 12px;border-radius:12px;margin-bottom:12px">
          <?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
        </div>
      <?php endif; ?>
      <form method="post" style="display:grid;gap:12px">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div>
          <label>Email</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div>
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <button type="submit">Log in</button>
        <div class="small">No account? <a class="btn-link" href="register.php">Sign up</a></div>
      </form>
    </div>
  </body>
</html>
