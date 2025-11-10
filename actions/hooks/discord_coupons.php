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
$webhook_url = $json_dataDecode['webhook']['url'] ?? '';

if (!$webhook_url) {
    echo "No webhook URL found for user " . htmlspecialchars($currentUser['username']);
    exit;
}

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$current_date = date('Y-m-d');
$query = "SELECT code, type, value, date, description FROM {$prefix}codes WHERE date >= :current_date ORDER BY type ASC, date ASC";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':current_date', $current_date);
$stmt->execute();
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$username = htmlspecialchars($currentUser['username']);
$picture = htmlspecialchars($currentUser['picture']);
$char = "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/{$picture}";

$fields = [];

$totalValueRuby = 0;
$totalValueStamina = 0;

if (empty($coupons)) {
    $title = "No coupons available";
    $color = 0xb7996d;
    $fields[] = [
        "name" => "❌ No coupons available",
        "value" => "All coupons are expired or none were found. Check back later!",
        "inline" => false
    ];
} else {
    $ruby_coupons = [];
    $stamina_coupons = [];
    $other_coupons = [];

    foreach ($coupons as $coupon) {
        $expiration = date('d/m/Y', strtotime($coupon['date']));
        $value = $coupon['value'] ? "x" . $coupon['value'] : "";
        $coupon_line = "• {$coupon['code']} - *{$expiration}*";

        if ($coupon['type'] === 'Ruby') {
            $totalValueRuby += $coupon['value'];
        } elseif ($coupon['type'] === 'Stamina') {
            $totalValueStamina += $coupon['value'];
        }

        if ($coupon['type'] === 'Ruby') {
            $ruby_coupons[] = $coupon_line;
        } elseif ($coupon['type'] === 'Stamina') {
            $stamina_coupons[] = $coupon_line;
        } else {
            $other_coupons[] = $coupon_line;
        }
    }

    $totalCoupon = count($coupons);
    $totalOther = count($other_coupons);

    if (!empty($ruby_coupons)) {
        $fields[] = [
            "name" => "💎 Ruby - Total: " . count($ruby_coupons),
            "value" => implode("\n", $ruby_coupons),
            "inline" => true
        ];
    }

    if (!empty($stamina_coupons)) {
        $fields[] = [
            "name" => "⚡ Stamina - Total: " . count($stamina_coupons),
            "value" => implode("\n", $stamina_coupons),
            "inline" => true
        ];
    }

    if (!empty($other_coupons)) {
        $fields[] = [
            "name" => "🎁 Other - Total: " . count($other_coupons),
            "value" => implode("\n", $other_coupons),
            "inline" => true
        ];
    }

    $title = "Total available - " . count($coupons) . " coupons";
    $color = 0xb7996d;
}

$description = "
```ansi
💎 Total value [2;31m{$totalValueRuby} ruby[0m[2;31m
[0m⚡ Total value [2;31m[0m[2;33m{$totalValueStamina} stamina[0m[2;31m
```
";

$img = 'https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/assets/img/banner/mia2.jpg';

$msg = [
    "avatar_url" => "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/assets/img/favicon.png",
    "username" => "SMSDv3 - Codes",
    "content" => "",
    "embeds" => [
        [
            "color" => $color,
            "author" => [
                "name" => "Sword Master Story Dashboard v3 - Codes",
                "icon_url" => "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/assets/img/favicon.png"
            ],
            "title" => $title,
            "description" => $description,
            "url" => "https://github.com/kerogs/sword-Master-Story-Dashboard-3",
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
            ],
            "fields" => $fields
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
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Location: /coupons');