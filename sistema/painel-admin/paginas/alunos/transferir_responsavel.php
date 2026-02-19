<?php
require_once("../../../conexao.php");
require_once(__DIR__ . '/../../../../helpers.php');
@session_start();

if (empty($_SESSION['nivel']) || !in_array($_SESSION['nivel'], ['Administrador', 'Secretario'], true)) {
    http_response_code(403);
    echo 'Sem permissao.';
    exit();
}

$isAdmin = (($_SESSION['nivel'] ?? '') === 'Administrador');
$adminOverrideTroca = $isAdmin && getConfigAdminOverrideTrocaAtendente($pdo);

$aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_VALIDATE_INT);
$novo_responsavel = filter_input(INPUT_POST, 'responsavel_id', FILTER_VALIDATE_INT);
$motivo = trim((string) ($_POST['motivo'] ?? ''));
$dataTransferencia = trim((string) ($_POST['data_transferencia_atendente'] ?? ''));

if (!$aluno_id || !$novo_responsavel) {
    echo 'Dados invalidos.';
    exit();
}

ensureAlunosResponsavelColumn($pdo);
$stmtAluno = $pdo->prepare("SELECT usuario, data, data_transferencia_atendente, responsavel_id FROM alunos WHERE id = :id LIMIT 1");
$stmtAluno->execute([':id' => $aluno_id]);
$alunoRow = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [];
$atendenteAtual = (int) ($alunoRow['usuario'] ?? 0);
$responsavelCadastro = (int) ($alunoRow['responsavel_id'] ?? 0);
$responsavelCadastro = $responsavelCadastro > 0 ? $responsavelCadastro : $atendenteAtual;

if ($atendenteAtual <= 0) {
    echo 'Aluno nao encontrado.';
    exit();
}
if ($atendenteAtual === (int) $novo_responsavel) {
    echo 'Aluno ja esta com este atendente.';
    exit();
}

$stmtRespCadastro = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
$stmtRespCadastro->execute([':id' => $responsavelCadastro]);
$responsavelRow = $stmtRespCadastro->fetch(PDO::FETCH_ASSOC) ?: [];
$responsavelProfessor = $responsavelRow && responsavelEhProfessor($pdo, $responsavelRow);
if (!$responsavelProfessor && !$adminOverrideTroca) {
    echo 'Responsavel sem Professor nao permite trocar atendente.';
    exit();
}

$allowedLevels = ['Tutor', 'Secretario'];
$placeholders = implode(',', array_fill(0, count($allowedLevels), '?'));
$stmtResp = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND nivel IN ($placeholders) AND ativo = 'Sim' LIMIT 1");
$stmtResp->execute(array_merge([$novo_responsavel], $allowedLevels));
if (!$stmtResp->fetchColumn()) {
    echo 'Atendente invalido.';
    exit();
}

$dataTransferenciaNorm = normalizeDate($dataTransferencia);
if ($dataTransferencia !== '' && $dataTransferenciaNorm === '') {
    echo 'Data de transferencia invalida.';
    exit();
}
if ($dataTransferenciaNorm === '') {
    $dataTransferenciaNorm = date('Y-m-d');
}
$dataMatricula = normalizeDate((string) ($alunoRow['data'] ?? ''));
if ($dataMatricula !== '' && $dataTransferenciaNorm < $dataMatricula) {
    echo 'Data de transferencia nao pode ser anterior a data de matricula.';
    exit();
}

if (!$adminOverrideTroca) {
    $bloqueioTroca = podeTrocarAtendente($pdo, (int) $aluno_id, (int) $novo_responsavel, $dataTransferenciaNorm);
    if (!empty($bloqueioTroca['bloqueado'])) {
        echo (string) ($bloqueioTroca['mensagem'] ?? 'Troca bloqueada por regra de comissao.');
        exit();
    }
}

$hasTransferCol = tableHasColumn($pdo, 'alunos', 'data_transferencia_atendente');
if (!$hasTransferCol) {
    try {
        $pdo->exec("ALTER TABLE alunos ADD COLUMN data_transferencia_atendente DATE DEFAULT NULL");
    } catch (Exception $e) {
        // sem bloqueio
    }
    $hasTransferCol = tableHasColumn($pdo, 'alunos', 'data_transferencia_atendente');
}

$updateSql = "UPDATE alunos SET usuario = :novo";
$paramsUpdate = [
    ':novo' => $novo_responsavel,
    ':id' => $aluno_id
];
if ($hasTransferCol) {
    $updateSql .= ", data_transferencia_atendente = :data_transferencia";
    $paramsUpdate[':data_transferencia'] = $dataTransferenciaNorm;
}
$updateSql .= " WHERE id = :id";
$stmtUpdate = $pdo->prepare($updateSql);
$stmtUpdate->execute($paramsUpdate);

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

    $stmtLog = $pdo->prepare("INSERT INTO transferencias_atendentes SET aluno_id = :aluno_id, usuario_anterior = :anterior, usuario_novo = :novo, motivo = :motivo, admin_id = :admin_id, data = NOW()");
    $stmtLog->execute([
        ':aluno_id' => $aluno_id,
        ':anterior' => $atendenteAtual,
        ':novo' => $novo_responsavel,
        ':motivo' => $motivo !== '' ? $motivo : null,
        ':admin_id' => (int) ($_SESSION['id'] ?? 0),
    ]);
} catch (Exception $e) {
    // Falha no log nao impede a transferencia
}

registrarHistoricoAtendente(
    $pdo,
    (int) $aluno_id,
    (int) $atendenteAtual,
    (int) $novo_responsavel,
    $motivo !== '' ? $motivo : null,
    'transferencia',
    (int) ($_SESSION['id'] ?? 0),
    $dataTransferenciaNorm
);

echo 'Transferido com sucesso';
