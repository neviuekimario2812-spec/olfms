<?php

/**
 * ONE-TIME SETUP SCRIPT
 * Creates the first "admin" account using PHP's own password_hash() so the
 * stored hash is guaranteed valid (bcrypt / adaptive, per the security
 * design). Visit this file once in the browser after importing sql/schema.sql,
 * then delete it or leave it — it refuses to run again once an admin exists.
 */
require_once __DIR__ . '/db.php';

$pdo = get_db();
$adminCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'admin'"
)->fetchColumn();

$done = false;
$error = '';

if ($adminCount > 0) {
    $done = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error = 'Password must be at least 8 characters and include upper case, lower case, a number and a special character.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $roleId = $pdo->query("SELECT role_id FROM roles WHERE role_name = 'admin'")->fetchColumn();
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role_id, is_active) VALUES (?,?,?,?,1)'
        );
        $stmt->execute([$fullName, $email, $hash, $roleId]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>OLFMS Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #F5F5F7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .box {
            background: #fff;
            padding: 32px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .1);
            max-width: 420px;
            width: 100%;
        }

        h1 {
            font-size: 20px;
            color: #0D47A1;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 6px 0 14px;
            border: 1px solid #E5E5EA;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            background: #0D47A1;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }

        .error {
            color: #C62828;
            margin-bottom: 10px;
        }

        .ok {
            color: #1B5E20;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1><?php echo APP_NAME; ?> — First-time setup</h1>
        <?php if ($done): ?>
            <p class="ok">An administrator account already exists. Setup is locked.</p>
            <p><a href="../auth/login.php">Go to login</a></p>
        <?php else: ?>
            <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <form method="post">
                <label>Full name</label>
                <input type="text" name="full_name" required>
                <label>Email</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit">Create administrator</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>