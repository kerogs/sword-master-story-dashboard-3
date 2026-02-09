<?php

require_once __DIR__ . '/../inc/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /coupons?form_error=invalid_method');
    exit;
}

if (!$auth->isLoggedIn()) {
    header('Location: /auth/login?error=login_required');
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
$type = trim($_POST['type'] ?? '');
$value = (int)($_POST['value'] ?? 0);
$date = trim($_POST['date'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = (int)($_POST['priority'] ?? 3);

if ($code === '' || $type === '' || $description === '' || $date === '') {
    header('Location: /coupons?form_error=missing_fields');
    exit;
}

if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $code)) {
    header('Location: /coupons?form_error=invalid_code_format');
    exit;
}

$allowedTypes = ['Ruby', 'Stamina', 'Special'];
if (!in_array($type, $allowedTypes, true)) {
    header('Location: /coupons?form_error=invalid_type');
    exit;
}

if ($priority < 1 || $priority > 5) {
    $priority = 3;
}

if ($value < 0) {
    $value = 0;
}

$expiresAt = DateTime::createFromFormat('Y-m-d', $date);
if (!$expiresAt || $expiresAt->format('Y-m-d') !== $date) {
    header('Location: /coupons?form_error=invalid_date');
    exit;
}

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $checkStmt = $pdo->prepare("SELECT id FROM {$prefix}codes WHERE code = ? LIMIT 1");
    $checkStmt->execute([$code]);

    if ($checkStmt->fetch()) {
        header('Location: /coupons?form_error=code_exists');
        exit;
    }

    $insertStmt = $pdo->prepare("INSERT INTO {$prefix}codes (code, type, value, date, description, added_by, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $insertStmt->execute([
        $code,
        $type,
        $value,
        $date,
        $description,
        $auth->getCurrentUser()['username'] ?? 'user',
        $priority,
    ]);

    header('Location: /coupons?form_success=coupon_added');
    exit;
} catch (Throwable $e) {
    error_log('Error adding coupon: ' . $e->getMessage());
    header('Location: /coupons?form_error=system_error');
    exit;
}
