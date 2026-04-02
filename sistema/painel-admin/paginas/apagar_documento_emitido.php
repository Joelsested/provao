<?php
require_once('../../conexao.php');
@session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nivel']) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
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
    echo json_encode(['success' => false, 'message' => 'ID do documento invalido.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmtDoc = $pdo->prepare("SELECT id, arquivo_relativo FROM documentos_emitidos WHERE id = :id LIMIT 1");
$stmtDoc->execute([':id' => $idDocumento]);
$doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Documento nao encontrado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$arquivoRelativo = ltrim((string)($doc['arquivo_relativo'] ?? ''), '/');
$arquivoRelativo = str_replace('\\', '/', $arquivoRelativo);
$arquivoRelativo = preg_replace('#\.\./#', '', $arquivoRelativo);

$raizProjeto = realpath(__DIR__ . '/../../..');
$arquivoCompleto = $raizProjeto ? realpath($raizProjeto . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $arquivoRelativo)) : false;

if ($arquivoCompleto && $raizProjeto && strpos($arquivoCompleto, $raizProjeto) === 0 && is_file($arquivoCompleto)) {
    @unlink($arquivoCompleto);
}

$stmtDelete = $pdo->prepare("DELETE FROM documentos_emitidos WHERE id = :id LIMIT 1");
$stmtDelete->execute([':id' => $idDocumento]);

echo json_encode(['success' => true, 'message' => 'Documento removido com sucesso.'], JSON_UNESCAPED_UNICODE);
exit();
?>
