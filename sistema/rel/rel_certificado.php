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
$html = utf8_encode(file_get_contents($url_sistema . "sistema/rel/certificado.php?id=" . $id . "&data=" . urlencode($data_certificado) . "&ano=" . urlencode($ano_certificado) . "&id_mat=" . urlencode($id_mat)));



//Definir o tamanho do papel e orientação da página
$pdf->set_paper('A4', 'landscape');

//CARREGAR O CONTEÚDO HTML
$pdf->load_html(utf8_decode($html));

//RENDERIZAR O PDF
$pdf->render();

//NOMEAR O PDF GERADO
$pdf->stream(
'certificado.pdf',
array("Attachment" => false)
);




?>
