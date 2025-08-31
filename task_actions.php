<?php
require_once __DIR__ . '/config.php';
require_login();

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: dashboard.php');
  exit;
}
if (!verify_csrf($_POST['csrf'] ?? '')) {
  die('Invalid CSRF token');
}

$action = $_POST['action'] ?? '';
$tab = $_POST['tab'] ?? 'all';

try {
  if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = strtolower(trim($_POST['category'] ?? 'personal'));
    $due_date = trim($_POST['due_date'] ?? '');
    if ($title === '') { $title = 'Untitled task'; }
    if (!in_array($category, ['personal','home','business'], true)) $category = 'personal';
    $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, description, category, due_date, is_done, created_at)
                           VALUES (?,?,?,?,?,0,NOW())');
    $stmt->execute([$uid, $title, $description, $category, $due_date !== '' ? $due_date : null]);
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = strtolower(trim($_POST['category'] ?? 'personal'));
    $due_date = trim($_POST['due_date'] ?? '');
    if (!in_array($category, ['personal','home','business'], true)) $category = 'personal';
    $stmt = $pdo->prepare('UPDATE tasks SET title=?, description=?, category=?, due_date=? WHERE id=? AND user_id=?');
    $stmt->execute([$title, $description, $category, $due_date !== '' ? $due_date : null, $id, $uid]);
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id=? AND user_id=?');
    $stmt->execute([$id, $uid]);
  } elseif ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $done = (int)($_POST['is_done'] ?? 0) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE tasks SET is_done=? WHERE id=? AND user_id=?');
    $stmt->execute([$done, $id, $uid]);
  }
} catch (PDOException $e) {
  
}

header('Location: dashboard.php?tab=' . urlencode($tab));
exit;
