<?php
require_once(__DIR__ . '/../../conexao.php');
@session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nivel']) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$idDocumento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$alunoIdParam = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);

if (!$idDocumento && !$alunoIdParam) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Informe o ID do documento ou do aluno.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$doc = [];
$alunoId = 0;

if ($idDocumento) {
    $stmtDoc = $pdo->prepare("
        SELECT *
        FROM documentos_emitidos
        WHERE id = :id
          AND tipo = 'certificado'
        LIMIT 1
    ");
    $stmtDoc->execute([':id' => $idDocumento]);
    $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!$doc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Certificado nao encontrado.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $alunoId = (int)($doc['aluno_id'] ?? 0);
} else {
    $alunoId = (int)$alunoIdParam;

    $stmtUltimoDoc = $pdo->prepare("
        SELECT *
        FROM documentos_emitidos
        WHERE aluno_id = :aluno_id
          AND tipo = 'certificado'
          AND (categoria = 'medio' OR categoria IS NULL OR categoria = '')
        ORDER BY criado_em DESC, id DESC
        LIMIT 1
    ");
    $stmtUltimoDoc->execute([':aluno_id' => $alunoId]);
    $doc = $stmtUltimoDoc->fetch(PDO::FETCH_ASSOC) ?: [];

    // Se o aluno não possui certificado emitido, mantém os campos da modal em branco.
    if (!$doc) {
        echo json_encode([
            'success' => true,
            'dados' => [
                'id_documento' => 0,
                'aluno_id' => $alunoId,
                'ano_certificado' => '',
                'data_certificado' => '',
                'numero_registro' => '',
                'folha_livro' => '',
                'numero_livro' => ''
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS certificados_livro_registro (
        id INT AUTO_INCREMENT PRIMARY KEY,
        aluno_id INT NOT NULL,
        categoria VARCHAR(30) NOT NULL DEFAULT 'medio',
        matricula_id INT NULL,
        ano_certificado VARCHAR(4) NULL,
        data_certificado DATE NULL,
        numero_registro VARCHAR(30) NOT NULL,
        folha_livro VARCHAR(20) NOT NULL,
        numero_livro VARCHAR(20) NOT NULL,
        criado_em DATETIME NOT NULL,
        atualizado_em DATETIME NOT NULL,
        criado_por INT NULL,
        atualizado_por INT NULL,
        UNIQUE KEY uk_aluno_categoria (aluno_id, categoria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$stmtRegistro = $pdo->prepare("
    SELECT ano_certificado, data_certificado, numero_registro, folha_livro, numero_livro
    FROM certificados_livro_registro
    WHERE aluno_id = :aluno_id AND categoria = 'medio'
    LIMIT 1
");
$stmtRegistro->execute([':aluno_id' => $alunoId]);
$registro = $stmtRegistro->fetch(PDO::FETCH_ASSOC) ?: [];

$ano = (string)($doc['ano_certificado'] ?? '');
if ($ano === '') {
    $ano = (string)($registro['ano_certificado'] ?? '');
}
if ($ano === '' && !empty($doc['criado_em'])) {
    $ano = date('Y', strtotime((string)$doc['criado_em']));
}

$data = (string)($doc['data_certificado'] ?? '');
if ($data === '') {
    $data = (string)($registro['data_certificado'] ?? '');
}
if ($data === '' && !empty($doc['criado_em'])) {
    $data = date('Y-m-d', strtotime((string)$doc['criado_em']));
}

$numeroRegistro = (string)($doc['numero_registro'] ?? '');
if ($numeroRegistro === '') {
    $numeroRegistro = (string)($registro['numero_registro'] ?? '');
}

$folhaLivro = (string)($doc['folha_livro'] ?? '');
if ($folhaLivro === '') {
    $folhaLivro = (string)($registro['folha_livro'] ?? '');
}

$numeroLivro = (string)($doc['numero_livro'] ?? '');
if ($numeroLivro === '') {
    $numeroLivro = (string)($registro['numero_livro'] ?? '');
}

echo json_encode([
    'success' => true,
    'dados' => [
        'id_documento' => (int)($doc['id'] ?? 0),
        'aluno_id' => $alunoId,
        'ano_certificado' => $ano,
        'data_certificado' => $data,
        'numero_registro' => $numeroRegistro,
        'folha_livro' => $folhaLivro,
        'numero_livro' => $numeroLivro
    ]
], JSON_UNESCAPED_UNICODE);
exit();
