<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../../helpers.php");
@session_start();
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['nivel'] ?? '') !== 'Administrador') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Apenas administrador pode liberar matrículas para menores.'
    ]);
    exit;
}

$idAluno = (int) ($_POST['id'] ?? 0);
if ($idAluno <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Aluno inválido.'
    ]);
    exit;
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
        // Mantem fluxo; falha sera tratada ao atualizar.
    }
}

function idadeCompletaAnosLocal(string $dataNascimento, ?DateTimeImmutable $hojeRef = null): int
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

garantirColunaLiberacaoMenorLocal($pdo);

$stmtAluno = $pdo->prepare("SELECT id, nascimento, COALESCE(liberado_menor_18, 0) AS liberado_menor_18 FROM alunos WHERE id = :id LIMIT 1");
$stmtAluno->execute([':id' => $idAluno]);
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: null;
if (!$aluno) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Aluno não encontrado.'
    ]);
    exit;
}

$idade = idadeCompletaAnosLocal((string) ($aluno['nascimento'] ?? ''));
if ($idade < 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Data de nascimento inválida.'
    ]);
    exit;
}

if ($idade >= 18) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Aluno já possui 18 anos ou mais.'
    ]);
    exit;
}

if ((int) ($aluno['liberado_menor_18'] ?? 0) === 1) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Aluno já está liberado para matrícula de menor.'
    ]);
    exit;
}

$stmtUpdate = $pdo->prepare("UPDATE alunos SET liberado_menor_18 = 1 WHERE id = :id LIMIT 1");
$stmtUpdate->execute([':id' => $idAluno]);

echo json_encode([
    'status' => 'success',
    'message' => 'Liberação de menor registrada com sucesso.'
]);
