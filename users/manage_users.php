<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$pdo = get_db();
$admin = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $message = 'Invalid session token.';
    } else {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        $action = clean($_POST['action'] ?? '');

        if ($targetId === (int) $admin['user_id']) {
            $message = 'You cannot modify your own account from this page.';
        } elseif ($action === 'toggle_active') {
            $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE user_id = ?')->execute([$targetId]);
            log_audit($admin['user_id'], 'user_toggle_active', 'success', "User #$targetId");
            $message = 'User status updated.';
        } elseif ($action === 'unlock') {
            $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?')->execute([$targetId]);
            log_audit($admin['user_id'], 'user_unlock', 'success', "User #$targetId");
            $message = 'Account unlocked.';
        } elseif ($action === 'change_role') {
            $newRole = clean($_POST['role_name'] ?? '');
            $roleId = $pdo->prepare('SELECT role_id FROM roles WHERE role_name = ?');
            $roleId->execute([$newRole]);
            $roleId = $roleId->fetchColumn();
            if ($roleId) {
                $pdo->prepare('UPDATE users SET role_id = ? WHERE user_id = ?')->execute([$roleId, $targetId]);
                log_audit($admin['user_id'], 'user_role_change', 'success', "User #$targetId -> $newRole");
                $message = 'Role updated.';
            }
        }
    }
}

$users = $pdo->query(
    'SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.created_at DESC'
)->fetchAll();
$roles = $pdo->query('SELECT role_name FROM roles')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Manage Users';
$pageCss = '/users/css/users.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h1>Manage Users &amp; Roles</h1>
    <?php if ($message): ?><div class="alert alert-info"><?php echo e($message); ?></div><?php endif; ?>
    <div class="table-wrap mt-2">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Failed logins</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo e($u['full_name']); ?></td>
                    <td><?php echo e($u['email']); ?></td>
                    <td>
                        <form method="post" class="inline-actions">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                            <input type="hidden" name="action" value="change_role">
                            <select name="role_name" onchange="this.form.submit()" <?php echo (int)$u['user_id']===(int)$admin['user_id']?'disabled':''; ?>>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo e($r); ?>" <?php echo $r===$u['role_name']?'selected':''; ?>><?php echo e(ucfirst($r)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if (is_account_locked($u)): ?>
                            <span class="badge badge-declined">Locked</span>
                        <?php elseif (!$u['is_active']): ?>
                            <span class="badge badge-closed">Disabled</span>
                        <?php else: ?>
                            <span class="badge badge-assigned">Active</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int)$u['failed_attempts']; ?></td>
                    <td class="inline-actions">
                        <?php if ((int)$u['user_id'] !== (int)$admin['user_id']): ?>
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                            <input type="hidden" name="action" value="toggle_active">
                            <button type="submit" class="btn btn-sm btn-outline"><?php echo $u['is_active']?'Disable':'Enable'; ?></button>
                        </form>
                        <?php if (is_account_locked($u)): ?>
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                            <input type="hidden" name="action" value="unlock">
                            <button type="submit" class="btn btn-sm">Unlock</button>
                        </form>
                        <?php endif; ?>
                        <?php else: ?>&mdash;<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
