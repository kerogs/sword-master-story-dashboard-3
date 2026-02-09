<?php

require_once __DIR__ . '/inc/core.php';

$account = $_GET['account'] ?? '';

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

$db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT * FROM {$prefix}users WHERE username = ?");
$stmt->execute([$account]);
$userInfo = $stmt->fetch();

if (!$userInfo) {
    header('Location: /profiles-list');
    exit;
}

$stmt = $db->prepare("SELECT * FROM {$prefix}claimed_coupons WHERE user_id = ? ORDER BY claimed_at DESC");
$stmt->execute([$userInfo['id']]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$claimedCodes = [];
if (!empty($coupons)) {
    $couponCodes = array_column($coupons, 'coupon_code');
    $placeholders = implode(',', array_fill(0, count($couponCodes), '?'));
    $stmt = $db->prepare("SELECT * FROM {$prefix}codes WHERE code IN ($placeholders)");
    $stmt->execute($couponCodes);
    $claimedCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalRubyCount = $totalRubyValue = $totalStaminaCount = $totalStaminaValue = $totalOtherCount = 0;
foreach ($claimedCodes as $code) {
    $value = (int)($code['value'] ?? 0);
    if ($code['type'] === 'Ruby') {
        $totalRubyCount++;
        $totalRubyValue += $value;
    } elseif (in_array($code['type'], ['Stamina', 'Stam'], true)) {
        $totalStaminaCount++;
        $totalStaminaValue += $value;
    } else {
        $totalOtherCount++;
    }
}
$totalClaimed = count($claimedCodes);

$totalCodesStmt = $db->query("SELECT COUNT(*) FROM {$prefix}codes");
$totalCodes = (int)$totalCodesStmt->fetchColumn();
$completion = $totalCodes > 0 ? (int)round(($totalClaimed / $totalCodes) * 100) : 0;

$isOwnProfile = $auth->isLoggedIn() && $userInfo['id'] === $auth->getCurrentUser()['id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | <?php echo htmlspecialchars($userInfo['username']); ?></title>
    <link rel="stylesheet" href="assets/styles/css/style.css">
    <link rel="shortcut icon" href="./assets/img/favicon.png" type="image/x-icon">
    <?php require_once __DIR__ . '/inc/head.php' ?>
</head>

<body>
    <?php require_once __DIR__ . '/inc/aside.php'; ?>

    <main>
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-picture">
                    <img src="<?php echo $userInfo['picture'] ?: '/assets/img/characters/default.png'; ?>" alt="Profile picture of <?php echo htmlspecialchars($userInfo['username']); ?>">
                </div>
                <div class="profile-info">
                    <h1 class="username"><?php echo htmlspecialchars($userInfo['username']); ?></h1>
                    <div class="profile-stats">
                        <div class="stat"><span class="stat-label">Member since</span><span class="stat-value"><?php echo date('d/m/Y', strtotime($userInfo['created_at'])); ?></span></div>
                        <div class="stat"><span class="stat-label">Last login</span><span class="stat-value"><?php echo $userInfo['last_login'] ? date('d/m/Y H:i', strtotime($userInfo['last_login'])) : 'Never'; ?></span></div>
                        <div class="stat"><span class="stat-label">Completion</span><span class="stat-value"><?php echo $completion; ?>%</span></div>
                        <?php if ($isOwnProfile) { ?>
                            <div class="stat"><span class="stat-label">Settings</span><span class="stat-value"><a href="/profile-edit" class="edit-profile-button">Edit profile</a></span></div>
                            <div class="stat"><span class="stat-label">Disconnect</span><span class="stat-value"><a href="/logout" class="disconnect-profile-button">Logout</a></span></div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-claimed">
            <div class="r1"><div class="ccenter"><p class="main"><?= $totalRubyCount ?> Ruby coupons</p><p class="details">Total <?= $totalRubyValue ?> ruby</p></div></div>
            <div class="st1"><div class="ccenter"><p class="main"><?= $totalStaminaCount ?> Stamina coupons</p><p class="details">Total <?= $totalStaminaValue ?> stamina</p></div></div>
            <div class="st2"><div class="ccenter"><p class="main"><?= $totalOtherCount ?> Special coupons</p></div></div>
            <div class="tt"><div class="ccenter"><p class="og"><?= $totalClaimed ?> claimed</p></div></div>
        </div>

        <div class="codes-container">
            <table id="codes">
                <thead><th>Claimed Code</th><th>Claimed at</th></thead>
                <tbody>
                    <?php foreach ($coupons as $coupon) { ?>
                        <tr><td style="text-align:left;"><?= htmlspecialchars($coupon['coupon_code']) ?></td><td><?= htmlspecialchars($coupon['claimed_at']) ?></td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <script>
            new DataTable('#codes', {
                order: [[1, 'desc']],
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50, 100]
            });
        </script>
    </main>

</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

</html>
