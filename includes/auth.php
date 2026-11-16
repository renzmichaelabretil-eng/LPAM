<?php
session_start();
function current_admin(): ?array  {
    return $_SESSION['admin'] ?? null;
}
function is_admin(): bool  {
    return current_admin() !== null;
}
function require_admin(bool $allowPasswordChange = false): void  {
    if (!is_admin())  {
        header('Location: ../login.php');
        exit;
    }
    if (!$allowPasswordChange && (int)(current_admin()['must_change_password'] ?? 0) === 1) {
        header('Location: change_password.php');
        exit;
    }
}
function is_super_admin(): bool {
    return (current_admin()['role'] ?? null) === 'super_admin';
}
function has_role(string ...$roles): bool {
    return in_array(current_admin()['role'] ?? '', $roles, true);
}
function is_sports_admin(): bool {
    return has_role('super_admin', 'sport_admin');
}
function is_area_coordinator(): bool {
    return has_role('area_coordinator');
}
function is_technical_official(): bool {
    return has_role('technical_official');
}
function require_role(string ...$roles): void {
    require_admin();
    if (!has_role(...$roles)) {
        http_response_code(403);
        exit('You do not have access to this part of the meet portal.');
    }
}
function require_super_admin(): void {
    require_admin();
    if (!is_super_admin()) {
        http_response_code(403);
        exit('Meet Administrator access is required.');
    }
}
function flash(string $key, ?string $value = null): ?string  {
    if ($value !== null)  {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
function require_csrf(): void {
    $token=$_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Your form session expired. Please return to the page and try again.');
    }
}
