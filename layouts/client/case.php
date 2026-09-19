<?php require_once '../../includes/auth.php';
require_role('client');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $pdo = db();
    $upload = null;
    if (!empty($_FILES['document']['name'])) {
        $upload = validate_and_store_upload($_FILES['document'], 'case_documents');
        if (!$upload['ok']) {
            $msg = $upload['error'];
        }
    }
    if (!$msg) {
        $s = $pdo->prepare('INSERT INTO cases(client_id,title,description,category,status) VALUES(?,?,?,?,"pending")');
        $s->execute([user()['user_id'], $_POST['title'], $_POST['description'], $_POST['category']]);
        $caseId = (int) $pdo->lastInsertId();

        if ($upload) {
            $file = $pdo->prepare(
                'INSERT INTO files (case_id, uploaded_by, category, original_name, stored_name, mime_type, file_size)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $file->execute([
                $caseId,
                user()['user_id'],
                'case_document',
                $upload['original_name'],
                $upload['stored_name'],
                $upload['mime'],
                $upload['size'],
            ]);
        }
        $msg = 'Case submitted.' . ($upload ? ' Document uploaded for the assigned lawyer.' : '');
    }
}
$title = 'Submit Case';
include '../../includes/header.php'; ?><div class="card">
    <h1>Submit case order</h1>
    <p><?= $msg ?></p>
    <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input name="title" placeholder="Case title" required><label for="category">Category</label><select name="category" id="category" required>
            <option value="">Select case category</option>
            <option value="criminal case">Criminal case</option>
            <option value="civil case">Civil case</option>
            <option value="family case">Family case</option>
            <option value="land and property case">Land and property case</option>
            <option value="employment case">Employment case</option>
            <option value="commercial case">Commercial case</option>
            <option value="others">Others</option>
        </select><textarea name="description" placeholder="Description" required></textarea><label for="document">Supporting document (optional, PDF or PNG, max 10MB)</label><input type="file" id="document" name="document" accept=".pdf,.png"><button>Submit</button></form>
</div><?php include '../../includes/footer.php';
