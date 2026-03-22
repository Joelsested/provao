<?php
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/scripts/backfill_comissoes_recebimentos.php';
}
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (!isset($_SERVER['SERVER_PORT'])) {
    $_SERVER['SERVER_PORT'] = '80';
}
if (!isset($_SERVER['HTTPS'])) {
    $_SERVER['HTTPS'] = 'off';
}

require_once __DIR__ . '/../sistema/conexao.php';

$opts = getopt('', ['dry-run', 'limit:', 'offset:', 'source:', 'file:']);
$dryRun = array_key_exists('dry-run', $opts);
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$offset = isset($opts['offset']) ? (int) $opts['offset'] : 0;
$source = isset($opts['source']) ? strtolower((string) $opts['source']) : 'db';
$filePath = isset($opts['file']) ? (string) $opts['file'] : (__DIR__ . '/../webhook_logs/webhook_log.txt');

if (!in_array($source, ['db', 'file'], true)) {
    echo "Fonte invalida. Use --source=db ou --source=file\n";
    exit(1);
}
if ($source === 'file' && !is_file($filePath)) {
    echo "Arquivo de log nao encontrado: {$filePath}\n";
    exit(1);
}

try {
    $stmtTabela = $pdo->query("SHOW TABLES LIKE 'comissoes_recebimentos'");
    $hasComissoesRecebimentos = (bool) ($stmtTabela ? $stmtTabela->fetchColumn() : false);
    if (!$hasComissoesRecebimentos) {
        echo "Tabela comissoes_recebimentos nao encontrada. Execute o script create_comissoes_recebimentos.sql\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Erro ao verificar tabela comissoes_recebimentos: " . $e->getMessage() . "\n";
    exit(1);
}

function parseDateString($value)
{
    if (!is_string($value) || $value === '') {
        return null;
    }
    $date = substr($value, 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }
    return $date;
}

function calcularValorSplit(array $split, ?float $paymentValue): ?float
{
    $valor = $split['totalValue'] ?? $split['value'] ?? $split['fixedValue'] ?? null;
    if ($valor !== null) {
        return (float) str_replace(',', '.', (string) $valor);
    }

    $percentual = $split['percentualValue'] ?? $split['percentual'] ?? null;
    if ($percentual !== null && $paymentValue !== null) {
        return ($paymentValue * (float) $percentual) / 100;
    }

    return null;
}

function extrairAlunoId(?string $descricao): ?int
{
    if (!is_string($descricao) || $descricao === '') {
        return null;
    }

    if (preg_match('/ID\\s*do\\s*aluno\\D*(\\d+)/i', $descricao, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

function extrairCursoId(?string $descricao, $externalReference): ?int
{
    if (is_numeric($externalReference)) {
        return (int) $externalReference;
    }

    if (is_string($descricao) && preg_match('/ID\\s*:\\s*(\\d+)/i', $descricao, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

function extrairNomeAluno(?string $descricao): ?string
{
    if (!is_string($descricao) || $descricao === '') {
        return null;
    }

    if (preg_match('/aluno\\s+(.+)$/i', $descricao, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

function upsertSplit(PDO $pdo, array $split, string $paymentId, ?int $matriculaId, string $status, ?string $paymentDate, ?float $paymentValue, bool $dryRun): bool
{
    $walletId = $split['walletId'] ?? $split['wallet_id'] ?? null;
    if (!$walletId) {
        return false;
    }

    $valor = calcularValorSplit($split, $paymentValue);
    if ($valor === null) {
        return false;
    }

    $statusComissao = in_array($status, ['RECEIVED', 'RECEIVED_IN_CASH', 'CONFIRMED'], true) ? 'RECEBIDO' : 'PENDENTE';
    $dataPgto = parseDateString($paymentDate);

    if ($dryRun) {
        return true;
    }

    $stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE wallet_id = :wallet_id LIMIT 1");
    $stmtUsuario->execute([':wallet_id' => $walletId]);
    $usuarioId = $stmtUsuario->fetchColumn();
    $usuarioId = $usuarioId ? (int) $usuarioId : null;

    $stmtUpsert = $pdo->prepare("INSERT INTO comissoes_recebimentos
        (gateway, pagamento_id, id_matricula, wallet_id, usuario_id, valor, status, data_pagamento)
        VALUES (:gateway, :pagamento_id, :id_matricula, :wallet_id, :usuario_id, :valor, :status, :data_pagamento)
        ON DUPLICATE KEY UPDATE
            id_matricula = VALUES(id_matricula),
            usuario_id = VALUES(usuario_id),
            valor = VALUES(valor),
            status = VALUES(status),
            data_pagamento = VALUES(data_pagamento)");

    $stmtUpsert->execute([
        ':gateway' => 'efy',
        ':pagamento_id' => $paymentId,
        ':id_matricula' => $matriculaId,
        ':wallet_id' => $walletId,
        ':usuario_id' => $usuarioId,
        ':valor' => $valor,
        ':status' => $statusComissao,
        ':data_pagamento' => $dataPgto,
    ]);

    return true;
}

function handlePayload(
    ?string $payloadJson,
    PDO $pdo,
    PDOStatement $stmtMatricula,
    PDOStatement $stmtParcelaMatricula,
    PDOStatement $stmtMatriculaByAlunoCurso,
    PDOStatement $stmtUsuarioByNome,
    PDOStatement $stmtUsuarioPessoa,
    PDOStatement $stmtUsuarioByPessoa,
    bool $dryRun,
    array &$stats
): void {
    $payload = json_decode($payloadJson ?? '', true);
    if (!is_array($payload)) {
        $stats['invalidJson']++;
        $stats['skipped']++;
        return;
    }

    $stats['decoded']++;
    $payment = $payload['payment'] ?? ($payload['data']['payment'] ?? null);
    if (!is_array($payment)) {
        $stats['skipped']++;
        return;
    }
    $stats['withPayment']++;

    $paymentId = $payment['id'] ?? '';
    $status = $payment['status'] ?? '';
    $splits = $payment['split'] ?? [];
    if ($paymentId === '' || !is_array($splits) || empty($splits)) {
        $stats['missingSplit']++;
        $stats['skipped']++;
        return;
    }
    $stats['withSplit']++;

    $stmtMatricula->execute([':id' => $paymentId]);
    $matriculaId = $stmtMatricula->fetchColumn();
    $matriculaId = $matriculaId ? (int) $matriculaId : null;

    if (!$matriculaId) {
        $stmtParcelaMatricula->execute([':id' => $paymentId]);
        $matriculaId = $stmtParcelaMatricula->fetchColumn();
        $matriculaId = $matriculaId ? (int) $matriculaId : null;
        if ($matriculaId) {
            $stats['matchedByParcela']++;
        }
    }

    $descricao = $payment['description'] ?? null;
    $externalReference = $payment['externalReference'] ?? null;

    if (!$matriculaId) {
        $alunoId = extrairAlunoId($descricao);
        $cursoId = extrairCursoId($descricao, $externalReference);
        if (!$alunoId) {
            $nomeAluno = extrairNomeAluno($descricao);
            if ($nomeAluno) {
                $stmtUsuarioByNome->execute([':nome' => $nomeAluno]);
                $usuarios = $stmtUsuarioByNome->fetchAll(PDO::FETCH_COLUMN);
                if (count($usuarios) === 1) {
                    $alunoId = (int) $usuarios[0];
                    $stats['matchedByNome']++;
                }
            }
        }

        if ($alunoId && $cursoId) {
            $stmtMatriculaByAlunoCurso->execute([
                ':aluno' => $alunoId,
                ':curso' => $cursoId,
            ]);
            $matriculaId = $stmtMatriculaByAlunoCurso->fetchColumn();
            $matriculaId = $matriculaId ? (int) $matriculaId : null;
            if ($matriculaId) {
                $stats['matchedByAlunoCurso']++;
            }
        }

        if (!$matriculaId && $alunoId && $cursoId) {
            $stmtUsuarioPessoa->execute([':id' => $alunoId]);
            $alunoPessoaId = $stmtUsuarioPessoa->fetchColumn();
            $alunoPessoaId = $alunoPessoaId ? (int) $alunoPessoaId : null;
            if ($alunoPessoaId) {
                $stmtMatriculaByAlunoCurso->execute([
                    ':aluno' => $alunoPessoaId,
                    ':curso' => $cursoId,
                ]);
                $matriculaId = $stmtMatriculaByAlunoCurso->fetchColumn();
                $matriculaId = $matriculaId ? (int) $matriculaId : null;
                if ($matriculaId) {
                    $stats['matchedByAlunoPessoa']++;
                }
            }
        }

        if (!$matriculaId && $alunoId && $cursoId) {
            $stmtUsuarioByPessoa->execute([':id' => $alunoId]);
            $usuarioId = $stmtUsuarioByPessoa->fetchColumn();
            $usuarioId = $usuarioId ? (int) $usuarioId : null;
            if ($usuarioId) {
                $stmtMatriculaByAlunoCurso->execute([
                    ':aluno' => $usuarioId,
                    ':curso' => $cursoId,
                ]);
                $matriculaId = $stmtMatriculaByAlunoCurso->fetchColumn();
                $matriculaId = $matriculaId ? (int) $matriculaId : null;
                if ($matriculaId) {
                    $stats['matchedByPessoaUsuario']++;
                }
            }
        }
    }

    if (!$matriculaId) {
        $stats['missingMatricula']++;
    }

    $paymentDate = $payment['paymentDate'] ?? $payment['confirmedDate'] ?? $payment['clientPaymentDate'] ?? null;
    $paymentValue = isset($payment['value']) ? (float) str_replace(',', '.', (string) $payment['value']) : null;

    foreach ($splits as $split) {
        if (upsertSplit($pdo, $split, (string) $paymentId, $matriculaId, (string) $status, $paymentDate, $paymentValue, $dryRun)) {
            $stats['inserted']++;
        }
    }
    $stats['processed']++;
}

$stats = [
    'processed' => 0,
    'decoded' => 0,
    'invalidJson' => 0,
    'withPayment' => 0,
    'withSplit' => 0,
    'missingSplit' => 0,
    'inserted' => 0,
    'skipped' => 0,
    'missingMatricula' => 0,
    'matchedByParcela' => 0,
    'matchedByAlunoCurso' => 0,
    'matchedByAlunoPessoa' => 0,
    'matchedByPessoaUsuario' => 0,
    'matchedByNome' => 0,
];

$stmtMatricula = $pdo->prepare("SELECT id FROM matriculas WHERE id_asaas = :id LIMIT 1");
$stmtParcelaMatricula = $pdo->prepare("SELECT id_matricula FROM parcelas_geradas_por_boleto WHERE id_asaas = :id LIMIT 1");
$stmtMatriculaByAlunoCurso = $pdo->prepare("SELECT id FROM matriculas WHERE aluno = :aluno AND id_curso = :curso ORDER BY id DESC LIMIT 1");
$stmtUsuarioByNome = $pdo->prepare("SELECT u.id FROM usuarios u INNER JOIN alunos a ON a.id = u.id_pessoa WHERE a.nome = :nome LIMIT 2");
$stmtUsuarioPessoa = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
$stmtUsuarioByPessoa = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa = :id LIMIT 1");

if ($source === 'db') {
    $sql = "SELECT id, event_type, payload FROM webhook_logs ORDER BY id ASC";
    if ($limit > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    $stmtLogs = $pdo->prepare($sql);
    if ($limit > 0) {
        $stmtLogs->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtLogs->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmtLogs->execute();

    while ($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)) {
        handlePayload(
            $log['payload'] ?? null,
            $pdo,
            $stmtMatricula,
            $stmtParcelaMatricula,
            $stmtMatriculaByAlunoCurso,
            $stmtUsuarioByNome,
            $stmtUsuarioPessoa,
            $stmtUsuarioByPessoa,
            $dryRun,
            $stats
        );
    }
} else {
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        echo "Falha ao abrir arquivo de log: {$filePath}\n";
        exit(1);
    }

    $marker = 'Webhook recebido: ';
    $matched = 0;
    while (($line = fgets($handle)) !== false) {
        $pos = strpos($line, $marker);
        if ($pos === false) {
            continue;
        }

        $json = trim(substr($line, $pos + strlen($marker)));
        if ($json === '') {
            continue;
        }

        if ($offset > 0) {
            $offset--;
            continue;
        }

        handlePayload(
            $json,
            $pdo,
            $stmtMatricula,
            $stmtParcelaMatricula,
            $stmtMatriculaByAlunoCurso,
            $stmtUsuarioByNome,
            $stmtUsuarioPessoa,
            $stmtUsuarioByPessoa,
            $dryRun,
            $stats
        );
        $matched++;
        if ($limit > 0 && $matched >= $limit) {
            break;
        }
    }
    fclose($handle);
}

echo "Backfill concluido.\n";
echo "Logs processados: {$stats['processed']}\n";
echo "Logs decodificados: {$stats['decoded']}\n";
echo "Logs invalidos: {$stats['invalidJson']}\n";
echo "Logs com payment: {$stats['withPayment']}\n";
echo "Logs com split: {$stats['withSplit']}\n";
echo "Logs sem split: {$stats['missingSplit']}\n";
echo "Splits inseridos: {$stats['inserted']}\n";
echo "Logs ignorados: {$stats['skipped']}\n";
echo "Pagamentos sem matricula: {$stats['missingMatricula']}\n";
echo "Matriculas via parcela: {$stats['matchedByParcela']}\n";
echo "Matriculas via aluno/curso: {$stats['matchedByAlunoCurso']}\n";
echo "Matriculas via aluno pessoa: {$stats['matchedByAlunoPessoa']}\n";
echo "Matriculas via pessoa/usuario: {$stats['matchedByPessoaUsuario']}\n";
echo "Alunos encontrados por nome: {$stats['matchedByNome']}\n";
if ($dryRun) {
    echo "Modo dry-run: nenhum registro foi gravado.\n";
}
