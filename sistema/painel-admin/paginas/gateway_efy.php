<?php
require_once __DIR__ . '/../../conexao.php';
@session_start();

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Administrador') {
    echo 'Acesso negado.';
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

    // Compatibilidade com bancos antigos: marca registros EFY/EFI sem provider.
    $pdo->exec("UPDATE gateways SET provider = 'efy' WHERE (provider IS NULL OR provider = '') AND UPPER(COALESCE(nome, '')) IN ('EFY', 'EFI')");
    $pdo->exec("UPDATE gateways SET ambiente = CASE WHEN LOWER(COALESCE(sandbox, '')) IN ('sim', '1', 'true', 'sandbox') THEN 'sandbox' ELSE 'producao' END WHERE provider = 'efy' AND (ambiente IS NULL OR ambiente = '')");
}
function cipherKey(): string
{
    $key = env('APP_KEY', 'sested-default-key-32chars!!');
    return substr(hash('sha256', $key, true), 0, 32);
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
    $stmt = $pdo->prepare('SELECT * FROM gateways WHERE provider = :provider AND ambiente = :ambiente LIMIT 1');
    $stmt->execute([':provider' => 'efy', ':ambiente' => $ambiente]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return $row;
    }

    $insert = $pdo->prepare("INSERT INTO gateways (nome, provider, ambiente, chave_api, chave_secreta, webhook_url, webhook_path, ativo, sandbox, data_cadastro) VALUES ('EFY', 'efy', :ambiente, '', '', '', '', 'Sim', 'Nao', NOW())");
    $insert->execute([':ambiente' => $ambiente]);

    $stmt->execute([':provider' => 'efy', ':ambiente' => $ambiente]);
    return (array) $stmt->fetch(PDO::FETCH_ASSOC);
}

ensureGatewaySchema($pdo);

$gatewaySandbox = ensureGatewayEnv($pdo, 'sandbox');
$gatewayProducao = ensureGatewayEnv($pdo, 'producao');

$stmtSel = $pdo->prepare("SELECT ambiente FROM gateways WHERE provider = 'efy' AND sandbox = 'Sim' ORDER BY updated_at DESC, id DESC LIMIT 1");
$stmtSel->execute();
$ambienteAtual = $stmtSel->fetchColumn();
if ($ambienteAtual !== 'sandbox' && $ambienteAtual !== 'producao') {
    $ambienteAtual = 'sandbox';
}

$envMap = [
    'sandbox' => [
        'client_id' => decryptValue($gatewaySandbox['chave_api'] ?? ''),
        'client_secret' => decryptValue($gatewaySandbox['chave_secreta'] ?? ''),
        'webhook_url' => (string)($gatewaySandbox['webhook_url'] ?? ''),
        'webhook_path' => (string)($gatewaySandbox['webhook_path'] ?? ''),
        'cert_password' => decryptValue($gatewaySandbox['cert_password'] ?? ''),
        'cert_name' => !empty($gatewaySandbox['cert_path']) ? basename((string)$gatewaySandbox['cert_path']) : 'nenhum',
    ],
    'producao' => [
        'client_id' => decryptValue($gatewayProducao['chave_api'] ?? ''),
        'client_secret' => decryptValue($gatewayProducao['chave_secreta'] ?? ''),
        'webhook_url' => (string)($gatewayProducao['webhook_url'] ?? ''),
        'webhook_path' => (string)($gatewayProducao['webhook_path'] ?? ''),
        'cert_password' => decryptValue($gatewayProducao['cert_password'] ?? ''),
        'cert_name' => !empty($gatewayProducao['cert_path']) ? basename((string)$gatewayProducao['cert_path']) : 'nenhum',
    ],
];

$current = $envMap[$ambienteAtual];
?>

<h3>Gateway de Pagamentos (EFY)</h3>
<p class="text-muted">Configure credenciais por ambiente. O ambiente selecionado sera usado no runtime.</p>
<div id="gateway-efy-alert"></div>

