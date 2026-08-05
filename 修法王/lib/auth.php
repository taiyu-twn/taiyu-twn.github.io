<?php
require_once __DIR__ . '/../config/config.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function login(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['real_name']  = $user['real_name'];
    $_SESSION['has_signed'] = $user['has_signed'] === 'true' || $user['has_signed'] === '1';
    $_SESSION['id_hash']    = $user['id_number_hash'];
}

function logout(): void {
    startSecureSession();
    session_unset();
    session_destroy();
}

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['user_id']);
}

function requireLogin(string $redirect = '/修法王/public/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function isAdmin(): bool {
    startSecureSession();
    return !empty($_SESSION['is_admin']);
}

function adminLogin(string $username, string $password): bool {
    if ($username !== ADMIN_USERNAME) return false;
    if (!password_verify($password, ADMIN_PASSWORD)) return false;
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    return true;
}

function requireAdmin(): void {
    startSecureSession();
    if (!isAdmin()) {
        header('Location: /修法王/admin/login.php');
        exit;
    }
}

function currentUser(): array {
    startSecureSession();
    return [
        'user_id'    => $_SESSION['user_id']    ?? '',
        'real_name'  => $_SESSION['real_name']  ?? '',
        'has_signed' => $_SESSION['has_signed'] ?? false,
        'id_hash'    => $_SESSION['id_hash']    ?? '',
    ];
}

function updateSessionSigned(): void {
    startSecureSession();
    $_SESSION['has_signed'] = true;
}

// CSRF token
function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'msg' => 'CSRF 驗證失敗']));
    }
}
