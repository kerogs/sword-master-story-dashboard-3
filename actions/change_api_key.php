<?php
require_once __DIR__ . '/../inc/core.php';

if (!$auth->isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    die("No user found");
}

$newApiKey = bin2hex(random_bytes(32));
$userId = $currentUser['id'];

$prefix = $_ENV['KAS_DB_PREFIX'];
$host   = $_ENV['KAS_DB_HOST'];
$db     = $_ENV['KAS_DB_NAME'];
$user   = $_ENV['KAS_DB_USER'];
$pass   = $_ENV['KAS_DB_PASS'];

try {
    $kas = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $kas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $kas->prepare("UPDATE {$prefix}users SET api_key = ? WHERE id = ?");
    $stmt->execute([$newApiKey, $userId]);

    header('Location: /profile-edit.php');
    exit;
} catch (PDOException $e) {
    die("database err : " . $e->getMessage());
}
