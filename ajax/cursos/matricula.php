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

function buscarUsuarioAtivoPorId(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

@session_start();
$id_aluno = $_SESSION['id'] ?? null;
$modoRetorno = strtolower((string) ($_POST['modo_retorno'] ?? 'texto'));
$retornoJson = $modoRetorno === 'json';

$responder = static function (string $mensagem, bool $sucesso = false, array $extra = []) use ($retornoJson): void {
    if ($retornoJson) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array_merge([
            'success' => $sucesso,
            'message' => $mensagem,
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit();
    }

    echo $mensagem;
    exit();
};

if (!$id_aluno) {
    $responder('Usuario nao autenticado!');
}

$allowedLevels = ['Administrador', 'Vendedor', 'Tutor', 'Secretario', 'Tesoureiro', 'Parceiro'];
$levelPlaceholders = implode(',', array_fill(0, count($allowedLevels), '?'));

$vendedorContextoId = (int) ($_SESSION['switch_vendedor_usuario_id'] ?? 0);
$vendedorContexto = buscarUsuarioAtivoPorId($pdo, $vendedorContextoId);
if ($vendedorContexto && ($vendedorContexto['nivel'] ?? '') !== 'Vendedor') {
    $vendedorContexto = null;
}

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
    $dataCadastroAluno = normalizeDate((string) ($aluno['data'] ?? '')) ?: date('Y-m-d');
}

$responsavelEfetivoId = (int) ($responsavelValido ?? 0);
if ($vendedorContexto) {
    // Regra: na pagina de vendedor, o vendedor sempre e o responsavel.
    $responsavelEfetivoId = (int) $vendedorContexto['id'];
} elseif ($responsavelEfetivoId <= 0 && $currentResponsavel > 0) {
    $responsavelEfetivoId = (int) (validarResponsavel($pdo, $currentResponsavel, $allowedLevels, $levelPlaceholders) ?? 0);
}

if ($responsavelEfetivoId <= 0 && $alunoPessoaId > 0) {
    $responder('Selecione um responsavel valido!');
}

if ($responsavelEfetivoId > 0 && $alunoPessoaId > 0) {
    $responsavelSelecionado = buscarResponsavel($pdo, $responsavelEfetivoId, $allowedLevels, $levelPlaceholders);
    if (!$responsavelSelecionado) {
        $responder('Responsavel invalido!');
    }

    $nivelResponsavel = (string) ($responsavelSelecionado['nivel'] ?? '');
    if (!$vendedorContexto && in_array($nivelResponsavel, ['Tutor', 'Secretario'], true)) {
        // Tutor/Secretario so podem ser responsaveis quando o aluno ja e deles.
        if ($currentResponsavel <= 0 || (int) $responsavelSelecionado['id'] !== (int) $currentResponsavel) {
            $responder('Tutor ou Secretario nao pode ser responsavel neste cadastro; use apenas como atendente.');
        }
    }

    $responsavelProfessor = responsavelEhProfessor($pdo, $responsavelSelecionado);
    $novoAtendente = $responsavelProfessor
        ? resolveAtendenteId($pdo, $responsavelSelecionado, $dataCadastroAluno)
        : (int) $responsavelSelecionado['id'];

    if ($responsavelProfessor) {
        if ($novoAtendente <= 0) {
            $responder('Responsavel com Professor marcado exige atendente ativo (Tutor ou Secretario).');
        }

        $stmtNivelDest = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
        $stmtNivelDest->execute([':id' => (int) $novoAtendente]);
        $nivelDestino = (string) ($stmtNivelDest->fetchColumn() ?: '');
        if (!in_array($nivelDestino, ['Tutor', 'Secretario'], true)) {
            $responder('Atendente invalido para responsavel com Professor marcado.');
        }
    }

    $responsavelMudou = (int) $responsavelSelecionado['id'] !== (int) $currentResponsavel;
    $atendenteMudou = $novoAtendente > 0 && $novoAtendente !== (int) $currentAtendente;

    if ($responsavelMudou || $atendenteMudou) {
        $bloqueioTroca = podeTrocarAtendente($pdo, (int) $alunoPessoaId, (int) $novoAtendente, date('Y-m-d'));
        if (!empty($bloqueioTroca['bloqueado'])) {
            $responder((string) ($bloqueioTroca['mensagem'] ?? 'Troca bloqueada por regra de comissao.'));
        }
    }

    $updates = [];
    $paramsUpdate = [':id' => $alunoPessoaId];

    if (ensureAlunosResponsavelColumn($pdo) && ($responsavelMudou || $currentResponsavel <= 0)) {
        $updates[] = 'responsavel_id = :responsavel_id';
        $paramsUpdate[':responsavel_id'] = (int) $responsavelSelecionado['id'];
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
    $responder('Aluno nao cadastrado com este e-mail!');
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
    $responder('Aluno ja matriculado neste curso!');
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
    $idMatriculaCriada = (int) $pdo->lastInsertId();
}

require_once('email-matricula.php');

$responder('Matriculado com Sucesso', true, [
    'id_matricula' => $idMatriculaCriada ?? 0,
    'id_curso' => (int) $curso,
    'pacote' => $pacote,
]);
