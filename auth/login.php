<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

function dashboard_for_role(int $roleId): string
{
    return [
        1 => BASE_URL . '/layouts/admin/dashboard.php',
        2 => BASE_URL . '/layouts/manager/dashboard.php',
        3 => BASE_URL . '/layouts/lawyer/dashboard.php',
        4 => BASE_URL . '/layouts/client/dashboard.php',
    ][$roleId] ?? BASE_URL . '/auth/login.php';
}

if (is_logged_in()) {
    redirect(dashboard_for_role((int) ($_SESSION['user']['role_id'] ?? 0)));
}

$error = '';
$emailOld = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session token. Please resubmit the form.';
    } else {
        $emailOld = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $pdo = get_db();

        $stmt = $pdo->prepare(
            'SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.email = ?'
        );
        $stmt->execute([$emailOld]);
        $user = $stmt->fetch();

        if (!$user) {
            // Do not reveal whether the account exists.
            $error = 'Invalid email or password.';
            log_audit(null, 'login', 'failure', "Unknown email: $emailOld");
        } elseif (!$user['is_active']) {
            $error = 'This account has been disabled. Contact the administrator.';
        } elseif (is_account_locked($user)) {
            $error = 'This account is locked due to repeated failed attempts. Try again later or reset your password.';
            log_audit((int) $user['user_id'], 'login', 'failure', 'Attempt while locked');
        } elseif (!password_verify($password, $user['password_hash'])) {
            register_failed_login($pdo, $user);
            $remaining = MAX_LOGIN_ATTEMPTS - ((int) $user['failed_attempts'] + 1);
            $error = $remaining > 0
                ? "Invalid email or password. $remaining attempt(s) remaining before lockout."
                : 'Invalid email or password. Your account has been locked.';
            log_audit((int) $user['user_id'], 'login', 'failure', 'Wrong password');
        } else {
            reset_failed_login($pdo, (int) $user['user_id']);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id'   => (int) $user['user_id'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role_id'   => (int) $user['role_id'],
                'role'      => $user['role_name'],
            ];
            log_audit((int) $user['user_id'], 'login', 'success');
            redirect(dashboard_for_role((int) $user['role_id']));
        }
    }
}

$pageTitle = 'Login';
$pageCss = BASE_URL . '/auth/css/auth.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card auth-card">
        <h1>Welcome back</h1>
        <p class="muted">Log in with your registered email and password.</p>
        <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
        <form method="post" novalidate>
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo e($emailOld); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-block">Login</button>
        </form>
        <div class="auth-links">
            <a href="<?= e(BASE_URL) ?>/auth/forgot_password.php">Forgot password?</a>
            <a href="<?= e(BASE_URL) ?>/auth/register.php">Create an account</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
