<?php

require_once __DIR__ . '/../config/env.php';

if (!function_exists('gwCipherKey')) {
    function gwCipherKey(): string
    {
        $key = env('APP_KEY', 'sested-default-key-32chars!!');
        return substr(hash('sha256', $key, true), 0, 32);
    }
}

if (!function_exists('gwDecryptValue')) {
    function gwDecryptValue(?string $encoded): string
    {
        if ($encoded === null || $encoded === '') {
            return '';
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', gwCipherKey(), OPENSSL_RAW_DATA, $iv);

        return $plain === false ? '' : $plain;
    }
}

if (!function_exists('gwDecryptCompat')) {
    function gwDecryptCompat(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $dec = gwDecryptValue($value);
        if ($dec !== '') {
            return $dec;
        }

        // Compatibilidade: bancos antigos podem ter valor em texto puro.
        return $value;
    }
}

if (!function_exists('gwNormalizePath')) {
    function gwNormalizePath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:\\\\/', $path) || strpos($path, DIRECTORY_SEPARATOR) === 0) {
            return $path;
        }

        return __DIR__ . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('gwIsAbsoluteHttpUrl')) {
    function gwIsAbsoluteHttpUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }
        return (bool) preg_match('#^https?://.+#i', $url);
    }
}

if (!function_exists('gwTableHasColumn')) {
    function gwTableHasColumn(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
        $stmt->execute([':column' => $column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$envSandbox = filter_var(env('EFI_SANDBOX', 'false'), FILTER_VALIDATE_BOOLEAN);
$selectedEnv = $envSandbox ? 'sandbox' : 'producao';

$clientIdProd = env('EFI_CLIENT_ID_PROD', '');
$clientSecretProd = env('EFI_CLIENT_SECRET_PROD', '');
$pathCertificateProd = env('EFI_CERT_PATH_PROD', __DIR__ . '/producao-517293-SESTED-EJA_cert.pem');
$pwdCertificateProd = env('EFI_CERT_PWD_PROD', '');

$clientIdHomolog = env('EFI_CLIENT_ID_HOMOLOG', '');
$clientSecretHomolog = env('EFI_CLIENT_SECRET_HOMOLOG', '');
$pathCertificateHomolog = env('EFI_CERT_PATH_HOMOLOG', __DIR__ . '/homologacao-517293-SESTED-EJA-HOMO_cert.pem');
$pwdCertificateHomolog = env('EFI_CERT_PWD_HOMOLOG', '');

$pixKey = env('EFI_PIX_KEY', '');
$envNotificationUrl = trim((string) env('EFI_NOTIFICATION_URL', ''));
if (!gwIsAbsoluteHttpUrl($envNotificationUrl)) {
    $envNotificationUrl = '';
}

$fallbackByEnv = [
    'sandbox' => [
        'clientId' => $clientIdHomolog,
        'clientSecret' => $clientSecretHomolog,
        'certificate' => $pathCertificateHomolog,
        'pwdCertificate' => $pwdCertificateHomolog,
    ],
    'producao' => [
        'clientId' => $clientIdProd,
        'clientSecret' => $clientSecretProd,
        'certificate' => $pathCertificateProd,
        'pwdCertificate' => $pwdCertificateProd,
    ],
];

$dbConfig = null;

try {
    $dbHost = env('DB_HOST', 'localhost');
    $dbName = env('DB_NAME', 'eja');
    $dbUser = env('DB_USER', 'root');
    $dbPass = env('DB_PASS', '');

    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $hasProvider = gwTableHasColumn($pdo, 'gateways', 'provider');
    $hasAmbiente = gwTableHasColumn($pdo, 'gateways', 'ambiente');

    $row = null;

    if ($hasProvider && $hasAmbiente) {
        $selActive = $pdo->prepare("SELECT ambiente FROM gateways WHERE provider = 'efy' AND sandbox = 'Sim' ORDER BY updated_at DESC, id DESC LIMIT 1");
        $selActive->execute();
        $activeEnv = $selActive->fetchColumn();
        if ($activeEnv === 'sandbox' || $activeEnv === 'producao') {
            $selectedEnv = $activeEnv;
        }

        $selEnv = $pdo->prepare('SELECT * FROM gateways WHERE provider = :provider AND ambiente = :ambiente ORDER BY id DESC LIMIT 1');
        $selEnv->execute([':provider' => 'efy', ':ambiente' => $selectedEnv]);
        $row = $selEnv->fetch(PDO::FETCH_ASSOC) ?: null;
    } else {
        // Legado: sem provider/ambiente.
        $selLegacy = $pdo->query("SELECT * FROM gateways WHERE UPPER(COALESCE(nome, '')) IN ('EFY', 'EFI') ORDER BY id DESC LIMIT 1");
        $row = $selLegacy ? ($selLegacy->fetch(PDO::FETCH_ASSOC) ?: null) : null;
        if ($row) {
            $sandboxLegacy = strtolower(trim((string) ($row['sandbox'] ?? '')));
            $selectedEnv = in_array($sandboxLegacy, ['sim', '1', 'true', 'sandbox'], true) ? 'sandbox' : 'producao';
        }
    }

    if ($row) {
        $dbClientId = trim(gwDecryptCompat($row['chave_api'] ?? ''));
        $dbClientSecret = trim(gwDecryptCompat($row['chave_secreta'] ?? ''));
        $dbCertPath = gwNormalizePath($row['cert_path'] ?? '');
        $dbPwd = trim(gwDecryptCompat($row['cert_password'] ?? ''));
        $dbNotification = trim((string)($row['webhook_url'] ?? ''));
        if (!gwIsAbsoluteHttpUrl($dbNotification)) {
            $dbNotification = '';
        }

        $fallback = $fallbackByEnv[$selectedEnv] ?? $fallbackByEnv['sandbox'];

        $dbConfig = [
            'clientId' => $dbClientId !== '' ? $dbClientId : $fallback['clientId'],
            'clientSecret' => $dbClientSecret !== '' ? $dbClientSecret : $fallback['clientSecret'],
            'certificate' => $dbCertPath !== '' ? $dbCertPath : $fallback['certificate'],
            'pwdCertificate' => $dbPwd !== '' ? $dbPwd : $fallback['pwdCertificate'],
            'sandbox' => $selectedEnv === 'sandbox',
            'notificationUrl' => $dbNotification !== '' ? $dbNotification : $envNotificationUrl,
        ];
    }
} catch (Throwable $e) {
    $dbConfig = null;
}

$defaultEnv = $fallbackByEnv[$selectedEnv] ?? $fallbackByEnv['sandbox'];
$fallbackConfig = [
    'clientId' => $defaultEnv['clientId'],
    'clientSecret' => $defaultEnv['clientSecret'],
    'certificate' => $defaultEnv['certificate'],
    'pwdCertificate' => $defaultEnv['pwdCertificate'],
    'sandbox' => $selectedEnv === 'sandbox',
    'notificationUrl' => $envNotificationUrl,
];

$cfg = $dbConfig ?: $fallbackConfig;

// Sanidade de ambiente: evita usar credencial de producao com sandbox (e vice-versa).
$envHClientId = trim((string) env('EFI_CLIENT_ID_HOMOLOG', ''));
$envHClientSecret = trim((string) env('EFI_CLIENT_SECRET_HOMOLOG', ''));
$envPClientId = trim((string) env('EFI_CLIENT_ID_PROD', ''));
$envPClientSecret = trim((string) env('EFI_CLIENT_SECRET_PROD', ''));

$cfgClientId = trim((string) ($cfg['clientId'] ?? ''));
$cfgClientSecret = trim((string) ($cfg['clientSecret'] ?? ''));
$cfgSandbox = (bool) ($cfg['sandbox'] ?? false);

if (
    $cfgSandbox
    && $envHClientId !== ''
    && $envHClientSecret !== ''
    && $cfgClientId === $envPClientId
    && $cfgClientSecret === $envPClientSecret
) {
    $cfg['clientId'] = $envHClientId;
    $cfg['clientSecret'] = $envHClientSecret;
}

if (
    !$cfgSandbox
    && $envPClientId !== ''
    && $envPClientSecret !== ''
    && $cfgClientId === $envHClientId
    && $cfgClientSecret === $envHClientSecret
) {
    $cfg['clientId'] = $envPClientId;
    $cfg['clientSecret'] = $envPClientSecret;
}

return [
    'clientId' => (string) ($cfg['clientId'] ?? ''),
    'clientSecret' => (string) ($cfg['clientSecret'] ?? ''),
    'certificate' => (string) ($cfg['certificate'] ?? ''),
    'pwdCertificate' => (string) ($cfg['pwdCertificate'] ?? ''),
    'pixKey' => $pixKey,
    'sandbox' => (bool) ($cfg['sandbox'] ?? false),
    'debug' => false,
    'timeout' => 30,
    'responseHeaders' => true,
    'notificationUrl' => gwIsAbsoluteHttpUrl($cfg['notificationUrl'] ?? '') ? (string) $cfg['notificationUrl'] : '',
];
