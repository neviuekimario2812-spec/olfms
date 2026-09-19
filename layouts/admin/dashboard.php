<?php require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$users = db()->query(
	'SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id DESC'
)->fetchAll();
$title = 'Administrator Dashboard';
include '../../includes/header.php';
?><h1>System Administrator Dashboard</h1>
<div class="card">
	<h2>User management and security</h2>
	<p><a class="btn" href="../../users/manage_users.php">Manage users and create managers</a></p>
	<table class="table">
		<tr><th>Name</th><th>Email</th><th>Role</th><th>Attempts</th><th>Lock</th></tr>
		<?php foreach ($users as $user): ?><tr>
			<td><?= e($user['full_name']) ?></td>
			<td><?= e($user['email']) ?></td>
			<td><?= e($user['role_name']) ?></td>
			<td><?= e($user['failed_attempts']) ?></td>
			<td><?= e($user['locked_until'] ?? 'No') ?></td>
		</tr><?php endforeach; ?>
	</table>
</div><?php include '../../includes/footer.php';
