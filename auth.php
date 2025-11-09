<?php
$authMode = htmlspecialchars($_GET['mode']);

require_once __DIR__ . '/inc/core.php';

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

$errorMessages = [
    'empty_fields' => 'Please fill in all the fields',
    'username_too_short' => 'Username must be at least 3 characters long',
    'password_too_short' => 'Password must be at least 8 characters long',
    'system_error' => 'Error on the server, please try again later',
    'account_created' => 'Account created successfully ! You can now log in',
];

$successMessages = [
    'account_created' => 'Account created successfully ! You can now log in',
];

$displayError = '';
$displaySuccess = '';

if ($error && isset($errorMessages[$error])) {
    $displayError = $errorMessages[$error];
} elseif ($error) {
    $displayError = htmlspecialchars($error);
}

if ($success && isset($successMessages[$success])) {
    $displaySuccess = $successMessages[$success];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | <?php echo $authMode === "login" ? "Log in" : "Sign up"; ?></title>
    <link rel="stylesheet" href="/assets/styles/css/login.css">
</head>

<body class="dark">
    <?php if ($_GET['mode'] === "login") { ?>

        <div class="login-page">
            <div class="login-box">

                <h2>Log in</h2>

                <?php if ($displayError) { ?>
                    <div class="error-message" style="color: #ff4444; background: #ff444420; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #ff4444;">
                        <?php echo $displayError; ?>
                    </div>
                <?php } ?>

                <?php if ($displaySuccess) { ?>
                    <div class="success-message" style="color: #00C851; background: #00C85120; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #00C851;">
                        <?php echo $displaySuccess; ?>
                    </div>
                <?php } ?>

                <form action="/actions/login_form.php" method="POST">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit" class="btn-signin">Sign In</button>

                    <div class="register">
                        New user? <a href="/auth/register">Register</a>
                    </div>
                </form>
            </div>
        </div>

    <?php } else { ?>
        <div class="login-page">
            <div class="login-box">

                <h2>Register</h2>
                
                <!-- Affichage des messages -->
                <?php if ($displayError): ?>
                    <div class="error-message" style="color: #ff4444; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #ffcdd2;">
                        <?php echo $displayError; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/actions/register_form.php">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit" class="btn-signin">Create Account</button>

                    <div class="register">
                        Already have an account? <a href="/auth/login">Login</a>
                    </div>
                </form>
            </div>
        </div>
    <?php } ?>

</body>

</html>