<?php
require_once __DIR__ . '/inc/core.php';

if (!$auth->isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$currentUser = $auth->getCurrentUser();
$charactersDir = __DIR__ . '/assets/img/characters/';
$characterImages = glob($charactersDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | SMSDV3</title>
    <link rel="stylesheet" href="assets/styles/css/style.css">
    <link rel="shortcut icon" href="./assets/img/favicon.png" type="image/x-icon">

    <?php require_once __DIR__ . '/inc/head.php' ?>
</head>

<body>

    <?php require_once __DIR__ . '/inc/aside.php'; ?>

    <main>
        <div class="profile-edit-container">
            <div class="profile-edit-header">
                <h1>Edit Your Profile</h1>
                <p>Customize your profile picture</p>
            </div>

            <div class="current-profile">
                <div class="current-picture">
                    <img src="<?php echo $currentUser['picture'] ?: '/assets/img/characters/default.png'; ?>" alt="Current profile picture">
                </div>
                <div class="current-info">
                    <h2><?php echo $currentUser['username']; ?></h2>
                    <p>Current profile picture</p>
                </div>
            </div>

            <form action="/actions/update_profile.php" method="POST" class="profile-edit-form">
                <div class="avatar-selection">
                    <h3>Choose your avatar</h3>
                    <div class="avatar-grid">
                        <?php foreach ($characterImages as $image):
                            $imageName = basename($image);
                            $imagePath = '/assets/img/characters/' . $imageName;
                        ?>
                            <div class="avatar-option">
                                <input type="radio" name="avatar" value="<?php echo $imagePath; ?>" id="avatar-<?php echo $imageName; ?>"
                                    <?php echo $currentUser['picture'] === $imagePath ? 'checked' : ''; ?>>
                                <label for="avatar-<?php echo $imageName; ?>">
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo $imageName; ?>">
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <a href="/profile/<?php echo $currentUser['username']; ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>

        <div class="profile-edit-container">
            <div class="profile-edit-header">
                <h1>API Key</h1>
                <p>Add or change your API key</p>
            </div>

            <div class="api-key">
                <div class="api">
                    <?php
                    
                    if ($currentUser['api_key']) {
                        echo '<p>Current API Key: <span>' . $currentUser['api_key'] . '</span></p>';
                    } else {
                        echo '<p>API Key not set yet</p>';
                    }

                    ?>
                </div>
                <div class="btn">
                    <a href="actions/change_api_key.php">
                        <button>Change API Key</button>
                    </a>
                </div>
            </div>
        </div>
    </main>

</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

</html>