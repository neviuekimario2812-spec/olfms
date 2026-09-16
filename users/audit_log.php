<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$pdo = get_db();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$total = (int) $pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
$stmt = $pdo->prepare(
    'SELECT a.*, u.full_name FROM audit_log a LEFT JOIN users u ON a.user_id = u.user_id
     ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
$totalPages = max(1, (int) ceil($total / $perPage));

$pageTitle = 'Audit Log';
$pageCss = '/users/css/users.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h1>Security Audit Log</h1>
    <p class="muted">Login attempts and sensitive access events across the system.</p>
    <div class="table-wrap mt-2">
        <table>
            <thead><tr><th>User</th><th>Action</th><th>Description</th><th>Status</th><th>IP</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?php echo e($l['full_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo e($l['action']); ?></td>
                    <td class="muted"><?php echo e($l['description']); ?></td>
                    <td><span class="badge badge-<?php echo $l['status']==='success'?'assigned':'declined'; ?>"><?php echo e($l['status']); ?></span></td>
                    <td><?php echo e($l['ip_address']); ?></td>
                    <td><?php echo e($l['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="pagination mt-2">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?php echo $p; ?>" class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
