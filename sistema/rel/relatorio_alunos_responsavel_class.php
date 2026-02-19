<?php
include('../conexao.php');
@session_start();

require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

header("Content-Transfer-Encoding: binary");
header("Content-Type: image/png");

$options = new Options();
$options->set('isRemoteEnabled', true);
$pdf = new DOMPDF($options);

$data_inicial = $_POST['data_inicial'] ?? '';
$data_final = $_POST['data_final'] ?? '';
$nivel = $_POST['nivel'] ?? '';
$responsavel_id = $_POST['responsavel_id'] ?? '';
$status_filtro = $_POST['status_filtro'] ?? '';

$_GET['data_inicial'] = $data_inicial;
$_GET['data_final'] = $data_final;
$_GET['nivel'] = $nivel;
$_GET['responsavel_id'] = $responsavel_id;
$_GET['status_filtro'] = $status_filtro;

ob_start();
include __DIR__ . '/relatorio_alunos_responsavel.php';
$html = ob_get_clean();

$pdf->set_paper('A4', 'landscape');
$pdf->load_html(utf8_decode($html));
$pdf->render();

$pdf->stream(
    'relatorio_alunos_responsavel.pdf',
    array("Attachment" => false)
);
?>
