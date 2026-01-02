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

$dataInicial = $_POST['dataInicial'] ?? '';
$dataFinal = $_POST['dataFinal'] ?? '';
$pago = $_POST['pago'] ?? '';
$usuario_id = $_POST['usuario_id'] ?? '';

$_GET['usuario_id'] = $usuario_id;
$_GET['dataInicial'] = $dataInicial;
$_GET['dataFinal'] = $dataFinal;
$_GET['pago'] = $pago;

ob_start();
include __DIR__ . '/comissoes_usuario.php';
$html = ob_get_clean();

$pdf->set_paper('A4', 'portrait');
$pdf->load_html(utf8_decode($html));
$pdf->render();

$pdf->stream(
	'comissoes_usuario.pdf',
	array("Attachment" => false)
);
?>
