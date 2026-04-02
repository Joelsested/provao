<?php
require_once(__DIR__ . '/../../conexao.php');
@session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['nivel']) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$idDocumento = filter_input(INPUT_POST, 'id_documento', FILTER_VALIDATE_INT);
$anoCertificado = trim((string)($_POST['ano_certificado'] ?? ''));
$dataCertificado = trim((string)($_POST['data_certificado'] ?? ''));
$numeroRegistro = trim((string)($_POST['numero_registro'] ?? ''));
$folhaLivro = trim((string)($_POST['folha_livro'] ?? ''));
$numeroLivro = trim((string)($_POST['numero_livro'] ?? ''));

if (!$idDocumento) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do documento invalido.'], JSON_UNESCAPED_UNICODE);
    exit();
}
if (!preg_match('/^\d{4}$/', $anoCertificado)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Ano do certificado invalido.'], JSON_UNESCAPED_UNICODE);
    exit();
}
$dataObj = DateTime::createFromFormat('Y-m-d', $dataCertificado);
if (!$dataObj || $dataObj->format('Y-m-d') !== $dataCertificado) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Data do certificado invalida.'], JSON_UNESCAPED_UNICODE);
    exit();
}
if ($numeroRegistro === '' || $folhaLivro === '' || $numeroLivro === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Preencha numero de registro, folha e numero do livro.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$numeroRegistro = mb_substr(preg_replace('/\s+/u', ' ', $numeroRegistro), 0, 30);
$folhaLivro = mb_substr(preg_replace('/\s+/u', ' ', $folhaLivro), 0, 20);
$numeroLivro = mb_substr(preg_replace('/\s+/u', ' ', $numeroLivro), 0, 20);

$stmtDoc = $pdo->prepare("
    SELECT id, aluno_id, tipo, categoria
    FROM documentos_emitidos
    WHERE id = :id
      AND tipo = 'certificado'
    LIMIT 1
");
$stmtDoc->execute([':id' => $idDocumento]);
$doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Certificado nao encontrado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $pdo->beginTransaction();

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

    $colunasDoc = [
        "ano_certificado" => "ALTER TABLE documentos_emitidos ADD COLUMN ano_certificado VARCHAR(4) NULL",
        "data_certificado" => "ALTER TABLE documentos_emitidos ADD COLUMN data_certificado DATE NULL",
        "numero_registro" => "ALTER TABLE documentos_emitidos ADD COLUMN numero_registro VARCHAR(30) NULL",
        "folha_livro" => "ALTER TABLE documentos_emitidos ADD COLUMN folha_livro VARCHAR(20) NULL",
        "numero_livro" => "ALTER TABLE documentos_emitidos ADD COLUMN numero_livro VARCHAR(20) NULL",
    ];
    foreach ($colunasDoc as $nomeColuna => $sqlAddColuna) {
        $stmtCol = $pdo->query("SHOW COLUMNS FROM documentos_emitidos LIKE " . $pdo->quote($nomeColuna));
        if (!$stmtCol || !$stmtCol->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec($sqlAddColuna);
        }
    }

    $categoria = !empty($doc['categoria']) ? (string)$doc['categoria'] : 'medio';

    $stmtUpsertRegistro = $pdo->prepare("
        INSERT INTO certificados_livro_registro
            (aluno_id, categoria, matricula_id, ano_certificado, data_certificado, numero_registro, folha_livro, numero_livro, criado_em, atualizado_em, criado_por, atualizado_por)
        VALUES
            (:aluno_id, :categoria, NULL, :ano_certificado, :data_certificado, :numero_registro, :folha_livro, :numero_livro, NOW(), NOW(), :usuario_id, :usuario_id)
        ON DUPLICATE KEY UPDATE
            ano_certificado = VALUES(ano_certificado),
            data_certificado = VALUES(data_certificado),
            numero_registro = VALUES(numero_registro),
            folha_livro = VALUES(folha_livro),
            numero_livro = VALUES(numero_livro),
            atualizado_em = NOW(),
            atualizado_por = VALUES(atualizado_por)
    ");
    $stmtUpsertRegistro->execute([
        ':aluno_id' => (int)$doc['aluno_id'],
        ':categoria' => $categoria,
        ':ano_certificado' => $anoCertificado,
        ':data_certificado' => $dataCertificado,
        ':numero_registro' => $numeroRegistro,
        ':folha_livro' => $folhaLivro,
        ':numero_livro' => $numeroLivro,
        ':usuario_id' => $_SESSION['id'] ?? null
    ]);

    $stmtUpdateDoc = $pdo->prepare("
        UPDATE documentos_emitidos
        SET ano_certificado = :ano_certificado,
            data_certificado = :data_certificado,
            numero_registro = :numero_registro,
            folha_livro = :folha_livro,
            numero_livro = :numero_livro
        WHERE id = :id
        LIMIT 1
    ");
    $stmtUpdateDoc->execute([
        ':ano_certificado' => $anoCertificado,
        ':data_certificado' => $dataCertificado,
        ':numero_registro' => $numeroRegistro,
        ':folha_livro' => $folhaLivro,
        ':numero_livro' => $numeroLivro,
        ':id' => (int)$doc['id']
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Falha ao salvar os dados do certificado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Dados do certificado atualizados com sucesso.'
], JSON_UNESCAPED_UNICODE);
exit();

