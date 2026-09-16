<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['client']);

$user = current_user();
$pdo = get_db();
$stmt = $pdo->prepare(
    'SELECT c.*, u.full_name AS lawyer_name FROM cases c LEFT JOIN users u ON c.lawyer_id = u.user_id
     WHERE c.client_id = ? ORDER BY c.created_at DESC'
);
$stmt->execute([$user['user_id']]);
$cases = $stmt->fetchAll();

$pageTitle = 'My Cases';
$pageCss = '/cases/css/cases.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <h1>My Cases</h1>
        <a href="/cases/submit_case.php" class="btn btn-gold">+ Submit New Case</a>
    </div>
    <div class="table-wrap mt-2">
        <table>
            <thead><tr><th>Title</th><th>Category</th><th>Lawyer</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($cases as $c): ?>
                <tr>
                    <td><?php echo e($c['title']); ?></td>
                    <td><?php echo e($c['category']); ?></td>
                    <td><?php echo e($c['lawyer_name'] ?? 'Not yet assigned'); ?></td>
                    <td><span class="badge badge-<?php echo e($c['status']); ?>"><?php echo e(str_replace('_',' ',$c['status'])); ?></span></td>
                    <td><?php echo e(date('d M Y', strtotime($c['created_at']))); ?></td>
                    <td><a href="/cases/view_case.php?id=<?php echo (int)$c['case_id']; ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cases): ?><tr><td colspan="6" class="muted">You have not submitted any cases yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
