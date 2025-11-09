<?php
require_once __DIR__ . '/inc/core.php';

if ($auth->isLoggedIn()) {
    $auth->logout();
}

header('Location: /');
exit;
