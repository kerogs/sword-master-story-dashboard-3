<?php

require_once __DIR__ . '/inc/core.php';

$loggedIn = $auth->isLoggedIn();
$currentUser = $loggedIn ? $auth->getCurrentUser() : null;

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

$filter = strtolower(trim($_GET['only'] ?? ''));
$allowedFilters = ['ruby', 'stamina', 'special', 'all'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$filterSql = '';
$params = [];
if ($filter === 'ruby') {
    $filterSql = 'WHERE LOWER(type) = ?';
    $params[] = 'ruby';
} elseif ($filter === 'stamina') {
    $filterSql = "WHERE LOWER(type) IN ('stamina','stam')";
} elseif ($filter === 'special') {
    $filterSql = "WHERE LOWER(type) NOT IN ('ruby','stamina','stam')";
}

$formError = $_GET['form_error'] ?? '';
$formSuccess = $_GET['form_success'] ?? '';
$formMessages = [
    'missing_fields' => 'Please complete all required coupon fields.',
    'invalid_code_format' => 'Code must be 3-40 chars, uppercase letters/numbers/_/-.',
    'invalid_type' => 'Please choose a valid coupon type.',
    'invalid_date' => 'Please provide a valid date format.',
    'code_exists' => 'This coupon code already exists.',
    'invalid_method' => 'Invalid request method.',
    'system_error' => 'Unable to add coupon now, try again later.',
    'coupon_added' => 'Coupon added successfully.',
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM {$prefix}codes {$filterSql} ORDER BY created_at DESC");
    $stmt->execute($params);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $claimedCoupons = [];
    if ($loggedIn) {
        $stmt = $pdo->prepare("SELECT coupon_code FROM {$prefix}claimed_coupons WHERE user_id = ?");
        $stmt->execute([$currentUser['id']]);
        $claimedCoupons = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $coupons = [];
    $claimedCoupons = [];
    error_log('Coupons page error: ' . $e->getMessage());
}

$userWebhook = false;
if ($loggedIn) {
    $jsonData = $currentUser['json_data'] ?? '{}';
    $jsonDecode = json_decode($jsonData, true);
    $userWebhook = $jsonDecode['webhook']['url'] ?? false;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | Coupons</title>
    <link rel="stylesheet" href="assets/styles/css/style.css">
    <link rel="shortcut icon" href="./assets/img/favicon.png" type="image/x-icon">
    <?php require_once __DIR__ . '/inc/head.php' ?>
</head>

<body>

    <?php require_once __DIR__ . '/inc/aside.php'; ?>

    <main>

        <?php if ($userWebhook) { ?>
            <a class="sendWebhook" href="/actions/hooks/discord_coupons.php">
                <div>
                    <p>Send webhook notification</p>
                </div>
            </a>
        <?php } ?>

        <?php if ($loggedIn) { ?>
            <section class="add-coupon-section">
                <h3>Add a coupon manually</h3>
                <?php if ($formError && isset($formMessages[$formError])) { ?>
                    <p class="form-message error"><?= htmlspecialchars($formMessages[$formError]) ?></p>
                <?php } ?>
                <?php if ($formSuccess && isset($formMessages[$formSuccess])) { ?>
                    <p class="form-message success"><?= htmlspecialchars($formMessages[$formSuccess]) ?></p>
                <?php } ?>
                <form method="POST" action="/actions/add_coupon.php" class="add-coupon-form">
                    <input type="text" name="code" placeholder="Coupon code" required>
                    <select name="type" required>
                        <option value="Ruby">Ruby</option>
                        <option value="Stamina">Stamina</option>
                        <option value="Special">Special</option>
                    </select>
                    <input type="number" name="value" min="0" step="1" placeholder="Value" required>
                    <input type="date" name="date" required>
                    <input type="number" name="priority" min="1" max="5" value="3" required>
                    <input type="text" name="description" placeholder="Reward description" required>
                    <button type="submit">Add coupon</button>
                </form>
            </section>
        <?php } ?>

        <div class="codesOnlyMode">
            <a href="?only=ruby"><div class="ruby <?= $filter === 'ruby' ? 'active' : '' ?>">Ruby<div class="icon"><img src="/assets/img/ruby.webp" alt=""></div></div></a>
            <a href="?only=stamina"><div class="stamina <?= $filter === 'stamina' ? 'active' : '' ?>">Stamina<div class="icon"><img src="/assets/img/stemina.webp" alt=""></div></div></a>
            <a href="?only=special"><div class="special <?= $filter === 'special' ? 'active' : '' ?>">Special</div></a>
            <a href="?only=all"><div class="all <?= $filter === 'all' ? 'active' : '' ?>">All</div></a>
        </div>

        <div class="codes-container">
            <table id="codes">
                <thead>
                    <th>Priority</th><th>Code</th><th>Type</th><th>Reward</th><th>Date</th><th>Status</th>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon) {
                        $couponDateTimestamp = strtotime($coupon['date']);
                        $actualTimestamp = time();
                        $status = $couponDateTimestamp < $actualTimestamp ? 'Expired' : 'Available';
                        if (in_array($coupon['code'], $claimedCoupons, true)) {
                            $status = 'Claimed';
                        }
                        $typeClass = strtolower($coupon['type']);
                        if ($typeClass === 'stam') {
                            $typeClass = 'stamina';
                            $coupon['type'] = 'Stamina';
                        }
                    ?>
                        <tr data-clipboard-text="<?= htmlspecialchars($coupon['code']) ?>" class="<?= $status ?>">
                            <td><?= (int)($coupon['priority'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($coupon['code']) ?></td>
                            <td class="<?= $typeClass ?>"><?= htmlspecialchars($coupon['type']) ?></td>
                            <td><?= htmlspecialchars(strip_tags($coupon['description'])) ?></td>
                            <td><?= htmlspecialchars($coupon['date']) ?></td>
                            <td class="<?= strtolower($status) ?>"><?= $status ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <script>
            new DataTable('#codes', {
                order: [[4, "desc"]],
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100, 200, 500],
                responsive: true,
                deferRender: true
            });
        </script>
    </main>

</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const clipboard = new ClipboardJS('tr[data-clipboard-text]', {
            text: function(trigger) {
                return trigger.getAttribute('data-clipboard-text');
            }
        });

        clipboard.on('success', function(e) {
            e.clearSelection();
        });

        clipboard.on('error', function() {
            alert('Error while copying code');
        });

        const body = document.querySelector('#codes tbody');
        if (!body) {
            return;
        }

        const notyf = new Notyf({
            duration: 2500,
            position: { x: 'right', y: 'bottom' }
        });

        body.addEventListener('click', async (e) => {
            const tr = e.target.closest('tr');
            if (!tr) return;

            const code = tr.querySelector('td:nth-child(2)').textContent.trim();
            const statusCell = tr.querySelector('td:last-child');
            const currentStatus = statusCell.textContent.trim().toLowerCase();

            if (currentStatus === 'claimed' || currentStatus === 'expired') {
                notyf.error('Code already claimed or expired.');
                return;
            }

            try {
                const res = await axios.post('actions/claim_coupon.php', { code });
                if (res.data?.success) {
                    notyf.success('Code claimed successfully.');
                    statusCell.textContent = 'Claimed';
                    statusCell.className = 'claimed';
                    tr.classList.add('claimed');
                } else {
                    notyf.error(res.data?.message || 'Error from server.');
                }
            } catch (err) {
                notyf.error('Error from server. Please try again.');
            }
        });
    });
</script>

</html>
