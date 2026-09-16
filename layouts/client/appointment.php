<?php require_once '../../includes/auth.php';
require_role('client');
$law = db()->query("SELECT u.user_id, u.full_name FROM users u JOIN roles r ON u.role_id=r.role_id WHERE r.role_name='lawyer'")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $appointmentAt = new DateTime($_POST['appointment_at']);
    db()->prepare('INSERT INTO appointments(client_id,lawyer_id,appointment_date,appointment_time,purpose,status) VALUES(?,?,?,?,?,"pending")')->execute([user()['id'], $_POST['lawyer_id'], $appointmentAt->format('Y-m-d'), $appointmentAt->format('H:i:s'), $_POST['notes']]);
}
$title = 'Appointment';
include '../../includes/header.php'; ?><div class="card">
    <h1>Book appointment</h1>
    <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><select name="lawyer_id" required>
            <option value="">Select lawyer</option><?php foreach ($law as $l): ?><option value="<?= $l['user_id'] ?>"><?= e($l['full_name']) ?></option><?php endforeach; ?>
        </select><input type="datetime-local" name="appointment_at" required><textarea name="notes" placeholder="Notes"></textarea><button>Request appointment</button></form>
</div><?php include '../../includes/footer.php';
