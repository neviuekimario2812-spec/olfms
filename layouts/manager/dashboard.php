<?php require_once '../../includes/auth.php';
require_role('manager');
$pdo = db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $message = 'Invalid session token.';
    } else {
        $caseId = (int) ($_POST['case_id'] ?? 0);
        $lawyerId = (int) ($_POST['lawyer_id'] ?? 0);
        $check = $pdo->prepare(
            "SELECT c.case_id FROM cases c
             JOIN users u ON u.user_id = ?
             JOIN roles r ON r.role_id = u.role_id
             WHERE c.case_id = ? AND c.lawyer_id IS NULL
             AND r.role_name = 'lawyer' AND u.is_active = 1"
        );
        $check->execute([$lawyerId, $caseId]);

        if ($check->fetch()) {
            $assign = $pdo->prepare("UPDATE cases SET lawyer_id = ?, status = 'assigned' WHERE case_id = ? AND lawyer_id IS NULL");
            $assign->execute([$lawyerId, $caseId]);
            log_audit(current_user()['user_id'], 'case_assign', 'success', "Case #$caseId -> Lawyer #$lawyerId");
            $message = 'Case assigned successfully.';
        } else {
            $message = 'Select an available case and active lawyer.';
        }
    }
}

$cases = $pdo->query(
    'SELECT c.*, u.full_name AS client, lw.full_name AS lawyer
     FROM cases c
     JOIN users u ON u.user_id = c.client_id
     LEFT JOIN users lw ON lw.user_id = c.lawyer_id
     ORDER BY c.created_at DESC'
)->fetchAll();
$lawyers = $pdo->query(
    "SELECT u.user_id, u.full_name FROM users u
     JOIN roles r ON r.role_id = u.role_id
     WHERE r.role_name = 'lawyer' AND u.is_active = 1
     ORDER BY u.full_name"
)->fetchAll();
$title = 'Manager Dashboard';
include '../../includes/header.php'; ?><h1>Company Manager Dashboard</h1>
<div class="card">
    <h2>Lawyer management</h2>
    <p><a class="btn" href="../../users/register_lawyer.php">Register a lawyer</a></p>
</div>
<div class="card">
    <h2>Case review and assignment</h2>
    <?php if ($message): ?><div class="alert alert-info"><?= e($message) ?></div><?php endif; ?>
    <table class="table">
        <tr>
            <th>Title</th>
            <th>Client</th>
            <th>Status</th>
            <th>Lawyer</th>
            <th>Action</th>
        </tr><?php foreach ($cases as $c): ?><tr>
                <td><?= e($c['title']) ?></td>
                <td><?= e($c['client']) ?></td>
                <td><?= e($c['status']) ?></td>
                <td><?= e($c['lawyer'] ?? 'Unassigned') ?></td>
                <td><?php if ($c['lawyer_id'] === null): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="case_id" value="<?= (int) $c['case_id'] ?>">
                        <select name="lawyer_id" required>
                            <option value="">Select lawyer</option>
                            <?php foreach ($lawyers as $lawyer): ?><option value="<?= (int) $lawyer['user_id'] ?>"><?= e($lawyer['full_name']) ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm">Assign</button>
                    </form>
                <?php else: ?>Assigned<?php endif; ?></td>
            </tr><?php endforeach; ?>
    </table>
</div><?php include '../../includes/footer.php';
