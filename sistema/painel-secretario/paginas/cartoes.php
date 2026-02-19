<?php 
require_once('../conexao.php');
require_once('verificar.php');

if(@$_SESSION['nivel'] != 'Aluno'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}

 ?>

 <div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px">
	


 <h3>Cartões Fidelidade</h3>
 <hr>

 <p>A cada <?php echo $cartoes_fidelidade ?> compras no site você ganha um curso a sua escolha!!</p>
<br><br>
<h4>Como Funciona?</h4>
<br>
 <p></i><small>Após efetuar <?php echo $cartoes_fidelidade ?> compras no site automáticamente ao se matricular em um curso irá aparecer uma opção na tela de pagamentos para usar seu cartão, de imediato o curso estará aprovado em seu painél, essa promoção só é valida para cursos, para pacotes não, pode escolher qualquer curso do site com valor até <b><?php echo $valor_max_cartao ?></b> reais ou se preferir poderá usar o código <b><i>cartao</i></b> no momento do pagamento para abater o valor de <b><?php echo $valor_max_cartao ?></b> reais na sua compra!!</small></i></p>
 

 <hr>

<div class="row">
 <?php 
for($i=1; $i<=$cartoes_fidelidade; $i++){ 
		if($cartao_aluno >= $i){
			$valor = 0;
		}else{
			$valor = 1;
		}
	?>

<div class="col-md-2 col-xs-4">
<img src="../img/logo.png" width="100%" style="filter: grayscale(<?php echo $valor ?>)">
</div>
<?php } ?>
</div>
 </div>