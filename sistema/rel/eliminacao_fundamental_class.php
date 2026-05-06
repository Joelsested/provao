<?php

include('../conexao.php');

@session_start();

if (!isset($_SESSION) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
    $json = json_encode(['error' => 'Voce nao esta autorizado a realizar essa operacao!'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo '' . highlight_string('' . $json, true) . '';
    return;
}

$id = $_GET['id'] ?? null;
$data_emissao = $_GET['data'] ?? null;

require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Transfer-Encoding: binary');
header('Content-Type: application/pdf');

$options = new Options();
$options->set('isRemoteEnabled', true);
$pdf = new DOMPDF($options);

$__getBackup = $_GET;
$_GET['id'] = $id;
$_GET['data'] = $data_emissao;
ob_start();
include __DIR__ . '/eliminacao_fundamental.php';
$html = ob_get_clean();
$_GET = $__getBackup;

$pdf->set_paper('A4', 'portrait');
$pdf->load_html($html, 'UTF-8');
$pdf->render();

$pdf->stream('Atestado Eliminacao Fundamental.pdf', array('Attachment' => false));

?>
