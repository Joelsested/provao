<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ob_start();
require_once __DIR__ . '/../../conexao.php';
@session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Administrador') {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => false, 'msg' => 'Acesso negado.']);
    exit;
}

function gatewayTableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
    $stmt->execute([':column' => $column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function ensureGatewaySchema(PDO $pdo): void
{
    $requiredColumns = [
        'provider' => "ALTER TABLE gateways ADD COLUMN provider VARCHAR(30) NULL DEFAULT NULL",
        'ambiente' => "ALTER TABLE gateways ADD COLUMN ambiente VARCHAR(20) NULL DEFAULT NULL",
        'cert_path' => "ALTER TABLE gateways ADD COLUMN cert_path VARCHAR(255) NULL DEFAULT NULL",
        'cert_password' => "ALTER TABLE gateways ADD COLUMN cert_password TEXT NULL",
        'updated_at' => "ALTER TABLE gateways ADD COLUMN updated_at DATETIME NULL",
        'updated_by' => "ALTER TABLE gateways ADD COLUMN updated_by INT NULL",
    ];

    foreach ($requiredColumns as $column => $sql) {
        if (!gatewayTableHasColumn($pdo, 'gateways', $column)) {
            $pdo->exec($sql);
        }
    }

    $pdo->exec("UPDATE gateways SET provider = 'efy' WHERE (provider IS NULL OR provider = '') AND UPPER(COALESCE(nome, '')) IN ('EFY', 'EFI')");
    $pdo->exec("UPDATE gateways SET ambiente = CASE WHEN LOWER(COALESCE(sandbox, '')) IN ('sim', '1', 'true', 'sandbox') THEN 'sandbox' ELSE 'producao' END WHERE provider = 'efy' AND (ambiente IS NULL OR ambiente = '')");
}

ensureGatewaySchema($pdo);


function cipherKey(): string
{
    $key = env('APP_KEY', 'sested-default-key-32chars!!');
    return substr(hash('sha256', $key, true), 0, 32);
}

function encryptValue(?string $plain): ?string
{
    if ($plain === null || $plain === '') {
        return null;
    }
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', cipherKey(), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        return null;
    }
    return base64_encode($iv . $cipher);
}

function decryptValue(?string $encoded): string
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
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', cipherKey(), OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}

function ensureGatewayEnv(PDO $pdo, string $ambiente): array
{
    $sel = $pdo->prepare('SELECT * FROM gateways WHERE provider = :provider AND ambiente = :ambiente LIMIT 1');
    $sel->execute([':provider' => 'efy', ':ambiente' => $ambiente]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return $row;
    }

    $ins = $pdo->prepare("INSERT INTO gateways (nome, provider, ambiente, chave_api, chave_secreta, webhook_url, webhook_path, ativo, sandbox, data_cadastro) VALUES ('EFY', 'efy', :ambiente, '', '', '', '', 'Sim', 'Nao', NOW())");
    $ins->execute([':ambiente' => $ambiente]);

    $sel->execute([':provider' => 'efy', ':ambiente' => $ambiente]);
    return (array) $sel->fetch(PDO::FETCH_ASSOC);
}

function isValidWebhookUrlForEfy(?string $url): bool
{
    $url = trim((string)$url);
    if ($url === '') {
        return true;
    }

    // Em ambiente local permitimos caminho relativo (ex: /efi/webhook.php).
    if (strpos($url, '/') === 0 && !preg_match('#^https?://#i', $url)) {
        return true;
    }

    if (!preg_match('#^https?://.+#i', $url)) {
        return false;
    }

    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
        return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (!filter_var($host, FILTER_VALIDATE_IP, $flags)) {
            return false;
        }
    }

    return true;
}
function saveCertUpload(string $fileKey, string $ambiente, ?string $oldPath = null): ?array
{
    if (empty($_FILES[$fileKey]['name'])) {
        return null;
    }

    $upload = $_FILES[$fileKey];
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload do certificado de ' . $ambiente . '.');
    }

    $ext = strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION));
    if ($ext !== 'p12') {
        throw new RuntimeException('Certificado de ' . $ambiente . ' invalido. Envie um arquivo .p12.');
    }

    if (($upload['size'] ?? 0) > (5 * 1024 * 1024)) {
        throw new RuntimeException('Arquivo de ' . $ambiente . ' maior que 5MB.');
    }

    $destDir = __DIR__ . '/../../../storage/certs';
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        throw new RuntimeException('Nao foi possivel criar diretorio de certificados.');
    }

    $filename = 'efy-' . $ambiente . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.p12';
    $destPath = $destDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file((string)$upload['tmp_name'], $destPath)) {
        throw new RuntimeException('Nao foi possivel salvar o certificado de ' . $ambiente . '.');
    }

    // Se um novo certificado foi salvo, remove o anterior para manter apenas o mais atual.
    if (!empty($oldPath) && $oldPath !== $destPath) {
        $oldReal = realpath($oldPath);
        $baseReal = realpath($destDir);
        if ($oldReal && $baseReal && strpos($oldReal, $baseReal) === 0 && file_exists($oldReal)) {
            @unlink($oldReal);
        }
    }

    return ['path' => $destPath, 'name' => $filename];
}

