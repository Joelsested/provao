<?php
require_once('../../config/env.php');
$mp_enabled = filter_var(env('MP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
if (!$mp_enabled) {
	echo '';
	exit();
}
 //includes para o mercado pago
include_once("../../pagamentos/mercadopago/lib/mercadopago.php");
include_once("../../pagamentos/mercadopago/PagamentoMP.php");
$pagar = new PagamentoMP;


require_once('../../sistema/conexao.php');

$id_aluno = $_POST['aluno'];
$id_do_curso_pag = $_POST['id'];
$nome = $_POST['nome'];
$pacote = $_POST['pacote'];

$id_aluno = (int) $id_aluno;
$id_do_curso_pag = (int) $id_do_curso_pag;

$stmt = $pdo->prepare("SELECT * FROM matriculas WHERE id_curso = ? AND aluno = ?");
$stmt->execute([$id_do_curso_pag, $id_aluno]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(@count($res) > 0){
	$id_matricula = $res[0]['id'];
	$valor_curso = $res[0]['subtotal'];

	if($valor_curso == 0){
		$valor_curso = 1;
	}

                           //botao do mercado pago                       
 $btn = $pagar->PagarMP($id_do_curso_pag, $nome, (float)$valor_curso, $url_sistema);
         echo $btn;

 echo '<div align="center"><i class="neutra"><small>(Parcele em atǸ 12 Vezes) <span class="neutra ocultar-mobile">Pagamento no Cartǜo ou Saldo</span></small></i></div>';
}

?>
                        

