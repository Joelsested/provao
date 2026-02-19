<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Este script deve ser executado via CLI.\n";
    exit(1);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/cli/scripts/corrigir_atendentes_retroativo.php';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require __DIR__ . '/../sistema/conexao.php';
require __DIR__ . '/../helpers.php';

$options = getopt('', ['apply', 'admin-id::', 'limit::', 'sample::', 'aluno-id::']);
$apply = isset($options['apply']);
$adminId = isset($options['admin-id']) ? (int) $options['admin-id'] : 0;
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : 0;
$sampleSize = isset($options['sample']) ? max(1, (int) $options['sample']) : 10;
$alunoIdFilter = isset($options['aluno-id']) ? max(1, (int) $options['aluno-id']) : 0;

ensureAlunosResponsavelColumn($pdo);

$sql = "SELECT id, nome, usuario, COALESCE(NULLIF(responsavel_id, 0), usuario) AS responsavel_id, data FROM alunos";
$params = [];
if ($alunoIdFilter > 0) {
    $sql .= " WHERE id = :id";
    $params[':id'] = $alunoIdFilter;
}
$sql .= " ORDER BY id";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total_lidos' => count($alunos),
    'resp_eq_atend' => 0,
    'resp_professor' => 0,
    'sem_mapeamento' => 0,
    'bloqueados' => 0,
    'candidatos' => 0,
    'aplicados' => 0,
];

$responsavelCache = [];
$changes = [];
$sample = [];

foreach ($alunos as $row) {
    $alunoId = (int) ($row['id'] ?? 0);
    $nomeAluno = (string) ($row['nome'] ?? '');
    $atendenteAtual = (int) ($row['usuario'] ?? 0);
    $responsavelId = (int) ($row['responsavel_id'] ?? 0);
    $dataAluno = normalizeDate((string) ($row['data'] ?? ''));

    if ($alunoId <= 0 || $atendenteAtual <= 0 || $responsavelId <= 0) {
        continue;
    }
    if ($responsavelId !== $atendenteAtual) {
        continue;
    }

    $stats['resp_eq_atend']++;

    if (!array_key_exists($responsavelId, $responsavelCache)) {
        $stmtResp = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
        $stmtResp->execute([':id' => $responsavelId]);
        $responsavelCache[$responsavelId] = $stmtResp->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $responsavel = $responsavelCache[$responsavelId];
    if (!$responsavel || !responsavelEhProfessor($pdo, $responsavel)) {
        continue;
    }

    $stats['resp_professor']++;

    $novoAtendente = resolveAtendenteId($pdo, $responsavel, $dataAluno !== '' ? $dataAluno : null);
    if ($novoAtendente <= 0 || $novoAtendente === $atendenteAtual) {
        $nivelResp = (string) ($responsavel['nivel'] ?? '');
        if ($nivelResp === 'Vendedor' || $nivelResp === 'Parceiro') {
            $fallbackAtendente = resolveAtendenteId($pdo, $responsavel, null);
            if ($fallbackAtendente > 0 && $fallbackAtendente !== $atendenteAtual) {
                $novoAtendente = $fallbackAtendente;
            }
        }
    }
    if ($novoAtendente <= 0 || $novoAtendente === $atendenteAtual) {
        $stats['sem_mapeamento']++;
        continue;
    }

    $check = podeTrocarAtendente($pdo, $alunoId, $novoAtendente, date('Y-m-d'));
    if (!empty($check['bloqueado'])) {
        $stats['bloqueados']++;
        if (count($sample) < $sampleSize) {
            $sample[] = [
                'id' => $alunoId,
                'nome' => $nomeAluno,
                'responsavel' => $responsavelId,
                'atual' => $atendenteAtual,
                'novo' => $novoAtendente,
                'status' => 'bloqueado',
                'mensagem' => (string) ($check['mensagem'] ?? ''),
            ];
        }
        continue;
    }

    $stats['candidatos']++;
    $change = [
        'id' => $alunoId,
        'nome' => $nomeAluno,
        'responsavel' => $responsavelId,
        'atual' => $atendenteAtual,
        'novo' => $novoAtendente,
    ];
    $changes[] = $change;

    if (count($sample) < $sampleSize) {
        $sample[] = $change + ['status' => 'ok', 'mensagem' => ''];
    }

    if ($limit > 0 && count($changes) >= $limit) {
        break;
    }
}

$dataCorte = getConfigDateCorteAtendente($pdo);
$modo = $apply ? 'APPLY' : 'DRY-RUN';

echo "modo={$modo}\n";
echo "data_corte=" . ($dataCorte !== '' ? $dataCorte : '(vazia)') . "\n";
echo "total_lidos={$stats['total_lidos']}\n";
echo "resp_eq_atend={$stats['resp_eq_atend']}\n";
echo "resp_professor={$stats['resp_professor']}\n";
echo "sem_mapeamento={$stats['sem_mapeamento']}\n";
echo "bloqueados={$stats['bloqueados']}\n";
echo "candidatos={$stats['candidatos']}\n";

if ($apply && !empty($changes)) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS transferencias_atendentes (
            id int(11) NOT NULL AUTO_INCREMENT,
            aluno_id int(11) NOT NULL,
            usuario_anterior int(11) NOT NULL,
            usuario_novo int(11) NOT NULL,
            motivo varchar(255) DEFAULT NULL,
            admin_id int(11) NOT NULL,
            data datetime NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $stmtUpdate = $pdo->prepare("UPDATE alunos
            SET usuario = :novo,
                responsavel_id = CASE
                    WHEN responsavel_id IS NULL OR responsavel_id = 0 THEN :responsavel
                    ELSE responsavel_id
                END
            WHERE id = :id AND usuario = :atual");
        $stmtLog = $pdo->prepare("INSERT INTO transferencias_atendentes SET aluno_id = :aluno_id, usuario_anterior = :anterior, usuario_novo = :novo, motivo = :motivo, admin_id = :admin_id, data = NOW()");

        foreach ($changes as $change) {
            $stmtUpdate->execute([
                ':novo' => (int) $change['novo'],
                ':responsavel' => (int) $change['responsavel'],
                ':id' => (int) $change['id'],
                ':atual' => (int) $change['atual'],
            ]);
            if ($stmtUpdate->rowCount() <= 0) {
                continue;
            }

            $stmtLog->execute([
                ':aluno_id' => (int) $change['id'],
                ':anterior' => (int) $change['atual'],
                ':novo' => (int) $change['novo'],
                ':motivo' => 'Correcao retroativa automatica',
                ':admin_id' => $adminId,
            ]);

            registrarHistoricoAtendente(
                $pdo,
                (int) $change['id'],
                (int) $change['atual'],
                (int) $change['novo'],
                'Correcao retroativa automatica',
                'correcao_retroativa',
                $adminId > 0 ? $adminId : null
            );

            $stats['aplicados']++;
        }
    } catch (Throwable $e) {
        echo "erro=" . $e->getMessage() . "\n";
        exit(1);
    }
}

if ($apply) {
    echo "aplicados={$stats['aplicados']}\n";
}

echo "amostra:\n";
foreach ($sample as $item) {
    echo json_encode($item, JSON_UNESCAPED_UNICODE) . "\n";
}

if (!$apply) {
    echo "para_aplicar=php scripts/corrigir_atendentes_retroativo.php --apply --admin-id=SEU_USUARIO_ADMIN\n";
}

