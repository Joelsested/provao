<?php
require_once __DIR__ . '/env.php';

function webhook_get_token(): string
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($authHeader !== '' && preg_match('/Bearer\\s+(.*)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }

    $headerToken = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '';
    if ($headerToken !== '') {
        return trim($headerToken);
    }

    $fallback = $_GET['token'] ?? '';
    return trim((string)$fallback);
}

function webhook_require_token(): void
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $isHml = stripos($uri, '/hml/') !== false;
    $suffix = $isHml ? 'HML' : 'PROD';

    $candidates = [
        'WEBHOOK_TOKEN_EJA_' . $suffix,
        'WEBHOOK_TOKEN_EJA',
        'WEBHOOK_TOKEN',
    ];

    $expected = '';
    foreach ($candidates as $key) {
        $value = env($key, '');
        if (trim((string) $value) !== '') {
            $expected = trim((string) $value);
            break;
        }
    }

    if ($expected === '') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Webhook token nao configurado.']);
        exit();
    }

    $provided = webhook_get_token();
    if ($provided === '' || !hash_equals($expected, $provided)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Nao autorizado.']);
        exit();
    }
}
