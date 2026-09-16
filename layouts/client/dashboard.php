<?php require_once '../../includes/auth.php';
require_role('client');
$title = 'Client Dashboard';
include '../../includes/header.php'; ?><h1>Client Dashboard</h1>
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
</div><?php include '../../includes/footer.php';
