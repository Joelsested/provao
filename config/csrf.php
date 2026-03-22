<?php
if (!function_exists('csrf_session_name')) {
    function csrf_session_name(): string
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $active = (string) session_name();
            if ($active !== '') {
                return $active;
            }
        }

        $name = function_exists('env') ? trim((string) env('SESSION_NAME', 'EJASESSID')) : 'EJASESSID';
        if ($name === '') {
            $name = 'EJASESSID';
        }
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        if ($name === '') {
            $name = 'EJASESSID';
        }

        // Compatibilidade: se existir sessao legada em PHPSESSID, reaproveita.
        if (!empty($_COOKIE[$name])) {
            return $name;
        }
        if (!empty($_COOKIE['PHPSESSID'])) {
            return 'PHPSESSID';
        }

        return $name;
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionName = csrf_session_name();
    @ini_set('session.name', $sessionName);
    @session_name($sessionName);
}

function csrf_cookie_domain(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = strtolower(trim(explode(':', (string)$host)[0] ?? ''));

    if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    if (function_exists('env')) {
        $envDomain = trim((string) env('SESSION_COOKIE_DOMAIN', ''));
        if ($envDomain !== '') {
            $envDomain = ltrim(strtolower($envDomain), '.');

            $hostMatches = ($host === $envDomain) || (substr($host, -1 - strlen($envDomain)) === ('.' . $envDomain));
            if ($hostMatches) {
                return '.' . $envDomain;
            }

            // Evita quebrar sessao quando o dominio do .env nao corresponde ao host atual.
            return '';
        }
    }

    $parts = explode('.', $host);
    if (count($parts) < 2) {
        return '';
    }

    return '.' . implode('.', array_slice($parts, -2));
}

function csrf_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $sessionName = csrf_session_name();
        @ini_set('session.name', $sessionName);
        @session_name($sessionName);

        if (!headers_sent()) {
            $cookieParams = session_get_cookie_params();
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) == 443;
            $domain = csrf_cookie_domain();

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $domain !== '' ? $domain : ($cookieParams['domain'] ?? ''),
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            $prevUseCookies = ini_get('session.use_cookies');
            $prevOnlyCookies = ini_get('session.use_only_cookies');
            if ($prevUseCookies !== false) {
                @ini_set('session.use_cookies', '0');
            }
            if ($prevOnlyCookies !== false) {
                @ini_set('session.use_only_cookies', '0');
            }
            $name = session_name();
            if ($name && !empty($_COOKIE[$name])) {
                @session_id($_COOKIE[$name]);
            }
            @session_start();
            if ($prevUseCookies !== false) {
                @ini_set('session.use_cookies', (string) $prevUseCookies);
            }
            if ($prevOnlyCookies !== false) {
                @ini_set('session.use_only_cookies', (string) $prevOnlyCookies);
            }
            return;
        }

        $name = session_name();
        if ($name && !empty($_COOKIE[$name])) {
            @session_id($_COOKIE[$name]);
        }
        @session_start();

        // Fallback: se a sessão abriu sem dados de login, tenta cookie legado.
        if (empty($_SESSION['id']) && empty($_SESSION['nivel'])) {
            $currentName = session_name();
            $alternateName = '';
            if ($currentName === 'PHPSESSID' && !empty($_COOKIE['EJASESSID'])) {
                $alternateName = 'EJASESSID';
            } elseif ($currentName !== 'PHPSESSID' && !empty($_COOKIE['PHPSESSID'])) {
                $alternateName = 'PHPSESSID';
            }

            if ($alternateName !== '') {
                @session_write_close();
                @session_name($alternateName);
                @session_id((string) $_COOKIE[$alternateName]);
                @session_start();
            }
        }
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
        return trim((string) $_POST['csrf_token']);
    }
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return trim((string) $_SERVER['HTTP_X_CSRF_TOKEN']);
    }
    if (!empty($_GET['csrf_token'])) {
        return trim((string) $_GET['csrf_token']);
    }
    return '';
}

function csrf_validate(string $token): bool
{
    csrf_start();
    $token = trim($token);
    $sessionToken = isset($_SESSION['csrf_token']) ? trim((string) $_SESSION['csrf_token']) : '';
    if ($token === '' || $sessionToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
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
        if (function_exists('env') && env('CSRF_DEBUG', '') === 'true') {
            $sessionToken = isset($_SESSION['csrf_token']) ? trim((string) $_SESSION['csrf_token']) : '';
            $sessionId = session_id();
            $cookie = $_SERVER['HTTP_COOKIE'] ?? '';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $user = $_SESSION['id'] ?? '';
            $line = "CSRF_INVALID host={$host} origin={$origin} referer={$referer} session_id={$sessionId} user={$user} token={$token} session_token={$sessionToken} cookie={$cookie}";
            error_log($line);
            $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'csrf_debug.log';
            @file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND);
        }
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'CSRF invalido.';
        exit();
    }
}
