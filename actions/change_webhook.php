<?php
require_once __DIR__ . '/../inc/core.php';

if (!$auth->isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    die("no user found");
}

if (!isset($_POST['webhookUrl'])) {
    die("No webhook URL provided");
}

if (!filter_var($_POST['webhookUrl'], FILTER_VALIDATE_URL)) {
    die("Invalid webhook URL");
}

// check webhook url contain https://discord.com/api/webhooks/*
if (!str_starts_with($_POST['webhookUrl'], 'https://discord.com/api/webhooks/')) {
    die("Invalid webhook URL format");
}

$userId = $currentUser['id'];
$json_dataDecode = json_decode($currentUser['json_data'], true);

if (isset($_POST['webhookUrl'])) {
    $json_dataDecode['webhook']['url'] = $_POST['webhookUrl'];
}

$json_dataDecode = json_encode($json_dataDecode);

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$db     = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

try {
    $kas = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $kas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $kas->prepare("UPDATE {$prefix}users SET json_data = ? WHERE id = ?");
    $stmt->execute([$json_dataDecode, $userId]);

    header('Location: /profile-edit.php');
    exit;
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}
