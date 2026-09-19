<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['manager','admin']);

$pdo = get_db();
$user = current_user();
$errors = [];
$success = false;
$old = ['full_name'=>'','email'=>'','education_level'=>'','phone'=>'','location'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session token. Please resubmit the form.';
    } else {
        foreach (['full_name','email','education_level','phone','location'] as $f) {
            $old[$f] = clean($_POST[$f] ?? '');
        }

        if ($old['full_name'] === '' || $old['email'] === '') {
            $errors[] = 'Full name and email are required.';
        }
        if (!valid_email($old['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $check->execute([$old['email']]);
            if ($check->fetch()) {
                $errors[] = 'A user with this email already exists.';
            } else {
                // Temporary password: the lawyer resets it via "forgot password" on first login.
                $tempPassword = bin2hex(random_bytes(6)) . 'Aa1!';
                $roleId = $pdo->query("SELECT role_id FROM roles WHERE role_name = 'lawyer'")->fetchColumn();
                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role_id, location, phone, education_level)
                     VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $old['full_name'], $old['email'], password_hash($tempPassword, PASSWORD_BCRYPT),
                    $roleId, $old['location'], $old['phone'], $old['education_level'],
                ]);
                log_audit($user['user_id'], 'lawyer_register', 'success', $old['email']);
                $success = true;
                $generatedPassword = $tempPassword;
            }
        }
    }
}

$pageTitle = 'Register Lawyer';
$pageCss = BASE_URL . '/users/css/users.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card">
        <h1>Register a new lawyer</h1>
        <p class="muted">Create an account for a lawyer joining the firm.</p>
        <?php if ($success): ?>
            <div class="alert alert-success">
                Lawyer account created.<br>
                Temporary password: <code><?php echo e($generatedPassword); ?></code><br>
                Share this securely — the lawyer should change it immediately after logging in, or use "Forgot password".
            </div>
        <?php else: ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?php echo e($err); ?></div><?php endforeach; ?>
            <form method="post" novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo e($old['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($old['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="education_level">Education level</label>
                    <input type="text" id="education_level" name="education_level" value="<?php echo e($old['education_level']); ?>" placeholder="e.g. LLB, LLM">
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo e($old['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo e($old['location']); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-block">Register lawyer</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
