<?php

function app_config(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }

    return $config[$key] ?? $default;
}

function base_url(string $path = ''): string
{
    $base = app_config('base_url');
    
    // Auto detect base URL jika config kosong
    if (empty($base)) {
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        $cfVisitor = $_SERVER['HTTP_CF_VISITOR'] ?? '';
        $https = $_SERVER['HTTPS'] ?? '';

        if ($forwardedProto !== '') {
            $protocol = strtolower(explode(',', $forwardedProto)[0]) === 'https' ? 'https' : 'http';
        } elseif (stripos($cfVisitor, '"scheme":"https"') !== false) {
            $protocol = 'https';
        } else {
            $protocol = ($https === 'on' || $https === '1') ? 'https' : 'http';
        }

        $host = $forwardedHost !== '' ? trim(explode(',', $forwardedHost)[0]) : ($_SERVER['HTTP_HOST'] ?? 'localhost');
        
        // Cari posisi script di docroot
        $script = dirname($_SERVER['SCRIPT_NAME']);
        if ($script === '/' || $script === '\\') {
            $script = '';
        }
        
        $base = $protocol . '://' . $host . $script;
    }

    $base = rtrim($base, '/');
    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
}

function site_url(string $path = ''): string
{
    return base_url($path);
}

function asset_url(string $path): string
{
    return base_url('public/assets/' . ltrim($path, '/'));
}

function redirect_to(string $path): never
{
    header('Location: ' . site_url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_datetime_id(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }

    return date('d M Y H:i', strtotime($datetime));
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['_csrf_token'] ?? '', (string) $token)) {
        http_response_code(419);
        exit('CSRF token tidak valid.');
    }
}

function auth_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function is_authenticated(): bool
{
    return auth_user() !== null;
}

function require_auth(): void
{
    if (!is_authenticated()) {
        redirect_to('login');
    }
}
