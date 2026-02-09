<?php
require_once __DIR__ . '/../inc/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/register?error=invalid_method');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: /auth/register?error=empty_fields&username=' . urlencode($username));
    exit;
}

if (strlen($username) < 3) {
    header('Location: /auth/register?error=username_too_short&username=' . urlencode($username));
    exit;
}

if (strlen($password) < 8) {
    header('Location: /auth/register?error=password_too_short&username=' . urlencode($username));
    exit;
}

try {
    $result = $auth->register($username, $password);

    if (!empty($result['success'])) {
        $prefix = $_ENV['DB_PREFIX'];
        $host   = $_ENV['DB_HOST'];
        $db     = $_ENV['DB_NAME'];
        $user   = $_ENV['DB_USER'];
        $pass   = $_ENV['DB_PASS'];

        $pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE username = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$username]);
        $createdUser = $stmt->fetch();

        if ($createdUser) {
            $charactersDir = __DIR__ . '/../assets/img/characters/';
            $randomPicturePaths = glob($charactersDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

            if (!empty($randomPicturePaths)) {
                $randomImage = $randomPicturePaths[array_rand($randomPicturePaths)];
                $imageFilename = basename($randomImage);
                $picturePath = '/assets/img/characters/' . $imageFilename;

                $updateStmt = $pdo->prepare("UPDATE {$prefix}users SET picture = ? WHERE id = ?");
                $updateStmt->execute([$picturePath, $createdUser['id']]);
            }
        }

        header('Location: /auth/login?success=account_created&username=' . urlencode($username));
        exit;
    }

    header('Location: /auth/register?error=register_failed&username=' . urlencode($username));
    exit;
} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());
    header('Location: /auth/register?error=system_error&username=' . urlencode($username));
    exit;
}