try {
    $ambienteSelecionado = ($_POST['ambiente'] ?? 'sandbox') === 'producao' ? 'producao' : 'sandbox';

    $gwSandbox = ensureGatewayEnv($pdo, 'sandbox');
    $gwProducao = ensureGatewayEnv($pdo, 'producao');
    $gwSelecionado = $ambienteSelecionado === 'sandbox' ? $gwSandbox : $gwProducao;

    $clientId = trim((string)($_POST['chave_api'] ?? ''));
    $clientSecret = trim((string)($_POST['chave_secreta'] ?? ''));
    $webhookUrl = trim((string)($_POST['webhook_url'] ?? ''));
    $webhookPath = trim((string)($_POST['webhook_path'] ?? ''));

    // Se o usuario informou caminho relativo no campo URL, guardamos em webhook_path.
    if ($webhookUrl !== '' && strpos($webhookUrl, '/') === 0 && !preg_match('#^https?://#i', $webhookUrl)) {
        if ($webhookPath === '') {
            $webhookPath = $webhookUrl;
        }
        $webhookUrl = '';
    }

    if (!isValidWebhookUrlForEfy($webhookUrl)) {
        throw new RuntimeException('Webhook URL invalida. Use URL publica com http/https (sem localhost).');
    }

    $passSandboxRaw = trim((string)($_POST['cert_password_sandbox'] ?? ''));
    $passProducaoRaw = trim((string)($_POST['cert_password_producao'] ?? ''));

    $apiToStore = $clientId === '' ? ($gwSelecionado['chave_api'] ?? null) : encryptValue($clientId);
    $secretToStore = $clientSecret === '' ? ($gwSelecionado['chave_secreta'] ?? null) : encryptValue($clientSecret);

    $passSandbox = $passSandboxRaw === '' ? ($gwSandbox['cert_password'] ?? null) : encryptValue($passSandboxRaw);
    $passProducao = $passProducaoRaw === '' ? ($gwProducao['cert_password'] ?? null) : encryptValue($passProducaoRaw);

    $certSandboxPath = $gwSandbox['cert_path'] ?? null;
    $certProducaoPath = $gwProducao['cert_path'] ?? null;

    $upSandbox = saveCertUpload('cert_p12_sandbox', 'sandbox', $certSandboxPath);
    if ($upSandbox) {
        $certSandboxPath = $upSandbox['path'];
    }

    $upProducao = saveCertUpload('cert_p12_producao', 'producao', $certProducaoPath);
    if ($upProducao) {
        $certProducaoPath = $upProducao['path'];
    }

    $pdo->beginTransaction();

    $upSel = $pdo->prepare('UPDATE gateways SET chave_api = :api, chave_secreta = :secret, webhook_url = :webhook_url, webhook_path = :webhook_path, ativo = :ativo, updated_at = NOW(), updated_by = :uid WHERE id = :id LIMIT 1');
    $upSel->execute([
        ':api' => $apiToStore,
        ':secret' => $secretToStore,
        ':webhook_url' => $webhookUrl,
        ':webhook_path' => $webhookPath,
        ':ativo' => 'Sim',
        ':uid' => (int)($_SESSION['id'] ?? 0),
        ':id' => (int)$gwSelecionado['id'],
    ]);

    $upSandboxStmt = $pdo->prepare('UPDATE gateways SET cert_path = :cert_path, cert_password = :cert_password, updated_at = NOW(), updated_by = :uid WHERE id = :id LIMIT 1');
    $upSandboxStmt->execute([
        ':cert_path' => $certSandboxPath,
        ':cert_password' => $passSandbox,
        ':uid' => (int)($_SESSION['id'] ?? 0),
        ':id' => (int)$gwSandbox['id'],
    ]);

    $upProdStmt = $pdo->prepare('UPDATE gateways SET cert_path = :cert_path, cert_password = :cert_password, updated_at = NOW(), updated_by = :uid WHERE id = :id LIMIT 1');
    $upProdStmt->execute([
        ':cert_path' => $certProducaoPath,
        ':cert_password' => $passProducao,
        ':uid' => (int)($_SESSION['id'] ?? 0),
        ':id' => (int)$gwProducao['id'],
    ]);

    $pdo->prepare("UPDATE gateways SET sandbox = 'Nao' WHERE provider = 'efy'")->execute();
    $pdo->prepare("UPDATE gateways SET sandbox = 'Sim' WHERE provider = 'efy' AND ambiente = :ambiente")->execute([
        ':ambiente' => $ambienteSelecionado,
    ]);

    $pdo->commit();

    $reload = $pdo->prepare("SELECT * FROM gateways WHERE provider = 'efy' AND ambiente IN ('sandbox','producao') ORDER BY ambiente");
    $reload->execute();
    $rows = $reload->fetchAll(PDO::FETCH_ASSOC);

    $map = [
        'sandbox' => ['client_id' => '', 'client_secret' => '', 'webhook_url' => '', 'webhook_path' => '', 'cert_password' => '', 'cert_name' => 'nenhum'],
        'producao' => ['client_id' => '', 'client_secret' => '', 'webhook_url' => '', 'webhook_path' => '', 'cert_password' => '', 'cert_name' => 'nenhum'],
    ];

    foreach ($rows as $row) {
        $amb = $row['ambiente'];
        if (!isset($map[$amb])) {
            continue;
        }
        $map[$amb] = [
            'client_id' => decryptValue($row['chave_api'] ?? ''),
            'client_secret' => decryptValue($row['chave_secreta'] ?? ''),
            'webhook_url' => (string)($row['webhook_url'] ?? ''),
            'webhook_path' => (string)($row['webhook_path'] ?? ''),
            'cert_password' => decryptValue($row['cert_password'] ?? ''),
            'cert_name' => !empty($row['cert_path']) ? basename((string)$row['cert_path']) : 'nenhum',
        ];
    }

    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode([
        'ok' => true,
        'msg' => 'Configurações salvas.',
        'ambiente_ativo' => $ambienteSelecionado,
        'env_data' => $map,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}







