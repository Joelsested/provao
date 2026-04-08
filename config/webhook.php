<?php
require_once __DIR__ . '/env.php';

function webhook_has_notification_hint(): bool
{
    foreach (['notification', 'notification_token'] as $k) {
        if (!empty($_POST[$k]) || !empty($_GET[$k])) {
            return true;
        }
    }

    $headerNotification = trim((string) ($_SERVER['HTTP_X_NOTIFICATION_TOKEN'] ?? ''));
    return $headerNotification !== '';
}

function webhook_log_auth_failure(bool $tokenProvided, string $uri): void
{
    try {
        $logFile = dirname(__DIR__) . '/logs/webhook_auth.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $line = sprintf(
            "[%s] denied ip=%s method=%s uri=%s token_provided=%s has_notification_hint=%s ua=%s\n",
            date('Y-m-d H:i:s'),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
            $uri,
            $tokenProvided ? '1' : '0',
            webhook_has_notification_hint() ? '1' : '0',
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );

        @file_put_contents($logFile, $line, FILE_APPEND);
    } catch (Throwable $e) {
        // Nao interrompe o fluxo por falha de log.
    }
}

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
    if ($fallback !== '') {
        return trim((string) $fallback);
    }

    $postToken = $_POST['token'] ?? '';
    if ($postToken !== '') {
        return trim((string) $postToken);
    }

    return '';
}

function webhook_require_token(array $options = []): void
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $isHml = stripos($uri, '/hml/') !== false;
    $suffix = $isHml ? 'HML' : 'PROD';
    $allowNotificationFallback = !empty($options['allow_notification_fallback']);

    $candidates = [
        'WEBHOOK_TOKEN_EJA_' . $suffix,
        'WEBHOOK_TOKEN_EJA',
        'WEBHOOK_TOKEN',
        // compatibilidade com nomes legados por endpoint/tipo
        'WEBHOOK_TOKEN_BOLETO_' . $suffix,
        'WEBHOOK_TOKEN_BOLETO',
        'WEBHOOK_TOKEN_BOLETO_PARCELADO_' . $suffix,
        'WEBHOOK_TOKEN_BOLETO_PARCELADO',
        'WEBHOOK_TOKEN_PIX_' . $suffix,
        'WEBHOOK_TOKEN_PIX',
    ];

    $expectedTokens = [];
    foreach ($candidates as $key) {
        $value = trim((string) env($key, ''));
        if ($value !== '' && !in_array($value, $expectedTokens, true)) {
            $expectedTokens[] = $value;
        }
    }

    if (empty($expectedTokens)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Webhook token nao configurado.']);
        exit();
    }

    $provided = trim((string) webhook_get_token());
    $tokenProvided = ($provided !== '');
    $tokenValido = false;

    foreach ($expectedTokens as $expected) {
        if (hash_equals($expected, $provided)) {
            $tokenValido = true;
            break;
        }
    }

    if ($allowNotificationFallback && webhook_has_notification_hint()) {
        return;
    }

    if (!$tokenProvided || !$tokenValido) {
        webhook_log_auth_failure($tokenProvided, $uri);
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Nao autorizado.']);
        exit();
    }
}
