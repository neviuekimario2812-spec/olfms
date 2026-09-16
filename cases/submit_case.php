<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['client']);

$user = current_user();
$pdo = get_db();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session token. Please resubmit the form.';
    } else {
        $title = clean($_POST['title'] ?? '');
        $category = clean($_POST['category'] ?? '');
        $description = clean($_POST['description'] ?? '');

        if ($title === '' || $category === '' || $description === '') {
            $errors[] = 'Title, category and description are all required.';
        }

        if (!$errors) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO cases (client_id, title, description, category, status) VALUES (?,?,?,?,\'pending\')'
            );
            $stmt->execute([$user['user_id'], $title, $description, $category]);
            $caseId = (int) $pdo->lastInsertId();

            // Optional evidence file (PDF only per functional requirement)
            if (!empty($_FILES['evidence']['name'])) {
                $result = validate_and_store_upload($_FILES['evidence'], 'case_documents');
                if (!$result['ok']) {
                    $errors[] = $result['error'];
                } else {
                    $fileStmt = $pdo->prepare(
                        'INSERT INTO files (case_id, uploaded_by, category, original_name, stored_name, mime_type, file_size)
                         VALUES (?,?,\'case_document\',?,?,?,?)'
                    );
                    $fileStmt->execute([
                        $caseId, $user['user_id'], $result['original_name'],
                        $result['stored_name'], $result['mime'], $result['size'],
                    ]);
                }
            }

            if ($errors) {
                $pdo->rollBack();
            } else {
                $pdo->commit();
                log_audit($user['user_id'], 'case_submit', 'success', "Case #$caseId");
                $success = true;
            }
        }
    }
}

$pageTitle = 'Submit a Case';
$pageCss = '/cases/css/cases.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container form-narrow">
    <div class="card">
        <h1>Submit a new case</h1>
        <p class="muted">Describe your legal matter. A manager will review it and assign a lawyer.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">Your case was submitted successfully. <a href="/cases/my_cases.php">View my cases</a></div>
        <?php else: ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?php echo e($err); ?></div><?php endforeach; ?>
            <form method="post" enctype="multipart/form-data" novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="title">Case title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select…</option>
                        <option>Family Law</option>
                        <option>Criminal Law</option>
                        <option>Property & Land</option>
                        <option>Employment</option>
                        <option>Corporate & Business</option>
                        <option>Contract Dispute</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="evidence">Supporting document (PDF, max 10MB) — optional</label>
                    <input type="file" id="evidence" name="evidence" accept=".pdf">
                </div>
                <button type="submit" class="btn btn-block">Submit case</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
