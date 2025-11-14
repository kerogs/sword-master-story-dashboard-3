<?php

require_once __DIR__ . '/inc/core.php';

$account = htmlspecialchars($_GET['account']);

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$db     = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

$kas = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$kas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $kas->prepare("SELECT * FROM {$prefix}users WHERE username = ?");
$stmt->execute([$account]);
$userInfo = $stmt->fetch();


if (!$userInfo) {
    header('Location: /');
    exit;
}

// other db info

$db_prefix = $_ENV['DB_PREFIX'];
$db_host   = $_ENV['DB_HOST'];
$db_dbname = $_ENV['DB_NAME'];
$db_user   = $_ENV['DB_USER'];
$db_pass   = $_ENV['DB_PASS'];

$db = new PDO("mysql:host=$db_host;dbname=$db_dbname", $db_user, $db_pass);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT * FROM {$db_prefix}claimed_coupons WHERE user_id = ? ORDER BY claimed_at DESC");
$stmt->execute([$userInfo['id']]);
$coupons = $stmt->fetchAll();

// Récupérer les informations des codes claimés
$claimedCodes = [];
if (!empty($coupons)) {
    $couponCodes = array_column($coupons, 'coupon_code');
    $placeholders = str_repeat('?,', count($couponCodes) - 1) . '?';

    $stmt = $db->prepare("SELECT * FROM {$db_prefix}codes WHERE code IN ($placeholders)");
    $stmt->execute($couponCodes);
    $claimedCodes = $stmt->fetchAll();
}

// Calcul des totaux
$totalRubyCount     = 0;
$totalRubyValue     = 0;
$totalStaminaCount  = 0;
$totalStaminaValue  = 0;
$totalOtherCount    = 0;
$totalOtherValue    = 0;
$totalClaimed = count($claimedCodes);

foreach ($claimedCodes as $code) {
    $value = intval($code['value']);

    switch ($code['type']) {
        case 'Ruby':
            $totalRubyCount++;
            $totalRubyValue += $value;
            break;
        case 'Stamina':
            $totalStaminaCount++;
            $totalStaminaValue += $value;
            break;
        default:
            $totalOtherCount++;
            $totalOtherValue += $value;
            break;
    }
}



// var_dump($coupons);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | <?php echo $userInfo['username']; ?></title>
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
                    <img src="<?php echo $userInfo['picture'] ?: '/assets/img/characters/default.png'; ?>" alt="Photo de profil de <?php echo $userInfo['username']; ?>">
                </div>
                <div class="profile-info">
                    <h1 class="username"><?php echo $userInfo['username']; ?></h1>
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-label">Member since</span>
                            <span class="stat-value"><?php echo date('d/m/Y', strtotime($userInfo['created_at'])); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Last login</span>
                            <span class="stat-value"><?php echo $userInfo['last_login'] ? date('d/m/Y H:i', strtotime($userInfo['last_login'])) : 'Jamais'; ?></span>
                        </div>
                        <?php if ($auth->isLoggedIn() && $userInfo['id'] === $auth->getCurrentUser()['id']) { ?>
                            <div class="stat">
                                <span class="stat-label">Edit your profile</span>
                                <span class="stat-value"><a href="/profile-edit" class="edit-profile-button">Custom your profile</a></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Disconnect</span>
                                <span class="stat-value"><a href="/logout.php" class="disconnect-profile-button">Logout</a></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-claimed">
            <div class="r1">
                <div class="ccenter">
                    <p class="main"><?= $totalRubyCount ?> Ruby code claimed</p>
                    <p class="details">for <?= $totalRubyValue ?> total of Ruby</p>
                </div>
            </div>
            <div class="st1">
                <div class="ccenter">
                    <p class="main"><?= $totalStaminaCount ?> Stamina code</p>
                    <p class="details">for <?= $totalStaminaValue ?> total of Stamina</p>
                </div>
            </div>
            <div class="st2">
                <div class="ccenter">
                    <p class="main"><?= $totalOtherCount ?> Spécials codes claimed</p>
                </div>
            </div>
            <div class="tt">
                <div class="ccenter">
                    <p class="og"><?= $totalClaimed ?> codes claimed</p>
                </div>
            </div>
        </div>

        <div class="codes-container">
            <table id="codes">
                <thead>
                    <th>Claimed Code</th>
                    <th>Claimed at</th>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon) { ?>
                        <tr>
                            <td style="text-align:left;"><?= htmlspecialchars($coupon['coupon_code'])  ?? '' ?></td>
                            <td><?= htmlspecialchars($coupon['claimed_at'])  ?? '' ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <script>
            let table = new DataTable('#codes', {
                error: function() {
                    return true;
                },
                order: [
                    [2, "desc"]
                ],
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50, 100, 200, 500],
            });
        </script>



    </main>

</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

</html>