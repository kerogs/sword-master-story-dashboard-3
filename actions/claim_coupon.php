<?php
require_once __DIR__ . '/../inc/core.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code manquant']);
    exit;
}

try {
    $prefix = $_ENV['DB_PREFIX'];
    $host   = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $user   = $_ENV['DB_USER'];
    $pass   = $_ENV['DB_PASS'];

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $currentUser = $auth->getCurrentUser();

    $couponStmt = $pdo->prepare("SELECT * FROM {$prefix}codes WHERE code = ? AND date > NOW()");
    $couponStmt->execute([$code]);
    $coupon = $couponStmt->fetch();

    if (!$coupon) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Code not found']);
        exit;
    }

    $checkStmt = $pdo->prepare("SELECT * FROM {$prefix}claimed_coupons WHERE user_id = ? AND coupon_code = ?");
    $checkStmt->execute([$currentUser['id'], $code]);

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Code already claimed']);
        exit;
    }

    $insertStmt = $pdo->prepare("INSERT INTO {$prefix}claimed_coupons (user_id, coupon_code) VALUES (?, ?)");
    $insertStmt->execute([$currentUser['id'], $code]);

    echo json_encode(['success' => true, 'message' => 'Code claimed']);
} catch (PDOException $e) {
    error_log("Database error in claim_coupon.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
