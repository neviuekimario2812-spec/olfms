<?php require_once '../../includes/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $p = $_POST['password'];
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $p)) $error = 'Password must be 8+ chars with upper, lower, number and special character.';
    else try {
        $roleId = db()->query("SELECT role_id FROM roles WHERE role_name = 'client'")->fetchColumn();
        if (!$roleId) throw new RuntimeException('Client role is not configured.');
        $s = db()->prepare('INSERT INTO users(full_name,email,password_hash,role_id,location,phone,gender) VALUES(?,?,?,?,?,?,?)');
        $s->execute([trim($_POST['full_name']), trim($_POST['email']), password_hash($p, PASSWORD_DEFAULT), $roleId, $_POST['location'], $_POST['contacts'], strtolower($_POST['gender'])]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $error = $e->getCode() === '23000'
            ? 'That email address is already registered.'
            : 'Registration is unavailable because the database schema is incomplete.';
    } catch (RuntimeException $e) {
        $error = 'Registration is unavailable because the client role is not configured.';
    }
}
$title = 'Register';
include '../../includes/header.php'; ?><div class="card">
    <h1>Client registration</h1><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input name="full_name" placeholder="Full name" required><input type="email" name="email" placeholder="Email" required><input name="location" placeholder="Location" required><input name="contacts" placeholder="Contacts" required><select name="gender" required>
            <option value="">Gender</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
        </select><input type="password" name="password" placeholder="Password" required><button>Register</button></form>
</div><?php include '../../includes/footer.php';
