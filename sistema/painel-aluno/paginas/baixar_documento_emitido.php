<?php
require_once(__DIR__ . '/../../conexao.php');
@session_start();

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Aluno') {
    http_response_code(403);
    echo 'Acesso negado.';
    exit();
}

$idDocumento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idDocumento) {
    http_response_code(400);
    echo 'ID invalido.';
    exit();
}
$view = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);

$idUsuario = (int)($_SESSION['id'] ?? 0);
$stmtAluno = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
$stmtAluno->execute([':id' => $idUsuario]);
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
$idPessoaAluno = (int)($aluno['id_pessoa'] ?? 0);

if ($idPessoaAluno <= 0) {
    http_response_code(403);
    echo 'Aluno nao identificado.';
    exit();
}

try {
    $stmtColuna = $pdo->query("SHOW COLUMNS FROM documentos_emitidos LIKE 'visivel_aluno'");
    if (!$stmtColuna || !$stmtColuna->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE documentos_emitidos ADD COLUMN visivel_aluno TINYINT(1) NOT NULL DEFAULT 1");
    }
} catch (Throwable $e) {
    // segue
}

$stmtDoc = $pdo->prepare("
    SELECT *
    FROM documentos_emitidos
    WHERE id = :id
      AND aluno_id = :aluno_id
      AND COALESCE(visivel_aluno, 1) = 1
      AND tipo IN ('certificado', 'historico', 'ficha_inscricao')
    LIMIT 1
");
$stmtDoc->execute([
    ':id' => $idDocumento,
    ':aluno_id' => $idPessoaAluno
]);
$doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    echo 'Documento nao encontrado ou indisponivel.';
    exit();
}

$arquivoRel = ltrim((string)$doc['arquivo_relativo'], '/');
$basePath = realpath(__DIR__ . '/../../../');
if (!$basePath) {
    http_response_code(500);
    echo 'Caminho base invalido.';
    exit();
}

$safeRel = str_replace(['\\', '..'], ['/', ''], $arquivoRel);
$fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRel);
if (!file_exists($fullPath)) {
    $safeRelComSistema = ltrim($safeRel, '/');
    if (strpos($safeRelComSistema, 'sistema/') !== 0) {
        $safeRelComSistema = 'sistema/' . $safeRelComSistema;
    }
    $fullPathAlt = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelComSistema);
    if (file_exists($fullPathAlt)) {
        $fullPath = $fullPathAlt;
    }
}

if (!file_exists($fullPath)) {
    http_response_code(404);
    echo 'Arquivo nao encontrado.';
    exit();
}

$nome = basename($fullPath);
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ((int)$view === 1 ? 'inline' : 'attachment') . '; filename="' . $nome . '"');
readfile($fullPath);
exit();
?>
