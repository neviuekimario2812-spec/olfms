<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
// Accessible to everyone, logged in or not — functional requirement.

$pageTitle = 'User Guide';
$pageCss = BASE_URL . '/guide/css/guide.css';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h1>System User Guide</h1>
    <p class="muted">A quick walkthrough of how each role uses the Online Law Firm Management System.</p>

    <div class="guide-nav mt-2">
        <a href="#clients">Clients</a>
        <a href="#lawyers">Lawyers</a>
        <a href="#managers">Managers</a>
        <a href="#security">Security &amp; Privacy</a>
        <a href="#faq">FAQ</a>
    </div>

    <div class="card mt-3" id="clients">
        <h2>For Clients</h2>
        <ol>
            <li>Register an account with your full name, email, password, location, contact and gender.</li>
            <li>Log in, then go to <strong>New Case</strong> to describe your legal matter and optionally attach a supporting PDF.</li>
            <li>Use <strong>Book Appointment</strong> to request a meeting with a lawyer, with a date, time and purpose.</li>
            <li>Track progress under <strong>My Cases</strong> — status moves from Pending &rarr; Assigned &rarr; In Progress &rarr; Closed.</li>
            <li>Download any files attached to your own cases from the case detail page.</li>
        </ol>
    </div>

    <div class="card mt-3" id="lawyers">
        <h2>For Lawyers</h2>
        <ol>
            <li>Log in to see cases assigned to you on your dashboard.</li>
            <li>Review appointment requests and Accept, Decline, or mark them Completed.</li>
            <li>Open an assigned case to view client details, update its status, and upload or download related documents.</li>
            <li>Upload law and constitutional reference documents (PDF/PNG) available to all authenticated users.</li>
        </ol>
    </div>

    <div class="card mt-3" id="managers">
        <h2>For Managers</h2>
        <ol>
            <li>Review newly submitted case orders and requested appointments.</li>
            <li>Assign an available lawyer to each case from <strong>Manage Cases</strong>.</li>
            <li>Register new lawyer accounts under <strong>Register Lawyer</strong>.</li>
        </ol>
    </div>

    <div class="card mt-3" id="security">
        <h2>Security &amp; Privacy</h2>
        <ul>
            <li>Passwords must be at least 8 characters with upper case, lower case, a number and a special character.</li>
            <li>Accounts lock automatically after 3 consecutive failed login attempts.</li>
            <li>Password reset links are single-use and expire after 30 seconds.</li>
            <li>Uploaded files are limited to PDF/PNG and 10MB, and access is restricted to authorized users only (owners, assigned lawyers, managers, and admins).</li>
            <li>All login attempts and sensitive access events are recorded in the security audit log.</li>
        </ul>
    </div>

    <div class="card mt-3" id="faq">
        <h2>Frequently Asked Questions</h2>
        <p><strong>I forgot my password — what do I do?</strong><br>
        Use "Forgot password" on the login page. You'll receive a link valid for 30 seconds — use it immediately.</p>
        <p><strong>Why was I locked out?</strong><br>
        After 3 failed login attempts, the account is temporarily locked as a security measure. Try again later or reset your password.</p>
        <p><strong>What file types can I upload?</strong><br>
        Only PDF and PNG files, up to 10MB each.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
