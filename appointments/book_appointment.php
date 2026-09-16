<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['client']);

$pdo = get_db();
$user = current_user();
$errors = [];
$success = false;

$myCases = $pdo->prepare('SELECT case_id, title FROM cases WHERE client_id = ? ORDER BY created_at DESC');
$myCases->execute([$user['user_id']]);
$myCases = $myCases->fetchAll();

$lawyers = $pdo->query(
    "SELECT u.user_id, u.full_name FROM users u JOIN roles r ON u.role_id=r.role_id WHERE r.role_name='lawyer' AND u.is_active=1"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session token. Please resubmit the form.';
    } else {
        $caseId = (int) ($_POST['case_id'] ?? 0) ?: null;
        $lawyerId = (int) ($_POST['lawyer_id'] ?? 0) ?: null;
        $date = clean($_POST['appointment_date'] ?? '');
        $time = clean($_POST['appointment_time'] ?? '');
        $purpose = clean($_POST['purpose'] ?? '');

        if ($date === '' || $time === '' || $purpose === '') {
            $errors[] = 'Date, time and purpose are required.';
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Please choose a present or future date.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO appointments (case_id, client_id, lawyer_id, appointment_date, appointment_time, purpose)
                 VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([$caseId, $user['user_id'], $lawyerId, $date, $time, $purpose]);
            log_audit($user['user_id'], 'appointment_book', 'success');
            $success = true;
        }
    }
}

$pageTitle = 'Book Appointment';
$pageCss = '/appointments/css/appointments.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card">
        <h1>Book an appointment</h1>
        <p class="muted">Choose a preferred lawyer, date and time. The lawyer will confirm or decline.</p>
        <?php if ($success): ?>
            <div class="alert alert-success">Appointment request submitted.</div>
        <?php else: ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?php echo e($err); ?></div><?php endforeach; ?>
            <form method="post" novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="case_id">Related case (optional)</label>
                    <select id="case_id" name="case_id">
                        <option value="">General consultation</option>
                        <?php foreach ($myCases as $c): ?>
                            <option value="<?php echo (int)$c['case_id']; ?>"><?php echo e($c['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="lawyer_id">Preferred lawyer (optional)</label>
                    <select id="lawyer_id" name="lawyer_id">
                        <option value="">No preference / manager will assign</option>
                        <?php foreach ($lawyers as $lw): ?>
                            <option value="<?php echo (int)$lw['user_id']; ?>"><?php echo e($lw['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="appointment_date">Date</label>
                        <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Time</label>
                        <input type="time" id="appointment_time" name="appointment_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <textarea id="purpose" name="purpose" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-block">Book appointment</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
