<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['client','lawyer','manager','admin']);

$pdo = get_db();
$user = current_user();
$errors = [];
$success = false;
$caseId = (int) ($_GET['case_id'] ?? $_POST['case_id'] ?? 0) ?: null;

// A client/lawyer may only attach files to a case they can access.
if ($caseId && !user_can_access_case($pdo, $user, $caseId)) {
    http_response_code(403);
    die('<h2>403 — Access denied</h2><p>You cannot upload to this case.</p>');
}

$allowLawDoc = $user['role'] === 'lawyer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session token. Please resubmit the form.';
    } else {
        $category = clean($_POST['category'] ?? 'case_document');
        if ($category === 'law_document' && !$allowLawDoc) {
            $errors[] = 'Only lawyers may upload law/constitutional documents.';
        }
        if (empty($_FILES['document']['name'])) {
            $errors[] = 'Please choose a file to upload.';
        }

        if (!$errors) {
            $subDir = $category === 'law_document' ? 'law_documents' : 'case_documents';
            $result = validate_and_store_upload($_FILES['document'], $subDir);
            if (!$result['ok']) {
                $errors[] = $result['error'];
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO files (case_id, uploaded_by, category, original_name, stored_name, mime_type, file_size)
                     VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $category === 'law_document' ? null : $caseId,
                    $user['user_id'], $category,
                    $result['original_name'], $result['stored_name'], $result['mime'], $result['size'],
                ]);
                log_audit($user['user_id'], 'file_upload', 'success', $result['original_name']);
                $success = true;
            }
        }
    }
}

$pageTitle = 'Upload Document';
$pageCss = BASE_URL . '/files/css/files.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card">
        <h1>Upload a document</h1>
        <p class="muted">Only PDF and PNG files up to 10MB are accepted.</p>
        <?php if ($success): ?>
            <div class="alert alert-success">File uploaded successfully.</div>
            <?php if ($caseId): ?><p><a href="<?= e(BASE_URL) ?>/cases/view_case.php?id=<?php echo $caseId; ?>">&larr; Back to case</a></p><?php endif; ?>
        <?php else: ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?php echo e($err); ?></div><?php endforeach; ?>
            <form method="post" enctype="multipart/form-data" novalidate>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="case_id" value="<?php echo (int) $caseId; ?>">
                <div class="form-group">
                    <label for="category">Document type</label>
                    <select id="category" name="category">
                        <option value="case_document">Case document</option>
                        <?php if ($allowLawDoc): ?>
                            <option value="law_document">Law / constitutional document</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="document">File (PDF or PNG, max 10MB)</label>
                    <input type="file" id="document" name="document" accept=".pdf,.png" required>
                </div>
                <button type="submit" class="btn btn-block">Upload</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
