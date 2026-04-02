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
$visivelAtual = filter_input(INPUT_POST, 'visivel_atual', FILTER_VALIDATE_INT);

if (!$idDocumento) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do documento invalido.'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $stmtColuna = $pdo->query("SHOW COLUMNS FROM documentos_emitidos LIKE 'visivel_aluno'");
    if (!$stmtColuna || !$stmtColuna->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE documentos_emitidos ADD COLUMN visivel_aluno TINYINT(1) NOT NULL DEFAULT 1");
    }
} catch (Throwable $e) {
    // segue para tentativa de update
}

$novoStatus = ((int)$visivelAtual === 1) ? 0 : 1;

$stmtUpdate = $pdo->prepare("UPDATE documentos_emitidos SET visivel_aluno = :visivel WHERE id = :id LIMIT 1");
$stmtUpdate->execute([
    ':visivel' => $novoStatus,
    ':id' => $idDocumento
]);

if ($stmtUpdate->rowCount() === 0) {
    $stmtExiste = $pdo->prepare("SELECT id FROM documentos_emitidos WHERE id = :id LIMIT 1");
    $stmtExiste->execute([':id' => $idDocumento]);
    if (!$stmtExiste->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Documento nao encontrado.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

echo json_encode([
    'success' => true,
    'visivel_aluno' => $novoStatus,
    'message' => $novoStatus === 1 ? 'Documento liberado para o aluno.' : 'Documento ocultado do aluno.'
], JSON_UNESCAPED_UNICODE);
exit();
?>
