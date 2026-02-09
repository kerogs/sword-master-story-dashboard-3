<?php

require_once __DIR__ . '/inc/core.php';

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

$loggedIn = $auth->isLoggedIn();
$currentUser = $loggedIn ? $auth->getCurrentUser() : null;

$metrics = [
    'available_count' => 0,
    'available_ruby' => 0,
    'available_stamina' => 0,
    'total_codes' => 0,
    'claimed_count' => 0,
    'claimed_ruby' => 0,
    'claimed_stamina' => 0,
    'claim_rate' => 0,
];
$lastClaimed = [];
$nextToClaim = [];
$recentCoupons = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $summaryStmt = $pdo->query("SELECT COUNT(*) AS total_codes,
        SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) AS available_count,
        SUM(CASE WHEN date >= CURDATE() AND type = 'Ruby' THEN value ELSE 0 END) AS available_ruby,
        SUM(CASE WHEN date >= CURDATE() AND type IN ('Stamina','Stam') THEN value ELSE 0 END) AS available_stamina
        FROM {$prefix}codes");
    $summary = $summaryStmt->fetch() ?: [];

    $metrics['total_codes'] = (int)($summary['total_codes'] ?? 0);
    $metrics['available_count'] = (int)($summary['available_count'] ?? 0);
    $metrics['available_ruby'] = (int)($summary['available_ruby'] ?? 0);
    $metrics['available_stamina'] = (int)($summary['available_stamina'] ?? 0);

    $recentStmt = $pdo->query("SELECT code, type, value, date FROM {$prefix}codes ORDER BY created_at DESC LIMIT 6");
    $recentCoupons = $recentStmt->fetchAll();

    if ($loggedIn && isset($currentUser['id'])) {
        $userId = (int)$currentUser['id'];

        $claimedStmt = $pdo->prepare("SELECT c.code, c.type, c.value, cc.claimed_at
            FROM {$prefix}claimed_coupons cc
            INNER JOIN {$prefix}codes c ON c.code = cc.coupon_code
            WHERE cc.user_id = ?
            ORDER BY cc.claimed_at DESC
            LIMIT 8");
        $claimedStmt->execute([$userId]);
        $lastClaimed = $claimedStmt->fetchAll();

        $claimedMetricsStmt = $pdo->prepare("SELECT COUNT(*) AS claimed_count,
            SUM(CASE WHEN c.type = 'Ruby' THEN c.value ELSE 0 END) AS claimed_ruby,
            SUM(CASE WHEN c.type IN ('Stamina','Stam') THEN c.value ELSE 0 END) AS claimed_stamina
            FROM {$prefix}claimed_coupons cc
            INNER JOIN {$prefix}codes c ON c.code = cc.coupon_code
            WHERE cc.user_id = ?");
        $claimedMetricsStmt->execute([$userId]);
        $claimedMetrics = $claimedMetricsStmt->fetch() ?: [];

        $metrics['claimed_count'] = (int)($claimedMetrics['claimed_count'] ?? 0);
        $metrics['claimed_ruby'] = (int)($claimedMetrics['claimed_ruby'] ?? 0);
        $metrics['claimed_stamina'] = (int)($claimedMetrics['claimed_stamina'] ?? 0);
        $metrics['claim_rate'] = $metrics['total_codes'] > 0 ? (int)round(($metrics['claimed_count'] / $metrics['total_codes']) * 100) : 0;

        $nextStmt = $pdo->prepare("SELECT c.code, c.type, c.value, c.date
            FROM {$prefix}codes c
            LEFT JOIN {$prefix}claimed_coupons cc ON cc.coupon_code = c.code AND cc.user_id = ?
            WHERE c.date >= CURDATE() AND cc.id IS NULL
            ORDER BY c.date ASC, c.priority ASC
            LIMIT 8");
        $nextStmt->execute([$userId]);
        $nextToClaim = $nextStmt->fetchAll();
    }
} catch (Throwable $e) {
    error_log('Dashboard load error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | Dashboard</title>
    <link rel="stylesheet" href="assets/styles/css/style.css">
    <link rel="shortcut icon" href="./assets/img/favicon.png" type="image/x-icon">

    <?php require_once __DIR__ . '/inc/head.php' ?>
</head>

<body>

    <?php require_once __DIR__ . '/inc/aside.php'; ?>

    <main class="dashboard-page">
        <section class="dashboard-grid">
            <article class="dashboard-card">
                <h2>Coupons available</h2>
                <p class="metric"><?= $metrics['available_count'] ?></p>
            </article>
            <article class="dashboard-card">
                <h2>Total ruby available</h2>
                <p class="metric"><?= $metrics['available_ruby'] ?></p>
            </article>
            <article class="dashboard-card">
                <h2>Total stamina available</h2>
                <p class="metric"><?= $metrics['available_stamina'] ?></p>
            </article>
            <article class="dashboard-card">
                <h2>Tracked coupons</h2>
                <p class="metric"><?= $metrics['total_codes'] ?></p>
            </article>
            <?php if ($loggedIn) { ?>
                <article class="dashboard-card">
                    <h2>Your claimed coupons</h2>
                    <p class="metric"><?= $metrics['claimed_count'] ?></p>
                </article>
                <article class="dashboard-card">
                    <h2>Your ruby collected</h2>
                    <p class="metric"><?= $metrics['claimed_ruby'] ?></p>
                </article>
                <article class="dashboard-card">
                    <h2>Your stamina collected</h2>
                    <p class="metric"><?= $metrics['claimed_stamina'] ?></p>
                </article>
                <article class="dashboard-card">
                    <h2>Your completion</h2>
                    <p class="metric"><?= $metrics['claim_rate'] ?>%</p>
                </article>
            <?php } ?>
        </section>

        <section class="dashboard-panels">
            <div class="dashboard-panel">
                <h3>Latest coupons added</h3>
                <ul>
                    <?php foreach ($recentCoupons as $coupon) { ?>
                        <li><strong><?= htmlspecialchars($coupon['code']) ?></strong> — <?= htmlspecialchars($coupon['type']) ?> x<?= (int)$coupon['value'] ?> (<?= htmlspecialchars($coupon['date']) ?>)</li>
                    <?php } ?>
                </ul>
            </div>

            <?php if ($loggedIn) { ?>
                <div class="dashboard-panel">
                    <h3>Last coupons you claimed</h3>
                    <?php if (empty($lastClaimed)) { ?>
                        <p>You haven't claimed any coupon yet.</p>
                    <?php } else { ?>
                        <ul>
                            <?php foreach ($lastClaimed as $coupon) { ?>
                                <li><strong><?= htmlspecialchars($coupon['code']) ?></strong> — <?= htmlspecialchars($coupon['type']) ?> x<?= (int)$coupon['value'] ?> (<?= htmlspecialchars($coupon['claimed_at']) ?>)</li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>

                <div class="dashboard-panel">
                    <h3>Next coupons left to collect</h3>
                    <?php if (empty($nextToClaim)) { ?>
                        <p>Great job! No available coupon left to collect right now.</p>
                    <?php } else { ?>
                        <ul>
                            <?php foreach ($nextToClaim as $coupon) { ?>
                                <li><strong><?= htmlspecialchars($coupon['code']) ?></strong> — <?= htmlspecialchars($coupon['type']) ?> x<?= (int)$coupon['value'] ?> (expires <?= htmlspecialchars($coupon['date']) ?>)</li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="dashboard-panel">
                    <h3>Track your own progress</h3>
                    <p>Create an account or log in to see your collected coupons, ruby, stamina and a personalized completion view.</p>
                    <p><a href="/auth/login">Log in now</a></p>
                </div>
            <?php } ?>
        </section>
    </main>

</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

</html>
