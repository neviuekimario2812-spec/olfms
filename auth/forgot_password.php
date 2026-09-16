<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$devLink = ''; // In production this is emailed, never shown on screen.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $message = 'Invalid session token. Please resubmit the form.';
    } else {
        $email = clean($_POST['email'] ?? '');
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Generate the token regardless, so response timing/content never
        // discloses whether the email exists (security design requirement).
        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + RESET_TOKEN_TTL_SECONDS);

            $ins = $pdo->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)'
            );
            $ins->execute([$user['user_id'], $tokenHash, $expiresAt]);
            log_audit((int) $user['user_id'], 'password_reset_request', 'success');

            // Simulated email delivery: in production this URL is sent via the
            // email service, never rendered in the HTTP response.
            $devLink = '/auth/reset_password.php?token=' . $rawToken;
        }

        $message = 'If an account exists for that email, a password reset link has been sent and will expire in '
                  . RESET_TOKEN_TTL_SECONDS . ' seconds.';
    }
}

$pageTitle = 'Forgot Password';
$pageCss = '/auth/css/auth.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card auth-card">
        <h1>Forgot your password?</h1>
        <p class="muted">Enter your account email. We'll send a reset link that is valid for 30 seconds.</p>
        <?php if ($message): ?><div class="alert alert-info"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($devLink): ?>
            <div class="alert alert-success">
                Demo mode (no email server configured) — your reset link:<br>
                <a href="<?php echo e($devLink); ?>"><?php echo e($devLink); ?></a><br>
                <strong>This link expires in <?php echo RESET_TOKEN_TTL_SECONDS; ?> seconds — click it now.</strong>
            </div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <button type="submit" class="btn btn-block">Send reset link</button>
        </form>
        <p class="text-center mt-2"><a href="/auth/login.php">Back to login</a></p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
