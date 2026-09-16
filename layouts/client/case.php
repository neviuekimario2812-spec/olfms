<?php require_once '../../includes/auth.php';
require_role('client');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $s = db()->prepare('INSERT INTO cases(client_id,title,description,category,status) VALUES(?,?,?,?,"pending")');
    $s->execute([user()['id'], $_POST['title'], $_POST['description'], $_POST['category']]);
    $msg = 'Case submitted.';
}
$title = 'Submit Case';
include '../../includes/header.php'; ?><div class="card">
    <h1>Submit case order</h1>
    <p><?= $msg ?></p>
    <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input name="title" placeholder="Case title" required><input name="category" placeholder="Category" required><textarea name="description" placeholder="Description" required></textarea><button>Submit</button></form>
</div><?php include '../../includes/footer.php';
