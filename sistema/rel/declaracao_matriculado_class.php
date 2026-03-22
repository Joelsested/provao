<?php

include('../conexao.php');

@session_start();

if (!isset($_SESSION) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
    $json = json_encode(['error' => 'Voce nao esta autorizado a realizar essa operacao!'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo '' . highlight_string('' . $json, true) . '';
    return;
}

$id = $_GET['id'] ?? null;
$data_certificado = $_GET['data'] ?? null;
$ano_certificado = $_GET['ano'] ?? null;

require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Transfer-Encoding: binary');
header('Content-Type: image/png');

$options = new Options();
$options->set('isRemoteEnabled', true);
$pdf = new DOMPDF($options);

$__getBackup = $_GET;
$_GET['id'] = $id;
$_GET['data'] = $data_certificado;
$_GET['ano'] = $ano_certificado;
ob_start();
include __DIR__ . '/declaracao_matriculado.php';
$html = ob_get_clean();
$_GET = $__getBackup;

$pdf->set_paper('A4', 'portrait');
$pdf->load_html($html, 'UTF-8');
$pdf->render();

$pdf->stream('Declaracao Matriculado.pdf', array('Attachment' => false));

?>