<?php

require_once __DIR__ . '/inc/core.php';

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$db     = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

$kas = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$kas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $kas->prepare("SELECT username, picture FROM {$prefix}users ORDER BY username ASC");
$stmt->execute();
$usersList = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | Profiles list</title>
    <link rel="stylesheet" href="assets/styles/css/style.css">
    <link rel="shortcut icon" href="./assets/img/favicon.png" type="image/x-icon">
    <?php require_once __DIR__ . '/inc/head.php' ?>
</head>

<body>

    <?php require_once __DIR__ . '/inc/aside.php'; ?>

    <main>

        <div class="users-list-container">
            <div class="list">
                <?php foreach ($usersList as $user) { ?>
                    <a href="/profile/<?= $user['username'] ?>">
                        <div class="user">
                            <img src="<?= $user['picture'] ?>" alt="">
                            <p><?= $user['username'] ?></p>
                        </div>
                    </a>
                <?php } ?>
            </div>
        </div>

    </main>

</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

</html>