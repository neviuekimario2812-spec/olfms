<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pdo = get_db();
$user = current_user();
$role = $user['role'];

// ---- Gather role-specific KPI numbers (kept lightweight for <2s response) ----
$stats = [];
$recentCases = [];
$appts = [];
$recentAudit = [];

if ($role === 'client') {
    $stmt = $pdo->prepare('SELECT status, COUNT(*) c FROM cases WHERE client_id=? GROUP BY status');
    $stmt->execute([$user['user_id']]);
    $byStatus = array_column($stmt->fetchAll(), 'c', 'status');
    $stats['My Cases'] = array_sum($byStatus);
    $stats['Pending'] = $byStatus['pending'] ?? 0;
    $stats['In Progress'] = $byStatus['in_progress'] ?? 0;
    $stats['Closed'] = $byStatus['closed'] ?? 0;

    $recentCases = $pdo->prepare('SELECT * FROM cases WHERE client_id=? ORDER BY created_at DESC LIMIT 5');
    $recentCases->execute([$user['user_id']]);
    $recentCases = $recentCases->fetchAll();

    $appts = $pdo->prepare('SELECT a.*, u.full_name AS lawyer_name FROM appointments a LEFT JOIN users u ON a.lawyer_id=u.user_id WHERE a.client_id=? ORDER BY a.appointment_date DESC LIMIT 5');
    $appts->execute([$user['user_id']]);
    $appts = $appts->fetchAll();

} elseif ($role === 'lawyer') {
    $stmt = $pdo->prepare('SELECT status, COUNT(*) c FROM cases WHERE lawyer_id=? GROUP BY status');
    $stmt->execute([$user['user_id']]);
    $byStatus = array_column($stmt->fetchAll(), 'c', 'status');
    $stats['Assigned Cases'] = array_sum($byStatus);
    $stats['In Progress'] = $byStatus['in_progress'] ?? 0;
    $stats['Closed'] = $byStatus['closed'] ?? 0;

    $pendingAppt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND status='pending'");
    $pendingAppt->execute([$user['user_id']]);
    $stats['Pending Appointments'] = (int) $pendingAppt->fetchColumn();

    $recentCases = $pdo->prepare('SELECT c.*, u.full_name AS client_name FROM cases c JOIN users u ON c.client_id=u.user_id WHERE c.lawyer_id=? ORDER BY c.updated_at DESC LIMIT 5');
    $recentCases->execute([$user['user_id']]);
    $recentCases = $recentCases->fetchAll();

} elseif ($role === 'manager') {
    $stats['Total Cases'] = (int) $pdo->query('SELECT COUNT(*) FROM cases')->fetchColumn();
    $stats['Unassigned'] = (int) $pdo->query("SELECT COUNT(*) FROM cases WHERE status='pending'")->fetchColumn();
    $stats['Lawyers'] = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.role_id WHERE r.role_name='lawyer'")->fetchColumn();
    $stats['Pending Appointments'] = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn();

    $recentCases = $pdo->query('SELECT c.*, u.full_name AS client_name FROM cases c JOIN users u ON c.client_id=u.user_id ORDER BY c.created_at DESC LIMIT 5')->fetchAll();

} else { // admin
    $stats['Total Users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['Clients'] = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.role_id WHERE r.role_name='client'")->fetchColumn();
    $stats['Lawyers'] = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.role_id WHERE r.role_name='lawyer'")->fetchColumn();
    $stats['Locked Accounts'] = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE locked_until IS NOT NULL AND locked_until > NOW()')->fetchColumn();

    $recentAudit = $pdo->query('SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 8')->fetchAll();
}

$pageTitle = ucfirst($role) . ' Dashboard';
$pageCss = '/dashboard/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h1>Welcome, <?php echo e($user['full_name']); ?></h1>
    <p class="muted">Role: <strong><?php echo e(ucfirst($role)); ?></strong></p>

    <div class="grid grid-4 mt-2">
        <?php foreach ($stats as $label => $value): ?>
            <div class="stat-card">
                <div class="stat-card__value"><?php echo e((string)$value); ?></div>
                <div class="stat-card__label"><?php echo e($label); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($role === 'client'): ?>
        <div class="card mt-3">
            <h2>Recent Cases</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentCases as $c): ?>
                    <tr>
                        <td><?php echo e($c['title']); ?></td>
                        <td><?php echo e($c['category']); ?></td>
                        <td><span class="badge badge-<?php echo e($c['status']); ?>"><?php echo e(str_replace('_',' ',$c['status'])); ?></span></td>
                        <td><?php echo e(date('d M Y', strtotime($c['created_at']))); ?></td>
                        <td><a href="/cases/view_case.php?id=<?php echo (int)$c['case_id']; ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentCases): ?><tr><td colspan="5" class="muted">No cases yet. <a href="/cases/submit_case.php">Submit one</a>.</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="card mt-3">
            <h2>Recent Appointments</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Time</th><th>Lawyer</th><th>Purpose</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($appts as $a): ?>
                    <tr>
                        <td><?php echo e($a['appointment_date']); ?></td>
                        <td><?php echo e(substr($a['appointment_time'],0,5)); ?></td>
                        <td><?php echo e($a['lawyer_name'] ?? 'Not yet assigned'); ?></td>
                        <td><?php echo e($a['purpose']); ?></td>
                        <td><span class="badge badge-<?php echo e($a['status']); ?>"><?php echo e($a['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$appts): ?><tr><td colspan="5" class="muted">No appointments yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php elseif ($role === 'lawyer'): ?>
        <div class="card mt-3">
            <h2>My Assigned Cases</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Client</th><th>Title</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentCases as $c): ?>
                    <tr>
                        <td><?php echo e($c['client_name']); ?></td>
                        <td><?php echo e($c['title']); ?></td>
                        <td><span class="badge badge-<?php echo e($c['status']); ?>"><?php echo e(str_replace('_',' ',$c['status'])); ?></span></td>
                        <td><?php echo e(date('d M Y', strtotime($c['updated_at']))); ?></td>
                        <td><a href="/cases/view_case.php?id=<?php echo (int)$c['case_id']; ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentCases): ?><tr><td colspan="5" class="muted">No cases assigned to you yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php elseif ($role === 'manager'): ?>
        <div class="card mt-3">
            <h2>Recently Submitted Cases</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Client</th><th>Title</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentCases as $c): ?>
                    <tr>
                        <td><?php echo e($c['client_name']); ?></td>
                        <td><?php echo e($c['title']); ?></td>
                        <td><span class="badge badge-<?php echo e($c['status']); ?>"><?php echo e(str_replace('_',' ',$c['status'])); ?></span></td>
                        <td><?php echo e(date('d M Y', strtotime($c['created_at']))); ?></td>
                        <td><a href="/cases/manage_cases.php?id=<?php echo (int)$c['case_id']; ?>">Review</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php else: ?>
        <div class="card mt-3">
            <h2>Recent System Activity</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Action</th><th>Status</th><th>IP</th><th>When</th></tr></thead>
                <tbody>
                <?php foreach ($recentAudit as $a): ?>
                    <tr>
                        <td><?php echo e($a['action']); ?> <span class="muted"><?php echo e($a['description']); ?></span></td>
                        <td><span class="badge badge-<?php echo $a['status']==='success'?'assigned':'declined'; ?>"><?php echo e($a['status']); ?></span></td>
                        <td><?php echo e($a['ip_address']); ?></td>
                        <td><?php echo e($a['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p class="mt-2"><a href="/users/audit_log.php">View full audit log &rarr;</a></p>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
