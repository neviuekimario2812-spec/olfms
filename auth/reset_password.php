<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$tokenHash = hash('sha256', $token);
$error = '';
$success = false;

$stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ?');
$stmt->execute([$tokenHash]);
$reset = $stmt->fetch();

$valid = $reset && !$reset['used'] && strtotime($reset['expires_at']) > time();

if (!$token || !$reset) {
    $error = 'This reset link is invalid.';
} elseif ($reset['used']) {
    $error = 'This reset link has already been used.';
} elseif (strtotime($reset['expires_at']) <= time()) {
    $error = 'This reset link has expired. Please request a new one.'; // TC-05
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session token. Please resubmit the form.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        if (!password_meets_policy($password)) {
            $error = 'Password must be at least 8 characters and include upper case, lower case, a number and a special character.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $pdo->beginTransaction();
            $upd = $pdo->prepare('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = NULL WHERE user_id = ?');
            $upd->execute([password_hash($password, PASSWORD_BCRYPT), $reset['user_id']]);
            $mark = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE reset_id = ?');
            $mark->execute([$reset['reset_id']]);
            $pdo->commit();
            log_audit((int) $reset['user_id'], 'password_reset', 'success');
            $success = true;
        }
    }
}

$pageTitle = 'Reset Password';
$pageCss = '/auth/css/auth.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card auth-card">
        <h1>Reset your password</h1>
        <?php if ($success): ?>
            <div class="alert alert-success">Your password has been changed. You can now <a href="/auth/login.php">log in</a>.</div>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
            <?php if ($valid): ?>
                <form method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">
                    <div class="form-group">
                        <label for="password">New password</label>
                        <input type="password" id="password" name="password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-block">Change password</button>
                </form>
            <?php else: ?>
                <p><a href="/auth/forgot_password.php">Request a new reset link</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
