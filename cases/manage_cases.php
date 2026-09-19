<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['manager','admin','lawyer']);

$pdo = get_db();
$user = current_user();
$message = '';

// Manager assigns a lawyer to a case (TC-09)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'manager') {
    if (!verify_csrf()) {
        $message = 'Invalid session token.';
    } else {
        $caseId = (int) ($_POST['case_id'] ?? 0);
        $lawyerId = (int) ($_POST['lawyer_id'] ?? 0);
        if ($caseId && $lawyerId) {
            $upd = $pdo->prepare("UPDATE cases SET lawyer_id = ?, status = 'assigned' WHERE case_id = ?");
            $upd->execute([$lawyerId, $caseId]);
            log_audit($user['user_id'], 'case_assign', 'success', "Case #$caseId -> Lawyer #$lawyerId");
            $message = 'Lawyer assigned successfully.';
        }
    }
}

if ($user['role'] === 'lawyer') {
    // A lawyer landing here is redirected to their own filtered case list.
    $stmt = $pdo->prepare(
        'SELECT c.*, u.full_name AS client_name FROM cases c JOIN users u ON c.client_id = u.user_id
         WHERE c.lawyer_id = ? ORDER BY c.updated_at DESC'
    );
    $stmt->execute([$user['user_id']]);
} else {
    $stmt = $pdo->query(
        'SELECT c.*, u.full_name AS client_name, lw.full_name AS lawyer_name
         FROM cases c JOIN users u ON c.client_id = u.user_id
         LEFT JOIN users lw ON c.lawyer_id = lw.user_id
         ORDER BY c.created_at DESC'
    );
}
$cases = $stmt->fetchAll();

$lawyers = $pdo->query(
    "SELECT u.user_id, u.full_name FROM users u JOIN roles r ON u.role_id=r.role_id WHERE r.role_name='lawyer' AND u.is_active=1"
)->fetchAll();

$pageTitle = $user['role'] === 'lawyer' ? 'My Assigned Cases' : 'Manage Cases';
$pageCss = BASE_URL . '/cases/css/cases.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h1><?php echo e($pageTitle); ?></h1>
    <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>

    <div class="table-wrap mt-2">
        <table>
            <thead>
                <tr>
                    <th>Client</th><th>Title</th><th>Category</th>
                    <?php if ($user['role'] !== 'lawyer'): ?><th>Lawyer</th><?php endif; ?>
                    <th>Status</th><th>Submitted</th><th></th>
                    <?php if ($user['role'] === 'manager'): ?><th>Assign lawyer</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cases as $c): ?>
                <tr>
                    <td><?php echo e($c['client_name']); ?></td>
                    <td><?php echo e($c['title']); ?></td>
                    <td><?php echo e($c['category']); ?></td>
                    <?php if ($user['role'] !== 'lawyer'): ?><td><?php echo e($c['lawyer_name'] ?? '—'); ?></td><?php endif; ?>
                    <td><span class="badge badge-<?php echo e($c['status']); ?>"><?php echo e(str_replace('_',' ',$c['status'])); ?></span></td>
                    <td><?php echo e(date('d M Y', strtotime($c['created_at']))); ?></td>
                    <td><a href="<?= e(BASE_URL) ?>/cases/view_case.php?id=<?php echo (int)$c['case_id']; ?>">View</a></td>
                    <?php if ($user['role'] === 'manager'): ?>
                    <td>
                        <form method="post" class="assign-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="case_id" value="<?php echo (int)$c['case_id']; ?>">
                            <select name="lawyer_id" required>
                                <option value="">Select lawyer…</option>
                                <?php foreach ($lawyers as $lw): ?>
                                    <option value="<?php echo (int)$lw['user_id']; ?>" <?php echo (int)$c['lawyer_id']===(int)$lw['user_id']?'selected':''; ?>>
                                        <?php echo e($lw['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm">Assign</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cases): ?><tr><td colspan="8" class="muted">No cases found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
