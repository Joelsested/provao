<?php 

include('../conexao.php');

@session_start();

$nivel = $_SESSION['nivel'] ?? '';
$allowedLevels = [
    'Administrador',
    'Secretario',
    'Tesoureiro',
    'Professor',
    'Tutor',
    'Vendedor',
    'Parceiro',
    'Assessor',
    'Aluno'
];

if (!in_array($nivel, $allowedLevels, true)) {
    $json = json_encode(['error' => 'Voce nao esta autorizado a realizar essa operacao!'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo '' . highlight_string("" . $json, true) . '';
    return;
}

$id = $_GET['id'] ?? $_POST['id'] ?? null;
$id_mat = $_GET['id_mat'] ?? $_POST['id_mat'] ?? null;
$usuario_id = null;

if (!empty($id_mat)) {
    $stmt = $pdo->prepare("SELECT aluno FROM matriculas WHERE id = :id");
    $stmt->bindValue(':id', $id_mat, PDO::PARAM_INT);
    $stmt->execute();
    $matricula = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($matricula) {
        $usuario_id = (int) $matricula['aluno'];
        $stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id");
        $stmt->bindValue(':id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pessoa && empty($id)) {
            $id = (int) $pessoa['id_pessoa'];
        }
    }
}

if ($nivel === 'Aluno') {
    $sessaoId = $_SESSION['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id");
    $stmt->bindValue(':id', $sessaoId, PDO::PARAM_INT);
    $stmt->execute();
    $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_pessoa_sessao = $pessoa['id_pessoa'] ?? 0;

    if (!empty($id) && (int) $id !== (int) $id_pessoa_sessao) {
        $json = json_encode(['error' => 'Voce nao esta autorizado a acessar este certificado.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo '' . highlight_string("" . $json, true) . '';
        return;
    }

    $id = (int) $id_pessoa_sessao;

    if (!empty($id_mat) && !empty($usuario_id) && (int) $usuario_id !== (int) $sessaoId) {
        $json = json_encode(['error' => 'Voce nao esta autorizado a acessar este certificado.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo '' . highlight_string("" . $json, true) . '';
        return;
    }
}

if (empty($id)) {
    $json = json_encode(['error' => 'ID do aluno nao informado.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo '' . highlight_string("" . $json, true) . '';
    return;
}

// $data_certificado = $_GET['data'];
$data_certificado = $_GET['data'] ?? $_POST['data'] ?? null;

$ano_certificado = $_GET['ano'] ?? $_POST['ano'] ?? null;
$numero_registro = trim((string) ($_GET['numero_registro'] ?? $_POST['numero_registro'] ?? ''));
$folha_livro = trim((string) ($_GET['folha_livro'] ?? $_POST['folha_livro'] ?? ''));
$numero_livro = trim((string) ($_GET['numero_livro'] ?? $_POST['numero_livro'] ?? ''));

if ($numero_registro !== '') {
    $numero_registro = mb_substr(preg_replace('/\s+/u', ' ', $numero_registro), 0, 30);
}
if ($folha_livro !== '') {
    $folha_livro = mb_substr(preg_replace('/\s+/u', ' ', $folha_livro), 0, 20);
}
if ($numero_livro !== '') {
    $numero_livro = mb_substr(preg_replace('/\s+/u', ' ', $numero_livro), 0, 20);
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

$stmtRegistroSalvo = $pdo->prepare("
    SELECT ano_certificado, data_certificado, numero_registro, folha_livro, numero_livro
    FROM certificados_livro_registro
    WHERE aluno_id = :aluno_id AND categoria = 'medio'
    LIMIT 1
");
$stmtRegistroSalvo->execute([':aluno_id' => (int) $id]);
$registroSalvo = $stmtRegistroSalvo->fetch(PDO::FETCH_ASSOC);

if ($registroSalvo) {
    if (empty($ano_certificado)) {
        $ano_certificado = (string) ($registroSalvo['ano_certificado'] ?? '');
    }
    if (empty($data_certificado)) {
        $data_certificado = (string) ($registroSalvo['data_certificado'] ?? '');
    }
    if ($numero_registro === '') {
        $numero_registro = (string) ($registroSalvo['numero_registro'] ?? '');
    }
    if ($folha_livro === '') {
        $folha_livro = (string) ($registroSalvo['folha_livro'] ?? '');
    }
    if ($numero_livro === '') {
        $numero_livro = (string) ($registroSalvo['numero_livro'] ?? '');
    }
}

if ($numero_registro !== '' && $folha_livro !== '' && $numero_livro !== '') {
    $stmtUpsertRegistro = $pdo->prepare("
        INSERT INTO certificados_livro_registro
            (aluno_id, categoria, matricula_id, ano_certificado, data_certificado, numero_registro, folha_livro, numero_livro, criado_em, atualizado_em, criado_por, atualizado_por)
        VALUES
            (:aluno_id, 'medio', :matricula_id, :ano_certificado, :data_certificado, :numero_registro, :folha_livro, :numero_livro, NOW(), NOW(), :criado_por, :atualizado_por)
        ON DUPLICATE KEY UPDATE
            matricula_id = VALUES(matricula_id),
            ano_certificado = VALUES(ano_certificado),
            data_certificado = VALUES(data_certificado),
            numero_registro = VALUES(numero_registro),
            folha_livro = VALUES(folha_livro),
            numero_livro = VALUES(numero_livro),
            atualizado_em = NOW(),
            atualizado_por = VALUES(atualizado_por)
    ");
    $stmtUpsertRegistro->execute([
        ':aluno_id' => (int) $id,
        ':matricula_id' => !empty($id_mat) ? (int) $id_mat : null,
        ':ano_certificado' => $ano_certificado ?: null,
        ':data_certificado' => $data_certificado ?: null,
        ':numero_registro' => $numero_registro,
        ':folha_livro' => $folha_livro,
        ':numero_livro' => $numero_livro,
        ':criado_por' => $_SESSION['id'] ?? null,
        ':atualizado_por' => $_SESSION['id'] ?? null
    ]);
}

//CARREGAR DOMPDF
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

header("Content-Transfer-Encoding: binary");
header("Content-Type: image/png");

//INICIALIZAR A CLASSE DO DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$pdf = new DOMPDF($options);



//ALIMENTAR OS DADOS NO RELATÓRIO
// $html = utf8_encode(file_get_contents($url_sistema."sistema/rel/certificado.php?id=".$id));
$html = utf8_encode(file_get_contents(
    $url_sistema . "sistema/rel/certificado.php?id=" . $id .
    "&data=" . urlencode($data_certificado) .
    "&ano=" . urlencode($ano_certificado) .
    "&id_mat=" . urlencode($id_mat) .
    "&numero_registro=" . urlencode($numero_registro) .
    "&folha_livro=" . urlencode($folha_livro) .
    "&numero_livro=" . urlencode($numero_livro)
));



//Definir o tamanho do papel e orientação da página
$pdf->set_paper('A4', 'landscape');

//CARREGAR O CONTEÚDO HTML
$pdf->load_html(utf8_decode($html));

//RENDERIZAR O PDF
$pdf->render();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS documentos_emitidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        aluno_id INT NOT NULL,
        tipo VARCHAR(30) NOT NULL,
        categoria VARCHAR(30) NULL,
        versao INT NULL,
        arquivo_relativo VARCHAR(255) NOT NULL,
        visivel_aluno TINYINT(1) NOT NULL DEFAULT 1,
        ano_certificado VARCHAR(4) NULL,
        data_certificado DATE NULL,
        numero_registro VARCHAR(30) NULL,
        folha_livro VARCHAR(20) NULL,
        numero_livro VARCHAR(20) NULL,
        criado_em DATETIME NOT NULL,
        criado_por INT NULL,
        ip VARCHAR(45) NULL,
        INDEX idx_aluno_tipo (aluno_id, tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

try {
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
} catch (Throwable $e) {
    // Nao interrompe a emissao por falha de estrutura.
}

$saidaPdf = $pdf->output();
$docDir = __DIR__ . '/../documentos/certificados/' . $id;
if (!is_dir($docDir)) {
    mkdir($docDir, 0777, true);
}
$nomeArquivo = 'CERTIFICADO_' . $id . '_' . date('YmdHis') . '.pdf';
$caminhoCompleto = $docDir . '/' . $nomeArquivo;
file_put_contents($caminhoCompleto, $saidaPdf);

$arquivoRelativo = '/sistema/documentos/certificados/' . $id . '/' . $nomeArquivo;
$stmtDoc = $pdo->prepare("
    INSERT INTO documentos_emitidos (
        aluno_id, tipo, categoria, versao, arquivo_relativo,
        ano_certificado, data_certificado, numero_registro, folha_livro, numero_livro,
        criado_em, criado_por, ip
    )
    VALUES (
        :aluno_id, :tipo, :categoria, :versao, :arquivo_relativo,
        :ano_certificado, :data_certificado, :numero_registro, :folha_livro, :numero_livro,
        :criado_em, :criado_por, :ip
    )
");
$stmtDoc->execute([
    ':aluno_id' => (int) $id,
    ':tipo' => 'certificado',
    ':categoria' => 'medio',
    ':versao' => null,
    ':arquivo_relativo' => $arquivoRelativo,
    ':ano_certificado' => $ano_certificado ?: null,
    ':data_certificado' => $data_certificado ?: null,
    ':numero_registro' => $numero_registro !== '' ? $numero_registro : null,
    ':folha_livro' => $folha_livro !== '' ? $folha_livro : null,
    ':numero_livro' => $numero_livro !== '' ? $numero_livro : null,
    ':criado_em' => date('Y-m-d H:i:s'),
    ':criado_por' => $_SESSION['id'] ?? null,
    ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
]);

//NOMEAR O PDF GERADO
$pdf->stream(
'certificado.pdf',
array("Attachment" => false)
);




?>
