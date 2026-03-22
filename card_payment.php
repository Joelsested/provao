<?php

require_once('../vendor/autoload.php');
require_once('../sistema/conexao.php');
require_once(__DIR__ . '/../helpers.php');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

@session_start();
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$cardDebugPaths = [
    __DIR__ . '/../logs/efi_card_request.log',
    __DIR__ . '/efi_card_request.log',
    sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'efi_card_request.log',
];
$cardDebugLogUsado = '';
$tokenA = is_array($data) ? (string) ($data['payment_token'] ?? '') : '';
$tokenB = is_array($data) ? (string) ($data['credit_card_token'] ?? '') : '';
$linhaLogCard = '[' . date('Y-m-d H:i:s') . '] '
    . 'method=' . (string) (is_array($data) ? ($data['payment_method'] ?? '') : '')
    . ' | installments=' . (string) (is_array($data) ? ($data['installments'] ?? '') : '')
    . ' | installments_value=' . (string) (is_array($data) ? ($data['installments_value'] ?? '') : '')
    . ' | has_payment_token=' . ($tokenA !== '' ? 'sim' : 'nao')
    . ' | len_payment_token=' . strlen($tokenA)
    . ' | has_credit_card_token=' . ($tokenB !== '' ? 'sim' : 'nao')
    . ' | len_credit_card_token=' . strlen($tokenB)
    . ' | checkout_env=' . (string) (is_array($data) ? ($data['efi_checkout_environment'] ?? '') : '')
    . ' | checkout_account=' . (string) (is_array($data) ? ($data['efi_checkout_account'] ?? '') : '')
    . PHP_EOL;

foreach ($cardDebugPaths as $pathLog) {
    $dirLog = dirname($pathLog);
    if (!is_dir($dirLog)) {
        @mkdir($dirLog, 0775, true);
    }
    $okWrite = @file_put_contents($pathLog, $linhaLogCard, FILE_APPEND);
    if ($okWrite !== false) {
        $cardDebugLogUsado = $pathLog;
        break;
    }
}
if ($cardDebugLogUsado !== '') {
    header('X-Card-Debug-Log: ' . $cardDebugLogUsado);
}

if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'type' => 'CREDIT_CARD',
        'error' => 'Payload inválido.'
    ]);
    exit;
}

function idadeCompletaEmAnosLocal(string $dataNascimento, ?DateTimeImmutable $hojeRef = null): int
{
    $dataNormalizada = function_exists('normalizeDate') ? normalizeDate($dataNascimento) : trim($dataNascimento);
    if ($dataNormalizada === '' || $dataNormalizada === '0000-00-00') {
        return -1;
    }

    $hoje = $hojeRef ?: new DateTimeImmutable('today');
    try {
        $nascimento = new DateTimeImmutable($dataNormalizada);
    } catch (Throwable $e) {
        return -1;
    }

    if ($nascimento > $hoje) {
        return -1;
    }

    return (int) $nascimento->diff($hoje)->y;
}

