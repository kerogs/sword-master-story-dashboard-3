<?php
require_once __DIR__ . '/../../inc/core.php';

if (!$auth->isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    die("no user found");
}

$json_dataDecode = json_decode($currentUser['json_data'], true);

$username = htmlspecialchars($currentUser['username']);
$picture = htmlspecialchars($currentUser['picture']);
$webhook_url = $json_dataDecode['webhook']['url'] ?? '';

if (!$webhook_url) {
    echo "No webhook URL found for user " . $username;
    exit;
}

// ? send webhook

$img = 'https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/assets/img/banner/banner1.jpg';
$char = "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/{$picture}";

// Initialize $msg with common fields
$msg = [
    "avatar_url" => "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/assets/img/favicon.png",
    "username" => "SMSDv3",
    "content" => "",
    "embeds" => [
        [
            "color" => 0xb7996d,
            "author" => [
                "name" => "Sword Master Story Dashboard v3",
                "icon_url" => "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/assets/img/favicon.png"
            ],
            "title" => "Webhook test",
            "url" => "https://github.com/kerogs/sword-Master-Story-Dashboard-3",
            "description" => "The webhook is working correctly and the message has been sent successfully. :) \n\n WebApp version ".smsdv3_version,
            "timestamp" => date('c'),
            "footer" => [
                "text" => "Requested by {$username} • SMSDv3",
                "icon_url" => $char
            ],
            "thumbnail" => [
                "url" => $char
            ],
            "image" => [
                "url" => $img
            ]
        ]
    ],
];

$headers = array('Content-Type: application/json');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msg));
$response = curl_exec($ch);
curl_close($ch);

header('Location: /profile-edit');

// var_dump($response);