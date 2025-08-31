<?php

session_start();


$DB_HOST = '127.0.0.1:3325';
$DB_NAME = 'todo_app';
$DB_USER = 'root';
$DB_PASS = '';

try {
  $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

function is_logged_in(): bool {
  return isset($_SESSION['user_id']);
}
function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function verify_csrf($token): bool {
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}