function colunaExisteTabelaLocal(PDO $pdo, string $tabela, string $coluna): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
        $stmt->execute([':coluna' => $coluna]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

function garantirColunaLiberacaoMenorLocal(PDO $pdo): void
{
    if (colunaExisteTabelaLocal($pdo, 'alunos', 'liberado_menor_18')) {
        return;
    }
    try {
        $pdo->exec("ALTER TABLE alunos ADD COLUMN liberado_menor_18 TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {
        // Mantem fluxo mesmo sem conseguir alterar estrutura.
    }
}

function montarNotificationUrlLocal(string $baseSistema, string $relativePath = 'efi_webhooks.php', ?string $fallback = null): ?string
{
    $candidatas = [];

    $baseSistema = trim($baseSistema);
    if ($baseSistema !== '') {
        $candidatas[] = rtrim($baseSistema, '/') . '/' . ltrim($relativePath, '/');
    }

    $fallback = trim((string) $fallback);
    if ($fallback !== '') {
        if (preg_match('#^https://[^/]+/?$#i', $fallback)) {
            $fallback = rtrim($fallback, '/') . '/' . ltrim($relativePath, '/');
        }
        $candidatas[] = $fallback;
    }

    $token = '';
    foreach (['WEBHOOK_TOKEN_EJA_PROD', 'WEBHOOK_TOKEN_EJA', 'WEBHOOK_TOKEN'] as $key) {
        $value = trim((string) env($key, ''));
        if ($value !== '') {
            $token = $value;
            break;
        }
    }

    foreach ($candidatas as $url) {
        if (stripos($url, 'https://') === 0) {
            if ($token !== '') {
                $sep = strpos($url, '?') === false ? '?' : '&';
                $url .= $sep . 'token=' . urlencode($token);
            }
            return $url;
        }
    }

    return null;
}

function aprovarMatriculaCartaoLocal(int $idMatricula, float $valorTotal, string $formaPgto, string $tabelaOrigem = 'matriculas', string $obs = ''): bool
{
    if ($idMatricula <= 0) {
        return false;
    }

    $arquivoAprovar = __DIR__ . '/../sistema/painel-admin/paginas/matriculas/aprovar.php';
    if (!file_exists($arquivoAprovar)) {
        return false;
    }

    $postOriginal = $_POST ?? [];
    $_POST = [
        'forma_pgto' => $formaPgto,
        'valor' => number_format($valorTotal, 2, '.', ''),
        'obs' => $obs,
        'cartao' => 'Nao',
        'id_mat' => $idMatricula,
    ];

    if ($tabelaOrigem !== 'matriculas') {
        $_POST['tabela_origem'] = $tabelaOrigem;
    }

    $cwdOriginal = getcwd();
    $dirAprovar = dirname($arquivoAprovar);
    if ($dirAprovar !== '') {
        @chdir($dirAprovar);
    }

    // Garante variaveis de conexao/config no mesmo escopo do include de aprovar.php.
    // O aprovar.php depende desses simbolos e usa require_once relativo.
    $arquivoConexao = __DIR__ . '/../sistema/conexao.php';
    if (file_exists($arquivoConexao)) {
        require $arquivoConexao;
    }

    ob_start();
    require $arquivoAprovar;
    ob_end_clean();

    if ($cwdOriginal !== false) {
        @chdir($cwdOriginal);
    }
    $_POST = $postOriginal;

    return true;
}

function registrarLogPagamentoLocal(PDO $pdo, int $idMatricula, string $descricao, string $formaPgto, float $valor, string $status, array $payload = []): void
{
    if ($idMatricula <= 0) {
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs_pagamentos (id_matricula, data, descricao, forma_pagamento, valor, status, json_response) VALUES (:id_matricula, NOW(), :descricao, :forma_pagamento, :valor, :status, :json_response)");
        $stmt->execute([
            ':id_matricula' => $idMatricula,
            ':descricao' => $descricao,
            ':forma_pagamento' => $formaPgto,
            ':valor' => $valor,
            ':status' => $status,
            ':json_response' => !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        ]);
    } catch (Throwable $e) {
        // Nao interrompe o pagamento por falha de log.
    }
}

function garantirColunasResumoCartaoLocal(PDO $pdo, string $tabelaMatricula = 'matriculas'): void
{
    if ($tabelaMatricula === '') {
        return;
    }

    try {
        $temQtd = $pdo->query("SHOW COLUMNS FROM {$tabelaMatricula} LIKE 'qtd_parcelas_cartao'")->fetch(PDO::FETCH_ASSOC);
        if (!$temQtd) {
            $pdo->exec("ALTER TABLE {$tabelaMatricula} ADD COLUMN qtd_parcelas_cartao INT NOT NULL DEFAULT 1");
        }
    } catch (Throwable $e) {
        // Mantem fluxo mesmo sem coluna.
    }

    try {
        $temTotal = $pdo->query("SHOW COLUMNS FROM {$tabelaMatricula} LIKE 'valor_total_cartao'")->fetch(PDO::FETCH_ASSOC);
        if (!$temTotal) {
            $pdo->exec("ALTER TABLE {$tabelaMatricula} ADD COLUMN valor_total_cartao DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (Throwable $e) {
        // Mantem fluxo mesmo sem coluna.
    }
}

function atualizarResumoCartaoMatriculaLocal(
    PDO $pdo,
    string $tabelaMatricula,
    int $idMatricula,
    int $qtdParcelas,
    float $valorTotal
): void {
    if ($idMatricula <= 0 || $tabelaMatricula === '') {
        return;
    }

    $qtdParcelas = max(1, $qtdParcelas);
    $valorTotal = round(max($valorTotal, 0), 2);

    try {
        $colunas = $pdo->query("SHOW COLUMNS FROM {$tabelaMatricula}")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return;
    }

    if (empty($colunas)) {
        return;
    }

    $sets = [];
    $params = [':id' => $idMatricula];

    if (in_array('qtd_parcelas_cartao', $colunas, true)) {
        $sets[] = "qtd_parcelas_cartao = :qtd_parcelas_cartao";
        $params[':qtd_parcelas_cartao'] = $qtdParcelas;
    }
    if (in_array('qtd_parcelas', $colunas, true)) {
        $sets[] = "qtd_parcelas = :qtd_parcelas";
        $params[':qtd_parcelas'] = $qtdParcelas;
    }
    if (in_array('parcelas', $colunas, true)) {
        $sets[] = "parcelas = :parcelas";
        $params[':parcelas'] = $qtdParcelas;
    }
    if (in_array('valor_total_cartao', $colunas, true)) {
        $sets[] = "valor_total_cartao = :valor_total_cartao";
        $params[':valor_total_cartao'] = $valorTotal;
    }

    if (empty($sets)) {
        return;
    }

    $sql = "UPDATE {$tabelaMatricula} SET " . implode(', ', $sets) . " WHERE id = :id LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } catch (Throwable $e) {
        // Nao interrompe pagamento por falha de persistencia auxiliar.
    }
}

function garantirEstruturaRecorrenciaCartaoLocal(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS efi_assinaturas_cartao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_matricula INT NOT NULL,
            subscription_id BIGINT NOT NULL,
            charge_id_inicial VARCHAR(100) NULL,
            quantidade_parcelas INT NOT NULL DEFAULT 1,
            valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            valor_parcela DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
            payload_inicial LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_assinatura_matricula_sub (id_matricula, subscription_id),
            KEY idx_assinatura_sub (subscription_id),
            KEY idx_assinatura_matricula (id_matricula)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS efi_assinaturas_cartao_parcelas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_assinatura INT NOT NULL,
            id_matricula INT NOT NULL,
            numero_parcela INT NOT NULL,
            valor_parcela DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            vencimento DATE NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
            charge_id VARCHAR(100) NULL,
            data_pagamento DATETIME NULL,
            payload LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_assinatura_parcela (id_assinatura, numero_parcela),
            KEY idx_parcela_matricula (id_matricula),
            KEY idx_parcela_status (status),
            KEY idx_parcela_charge (charge_id),
            CONSTRAINT fk_parcela_assinatura FOREIGN KEY (id_assinatura)
                REFERENCES efi_assinaturas_cartao(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function statusRecorrenciaGatewayParaInterno(string $statusGateway): string
{
    $status = strtolower(trim($statusGateway));
    if ($status === '') {
        return 'PENDENTE';
    }

    if (in_array($status, ['paid', 'approved', 'active', 'settled'], true)) {
        return 'ATIVA';
    }
    if (in_array($status, ['canceled', 'cancelled'], true)) {
        return 'CANCELADA';
    }
    if (in_array($status, ['refused', 'rejected', 'unpaid'], true)) {
        return 'FALHA';
    }

    return 'PENDENTE';
}

function registrarPlanoRecorrenciaCartaoLocal(
    PDO $pdo,
    int $idMatricula,
    int $subscriptionId,
    string $chargeIdInicial,
    int $quantidadeParcelas,
    float $valorTotal,
    float $valorParcela,
    string $statusAssinatura,
    array $payloadInicial = []
): void {
    if ($idMatricula <= 0 || $subscriptionId <= 0 || $quantidadeParcelas < 1) {
        return;
    }

    garantirEstruturaRecorrenciaCartaoLocal($pdo);

    $stmtAssinatura = $pdo->prepare("
        INSERT INTO efi_assinaturas_cartao
            (id_matricula, subscription_id, charge_id_inicial, quantidade_parcelas, valor_total, valor_parcela, status, payload_inicial)
        VALUES
            (:id_matricula, :subscription_id, :charge_id_inicial, :quantidade_parcelas, :valor_total, :valor_parcela, :status, :payload_inicial)
        ON DUPLICATE KEY UPDATE
            charge_id_inicial = VALUES(charge_id_inicial),
            quantidade_parcelas = VALUES(quantidade_parcelas),
            valor_total = VALUES(valor_total),
            valor_parcela = VALUES(valor_parcela),
            status = VALUES(status),
            payload_inicial = VALUES(payload_inicial),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmtAssinatura->execute([
        ':id_matricula' => $idMatricula,
        ':subscription_id' => $subscriptionId,
        ':charge_id_inicial' => $chargeIdInicial !== '' ? $chargeIdInicial : null,
        ':quantidade_parcelas' => $quantidadeParcelas,
        ':valor_total' => round($valorTotal, 2),
        ':valor_parcela' => round($valorParcela, 2),
        ':status' => $statusAssinatura,
        ':payload_inicial' => !empty($payloadInicial) ? json_encode($payloadInicial, JSON_UNESCAPED_UNICODE) : null,
    ]);

    $stmtBusca = $pdo->prepare("SELECT id FROM efi_assinaturas_cartao WHERE id_matricula = :id_matricula AND subscription_id = :subscription_id LIMIT 1");
    $stmtBusca->execute([
        ':id_matricula' => $idMatricula,
        ':subscription_id' => $subscriptionId,
    ]);
    $idAssinatura = (int) ($stmtBusca->fetchColumn() ?: 0);
    if ($idAssinatura <= 0) {
        return;
    }

    $hoje = new DateTimeImmutable('today');
    $statusPrimeiraParcela = in_array($statusAssinatura, ['ATIVA'], true) ? 'PAGA' : 'PENDENTE';
    $dataPagamentoPrimeira = $statusPrimeiraParcela === 'PAGA' ? date('Y-m-d H:i:s') : null;

    $stmtParcela = $pdo->prepare("
        INSERT INTO efi_assinaturas_cartao_parcelas
            (id_assinatura, id_matricula, numero_parcela, valor_parcela, vencimento, status, charge_id, data_pagamento, payload)
        VALUES
            (:id_assinatura, :id_matricula, :numero_parcela, :valor_parcela, :vencimento, :status, :charge_id, :data_pagamento, :payload)
        ON DUPLICATE KEY UPDATE
            valor_parcela = VALUES(valor_parcela),
            vencimento = VALUES(vencimento),
            status = VALUES(status),
            charge_id = COALESCE(VALUES(charge_id), charge_id),
            data_pagamento = COALESCE(VALUES(data_pagamento), data_pagamento),
            payload = COALESCE(VALUES(payload), payload),
            updated_at = CURRENT_TIMESTAMP
    ");

    for ($parcela = 1; $parcela <= $quantidadeParcelas; $parcela++) {
        $vencimento = $hoje->modify('+' . ($parcela - 1) . ' month')->format('Y-m-d');
        $statusParcela = $parcela === 1 ? $statusPrimeiraParcela : 'PENDENTE';
        $chargeParcela = $parcela === 1 && $chargeIdInicial !== '' ? $chargeIdInicial : null;
        $dataPgParcela = $parcela === 1 ? $dataPagamentoPrimeira : null;

        $stmtParcela->execute([
            ':id_assinatura' => $idAssinatura,
            ':id_matricula' => $idMatricula,
            ':numero_parcela' => $parcela,
            ':valor_parcela' => round($valorParcela, 2),
            ':vencimento' => $vencimento,
            ':status' => $statusParcela,
            ':charge_id' => $chargeParcela,
            ':data_pagamento' => $dataPgParcela,
            ':payload' => $parcela === 1 && !empty($payloadInicial) ? json_encode($payloadInicial, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}

function mapearStatusParcelaInterno(string $statusAssinatura): string
{
    if ($statusAssinatura === 'ATIVA') {
        return 'PAGA';
    }
    if ($statusAssinatura === 'FALHA') {
        return 'ATRASADA';
    }
    if ($statusAssinatura === 'CANCELADA') {
        return 'CANCELADA';
    }
    return 'PENDENTE';
}

function registrarBaixaRecorrenciaExistenteLocal(
    PDO $pdo,
    int $subscriptionId,
    string $statusAssinatura,
    string $chargeId,
    array $payload = []
): void {
    if ($subscriptionId <= 0) {
        return;
    }

    $stmtAss = $pdo->prepare("SELECT id FROM efi_assinaturas_cartao WHERE subscription_id = :subscription_id ORDER BY id DESC LIMIT 1");
    $stmtAss->execute([':subscription_id' => $subscriptionId]);
    $idAssinatura = (int) ($stmtAss->fetchColumn() ?: 0);
    if ($idAssinatura <= 0) {
        return;
    }

    $stmtUpdateAss = $pdo->prepare("UPDATE efi_assinaturas_cartao SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id LIMIT 1");
    $stmtUpdateAss->execute([
        ':status' => $statusAssinatura,
        ':id' => $idAssinatura,
    ]);

    $payloadJson = !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;
    $statusParcela = mapearStatusParcelaInterno($statusAssinatura);

    if ($statusParcela === 'PAGA') {
        $stmtParcela = $pdo->prepare("
            SELECT id
            FROM efi_assinaturas_cartao_parcelas
            WHERE id_assinatura = :id_assinatura
              AND status IN ('PENDENTE', 'ATRASADA')
            ORDER BY numero_parcela ASC
            LIMIT 1
        ");
        $stmtParcela->execute([':id_assinatura' => $idAssinatura]);
        $idParcela = (int) ($stmtParcela->fetchColumn() ?: 0);
        if ($idParcela > 0) {
            $stmtUpdateParcela = $pdo->prepare("
                UPDATE efi_assinaturas_cartao_parcelas
                SET status = 'PAGA',
                    charge_id = COALESCE(:charge_id, charge_id),
                    data_pagamento = NOW(),
                    payload = COALESCE(:payload, payload),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                LIMIT 1
            ");
            $stmtUpdateParcela->execute([
                ':charge_id' => $chargeId !== '' ? $chargeId : null,
                ':payload' => $payloadJson,
                ':id' => $idParcela,
            ]);
        }
        return;
    }

    if (in_array($statusParcela, ['ATRASADA', 'CANCELADA'], true)) {
        $stmtUpdateParcela = $pdo->prepare("
            UPDATE efi_assinaturas_cartao_parcelas
            SET status = :status,
                payload = COALESCE(:payload, payload),
                updated_at = CURRENT_TIMESTAMP
            WHERE id_assinatura = :id_assinatura
              AND status IN ('PENDENTE', 'ATRASADA')
        ");
        $stmtUpdateParcela->execute([
            ':status' => $statusParcela,
            ':payload' => $payloadJson,
            ':id_assinatura' => $idAssinatura,
        ]);
    }
}

$formaDePagamento = strtoupper((string) ($data['payment_method'] ?? 'CREDIT_CARD'));
$subscriptionIdExistente = (int) ($data['recurring_subscription_id'] ?? 0);
$isReprocessamentoRecorrente = $subscriptionIdExistente > 0;
$isRecorrente = ($formaDePagamento === 'DEBIT_CARD') || $isReprocessamentoRecorrente;
$installments = 0;
if (isset($data['installments']) && is_numeric($data['installments'])) {
    $installments = (int) $data['installments'];
}
if ($installments < 1 && isset($data['qtd_parcelas']) && is_numeric($data['qtd_parcelas'])) {
    $installments = (int) $data['qtd_parcelas'];
}
if ($installments < 1 && isset($data['parcelas']) && is_numeric($data['parcelas'])) {
    $installments = (int) $data['parcelas'];
}
if ($installments < 1 && isset($data['quantidadeParcelas']) && is_numeric($data['quantidadeParcelas'])) {
    $installments = (int) $data['quantidadeParcelas'];
}
if ($installments < 1) {
    $installmentsText = trim((string) ($data['installments_value'] ?? ''));
    if ($installmentsText !== '' && preg_match('/^(\d+)\s*x/i', $installmentsText, $mInst)) {
        $installments = (int) ($mInst[1] ?? 0);
    }
}
if ($installments < 1) {
    $installments = 1;
}
if ($isReprocessamentoRecorrente) {
    $installments = 1;
} elseif ($isRecorrente && $installments > 6) {
    $installments = 6;
}
if ($isRecorrente && !$isReprocessamentoRecorrente && $installments < 2) {
    $installments = 2;
}

$idAlunoUsuario = (int) (@$_SESSION['id'] ?? 0);
$idCurso = (int) ($data['id_do_curso'] ?? 0);
$idMatricula = (int) ($data['id_matricula'] ?? 0);
$nomeCurso = (string) ($data['nome_do_curso'] ?? 'Produto/Servico');
$tipo = (string) ($data['tipo'] ?? 'cursos');

if ($idAlunoUsuario <= 0 || $idCurso <= 0) {
    echo json_encode([
        'success' => false,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'error' => 'Dados obrigatórios ausentes.'
    ]);
    exit;
}

$tabelaMatricula = 'matriculas';
if ($tipo === 'tecnicos') {
    $tabelaMatricula = 'matriculas_tecnicos';
} elseif ($tipo === 'profissionalizantes') {
    $tabelaMatricula = 'matriculas_profissionalizantes';
}

$options = require_once 'options.php';

$stmtUsuario = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
$stmtUsuario->execute([':id' => $idAlunoUsuario]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$usuario) {
    echo json_encode([
        'success' => false,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'error' => 'Usuário do aluno não encontrado.'
    ]);
    exit;
}

$idPessoa = (int) ($usuario['id_pessoa'] ?? 0);
$stmtAluno = $pdo->prepare('SELECT * FROM alunos WHERE id = :id LIMIT 1');
$stmtAluno->execute([':id' => $idPessoa]);
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$aluno) {
    echo json_encode([
        'success' => false,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'error' => 'Cadastro do aluno não encontrado.'
    ]);
    exit;
}

garantirColunaLiberacaoMenorLocal($pdo);
$idPessoaAluno = (int) ($aluno['id'] ?? 0);
$stmtMenor = $pdo->prepare("SELECT nascimento, COALESCE(liberado_menor_18, 0) AS liberado_menor_18 FROM alunos WHERE id = :id LIMIT 1");
$stmtMenor->execute([':id' => $idPessoaAluno]);
$dadosMenor = $stmtMenor->fetch(PDO::FETCH_ASSOC) ?: null;
$idadeAluno = idadeCompletaEmAnosLocal((string) ($dadosMenor['nascimento'] ?? ''));
$liberadoMenor = (int) ($dadosMenor['liberado_menor_18'] ?? 0) === 1;
if ($idadeAluno >= 0 && $idadeAluno < 18 && !$liberadoMenor) {
    echo json_encode([
        'success' => false,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'error' => 'Aluno menor de 18 anos. Só admin pode liberar matrículas para alunos menores.'
    ]);
    exit;
}

$sqlMat = "SELECT * FROM {$tabelaMatricula} WHERE aluno = :aluno";
$paramsMat = [':aluno' => $idAlunoUsuario];
if ($idMatricula > 0) {
    $sqlMat .= " AND id = :id";
    $paramsMat[':id'] = $idMatricula;
} else {
    $sqlMat .= " AND id_curso = :id_curso";
    $paramsMat[':id_curso'] = $idCurso;
}
$sqlMat .= " LIMIT 1";
$stmtMat = $pdo->prepare($sqlMat);
$stmtMat->execute($paramsMat);
$matricula = $stmtMat->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$matricula) {
    echo json_encode([
        'success' => false,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'error' => 'Matrícula não encontrada.'
    ]);
    exit;
}

garantirColunasResumoCartaoLocal($pdo, $tabelaMatricula);

$parcelaAbertaRecorrente = null;
if ($isReprocessamentoRecorrente) {
    garantirEstruturaRecorrenciaCartaoLocal($pdo);

    $stmtAssinaturaExistente = $pdo->prepare("
        SELECT id, valor_parcela
        FROM efi_assinaturas_cartao
        WHERE id_matricula = :id_matricula
          AND subscription_id = :subscription_id
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmtAssinaturaExistente->execute([
        ':id_matricula' => (int) ($matricula['id'] ?? 0),
        ':subscription_id' => $subscriptionIdExistente,
    ]);
    $assinaturaExistente = $stmtAssinaturaExistente->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$assinaturaExistente) {
        echo json_encode([
            'success' => false,
            'type' => 'RECURRING_CARD',
            'error' => 'Assinatura recorrente não encontrada para esta matrícula.'
        ]);
        exit;
    }

    $stmtParcelaAberta = $pdo->prepare("
        SELECT id, numero_parcela, valor_parcela, status, vencimento
        FROM efi_assinaturas_cartao_parcelas
        WHERE id_assinatura = :id_assinatura
          AND status IN ('PENDENTE', 'ATRASADA')
        ORDER BY numero_parcela ASC
        LIMIT 1
    ");
    $stmtParcelaAberta->execute([
        ':id_assinatura' => (int) ($assinaturaExistente['id'] ?? 0),
    ]);
    $parcelaAbertaRecorrente = $stmtParcelaAberta->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$parcelaAbertaRecorrente) {
        echo json_encode([
            'success' => false,
            'type' => 'RECURRING_CARD',
            'error' => 'Não há parcelas pendentes/atrasadas para regularizar.'
        ]);
        exit;
    }
}

$valorBase = (float) ($matricula['subtotal'] ?? 0);
if ($valorBase <= 0) {
    $valorBase = (float) ($matricula['valor'] ?? 0);
}
$valorCurso = max($valorBase, 0);

$taxaFixaCartao = (float) env('EFI_CARD_FEE_FIXED', '0.29');
$taxaPercentualCartao = ((float) env('EFI_CARD_FEE_PERCENT', '4.99')) / 100;
$jurosMensalParcelado = ((float) env('EFI_CARD_INTEREST_MONTHLY', '1.99')) / 100;

$calcularTotalCartaoCliente = static function (
    float $valorLiquido,
    int $parcelas,
    float $taxaFixa,
    float $taxaPercentual,
    float $jurosMensal
): float {
    $parcelas = max(1, $parcelas);
    $denominador = 1 - $taxaPercentual;
    $baseBruta = $denominador > 0 ? (($valorLiquido + $taxaFixa) / $denominador) : $valorLiquido;
    if ($parcelas > 1) {
        $baseBruta *= pow(1 + $jurosMensal, $parcelas - 1);
    }
    return round(max($baseBruta, 0), 2);
};

if ($isReprocessamentoRecorrente) {
    $valorAPagar = round(max((float) ($parcelaAbertaRecorrente['valor_parcela'] ?? 0), 0), 2);
} else {
    $valorAPagar = $calcularTotalCartaoCliente(
        $valorCurso,
        $installments,
        $taxaFixaCartao,
        $taxaPercentualCartao,
        $jurosMensalParcelado
    );
    $valorAPagar = round(max($valorAPagar, 0), 2);
}

// Comissoes: fixos + vendedor vinculado ao aluno (alunos.usuario/responsavel_id).
$fixosWalletIds = [];

$adicionarRepasse = function (&$lista, string $walletId, float $percentualEmBase100) {
    $walletId = trim($walletId);
    if ($walletId === '' || $percentualEmBase100 <= 0) {
        return;
    }

    foreach ($lista as &$item) {
        if (($item['payee_code'] ?? '') === $walletId) {
            $item['percentage'] += $percentualEmBase100;
            return;
        }
    }

    $lista[] = [
        'payee_code' => $walletId,
        'percentage' => $percentualEmBase100
    ];
};

// 1) Fixos (recebeSempre = 1)
$listaCargosRecebemFixo = [];
$resComissoesFixas = $pdo->query("SELECT nivel FROM comissoes WHERE recebeSempre = 1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($resComissoesFixas as $registro) {
    if (!empty($registro['nivel'])) {
        $listaCargosRecebemFixo[] = $registro['nivel'];
    }
}

if (!empty($listaCargosRecebemFixo)) {
    $placeholders = implode(',', array_fill(0, count($listaCargosRecebemFixo), '?'));
    $sql = "SELECT usuarios.wallet_id, comissoes.porcentagem
            FROM usuarios
            INNER JOIN comissoes ON comissoes.nivel = usuarios.nivel
            WHERE usuarios.nivel IN ($placeholders)
              AND usuarios.wallet_id IS NOT NULL
              AND usuarios.wallet_id <> ''";
    $stmtFixos = $pdo->prepare($sql);
    $stmtFixos->execute($listaCargosRecebemFixo);
    $usuariosFixos = $stmtFixos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuariosFixos as $item) {
        $adicionarRepasse(
            $fixosWalletIds,
            (string) ($item['wallet_id'] ?? ''),
            (float) ($item['porcentagem'] ?? 0) * 100
        );
    }
}

// 2) Vendedor da venda: prioriza vinculo do aluno (usuario/responsavel_id)
$idUsuarioVendedor = (int) ($aluno['usuario'] ?? 0);
if ($idUsuarioVendedor <= 0) {
    $idUsuarioVendedor = (int) ($aluno['responsavel_id'] ?? 0);
}
if ($idUsuarioVendedor <= 0) {
    // Fallback para o antigo comportamento (usuario professor da matricula)
    $idUsuarioVendedor = (int) ($matricula['professor'] ?? 0);
}

if ($idUsuarioVendedor > 0) {
    $stmtUsuarioVendedor = $pdo->prepare('SELECT id, nivel, id_pessoa, wallet_id FROM usuarios WHERE id = :id LIMIT 1');
    $stmtUsuarioVendedor->execute([':id' => $idUsuarioVendedor]);
    $usuarioVendedor = $stmtUsuarioVendedor->fetch(PDO::FETCH_ASSOC) ?: [];

    $nivelVendedor = (string) ($usuarioVendedor['nivel'] ?? '');
    $idPessoaVendedor = (int) ($usuarioVendedor['id_pessoa'] ?? 0);
    $walletIdVendedor = trim((string) ($usuarioVendedor['wallet_id'] ?? ''));
    $porcentagemVendedor = 0.0;

    if ($nivelVendedor === 'Vendedor' && $idPessoaVendedor > 0) {
        $stmtPct = $pdo->prepare('SELECT comissao FROM vendedores WHERE id = :id LIMIT 1');
        $stmtPct->execute([':id' => $idPessoaVendedor]);
        $porcentagemVendedor = (float) ($stmtPct->fetchColumn() ?: 0);
    } elseif ($nivelVendedor === 'Parceiro' && $idPessoaVendedor > 0) {
        $stmtPct = $pdo->prepare('SELECT comissao FROM parceiros WHERE id = :id LIMIT 1');
        $stmtPct->execute([':id' => $idPessoaVendedor]);
        $porcentagemVendedor = (float) ($stmtPct->fetchColumn() ?: 0);
    } elseif ($nivelVendedor !== '') {
        // Fallback por nivel na tabela comissoes
        $stmtPct = $pdo->prepare('SELECT porcentagem FROM comissoes WHERE nivel = :nivel LIMIT 1');
        $stmtPct->execute([':nivel' => $nivelVendedor]);
        $porcentagemVendedor = (float) ($stmtPct->fetchColumn() ?: 0);
    }

    $adicionarRepasse($fixosWalletIds, $walletIdVendedor, $porcentagemVendedor * 100);
}

// Arredonda para inteiro (formato esperado pela Efí, ex.: 1500 = 15%)
foreach ($fixosWalletIds as &$rep) {
    $rep['percentage'] = (int) round((float) ($rep['percentage'] ?? 0));
}
unset($rep);

$nomeAluno = trim((string) ($aluno['nome'] ?? ''));
$emailAluno = trim((string) ($aluno['email'] ?? ($usuario['email'] ?? ($usuario['usuario'] ?? ''))));
$cpfAluno = preg_replace('/\D/', '', (string) ($aluno['cpf'] ?? ($usuario['cpf'] ?? '')));
$telefoneAluno = preg_replace('/\D/', '', (string) ($aluno['telefone'] ?? ($usuario['telefone'] ?? '')));

$notificationUrl = montarNotificationUrlLocal((string) ($url_sistema ?? ''), 'efi_webhooks.php', (string) ($options['notificationUrl'] ?? ''));

$clientId = (string) env('EFI_CARD_CLIENT_ID', (string) ($options['clientId'] ?? ''));
$clientSecret = (string) env('EFI_CARD_CLIENT_SECRET', (string) ($options['clientSecret'] ?? ''));
$sandbox = filter_var(env('EFI_CARD_SANDBOX', !empty($options['sandbox']) ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);

if ($cardDebugLogUsado !== '') {
    $linhaBackend = '[' . date('Y-m-d H:i:s') . '] '
        . 'backend_env=' . ($sandbox ? 'sandbox' : 'production')
        . ' | backend_client_prefix=' . substr($clientId, 0, 18)
        . PHP_EOL;
    @file_put_contents($cardDebugLogUsado, $linhaBackend, FILE_APPEND);
}

if ($clientId === '' || $clientSecret === '') {
    echo json_encode([
        'success' => false,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'error' => 'Credenciais EFY não configuradas.'
    ]);
    exit;
}

require_once 'card.php';

try {
    $cardPayment = new EFICreditCardPayment($clientId, $clientSecret, $sandbox);

    $streetPayload = trim((string) ($data['street'] ?? ($data['address'] ?? '')));
    $zipcodePayload = preg_replace('/\D/', '', (string) ($data['zipcode'] ?? ($data['cep'] ?? '')));
    $numberPayload = trim((string) ($data['number'] ?? ''));
    $neighborhoodPayload = trim((string) ($data['neighborhood'] ?? ''));
    $cityPayload = trim((string) ($data['city'] ?? ''));
    $statePayload = strtoupper(trim((string) ($data['state'] ?? '')));

    $dadosCartao = [
        'valor' => $valorAPagar,
        'item_nome' => $nomeCurso,
        'quantidade' => 1,
        'nome' => $nomeAluno,
        'email' => $emailAluno,
        'cpf' => $cpfAluno,
        'telefone' => $telefoneAluno,
        'credit_card_token' => (string) ($data['payment_token'] ?? ($data['credit_card_token'] ?? '')),
        'installments' => $installments,
        'street' => $streetPayload,
        'number' => $numberPayload,
        'neighborhood' => $neighborhoodPayload,
        'zipcode' => $zipcodePayload,
        'city' => $cityPayload,
        'state' => $statePayload,
        'notification_url' => $notificationUrl,
    ];

    if (!empty($fixosWalletIds)) {
        $dadosCartao['repasses'] = $fixosWalletIds;
    }

    if (empty($dadosCartao['nome']) || empty($dadosCartao['email']) || empty($dadosCartao['cpf']) || empty($dadosCartao['credit_card_token'])) {
        throw new Exception('Dados do cliente/cartao incompletos.');
    }
    if (
        empty($dadosCartao['street']) ||
        empty($dadosCartao['number']) ||
        empty($dadosCartao['neighborhood']) ||
        empty($dadosCartao['city']) ||
        empty($dadosCartao['state']) ||
        strlen((string) $dadosCartao['zipcode']) !== 8
    ) {
        throw new Exception('Endereco incompleto: informe rua, numero, bairro, cidade, UF e CEP com 8 digitos.');
    }

    if (empty($dadosCartao['notification_url'])) {
        unset($dadosCartao['notification_url']);
    }

    if ($isRecorrente) {
        if ($isReprocessamentoRecorrente) {
            $resultado = $cardPayment->payExistingRecurringSubscription($subscriptionIdExistente, $dadosCartao);
        } else {
            $resultado = $cardPayment->createRecurringSubscription($dadosCartao);
        }
    } else {
        $resultado = $cardPayment->createCreditCardCharge($dadosCartao);
    }

    $formaPgtoMatricula = $isRecorrente ? 'CARTAO_RECORRENTE' : 'CARTAO_DE_CREDITO';
    $statusGateway = strtoupper((string) ($resultado['status'] ?? ''));
    $descricaoLog = $isRecorrente ? 'Pagamento cartao recorrente aprovado' : 'Pagamento cartao de credito aprovado';
    $idMatriculaAtual = (int) ($matricula['id'] ?? 0);

    $stmtForma = $pdo->prepare("UPDATE {$tabelaMatricula} SET forma_pgto = :forma_pgto WHERE id = :id");
    $stmtForma->execute([
        ':forma_pgto' => $formaPgtoMatricula,
        ':id' => $idMatriculaAtual,
    ]);

    if (!$isReprocessamentoRecorrente) {
        atualizarResumoCartaoMatriculaLocal(
            $pdo,
            $tabelaMatricula,
            $idMatriculaAtual,
            (int) $installments,
            (float) ($resultado['total'] ?? $valorAPagar ?? 0)
        );
    }

    registrarLogPagamentoLocal(
        $pdo,
        $idMatriculaAtual,
        $descricaoLog,
        $formaPgtoMatricula,
        (float) ($resultado['total'] ?? $valorAPagar ?? 0),
        $statusGateway !== '' ? $statusGateway : 'APROVADO',
        (array) ($resultado['payment_data'] ?? [])
    );

    $deveAprovarMatricula = ((string) ($matricula['status'] ?? '') !== 'Matriculado');
    $matriculaAprovada = false;
    if ($deveAprovarMatricula) {
        $matriculaAprovada = aprovarMatriculaCartaoLocal(
            $idMatriculaAtual,
            (float) ($valorAPagar ?? 0),
            $formaPgtoMatricula,
            $tabelaMatricula,
            $descricaoLog
        );
    }

    if ($isRecorrente) {
        $statusAssinatura = statusRecorrenciaGatewayParaInterno($statusGateway);
        if ($isReprocessamentoRecorrente) {
            registrarBaixaRecorrenciaExistenteLocal(
                $pdo,
                $subscriptionIdExistente,
                $statusAssinatura,
                (string) ($resultado['charge_id'] ?? ''),
                (array) ($resultado['payment_data'] ?? [])
            );
        } else {
            $subscriptionId = (int) ($resultado['subscription_id'] ?? 0);
            $chargeIdInicial = (string) ($resultado['charge_id'] ?? '');
            $quantidadeParcelasRec = max(1, (int) $installments);
            $valorTotalRec = (float) ($resultado['total'] ?? $valorAPagar ?? 0);
            $valorParcelaRec = $quantidadeParcelasRec > 0 ? round($valorTotalRec / $quantidadeParcelasRec, 2) : round($valorTotalRec, 2);

            registrarPlanoRecorrenciaCartaoLocal(
                $pdo,
                $idMatriculaAtual,
                $subscriptionId,
                $chargeIdInicial,
                $quantidadeParcelasRec,
                $valorTotalRec,
                $valorParcelaRec,
                $statusAssinatura,
                (array) ($resultado['payment_data'] ?? [])
            );
        }
    }

    echo json_encode([
        'success' => true,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'data' => [
            'charge_id' => $resultado['charge_id'] ?? null,
            'subscription_id' => $resultado['subscription_id'] ?? null,
            'status' => $resultado['status'] ?? null,
            'total' => $resultado['total'] ?? null,
            'payment_data' => $resultado['payment_data'] ?? null,
        ],
        'matricula_processada' => $deveAprovarMatricula ? $matriculaAprovada : true
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'type' => $isRecorrente ? 'RECURRING_CARD' : 'CREDIT_CARD',
        'error' => 'Não foi possível processar o pagamento.',
        'detail' => $e->getMessage(),
        'data' => [
            'charge_id' => 'CHARGE_ID',
            'status' => 'STATUS',
            'total' => 'TOTAL',
            'payment_data' => 'PAYMENT_DATA',
        ]
    ]);
    exit;
}
