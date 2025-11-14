<?php
require_once __DIR__ . '/../inc/core.php';

if (!$auth->isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /profile-edit');
    exit;
}

$currentUser = $auth->getCurrentUser();
$avatar = $_POST['avatar'] ?? '';

if (empty($avatar)) {
    header('Location: /profile-edit?error=no_avatar_selected');
    exit;
}

$allowedPaths = ['/assets/img/characters/'];
$isValidPath = false;

foreach ($allowedPaths as $allowedPath) {
    if (strpos($avatar, $allowedPath) === 0) {
        $isValidPath = true;
        break;
    }
}

if (!$isValidPath) {
    header('Location: /profile-edit?error=invalid_avatar');
    exit;
}

try {
    $prefix = $_ENV['DB_PREFIX'];
    $host   = $_ENV['DB_HOST'];
    $db     = $_ENV['DB_NAME'];
    $user   = $_ENV['DB_USER'];
    $pass   = $_ENV['DB_PASS'];

    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE {$prefix}users SET picture = ? WHERE id = ?");
    $stmt->execute([$avatar, $currentUser['id']]);

    header('Location: /profile/' . $currentUser['username']);
    exit;

} catch (PDOException $e) {
    error_log('Erreur mise à jour profil: ' . $e->getMessage());
    header('Location: /profile-edit?error=update_failed');
    exit;
}
?>