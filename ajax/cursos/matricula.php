<?php
require_once('../../sistema/conexao.php');
require_once(__DIR__ . '/../../helpers.php');

function buscarResponsavel(PDO $pdo, int $id, array $allowedLevels, string $placeholders): ?array
{
    $stmt = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = ? AND nivel IN ($placeholders) AND ativo = 'Sim' LIMIT 1");
    $params = array_merge([$id], $allowedLevels);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function validarResponsavel(PDO $pdo, int $id, array $allowedLevels, string $placeholders): ?int
{
    $responsavel = buscarResponsavel($pdo, $id, $allowedLevels, $placeholders);
    if (!$responsavel) {
        return null;
    }

    return (int) $responsavel['id'];
}

@session_start();
$id_aluno = $_SESSION['id'] ?? null;

if (!$id_aluno) {
    echo 'Usuario nao autenticado!';
    exit();
}

$allowedLevels = ['Administrador', 'Vendedor', 'Tutor', 'Secretario', 'Tesoureiro', 'Parceiro'];
$levelPlaceholders = implode(',', array_fill(0, count($allowedLevels), '?'));

$stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id AND nivel = 'Aluno' LIMIT 1");
$stmt->execute(['id' => $id_aluno]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$alunoPessoaId = (int) ($row['id_pessoa'] ?? 0);

$responsavelId = filter_input(INPUT_POST, 'responsavel', FILTER_VALIDATE_INT);
$responsavelValido = null;
if ($responsavelId) {
    $responsavelValido = validarResponsavel($pdo, $responsavelId, $allowedLevels, $levelPlaceholders);
}

$currentResponsavel = 0;
$currentAtendente = 0;
$dataCadastroAluno = date('Y-m-d');
if ($alunoPessoaId > 0) {
    ensureAlunosResponsavelColumn($pdo);
    $stmtAluno = $pdo->prepare("SELECT usuario, responsavel_id, data FROM alunos WHERE id = :id LIMIT 1");
    $stmtAluno->execute([':id' => $alunoPessoaId]);
    $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [];
    $currentAtendente = (int) ($aluno['usuario'] ?? 0);
    $currentResponsavel = (int) ($aluno['responsavel_id'] ?? 0);
    if ($currentResponsavel <= 0) {
        $currentResponsavel = $currentAtendente;
    }
    $dataCadastroAluno = normalizeDate((string) ($aluno['data'] ?? '')) ?: date('Y-m-d');
    if (!$responsavelValido && $currentResponsavel > 0) {
        $responsavelValido = validarResponsavel($pdo, $currentResponsavel, $allowedLevels, $levelPlaceholders);
    }
}

if (!$responsavelValido && $alunoPessoaId > 0) {
    echo 'Selecione um responsavel valido!';
    exit();
}

if ($responsavelValido && $alunoPessoaId > 0) {
    $responsavelSelecionado = buscarResponsavel($pdo, (int) $responsavelValido, $allowedLevels, $levelPlaceholders);
    if (!$responsavelSelecionado) {
        echo 'Responsavel invalido!';
        exit();
    }

    $responsavelProfessor = responsavelEhProfessor($pdo, $responsavelSelecionado);
    $novoAtendente = $responsavelProfessor
        ? resolveAtendenteId($pdo, $responsavelSelecionado, $dataCadastroAluno)
        : (int) $responsavelValido;

    if ($responsavelProfessor) {
        if ($novoAtendente <= 0) {
            echo 'Responsavel com Professor marcado exige atendente ativo (Tutor ou Secretario).';
            exit();
        }
        $stmtNivelDest = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
        $stmtNivelDest->execute([':id' => (int) $novoAtendente]);
        $nivelDestino = (string) ($stmtNivelDest->fetchColumn() ?: '');
        if (!in_array($nivelDestino, ['Tutor', 'Secretario'], true)) {
            echo 'Atendente invalido para responsavel com Professor marcado.';
            exit();
        }
    }

    $responsavelMudou = (int) $responsavelValido !== (int) $currentResponsavel;
    $atendenteMudou = $novoAtendente > 0 && $novoAtendente !== (int) $currentAtendente;

    if ($responsavelMudou || $atendenteMudou) {
        $bloqueioTroca = podeTrocarAtendente($pdo, (int) $alunoPessoaId, (int) $novoAtendente, date('Y-m-d'));
        if (!empty($bloqueioTroca['bloqueado'])) {
            echo (string) ($bloqueioTroca['mensagem'] ?? 'Troca bloqueada por regra de comissao.');
            exit();
        }
    }

    $updates = [];
    $paramsUpdate = [':id' => $alunoPessoaId];

    if (ensureAlunosResponsavelColumn($pdo) && ($responsavelMudou || $currentResponsavel <= 0)) {
        $updates[] = 'responsavel_id = :responsavel_id';
        $paramsUpdate[':responsavel_id'] = (int) $responsavelValido;
    }

    if ($novoAtendente > 0 && ($atendenteMudou || $currentAtendente <= 0)) {
        $updates[] = 'usuario = :usuario';
        $paramsUpdate[':usuario'] = (int) $novoAtendente;
    }

    if (!empty($updates)) {
        $sql = 'UPDATE alunos SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($paramsUpdate);
    }
}

$usuario = @$_POST['email'];
$curso = (int) $_POST['curso'];
$pacote = $_POST['pacote'];

if ($pacote == 'Sim') {
    $tabela = 'pacotes';
} else {
    $tabela = 'cursos';
}

if ($_SESSION['nivel'] == 'Aluno') {
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $query->execute([(int) $id_aluno]);
} else {
    $query = $pdo->prepare("SELECT * FROM usuarios where usuario = :usuario ");
    $query->bindValue(':usuario', "$usuario");
    $query->execute();
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) == 0) {
    echo 'Aluno nao cadastrado com este email!';
    exit();
} else {
    $id_aluno = $res[0]['id'];
    $nome_aluno = $res[0]['nome'];
    $email_aluno = $res[0]['usuario'];
}

$stmtCurso = $pdo->prepare("SELECT * FROM $tabela WHERE id = ?");
$stmtCurso->execute([$curso]);
$res = $stmtCurso->fetchAll(PDO::FETCH_ASSOC);
$valor = $res[0]['valor'];
$promocao = $res[0]['promocao'];
$nome_curso = $res[0]['nome'];
$professor = $res[0]['professor'];

if ($promocao > 0) {
    $valor = $promocao;
}

$stmtMat = $pdo->prepare("SELECT * FROM matriculas WHERE aluno = ? AND id_curso = ? AND pacote = ?");
$stmtMat->execute([(int) $id_aluno, $curso, $pacote]);
$res = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) > 0) {
    echo 'Aluno ja matriculado neste curso!';
    exit();
} else {
    if ($valor == '0') {
        $status = 'Matriculado';
    } else {
        $status = 'Aguardando';
    }

    $stmtInsert = $pdo->prepare("INSERT INTO matriculas SET id_curso = :curso, aluno = :aluno, professor = :professor, valor = :valor, data = curDate(), status = :status, pacote = :pacote, subtotal = :subtotal, aulas_concluidas = '1'");
    $stmtInsert->bindValue(':curso', $curso, PDO::PARAM_INT);
    $stmtInsert->bindValue(':aluno', (int) $id_aluno, PDO::PARAM_INT);
    $stmtInsert->bindValue(':professor', (int) $professor, PDO::PARAM_INT);
    $stmtInsert->bindValue(':valor', "$valor");
    $stmtInsert->bindValue(':status', $status);
    $stmtInsert->bindValue(':pacote', $pacote);
    $stmtInsert->bindValue(':subtotal', "$valor");
    $stmtInsert->execute();
}

echo 'Matriculado com Sucesso';

require_once('email-matricula.php');

