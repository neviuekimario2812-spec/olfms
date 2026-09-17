<?php
require_once '../../includes/auth.php';

$ok = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $s = db()->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()');
    $s->execute([hash('sha256', $token)]);
    $reset = $s->fetch();

    if ($reset && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', (string) ($_POST['password'] ?? ''))) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
            ->execute([
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                (int) $reset['user_id']
            ]);

        db()->prepare('UPDATE password_resets SET used = 1 WHERE reset_id = ?')
            ->execute([(int) $reset['reset_id']]);

        $ok = true;
    }
}

$title = 'Reset password';
include '../../includes/header.php';
?>
<div class="card">
    <h1>Reset password</h1>
    <?php if ($ok): ?>
        Password changed. <a href="login.php">Login</a>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="password" name="password" placeholder="New password" required>
            <button>Change password</button>
        </form>
    <?php endif; ?>
</div>
<?php include '../../includes/footer.php';

