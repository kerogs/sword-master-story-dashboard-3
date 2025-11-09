<?php
require_once __DIR__ . '/../inc/core.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($username) || empty($password)) {
        header('Location: /auth/register?error=empty_fields');
        exit;
    }
    
    if (strlen($username) < 3) {
        header('Location: /auth/register?error=username_too_short');
        exit;
    }
    
    if (strlen($password) < 8) {
        header('Location: /auth/register?error=password_too_short');
        exit;
    }
    
    try {
        // Tentative d'inscription
        $result = $auth->register($username, $password);
        
        if ($result['success']) {
            
            $prefix = $_ENV['KAS_DB_PREFIX'];
            $host   = $_ENV['KAS_DB_HOST'];
            $db     = $_ENV['KAS_DB_NAME'];
            $user   = $_ENV['KAS_DB_USER'];
            $pass   = $_ENV['KAS_DB_PASS'];
            
            $pdo = new PDO(
                "mysql:host=$host;dbname=$db;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Récupérer l'ID du dernier utilisateur créé avec ce username
            $stmt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE username = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Mettre à jour l'avatar avec une image locale
                $charactersDir = __DIR__ . '/../assets/img/characters/';
                $randomPicturePaths = glob($charactersDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                
                if (!empty($randomPicturePaths)) {
                    $randomImage = $randomPicturePaths[array_rand($randomPicturePaths)];
                    $imageFilename = basename($randomImage);
                    $picturePath = '/assets/img/characters/' . $imageFilename;
                    
                    // Mettre à jour en base de données
                    $updateStmt = $pdo->prepare("UPDATE {$prefix}users SET picture = ? WHERE id = ?");
                    $updateStmt->execute([$picturePath, $user['id']]);
                }
            }
            
            // Redirection vers la page de connexion avec message de succès
            header('Location: /auth/login?success=account_created');
            exit;
        } else {
            // Redirection avec message d'erreur
            header('Location: /auth/register?error=' . urlencode($result['message']));
            exit;
        }
    } catch (Exception $e) {
        error_log('Erreur inscription: ' . $e->getMessage());
        header('Location: /auth/register?error=system_error');
        exit;
    }
} else {
    // Si pas en POST, redirection vers la page d'inscription
    header('Location: /auth/register');
    exit;
}
?>