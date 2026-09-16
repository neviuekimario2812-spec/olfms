<?php require_once '../../includes/auth.php';
require_role('manager');
$cases = db()->query('SELECT c.*,u.full_name client FROM cases c JOIN users u ON u.user_id=c.client_id ORDER BY c.created_at DESC')->fetchAll();
$title = 'Manager Dashboard';
include '../../includes/header.php'; ?><h1>Company Manager Dashboard</h1>
<div class="card">
    <h2>Case review and assignment</h2>
    <table class="table">
        <tr>
            <th>Title</th>
            <th>Client</th>
            <th>Status</th>
        </tr><?php foreach ($cases as $c): ?><tr>
                <td><?= e($c['title']) ?></td>
                <td><?= e($c['client']) ?></td>
                <td><?= e($c['status']) ?></td>
            </tr><?php endforeach; ?>
    </table>
</div><?php include '../../includes/footer.php';
