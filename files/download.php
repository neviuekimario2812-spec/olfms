<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pdo = get_db();
$user = current_user();
$fileId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM files WHERE file_id = ?');
$stmt->execute([$fileId]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    die('File not found.');
}

// Authorization: law/constitutional documents are readable by any authenticated
// user; case documents require ownership, assignment, or manager/admin role.
$authorized = false;
if ($file['category'] === 'law_document') {
    $authorized = true;
} elseif ($file['case_id']) {
    $authorized = user_can_access_case($pdo, $user, (int) $file['case_id']);
} elseif ((int) $file['uploaded_by'] === (int) $user['user_id']) {
    $authorized = true;
}

if (!$authorized) {
    log_audit($user['user_id'], 'file_access_denied', 'failure', "File #$fileId");
    http_response_code(403);
    die('<h2>403 — Access denied</h2><p>You are not authorized to download this file.</p>');
}

$path = rtrim(UPLOAD_BASE_DIR, '/') . '/' . $file['stored_name'];
if (!is_file($path)) {
    http_response_code(404);
    die('File is missing from storage.');
}

log_audit($user['user_id'], 'file_download', 'success', $file['original_name']);

header('Content-Type: ' . $file['mime_type']);
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
