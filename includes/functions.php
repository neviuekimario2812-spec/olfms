<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Shared backend helpers used by every layout module.
 * Include this AFTER config/db.php.
 */

// ---- Secure session bootstrap ----------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),   // only over HTTPS when available
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// -----------------------------------------------------------------------
// Generic helpers
// -----------------------------------------------------------------------
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function clean(string $value): string
{
    return trim(strip_tags($value));
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// -----------------------------------------------------------------------
// CSRF protection
// -----------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// -----------------------------------------------------------------------
// Authentication / RBAC
// -----------------------------------------------------------------------
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/** Redirects to login if not authenticated. Call at the top of protected pages. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

/** Restricts a page to one or more roles: require_role(['admin','manager']) */
function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        die('<h2>403 — Access denied</h2><p>Your role does not permit this action.</p>');
    }
}

// -----------------------------------------------------------------------
// Auditing (login attempts + sensitive access) — non-functional requirement
// -----------------------------------------------------------------------
function log_audit(?int $userId, string $action, string $status, string $description = ''): void
{
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'INSERT INTO audit_log (user_id, action, description, ip_address, status) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([$userId, $action, $description, client_ip(), $status]);
}

// -----------------------------------------------------------------------
// Password policy: min 8 chars, upper, lower, number, special character
// -----------------------------------------------------------------------
function password_meets_policy(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

// -----------------------------------------------------------------------
// Account lockout helpers (3 failed attempts)
// -----------------------------------------------------------------------
function is_account_locked(array $user): bool
{
    return !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
}

function register_failed_login(PDO $pdo, array $user): void
{
    $attempts = (int) $user['failed_attempts'] + 1;
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $stmt = $pdo->prepare(
            'UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?'
        );
        $stmt->execute([$attempts, $user['user_id']]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE user_id = ?');
        $stmt->execute([$attempts, $user['user_id']]);
    }
}

function reset_failed_login(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?');
    $stmt->execute([$userId]);
}

// -----------------------------------------------------------------------
// File upload validation — PDF/PNG only, <=10MB, real content-type check,
// random server-generated storage name (non-functional requirements)
// -----------------------------------------------------------------------
function validate_and_store_upload(array $file, string $subDir): array
{
    // ['ok' => bool, 'error' => string, 'stored_name'=>, 'original_name'=>, 'mime'=>, 'size'=>]
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'File exceeds the 10MB maximum size.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return ['ok' => false, 'error' => 'Only PDF and PNG files are allowed.'];
    }

    // Verify the ACTUAL content type, not just the extension the client sent.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME_TYPES, true)) {
        return ['ok' => false, 'error' => 'File content does not match an allowed PDF/PNG type.'];
    }

    $targetDir = rtrim(UPLOAD_BASE_DIR, '/') . '/' . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0750, true);
    }

    // Server-generated random storage key — never trust the client filename.
    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $targetDir . '/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded file.'];
    }

    return [
        'ok' => true,
        'stored_name'   => $subDir . '/' . $storedName,
        'original_name' => basename($file['name']),
        'mime'          => $mime,
        'size'          => $file['size'],
    ];
}

// -----------------------------------------------------------------------
// Ownership / assignment authorization checks used before serving a case or file
// -----------------------------------------------------------------------
function user_can_access_case(PDO $pdo, array $user, int $caseId): bool
{
    if (in_array($user['role'], ['admin', 'manager'], true)) {
        return true;
    }
    $stmt = $pdo->prepare('SELECT client_id, lawyer_id FROM cases WHERE case_id = ?');
    $stmt->execute([$caseId]);
    $case = $stmt->fetch();
    if (!$case) {
        return false;
    }
    if ($user['role'] === 'client') {
        return (int) $case['client_id'] === (int) $user['user_id'];
    }
    if ($user['role'] === 'lawyer') {
        return (int) $case['lawyer_id'] === (int) $user['user_id'];
    }
    return false;
}
