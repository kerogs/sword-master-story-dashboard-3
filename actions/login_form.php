<?php
require_once __DIR__ . '/../inc/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/login?error=invalid_method');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: /auth/login?error=empty_fields&username=' . urlencode($username));
    exit;
}

try {
    $result = $auth->login($username, $password, true);

    if (!empty($result['success'])) {
        header('Location: /');
        exit;
    }

    $message = strtolower($result['message'] ?? '');
    $errorCode = 'login_failed';

    if (str_contains($message, 'incorrect') || str_contains($message, 'identifi')) {
        $errorCode = 'invalid_credentials';
    } elseif (str_contains($message, 'verrou') || str_contains($message, 'locked')) {
        $errorCode = 'account_locked';
    } elseif (str_contains($message, 'désactiv') || str_contains($message, 'disabled')) {
        $errorCode = 'account_disabled';
    }

    header('Location: /auth/login?error=' . $errorCode . '&username=' . urlencode($username));
    exit;
} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    header('Location: /auth/login?error=system_error&username=' . urlencode($username));
    exit;
}
