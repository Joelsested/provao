<?php
require_once('../../conexao.php');
require_once('../verificar.php');
@session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Aluno') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$idDocumento = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$idDocumento) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalido.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$idUsuario = (int) ($_SESSION['id'] ?? 0);
$stmtAluno = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
$stmtAluno->execute([':id' => $idUsuario]);
$idPessoaAluno = (int) ($stmtAluno->fetchColumn() ?: 0);

if ($idPessoaAluno <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Aluno nao identificado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmtDoc = $pdo->prepare("
    SELECT id, arquivo_relativo
    FROM documentos_emitidos
    WHERE id = :id
      AND aluno_id = :aluno_id
      AND tipo = 'ficha_inscricao'
    LIMIT 1
");
$stmtDoc->execute([
    ':id' => $idDocumento,
    ':aluno_id' => $idPessoaAluno
]);
$doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ficha nao encontrada.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$arquivoRelativo = ltrim((string) ($doc['arquivo_relativo'] ?? ''), '/');
$arquivoRelativo = str_replace('\\', '/', $arquivoRelativo);
$arquivoRelativo = preg_replace('#\.\./#', '', $arquivoRelativo);

$raizProjeto = realpath(__DIR__ . '/../../..');
$arquivoCompleto = $raizProjeto ? realpath($raizProjeto . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $arquivoRelativo)) : false;

if ($arquivoCompleto && $raizProjeto && strpos($arquivoCompleto, $raizProjeto) === 0 && is_file($arquivoCompleto)) {
    @unlink($arquivoCompleto);
} elseif ($raizProjeto) {
    $arquivoMontado = $raizProjeto . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $arquivoRelativo);
    if (is_file($arquivoMontado)) {
        @unlink($arquivoMontado);
    }
}

$stmtDelete = $pdo->prepare("DELETE FROM documentos_emitidos WHERE id = :id LIMIT 1");
$stmtDelete->execute([':id' => $idDocumento]);

echo json_encode(['success' => true, 'message' => 'Ficha excluida com sucesso.'], JSON_UNESCAPED_UNICODE);
exit();
?>
