<?php
require_once __DIR__ . '/config.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$errors = [];
$name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if ($name === '') $errors[] = 'Name is required.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
  if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
  if ($password !== $confirm) $errors[] = 'Passwords do not match.';

  if (!$errors) {
    
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors[] = 'Email already in use. Try logging in.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, created_at) VALUES (?,?,?,NOW())');
      $stmt->execute([$name, $email, $hash]);
      header('Location: login.php?registered=1');
      exit;
    }
  }
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign up • Simple Tasks</title>
    <link rel="stylesheet" href="styles.css">
  </head>
  <body class="auth-wrap">
    <div class="auth-card">
      <h1>Create account</h1>
      <?php if ($errors): ?>
        <div style="background:#ffe6e6;border:1px solid #f5c2c7;color:#842029;padding:10px 12px;border-radius:12px;margin-bottom:12px">
          <?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
        </div>
      <?php endif; ?>
      <form method="post" autocomplete="on" style="display:grid;gap:12px">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div>
          <label>Name</label>
          <input name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="row">
          <div style="flex:1">
            <label>Password</label>
            <input type="password" name="password" required>
          </div>
          <div style="flex:1">
            <label>Confirm</label>
            <input type="password" name="confirm" required>
          </div>
        </div>
        <button type="submit">Create account</button>
        <div class="small">Already have an account? <a class="btn-link" href="login.php">Log in</a></div>
      </form>
    </div>
  </body>
</html>
