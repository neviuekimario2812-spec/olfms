<?php
require_once '../../includes/auth.php';

$msg = 'If the email exists, a reset link has been generated.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = trim($_POST['email'] ?? '');

    $s = db()->prepare('SELECT user_id FROM users WHERE email = ?');
    $s->execute([$email]);

    if ($u = $s->fetch()) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)')
            ->execute([
                (int) $u['user_id'],
                hash('sha256', $token),
                date('Y-m-d H:i:s', time() + 30)
            ]);

        $msg .= ' Demo link: <a href="reset.php?token=' . e($token) . '">Reset password</a>';
    }
}

$title = 'Password reset';
include '../../includes/header.php';
?>
<div class="card">
    <h1>Forgot password</h1>
    <p><?= $msg ?></p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="email" name="email" required>
        <button>Request link</button>
    </form>
</div>
<?php include '../../includes/footer.php';

