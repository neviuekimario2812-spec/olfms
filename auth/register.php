<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('/dashboard/dashboard.php');
}

$errors = [];
$success = false;
$old = ['full_name' => '', 'email' => '', 'location' => '', 'phone' => '', 'gender' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session token. Please resubmit the form.';
    } else {
        $old['full_name'] = clean($_POST['full_name'] ?? '');
        $old['email']     = clean($_POST['email'] ?? '');
        $old['location']  = clean($_POST['location'] ?? '');
        $old['phone']     = clean($_POST['phone'] ?? '');
        $old['gender']    = clean($_POST['gender'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        if ($old['full_name'] === '' || $old['email'] === '' || $password === '') {
            $errors[] = 'Full name, email and password are required.';
        }
        if (!valid_email($old['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!password_meets_policy($password)) {
            $errors[] = 'Password must be at least 8 characters and include upper case, lower case, a number and a special character.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Password and confirmation do not match.';
        }
        if (!in_array($old['gender'], ['male', 'female', 'other'], true)) {
            $errors[] = 'Please select a gender.';
        }

        if (!$errors) {
            $pdo = get_db();
            // Enforce uniqueness explicitly (also backed by DB UNIQUE constraint) — TC-02
            $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $check->execute([$old['email']]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                try {
                    $roleId = $pdo->query("SELECT role_id FROM roles WHERE role_name = 'client'")->fetchColumn();
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (full_name, email, password_hash, role_id, gender, location, phone)
                         VALUES (?,?,?,?,?,?,?)'
                    );
                    $stmt->execute([
                        $old['full_name'],
                        $old['email'],
                        password_hash($password, PASSWORD_BCRYPT),
                        $roleId,
                        $old['gender'],
                        $old['location'],
                        $old['phone'],
                    ]);
                    log_audit((int) $pdo->lastInsertId(), 'register', 'success', 'Client self-registration');
                    $success = true;
                } catch (PDOException $e) {
                    $errors[] = 'Registration failed due to a database constraint. The email may already be taken.';
                }
            }
        }
    }
}

$pageTitle = 'Create an Account';
$pageCss = '/auth/css/auth.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card auth-card">
        <h1>Create your client account</h1>
        <p class="muted">Register to submit case requests and book appointments with a lawyer.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">Account created successfully. You can now <a href="/auth/login.php">log in</a>.</div>
        <?php else: ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?php echo e($err); ?></div>
            <?php endforeach; ?>
            <form method="post" novalidate id="registerForm">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo e($old['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($old['email']); ?>" required>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                </div>
                <small class="muted" id="pwHint">Min 8 characters, with upper case, lower case, a number and a special character.</small>
                <div class="grid grid-2 mt-2">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo e($old['location']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Contacts (phone)</label>
                        <input type="text" id="phone" name="phone" value="<?php echo e($old['phone']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select…</option>
                        <option value="male" <?php echo $old['gender']==='male'?'selected':''; ?>>Male</option>
                        <option value="female" <?php echo $old['gender']==='female'?'selected':''; ?>>Female</option>
                        <option value="other" <?php echo $old['gender']==='other'?'selected':''; ?>>Other</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-block">Register</button>
            </form>
            <p class="text-center mt-2">Already have an account? <a href="/auth/login.php">Log in</a></p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
