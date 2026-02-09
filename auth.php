<?php
require_once __DIR__ . '/inc/core.php';

$mode = $_GET['mode'] ?? 'login';
if (!in_array($mode, ['login', 'register'], true)) {
    header('Location: /auth/login?error=invalid_auth_mode');
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$prefilledUsername = trim($_GET['username'] ?? '');

$errorMessages = [
    'empty_fields' => 'Please fill in all the fields.',
    'username_too_short' => 'Username must be at least 3 characters long.',
    'password_too_short' => 'Password must be at least 8 characters long.',
    'invalid_credentials' => 'Invalid username or password.',
    'account_locked' => 'Account temporarily locked. Try again later.',
    'account_disabled' => 'This account is disabled.',
    'register_failed' => 'Unable to create account with these credentials.',
    'login_failed' => 'Login failed. Please try again.',
    'invalid_method' => 'Invalid request method.',
    'invalid_auth_mode' => 'Unknown auth page requested.',
    'login_required' => 'Please log in first.',
    'system_error' => 'Server error, please try again later.',
];

$successMessages = [
    'account_created' => 'Account created successfully! You can now log in.',
];

$displayError = $error && isset($errorMessages[$error]) ? $errorMessages[$error] : ($error ? htmlspecialchars($error) : '');
$displaySuccess = $success && isset($successMessages[$success]) ? $successMessages[$success] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | <?php echo $mode === 'login' ? 'Log in' : 'Sign up'; ?></title>
    <link rel="stylesheet" href="/assets/styles/css/login.css">
</head>

<body>
    <div class="login-page">
        <div class="login-box">
            <h2><?php echo $mode === 'login' ? 'Log in' : 'Register'; ?></h2>

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

            <?php if ($mode === 'login') { ?>
                <form action="/actions/login_form.php" method="POST">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($prefilledUsername); ?>" required>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit" class="btn-signin">Sign In</button>

                    <div class="register">
                        New user? <a href="/auth/register">Register</a>
                    </div>
                </form>
            <?php } else { ?>
                <form method="POST" action="/actions/register_form.php">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($prefilledUsername); ?>" required>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit" class="btn-signin">Create Account</button>

                    <div class="register">
                        Already have an account? <a href="/auth/login">Login</a>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
</body>

</html>
