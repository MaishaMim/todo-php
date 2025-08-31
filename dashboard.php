<?php
require_once __DIR__ . '/config.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'User';

$tab = strtolower($_GET['tab'] ?? 'all');
$allowed = ['all', 'personal', 'home', 'business'];
if (!in_array($tab, $allowed, true)) $tab = 'all';

$only_done = isset($_GET['done']) && $_GET['done'] === '1';
$search = trim($_GET['q'] ?? '');

// fetch tasks
$where = 'user_id = :uid';
$params = [':uid' => $uid];
if ($tab !== 'all') {
  $where .= ' AND category = :cat';
  $params[':cat'] = $tab;
}
if ($only_done) {
  $where .= ' AND is_done = 1';
}
if ($search !== '') {
  $where .= ' AND (title LIKE :q OR description LIKE :q)';
  $params[':q'] = '%' . $search . '%';
}

$sql = "SELECT * FROM tasks WHERE {$where} ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// edit mode
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_task = null;
if ($edit_id) {
  $s = $pdo->prepare('SELECT * FROM tasks WHERE id=? AND user_id=?');
  $s->execute([$edit_id, $uid]);
  $edit_task = $s->fetch();

  
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard • Simple Tasks</title>
    <link rel="stylesheet" href="styles.css">
  </head>
  <body>
    <div class="topbar">
      <div style="font-weight:800">Simple Tasks</div>
      <form method="get" action="dashboard.php" style="display:flex; gap:8px; flex:1">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
        <input class="search" name="q" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn" type="submit">Search</button>
      </form>
      <!-- <a class="btn add-btn" href="#new">+ Add</a> -->
      <a class="btn" style="background:#ef4444" href="logout.php">Logout</a>
    </div>

    <div class="container">

      <!-- Tabs -->
      <div class="tabs">
        <?php
          foreach ($allowed as $t) {
            $active = $tab === $t ? 'active' : '';
            $label = $t === 'all' ? 'ALL' : ucfirst($t);
            $href = 'dashboard.php?tab=' . urlencode($t) . ($only_done ? '&done=1' : '') . ($search !== '' ? '&q=' . urlencode($search) : '');
            echo '<a class="'.$active.'" href="'.$href.'">'.$label.'</a>';
          }
        ?>
        <div class="options">
          <form method="get" action="dashboard.php" style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
            <label class="toggle-done">
              <input type="checkbox" name="done" value="1" <?php echo $only_done ? 'checked' : '';?> onchange="this.form.submit()"> Show only completed
            </label>
          </form>
        </div>
      </div>

     
      <div class="panel" id="new">
        <?php if ($edit_task): ?>
          <h2>Edit task</h2>
          <form class="flex" method="post" action="task_actions.php">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$edit_task['id']; ?>">
            <input name="title" placeholder="Title" value="<?php echo htmlspecialchars($edit_task['title']); ?>" required>
            <select name="category">
              <?php
                foreach (['personal'=>'Personal','home'=>'Home','business'=>'Business'] as $v=>$label) {
                  $sel = $edit_task['category'] === $v ? 'selected' : '';
                  echo "<option value='$v' $sel>$label</option>";
                }
              ?>
            </select>
            <input type="date" name="due_date" value="<?php echo htmlspecialchars($edit_task['due_date'] ?? ''); ?>">
            <textarea name="description" placeholder="Description" rows="3"><?php echo htmlspecialchars($edit_task['description']); ?></textarea>
            <div style="display:flex; gap:8px">
              <button type="submit">Save changes</button>
              <a class="btn" style="background:#6b7280" href="dashboard.php?tab=<?php echo urlencode($tab); ?>">Cancel</a>
            </div>
          </form>
        <?php else: ?>
          <h2>Add a new task</h2>
          <form class="flex" method="post" action="task_actions.php">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <input name="title" placeholder="Title" required>
            <select name="category">
              <option value="personal">Personal</option>
              <option value="home">Home</option>
              <option value="business">Business</option>
            </select>
            <input type="date" name="due_date">
            <textarea name="description" placeholder="Description" rows="3"></textarea>
            <button type="submit">Add Task</button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Cards Grid -->
      <div class="grid">
        <?php if (!$tasks): ?>
          <div class="card" style="grid-column:1 / -1">
            <strong>No tasks yet.</strong> Use the form above to add your first task.
          </div>
        <?php else: ?>
          <?php foreach ($tasks as $t): ?>
            <div class="card">
              <span class="badge <?php echo htmlspecialchars($t['category']); ?>"><?php echo ucfirst(htmlspecialchars($t['category'])); ?></span>
              <h3><?php echo htmlspecialchars($t['title']); ?></h3>
              <?php if (!empty($t['description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($t['description'])); ?></p>
              <?php endif; ?>
              <div class="meta">
                <div>
                  <?php if (!empty($t['due_date'])): ?>
                    Due: <?php echo date('d.m.Y', strtotime($t['due_date'])); ?>
                  <?php else: ?>
                    &nbsp;
                  <?php endif; ?>
                </div>
                <form method="post" action="task_actions.php" style="margin:0">
                  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                  <label class="toggle-done">
                    <input type="checkbox" name="is_done" value="1" <?php echo $t['is_done'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                    Completed
                  </label>
                </form>
              </div>
              <div class="card-actions">
                <a class="icon-btn" title="Edit" href="dashboard.php?tab=<?php echo urlencode($tab); ?>&edit=<?php echo (int)$t['id']; ?>">✏️</a>
                <form method="post" action="task_actions.php" onsubmit="return confirm('Delete this task?');">
                  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                  <button class="icon-btn" title="Delete">🗑️</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div style="height:40px"></div>
    </div>
  </body>
</html>
