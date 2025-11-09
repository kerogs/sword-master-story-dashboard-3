<?php
require_once __DIR__ . '/../inc/core.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation basique
    if (empty($username) || empty($password)) {
        header('Location: /auth/login?error=empty_fields');
        exit;
    }

    try {
        // Tentative de connexion
        $result = $auth->login($username, $password, true);

        if ($result['success']) {
            // Connexion réussie - redirection vers la page d'accueil
            header('Location: /');
            exit;
        } else {
            // Redirection avec message d'erreur
            header('Location: /auth/login?error=' . urlencode($result['message']));
            exit;
        }
    } catch (Exception $e) {
        error_log('Erreur connexion: ' . $e->getMessage());
        header('Location: /auth/login?error=system_error');
        exit;
    }
} else {
    // Si pas en POST, redirection vers la page de connexion
    header('Location: /auth/login');
    exit;
}
