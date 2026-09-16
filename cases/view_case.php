<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pdo = get_db();
$user = current_user();
$caseId = (int) ($_GET['id'] ?? 0);

if (!$caseId || !user_can_access_case($pdo, $user, $caseId)) {
    log_audit($user['user_id'], 'case_access_denied', 'failure', "Case #$caseId");
    http_response_code(403);
    die('<h2>403 — Access denied</h2><p>You are not authorized to view this case.</p>');
}

$stmt = $pdo->prepare(
    'SELECT c.*, cl.full_name AS client_name, cl.email AS client_email,
            lw.full_name AS lawyer_name
     FROM cases c
     JOIN users cl ON c.client_id = cl.user_id
     LEFT JOIN users lw ON c.lawyer_id = lw.user_id
     WHERE c.case_id = ?'
);
$stmt->execute([$caseId]);
$case = $stmt->fetch();

if (!$case) {
    http_response_code(404);
    die('<h2>404 — Case not found</h2>');
}

$statusMsg = '';
// Lawyer can move the case forward; client/manager review only from here.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($user['role'], ['lawyer','manager','admin'], true)) {
    if (!verify_csrf()) {
        $statusMsg = 'Invalid session token.';
    } else {
        $newStatus = clean($_POST['status'] ?? '');
        if (in_array($newStatus, ['in_progress','closed','assigned'], true)) {
            $upd = $pdo->prepare('UPDATE cases SET status = ? WHERE case_id = ?');
            $upd->execute([$newStatus, $caseId]);
            log_audit($user['user_id'], 'case_status_update', 'success', "Case #$caseId -> $newStatus");
            redirect('/cases/view_case.php?id=' . $caseId);
        }
    }
}

$files = $pdo->prepare('SELECT * FROM files WHERE case_id = ? ORDER BY uploaded_at DESC');
$files->execute([$caseId]);
$files = $files->fetchAll();

$pageTitle = 'Case: ' . $case['title'];
$pageCss = '/cases/css/cases.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <h1><?php echo e($case['title']); ?></h1>
        <span class="badge badge-<?php echo e($case['status']); ?>"><?php echo e(str_replace('_',' ',$case['status'])); ?></span>
    </div>
    <?php if ($statusMsg): ?><div class="alert alert-error"><?php echo e($statusMsg); ?></div><?php endif; ?>

    <div class="grid grid-2 mt-2">
        <div class="card">
            <h2>Details</h2>
            <p><strong>Category:</strong> <?php echo e($case['category']); ?></p>
            <p><strong>Description:</strong><br><?php echo nl2br(e($case['description'])); ?></p>
            <p><strong>Submitted:</strong> <?php echo e(date('d M Y H:i', strtotime($case['created_at']))); ?></p>
        </div>
        <div class="card">
            <h2>People</h2>
            <p><strong>Client:</strong> <?php echo e($case['client_name']); ?> (<?php echo e($case['client_email']); ?>)</p>
            <p><strong>Lawyer:</strong> <?php echo e($case['lawyer_name'] ?? 'Not yet assigned'); ?></p>

            <?php if (in_array($user['role'], ['lawyer','manager','admin'], true)): ?>
                <form method="post" class="mt-2">
                    <?php echo csrf_field(); ?>
                    <label for="status">Update status</label>
                    <select name="status" id="status">
                        <option value="assigned" <?php echo $case['status']==='assigned'?'selected':''; ?>>Assigned</option>
                        <option value="in_progress" <?php echo $case['status']==='in_progress'?'selected':''; ?>>In progress</option>
                        <option value="closed" <?php echo $case['status']==='closed'?'selected':''; ?>>Closed</option>
                    </select>
                    <button type="submit" class="btn btn-sm mt-1">Update</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="page-head">
            <h2>Case Files</h2>
            <a href="/files/upload.php?case_id=<?php echo (int)$case['case_id']; ?>" class="btn btn-sm btn-gold">Upload file</a>
        </div>
        <div class="table-wrap mt-2">
            <table>
                <thead><tr><th>File</th><th>Category</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($files as $f): ?>
                    <tr>
                        <td><?php echo e($f['original_name']); ?></td>
                        <td><?php echo e(str_replace('_',' ',$f['category'])); ?></td>
                        <td><?php echo e(round($f['file_size']/1024)); ?> KB</td>
                        <td><?php echo e(date('d M Y', strtotime($f['uploaded_at']))); ?></td>
                        <td><a href="/files/download.php?id=<?php echo (int)$f['file_id']; ?>">Download</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$files): ?><tr><td colspan="5" class="muted">No files uploaded yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