<div id="gateway-efy-form">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>Ambiente ativo</label>
                <select name="ambiente" class="form-control">
                    <option value="sandbox" <?php echo $ambienteAtual === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Homologacao)</option>
                    <option value="producao" <?php echo $ambienteAtual === 'producao' ? 'selected' : ''; ?>>Producao</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Client ID (ambiente selecionado)</label>
                <input type="text" name="chave_api" class="form-control" value="<?php echo htmlspecialchars($current['client_id'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
        <div class="col-md-5">
            <div class="form-group">
                <label>Client Secret (ambiente selecionado)</label>
                <input type="text" name="chave_secreta" class="form-control" value="<?php echo htmlspecialchars($current['client_secret'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Webhook URL (ambiente selecionado)</label>
                <input type="text" name="webhook_url" class="form-control" placeholder="https://seu-dominio.com/efi_webhook_boleto_parcelado.php" value="<?php echo htmlspecialchars($current['webhook_url'], ENT_QUOTES, 'UTF-8'); ?>">
                <small class="text-muted">Use URL publica (http/https). Em localhost, deixe em branco e use o Webhook Path.</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Webhook Path interno (ambiente selecionado)</label>
                <input type="text" name="webhook_path" class="form-control" value="<?php echo htmlspecialchars($current['webhook_path'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info" style="margin-bottom:10px;">
                Certificado do ambiente selecionado: <strong id="cert-atual-label"><?php echo htmlspecialchars($current['cert_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Certificado Homologacao (.p12)</label>
                <input type="file" name="cert_p12_sandbox" class="form-control" accept=".p12">
                <small class="text-muted">Atual: <span id="cert-atual-sandbox"><?php echo htmlspecialchars($envMap['sandbox']['cert_name'], ENT_QUOTES, 'UTF-8'); ?></span></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Certificado Producao (.p12)</label>
                <input type="file" name="cert_p12_producao" class="form-control" accept=".p12">
                <small class="text-muted">Atual: <span id="cert-atual-producao"><?php echo htmlspecialchars($envMap['producao']['cert_name'], ENT_QUOTES, 'UTF-8'); ?></span></small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Senha .p12 Homologacao</label>
                <input type="text" name="cert_password_sandbox" class="form-control" value="<?php echo htmlspecialchars($envMap['sandbox']['cert_password'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Senha .p12 Producao</label>
                <input type="text" name="cert_password_producao" class="form-control" value="<?php echo htmlspecialchars($envMap['producao']['cert_password'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const formRoot = document.getElementById('gateway-efy-form');
    if (!formRoot) return;

    let envData = <?php echo json_encode($envMap, JSON_UNESCAPED_UNICODE); ?>;
    const alertBox = document.getElementById('gateway-efy-alert');
    const btn = formRoot.querySelector('button[type="submit"]');
    const ambienteSel = formRoot.querySelector('[name="ambiente"]');

    function showAlert(msg, ok) {
        if (!alertBox) return;
        const type = ok ? 'info' : 'danger';
        alertBox.innerHTML = '<div class="alert alert-' + type + '">' + msg + '</div>';
    }

    function fillSelectedEnvironment(env) {
        const data = envData[env] || {};
        formRoot.querySelector('[name="chave_api"]').value = data.client_id || '';
        formRoot.querySelector('[name="chave_secreta"]').value = data.client_secret || '';
        formRoot.querySelector('[name="webhook_url"]').value = data.webhook_url || '';
        formRoot.querySelector('[name="webhook_path"]').value = data.webhook_path || '';

        const certLabel = document.getElementById('cert-atual-label');
        if (certLabel) certLabel.textContent = data.cert_name || 'nenhum';
    }

    function refreshCertLabels() {
        const s = document.getElementById('cert-atual-sandbox');
        const p = document.getElementById('cert-atual-producao');
        if (s) s.textContent = (envData.sandbox && envData.sandbox.cert_name) ? envData.sandbox.cert_name : 'nenhum';
        if (p) p.textContent = (envData.producao && envData.producao.cert_name) ? envData.producao.cert_name : 'nenhum';
    }

    ambienteSel.addEventListener('change', function () {
        fillSelectedEnvironment(ambienteSel.value);
        showAlert('', true);
    });

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        const fd = new FormData();
        fd.append('ambiente', ambienteSel.value);
        fd.append('chave_api', formRoot.querySelector('[name="chave_api"]').value);
        fd.append('chave_secreta', formRoot.querySelector('[name="chave_secreta"]').value);
        fd.append('webhook_url', formRoot.querySelector('[name="webhook_url"]').value);
        fd.append('webhook_path', formRoot.querySelector('[name="webhook_path"]').value);
        fd.append('cert_password_sandbox', formRoot.querySelector('[name="cert_password_sandbox"]').value);
        fd.append('cert_password_producao', formRoot.querySelector('[name="cert_password_producao"]').value);

        const certSandbox = formRoot.querySelector('[name="cert_p12_sandbox"]');
        const certProducao = formRoot.querySelector('[name="cert_p12_producao"]');
        if (certSandbox && certSandbox.files.length > 0) fd.append('cert_p12_sandbox', certSandbox.files[0]);
        if (certProducao && certProducao.files.length > 0) fd.append('cert_p12_producao', certProducao.files[0]);

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        btn.disabled = true;

        fetch('paginas/gateway_efy_save.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: token ? { 'X-CSRF-Token': token } : {}
        })
        .then((r) => r.text())
        .then((raw) => {
            btn.disabled = false;

            let data = null;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                const txt = (raw || '').trim();
                showAlert(txt !== '' ? txt : 'Erro na requisicao.', false);
                return;
            }

            if (!data.ok) {
                showAlert(data.msg || 'Erro ao salvar.', false);
                return;
            }

            if (data.env_data) {
                envData = data.env_data;
            }
            refreshCertLabels();
            fillSelectedEnvironment(ambienteSel.value);
            showAlert(data.msg || 'Configuracoes salvas.', true);

            if (certSandbox) certSandbox.value = '';
            if (certProducao) certProducao.value = '';
        })
        .catch((err) => {
            btn.disabled = false;
            showAlert('Erro na requisicao: ' + err, false);
        });
    });

    fillSelectedEnvironment(ambienteSel.value);
    refreshCertLabels();
})();
</script>





