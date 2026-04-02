<?php
require_once(__DIR__ . '/../../conexao.php');
@session_start();

if (!isset($_SESSION['nivel']) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo 'ID invalido.';
    exit();
}
$view = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);

$stmt = $pdo->prepare('SELECT * FROM documentos_emitidos WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    echo 'Documento nao encontrado.';
    exit();
}

$arquivoRel = ltrim($doc['arquivo_relativo'], '/');
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

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

// Se ja for PDF, entrega direto
if ($ext === 'pdf') {
    $nome = basename($fullPath);
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ((int)$view === 1 ? 'inline' : 'attachment') . '; filename="' . $nome . '"');
    readfile($fullPath);
    exit();
}

// Converter HTML em PDF
if ($ext !== 'html' && $ext !== 'htm') {
    http_response_code(415);
    echo 'Formato nao suportado.';
    exit();
}

require_once(__DIR__ . '/../../dompdf/autoload.inc.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$html = file_get_contents($fullPath);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nomeArquivo = strtoupper($doc['tipo'] ?? 'documento');
if (!empty($doc['categoria'])) {
    $nomeArquivo .= '_' . strtoupper($doc['categoria']);
}
if (!empty($doc['versao'])) {
    $nomeArquivo .= '_V' . $doc['versao'];
}
$nomeArquivo .= '.pdf';

$dompdf->stream($nomeArquivo, ['Attachment' => true]);
exit();
?>
