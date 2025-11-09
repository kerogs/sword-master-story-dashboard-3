<?php

require_once __DIR__ . '/inc/core.php';
$loggedIn = false;

if ($auth->isLoggedIn()) {
    $loggedIn = true;
}

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['only'])) {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}codes ORDER BY created_at DESC");
    } elseif ($_GET['only'] == 'ruby') {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}codes WHERE type = 'Ruby' ORDER BY created_at DESC");
    } elseif ($_GET['only'] == 'stamina') {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}codes WHERE type = 'Stamina' ORDER BY created_at DESC");
    } elseif ($_GET['only'] == 'special') {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}codes WHERE type NOT IN ('Ruby', 'Stamina') ORDER BY created_at DESC");
    }
    $stmt->execute();
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $claimedCoupons = [];
    if ($loggedIn) {
        $currentUser = $auth->getCurrentUser();
        $stmt = $pdo->prepare("SELECT coupon_code FROM {$prefix}claimed_coupons WHERE user_id = ?");
        $stmt->execute([$currentUser['id']]);
        $claimedCoupons = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $coupons = [];
    $claimedCoupons = [];
    var_dump($e);
}

$_GET['only'] = isset($_GET['only']) ? $_GET['only'] : '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
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

        <div class="codesOnlyMode">
            <a href="?only=ruby">
                <div class="ruby <?= $_GET['only'] == 'ruby' ? 'active' : '' ?>">
                    Ruby
                </div>
            </a>
            <a href="?only=stamina">
                <div class="stamina <?= $_GET['only'] == 'stamina' ? 'active' : '' ?>">
                    Stamina
                </div>
            </a>
            <a href="?only=special">
                <div class="special <?= $_GET['only'] == 'special' ? 'active' : '' ?>">
                    Special
                </div>
            </a>
            <a href="?">
                <div class="all <?= $_GET['only'] == '' ? 'active' : '' ?>">
                    All
                </div>
            </a>

        </div>

        <div class="codes-container">
            <table id="codes">
                <thead>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Reward</th>
                    <th>Date</th>
                    <th>Status</th>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon) { ?>
                        <?php

                        switch ($coupon['type']) {
                            case 'Stamina':
                                $coupon['type'] = 'Stamina';
                                break;
                            case 'Ruby':
                                $coupon['type'] = 'Ruby';
                                break;
                            default:
                                $coupon['type'] = 'Special';
                        }

                        // date format Y-m-d
                        // si la date est pas encore passé indiquer comme disponible
                        // si la date est deja passé indiquer comme expirer
                        // si le code est dans les $claimedCoupons, indiquer comme claimer
                        $couponDateTimestamp = strtotime($coupon['date']);
                        $actualTimestamp = time();
                        // echo $couponDateTimestamp . " " . $actualTimestamp;

                        if ($couponDateTimestamp < $actualTimestamp) {
                            $coupon['status'] = 'Expired';
                        } else {
                            $coupon['status'] = 'Available';
                        }

                        if (in_array($coupon['code'], $claimedCoupons)) {
                            $coupon['status'] = 'Claimed';
                        }

                        ?>
                        <tr class="<?= $coupon['status'] ?>">
                            <td><?= htmlspecialchars($coupon['code'])  ?? '' ?></td>
                            <td class="<?= strtolower($coupon['type']) ?>"><?= htmlspecialchars($coupon['type'])  ?? '' ?></td>
                            <td><?= htmlspecialchars(str_replace(["<tr>", "<td>", "</td>", "</tr>", "<strong>", "</strong>"], "", $coupon['description']))  ?? '' ?></td>
                            <td><?= htmlspecialchars($coupon['date'])  ?? '' ?></td>
                            <td class="<?= strtolower($coupon['status']) ?>"><?= htmlspecialchars($coupon['status'])  ?? '' ?></td>
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
                dateFormat: "Y-m-d",
                order: [
                    [3, "desc"]
                ]
            });
        </script>
    </main>

</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

<script>
    const notyf = new Notyf({
        duration: 2500,
        position: {
            x: 'right',
            y: 'bottom'
        }
    });

    document.querySelector('#codes tbody').addEventListener('click', async (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;

        const code = tr.querySelector('td:first-child').textContent.trim();
        const statusCell = tr.querySelector('td:last-child');
        const currentStatus = statusCell.textContent.trim().toLowerCase();

        if (currentStatus === 'claimed' || currentStatus === 'expired') {
            notyf.error('Code already claimed or expired.');
            return;
        }

        try {
            const res = await axios.post('/actions/claim_coupon.php', {
                code
            });

            if (res.data?.success) {
                notyf.success('Code claimed successfully.');
                statusCell.textContent = 'Claimed';
                statusCell.className = 'claimed';
                tr.classList.add('claimed');
            } else {
                notyf.error(res.data?.message || 'Error from server.');
            }
        } catch (err) {
            console.error(err);
            notyf.error('Error from server. Please try again.');
        }
    });
</script>

</html>