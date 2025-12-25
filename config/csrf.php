<?php
function csrf_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
        @session_start();
    }
}

function csrf_token(): string
{
    csrf_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_request_token(): string
{
    if (!empty($_POST['csrf_token'])) {
        return (string) $_POST['csrf_token'];
    }
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    if (!empty($_GET['csrf_token'])) {
        return (string) $_GET['csrf_token'];
    }
    return '';
}

function csrf_validate(string $token): bool
{
    csrf_start();
    if ($token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_require(bool $requireLogin = true): void
{
    if ($requireLogin && empty($_SESSION['id'])) {
        return;
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS') {
        return;
    }
    $token = csrf_request_token();
    if (!csrf_validate($token)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'CSRF invalido.';
        exit();
    }
}
