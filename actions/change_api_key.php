<?php
require_once __DIR__ . '/../inc/core.php';

// Vérifier la connexion
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    die("Utilisateur non trouvé");
}

$newApiKey = bin2hex(random_bytes(32));
$userId = $currentUser['id'];

// Configuration base de données
$prefix = $_ENV['KAS_DB_PREFIX'];
$host   = $_ENV['KAS_DB_HOST'];
$db     = $_ENV['KAS_DB_NAME'];
$user   = $_ENV['KAS_DB_USER'];
$pass   = $_ENV['KAS_DB_PASS'];

try {
    $kas = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $kas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Requête préparée
    $stmt = $kas->prepare("UPDATE {$prefix}users SET api_key = ? WHERE id = ?");
    $stmt->execute([$newApiKey, $userId]);

    header('Location: /profile-edit.php');
    exit;
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}
