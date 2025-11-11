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

$userWebhook = false;

if ($loggedIn) {
    $currentUser = $auth->getCurrentUser();
    $jsonData = $currentUser['json_data'] ?? '{}'; // si null, on met un JSON vide
    $json_dataDecode = json_decode($jsonData, true);
    $userWebhook = $json_dataDecode['webhook']['url'] ?? false;
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

        <!-- if user has webhooks show it  -->
        <?php if ($userWebhook) { ?>
            <a class="sendWebhook" href="/actions/hooks/discord_coupons.php">
                <div>
                    <p>Send webhook notification</p>
                    <div class="icon">
                        <svg viewBox="0 -28.5 256 256" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <g>
                                    <path d="M216.856339,16.5966031 C200.285002,8.84328665 182.566144,3.2084988 164.041564,0 C161.766523,4.11318106 159.108624,9.64549908 157.276099,14.0464379 C137.583995,11.0849896 118.072967,11.0849896 98.7430163,14.0464379 C96.9108417,9.64549908 94.1925838,4.11318106 91.8971895,0 C73.3526068,3.2084988 55.6133949,8.86399117 39.0420583,16.6376612 C5.61752293,67.146514 -3.4433191,116.400813 1.08711069,164.955721 C23.2560196,181.510915 44.7403634,191.567697 65.8621325,198.148576 C71.0772151,190.971126 75.7283628,183.341335 79.7352139,175.300261 C72.104019,172.400575 64.7949724,168.822202 57.8887866,164.667963 C59.7209612,163.310589 61.5131304,161.891452 63.2445898,160.431257 C105.36741,180.133187 151.134928,180.133187 192.754523,160.431257 C194.506336,161.891452 196.298154,163.310589 198.110326,164.667963 C191.183787,168.842556 183.854737,172.420929 176.223542,175.320965 C180.230393,183.341335 184.861538,190.991831 190.096624,198.16893 C211.238746,191.588051 232.743023,181.531619 254.911949,164.955721 C260.227747,108.668201 245.831087,59.8662432 216.856339,16.5966031 Z M85.4738752,135.09489 C72.8290281,135.09489 62.4592217,123.290155 62.4592217,108.914901 C62.4592217,94.5396472 72.607595,82.7145587 85.4738752,82.7145587 C98.3405064,82.7145587 108.709962,94.5189427 108.488529,108.914901 C108.508531,123.290155 98.3405064,135.09489 85.4738752,135.09489 Z M170.525237,135.09489 C157.88039,135.09489 147.510584,123.290155 147.510584,108.914901 C147.510584,94.5396472 157.658606,82.7145587 170.525237,82.7145587 C183.391518,82.7145587 193.761324,94.5189427 193.539891,108.914901 C193.539891,123.290155 183.391518,135.09489 170.525237,135.09489 Z" fill-rule="nonzero"> </path>
                                </g>
                            </g>
                        </svg>
                    </div>
                </div>
            </a>
        <?php } ?>

        <div class="codesOnlyMode">
            <a href="?only=ruby">
                <div class="ruby <?= $_GET['only'] == 'ruby' ? 'active' : '' ?>">
                    Ruby

                    <div class="icon">
                        <img src="/assets/img/ruby.webp" alt="">
                    </div>
                </div>
            </a>
            <a href="?only=stamina">
                <div class="stamina <?= $_GET['only'] == 'stamina' ? 'active' : '' ?>">
                    Stamina

                    <div class="icon">
                        <img src="/assets/img/stemina.webp" alt="">
                    </div>
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
                    <th>Priority</th>
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
                                $coupon['type'] = $coupon['type'];
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
                            <td><?= $coupon['priority']  ?? '' ?></td>
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
                    [4, "desc"]
                ],
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

        const code = tr.querySelector('td:nth-child(2)').textContent.trim();
        const statusCell = tr.querySelector('td:last-child');
        const currentStatus = statusCell.textContent.trim().toLowerCase();

        if (currentStatus === 'claimed' || currentStatus === 'expired') {
            notyf.error('Code already claimed or expired.');
            return;
        }

        try {
            const res = await axios.post('./actions/claim_coupon.php', {
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