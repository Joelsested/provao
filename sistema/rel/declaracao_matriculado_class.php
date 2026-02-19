<?php

include('../conexao.php');

@session_start();

if (!isset($_SESSION) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
    $json = json_encode(['error' => 'Voce nao esta autorizado a realizar essa operacao!'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo '' . highlight_string("" . $json, true) . '';
    return;
}

$id_user = $_SESSION['id'];

$id = $_GET['id'];
$data_certificado = $_GET['data'] ?? null;
$ano_certificado = $_GET['ano'] ?? null;

// CARREGAR DOMPDF
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

header("Content-Transfer-Encoding: binary");
header("Content-Type: image/png");

// INICIALIZAR A CLASSE DO DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$pdf = new DOMPDF($options);

// ALIMENTAR OS DADOS NO RELATORIO
$html = utf8_encode(file_get_contents($url_sistema . "sistema/rel/declaracao_matriculado.php?id=$id&data=$data_certificado&ano=$ano_certificado"));

// Definir o tamanho do papel e orientacao da pagina
$pdf->set_paper('A4', 'portrait');

// CARREGAR O CONTEUDO HTML
$pdf->load_html(utf8_decode($html));

// RENDERIZAR O PDF
$pdf->render();

// NOMEAR O PDF GERADO
$pdf->stream(
    'Declaracao Matriculado.pdf',
    array("Attachment" => false)
);

?>
