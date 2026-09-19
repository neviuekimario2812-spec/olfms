<?php require_once __DIR__ . '/../../includes/auth.php';
require_role('lawyer');
$s = db()->prepare('SELECT c.*, u.full_name AS client FROM cases c JOIN users u ON u.user_id = c.client_id WHERE c.lawyer_id = ?');
$s->execute([user()['user_id']]);
$cases = $s->fetchAll();
$files = db()->prepare(
    'SELECT f.file_id, f.case_id, f.original_name
     FROM files f JOIN cases c ON c.case_id = f.case_id
     WHERE c.lawyer_id = ? AND f.category = "case_document"
     ORDER BY f.uploaded_at DESC'
);
$files->execute([user()['user_id']]);
$filesByCase = [];
foreach ($files->fetchAll() as $file) {
    $filesByCase[$file['case_id']][] = $file;
}
$title = 'Lawyer Dashboard';
include '../../includes/header.php'; ?><h1>Lawyer Dashboard</h1>
<div class="card">
    <h2>Assigned cases</h2>
    <table class="table">
        <tr>
            <th>Title</th>
            <th>Client</th>
            <th>Status</th>
            <th>Documents</th>
        </tr><?php foreach ($cases as $c): ?><tr>
                <td><?= e($c['title']) ?></td>
                <td><?= e($c['client']) ?></td>
                <td><?= e($c['status']) ?></td                <td><?php foreach ($filesByCase[$c['case_id']] ?? [] as $file): ?><a href="<?= e(BASE_URL) ?>/files/download.php?id=<?= (int) $file['file_id'] ?>"><?= e($file['original_name']) ?></a><br><?php endforeach; ?><?php if (empty($filesByCase[$c['case_id']])): ?>None<?php endif; ?></td>
            </tr><?php endforeach; ?>
    </table>
    <p>Authorized PDF document uploads can be added through the case file module.</p>
</div><?php include '../../includes/footer.php';
