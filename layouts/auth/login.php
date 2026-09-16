<?php require_once '../../includes/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = trim($_POST['email']);
    $s = db()->prepare('SELECT u.*, r.role_name AS role FROM users u JOIN roles r ON u.role_id=r.role_id WHERE u.email=?');
    $s->execute([$email]);
    $u = $s->fetch();
    if ($u && $u['locked_until'] && strtotime($u['locked_until']) > time()) $error = 'Account locked. Try later.';
    elseif ($u && password_verify($_POST['password'], $u['password_hash'])) {
        db()->prepare('UPDATE users SET failed_attempts=0,locked_until=NULL WHERE user_id=?')->execute([$u['user_id']]);
        $_SESSION['user'] = ['id' => $u['user_id'], 'user_id' => $u['user_id'], 'name' => $u['full_name'], 'full_name' => $u['full_name'], 'role' => $u['role'], 'email' => $u['email']];
        header('Location: /olfms/layouts/' . $u['role'] . '/dashboard.php');
        exit;
    } else {
        if ($u) {
            $n = $u['failed_attempts'] + 1;
            $lock = $n >= 3 ? date('Y-m-d H:i:s', time() + 900) : null;
            db()->prepare('UPDATE users SET failed_attempts=?,locked_until=? WHERE user_id=?')->execute([$n, $lock, $u['user_id']]);
        }
        $error = 'Invalid credentials.';
    }
}
$title = 'Login';
include '../../includes/header.php'; ?><div class="card">
    <h1>Sign in</h1><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" required><button>Login</button></form>
    <p><a href="register.php">Create client account</a> · <a href="forgot.php">Forgot password?</a></p>
</div><?php include '../../includes/footer.php';
