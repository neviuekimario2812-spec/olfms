<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pdo = get_db();
$user = current_user();
$errors = [];
$success = false;

$stmt = $pdo->prepare(
    'SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id=r.role_id WHERE u.user_id = ?'
);
$stmt->execute([$user['user_id']]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session token. Please resubmit the form.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $profile['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (!password_meets_policy($new)) {
            $errors[] = 'New password must be at least 8 characters and include upper case, lower case, a number and a special character.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $upd->execute([password_hash($new, PASSWORD_BCRYPT), $user['user_id']]);
            log_audit($user['user_id'], 'password_change', 'success');
            $success = true;
        }
    }
}

$pageTitle = 'My Profile';
$pageCss = BASE_URL . '/profile/css/profile.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card">
        <h1>My Profile</h1>
        <p><strong>Name:</strong> <?php echo e($profile['full_name']); ?></p>
        <p><strong>Email:</strong> <?php echo e($profile['email']); ?></p>
        <p><strong>Role:</strong> <?php echo e(ucfirst($profile['role_name'])); ?></p>
        <?php if ($profile['location']): ?><p><strong>Location:</strong> <?php echo e($profile['location']); ?></p><?php endif; ?>
        <?php if ($profile['phone']): ?><p><strong>Phone:</strong> <?php echo e($profile['phone']); ?></p><?php endif; ?>
        <?php if ($profile['education_level']): ?><p><strong>Education:</strong> <?php echo e($profile['education_level']); ?></p><?php endif; ?>
    </div>

    <div class="card mt-3">
        <h2>Change password</h2>
        <p class="muted">All roles may only change their password from here; other profile details are managed by an administrator.</p>
        <?php if ($success): ?>
            <div class="alert alert-success">Password changed successfully.</div>
        <?php else: ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?php echo e($err); ?></div><?php endforeach; ?>
            <form method="post" novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-block">Update password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>