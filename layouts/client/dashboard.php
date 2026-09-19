<?php require_once '../../includes/auth.php';
require_role('client');
$lawyers = db()->prepare(
    'SELECT DISTINCT u.full_name, u.email, u.phone, u.education_level
     FROM cases c
     JOIN users u ON u.user_id = c.lawyer_id
     JOIN roles r ON r.role_id = u.role_id
     WHERE c.client_id = ? AND c.lawyer_id IS NOT NULL AND r.role_name = \'lawyer\'
     ORDER BY u.full_name'
);
$lawyers->execute([user()['user_id']]);
$lawyers = $lawyers->fetchAll();
$title = 'Client Dashboard';
include '../../includes/header.php';
?>
<h1>Client Dashboard</h1>
<div class="grid">
    <div class="card">
        <h2>Case requests</h2><a class="btn" href="case.php">Submit case order</a>
    </div>
    <div class="card">
        <h2>Appointments</h2><a class="btn" href="appointment.php">Book appointment</a>
    </div>
    <div class="card">
        <h2>Guide</h2><a class="btn" href="../guide/index.php">User guide</a>
    </div>
</div>
<div class="card">
    <h2>Assigned lawyers</h2>
    <?php if ($lawyers): ?>
        <table class="table">
            <tr><th>Full name</th><th>Email</th><th>Phone</th><th>Education level</th></tr>
            <?php foreach ($lawyers as $lawyer): ?><tr>
                <td><?= e($lawyer['full_name']) ?></td>
                <td><?= e($lawyer['email']) ?></td>
                <td><?= e($lawyer['phone'] ?? 'Not provided') ?></td>
                <td><?= e($lawyer['education_level'] ?? 'Not provided') ?></td>
            </tr><?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="muted">No lawyer has been assigned to your cases yet.</p>
    <?php endif; ?>
</div>
<?php include '../../includes/footer.php'; ?>
