<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['lawyer','manager','admin']);

$pdo = get_db();
$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'lawyer') {
    if (!verify_csrf()) {
        $message = 'Invalid session token.';
    } else {
        $apptId = (int) ($_POST['appointment_id'] ?? 0);
        $action = clean($_POST['action'] ?? '');
        if ($apptId && in_array($action, ['accepted','declined','completed'], true)) {
            // Ownership check: only the assigned lawyer may update it.
            $check = $pdo->prepare('SELECT lawyer_id FROM appointments WHERE appointment_id = ?');
            $check->execute([$apptId]);
            $row = $check->fetch();
            if ($row && (int) $row['lawyer_id'] === (int) $user['user_id']) {
                $upd = $pdo->prepare('UPDATE appointments SET status = ? WHERE appointment_id = ?');
                $upd->execute([$action, $apptId]);
                $message = 'Appointment updated.';
                log_audit($user['user_id'], 'appointment_update', 'success', "Appt #$apptId -> $action");
            } else {
                $message = 'You can only update appointments assigned to you.';
            }
        }
    }
}

if ($user['role'] === 'lawyer') {
    $stmt = $pdo->prepare(
        'SELECT a.*, u.full_name AS client_name FROM appointments a JOIN users u ON a.client_id = u.user_id
         WHERE a.lawyer_id = ? ORDER BY a.appointment_date, a.appointment_time'
    );
    $stmt->execute([$user['user_id']]);
} else {
    $stmt = $pdo->query(
        'SELECT a.*, u.full_name AS client_name, lw.full_name AS lawyer_name
         FROM appointments a JOIN users u ON a.client_id = u.user_id
         LEFT JOIN users lw ON a.lawyer_id = lw.user_id
         ORDER BY a.appointment_date, a.appointment_time'
    );
}
$appointments = $stmt->fetchAll();

$pageTitle = 'Appointments';
$pageCss = '/appointments/css/appointments.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h1>Appointments</h1>
    <?php if ($message): ?><div class="alert alert-info"><?php echo e($message); ?></div><?php endif; ?>
    <div class="table-wrap mt-2">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <?php if ($user['role'] !== 'lawyer'): ?><th>Lawyer</th><?php endif; ?>
                    <th>Date</th><th>Time</th><th>Purpose</th><th>Status</th>
                    <?php if ($user['role'] === 'lawyer'): ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><?php echo e($a['client_name']); ?></td>
                    <?php if ($user['role'] !== 'lawyer'): ?><td><?php echo e($a['lawyer_name'] ?? '—'); ?></td><?php endif; ?>
                    <td><?php echo e($a['appointment_date']); ?></td>
                    <td><?php echo e(substr($a['appointment_time'],0,5)); ?></td>
                    <td><?php echo e($a['purpose']); ?></td>
                    <td><span class="badge badge-<?php echo e($a['status']); ?>"><?php echo e($a['status']); ?></span></td>
                    <?php if ($user['role'] === 'lawyer'): ?>
                    <td>
                        <?php if ($a['status'] === 'pending'): ?>
                        <form method="post" class="inline-actions">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="appointment_id" value="<?php echo (int)$a['appointment_id']; ?>">
                            <button type="submit" name="action" value="accepted" class="btn btn-sm">Accept</button>
                            <button type="submit" name="action" value="declined" class="btn btn-sm btn-outline">Decline</button>
                        </form>
                        <?php elseif ($a['status'] === 'accepted'): ?>
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="appointment_id" value="<?php echo (int)$a['appointment_id']; ?>">
                            <button type="submit" name="action" value="completed" class="btn btn-sm">Mark completed</button>
                        </form>
                        <?php else: ?>&mdash;<?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$appointments): ?><tr><td colspan="7" class="muted">No appointments found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
