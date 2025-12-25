<?php 
include('../conexao.php');

$dataInicial = $_GET['dataInicial'];
$dataFinal = $_GET['dataFinal'];

$dataInicialF = implode('/', array_reverse(explode('-', $dataInicial)));
$dataFinalF = implode('/', array_reverse(explode('-', $dataFinal)));


if($dataInicial == $dataFinal){
	$texto_apuracao = 'APURADO EM '.$dataInicialF;
}else if($dataInicial == '1980-01-01'){
	$texto_apuracao = 'APURADO EM TODO O PERÍODO';
}else{
	$texto_apuracao = 'APURAÇÃO DE '.$dataInicialF. ' ATÉ '.$dataFinalF;
}


setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Porto_Velho');
$data_hoje = utf8_encode(strftime('%A, %d de %B de %Y', strtotime('today')));


?>

<!DOCTYPE html>
<html>
<head>
	<title>Detalhamento de Lucro</title>	

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">

	<style>

		@page {
			margin: 0px;

		}

		body{
			margin-top:0px;
			font-family:Times, "Times New Roman", Georgia, serif;
		}		

			.footer {
				margin-top:20px;
				width:100%;
				background-color: #ebebeb;
				padding:5px;
				position:absolute;
				bottom:0;
			}

		

		.cabecalho {    
			padding:10px;
			margin-bottom:30px;
			width:100%;
			font-family:Times, "Times New Roman", Georgia, serif;
		}

		.titulo_cab{
			color:#0340a3;
			font-size:17px;
		}

		
		
		.titulo{
			margin:0;
			font-size:28px;
			font-family:Arial, Helvetica, sans-serif;
			color:#6e6d6d;

		}

		.subtitulo{
			margin:0;
			font-size:12px;
			font-family:Arial, Helvetica, sans-serif;
			color:#6e6d6d;
		}



		hr{
			margin:8px;
			padding:0px;
		}


		
		.area-cab{
			
			display:block;
			width:100%;
			height:10px;

		}

		
		.coluna{
			margin: 0px;
			float:left;
			height:30px;
		}

		.area-tab{
			
			display:block;
			width:100%;
			height:30px;

		}


		.imagem {
			width: 200px;
			position:absolute;
			right:20px;
			top:10px;
		}

		.titulo_img {
			position: absolute;
			margin-top: 10px;
			margin-left: 10px;

		}

		.data_img {
			position: absolute;
			margin-top: 40px;
			margin-left: 10px;
			border-bottom:1px solid #000;
			font-size: 10px;
		}

		.endereco {
			position: absolute;
			margin-top: 50px;
			margin-left: 10px;
			border-bottom:1px solid #000;
			font-size: 10px;
		}

		.verde{
			color:green;
		}
		

	</style>


</head>
<body>	



<div class="titulo_cab titulo_img"><u>Detalhamento de Lucro </u></div>	
	<div class="data_img"><?php echo mb_strtoupper($data_hoje) ?></div>

	<img class="imagem" src="<?php echo $url_sistema ?>/sistema/img/logo_rel.jpg" width="200px" height="47">

	
	<br><br><br>
	<div class="cabecalho" style="border-bottom: solid 1px #0340a3">
	</div>

	<div class="mx-2" style="padding-top:10px ">

		<section class="area-cab">
			
			<div class="coluna" style="width:50%">
				<small><small><small><u><?php echo $texto_apuracao ?></u></small></small></small>
			</div>

	
			</section>

		<br>

		<?php 
		$total_vendas = 0;
$total_pago = 0;
$total_recebido = 0;
$saldo_total = 0;

$total_vendasF = 0;
$total_pagoF = 0;
$total_recebidoF = 0;
$saldo_totalF = 0;
		
			?>

			<small><small>
				<section class="area-tab" style="background-color: #f5f5f5;">
					
					<div class="linha-cab" style="padding-top: 5px;">
						<div class="coluna" style="width:50%">DESCRIÇÃO</div>
						<div class="coluna" style="width:20%">TIPO</div>
						<div class="coluna" style="width:15%">VALOR</div>
						<div class="coluna" style="width:15%">DATA</div>									

					</div>
					
				</section><small></small>

				<div class="cabecalho mb-1" style="border-bottom: solid 1px #e3e3e3;">
				</div>

				<?php 
				$query = $pdo->prepare("SELECT * FROM pagar where data_pgto >= :data_inicial and data_pgto <= :data_final and pago = 'Sim' ORDER BY id desc");
				$query->execute([':data_inicial' => $dataInicial, ':data_final' => $dataFinal]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$descricao = $res[$i]['descricao'];
	$valor = $res[$i]['valor'];
	$data_pgto = $res[$i]['data_pgto'];		
	
	$total_pago += $valor;	
	
	$valorF = number_format($valor, 2, ',', '.');
	$total_pagoF = number_format($total_pago, 2, ',', '.');
	$data_pgtoF = implode('/', array_reverse(explode('-', $data_pgto)));

				 ?>
		
		<section class="area-tab" style="padding-top:5px">					
						<div class="linha-cab">				
							<div class="coluna" style="width:50%"><?php echo $descricao ?></div>
							<div class="coluna" style="width:20%">Conta Paga</div>
							<div class="coluna text-danger" style="width:15%">R$ <?php echo $valorF ?></div>
							<div class="coluna" style="width:15%"> <?php echo $data_pgtoF ?></div>	
						</div>
					</section>
					<div class="cabecalho" style="border-bottom: solid 1px #e3e3e3;">
					</div>

				<?php } } 




$query = $pdo->prepare("SELECT * FROM receber where data_pgto >= :data_inicial and data_pgto <= :data_final and pago = 'Sim' ORDER BY id desc");
$query->execute([':data_inicial' => $dataInicial, ':data_final' => $dataFinal]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$descricao = $res[$i]['descricao'];
	$valor = $res[$i]['valor'];
	$data_pgto = $res[$i]['data_pgto'];		
	
	$total_recebido += $valor;	
	
	$valorF = number_format($valor, 2, ',', '.');
	$total_recebidoF = number_format($total_recebido, 2, ',', '.');
	$data_pgtoF = implode('/', array_reverse(explode('-', $data_pgto)));
	



				 ?>


			<section class="area-tab" style="padding-top:5px">					
						<div class="linha-cab">				
							<div class="coluna" style="width:50%"><?php echo $descricao ?></div>
							<div class="coluna" style="width:20%">Conta Recebida</div>
							<div class="coluna text-primary" style="width:15%">R$ <?php echo $valorF ?></div>
							<div class="coluna" style="width:15%"> <?php echo $data_pgtoF ?></div>	
						</div>
					</section>
					<div class="cabecalho" style="border-bottom: solid 1px #e3e3e3;">
					</div>

				<?php } }






$query = $pdo->prepare("SELECT * FROM matriculas where status != 'Aguardando' and subtotal > 0 and total_recebido > 0 and data >= :data_inicial and data <= :data_final ORDER BY id asc");
$query->execute([':data_inicial' => $dataInicial, ':data_final' => $dataFinal]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$id_curso = $res[$i]['id_curso'];	
	$total_receb = $res[$i]['total_recebido'];	
	$data = $res[$i]['data'];	
	$pacote = $res[$i]['pacote'];

	$total_vendas += $total_receb;

	if($pacote == 'Sim'){
		$tab = 'pacotes';
	}else{
		$tab = 'cursos';
	}

	$query2 = $pdo->prepare("SELECT * FROM {$tab} where id = :id");
	$query2->execute([':id' => $id_curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_curso = $res2[0]['nome'];

	
	$total_recebF = number_format($total_receb, 2, ',', '.');
	$total_vendasF = number_format($total_vendas, 2, ',', '.');
	$dataF = implode('/', array_reverse(explode('-', $data)));



				 ?>


				 <section class="area-tab" style="padding-top:5px">					
						<div class="linha-cab">				
							<div class="coluna" style="width:50%"><?php echo $nome_curso ?></div>
							<div class="coluna" style="width:20%">Venda de Curso</div>
							<div class="coluna text-success" style="width:15%">R$ <?php echo $total_recebF ?></div>
							<div class="coluna" style="width:15%"> <?php echo $dataF ?></div>	
						</div>
					</section>
					<div class="cabecalho" style="border-bottom: solid 1px #e3e3e3;">
					</div>

				<?php } }


				$saldo_total = $total_vendas + $total_recebido - $total_pago;
$saldo_totalF = number_format($saldo_total, 2, ',', '.');
if($saldo_total >= 0){
	$classe_saldo = 'text-success';
}else{
	$classe_saldo = 'text-danger';
}

				 ?>






			</small>



		</div>


		<div class="cabecalho mt-3" style="border-bottom: solid 1px #0340a3">
		</div>


	





	<div class="col-md-12 p-2">
		<div class="" align="right">

		<span > <small><small><small><small>TOTAL PAGO</small> : <span class="text-danger">R$ <?php echo $total_pagoF ?></span></small></small></small>  </span>

		<span > <small><small><small><small>CONTAS RECEBIDAS</small> : <span class="text-primary">R$ <?php echo $total_recebidoF ?></span></small></small></small>  </span>

		<span > <small><small><small><small>TOTAL DE VENDAS</small> : <span class="text-success">R$ <?php echo $total_vendasF ?></span></small></small></small>  </span>

		<span > <small><small><small><small><b><i>SALDO TOTAL</small> :<span class="<?php echo $classe_saldo ?>"> R$ <?php echo $saldo_totalF ?></span></i></b></small></small></small>  </span>			

		
		</div>
	</div>
	<div class="cabecalho" style="border-bottom: solid 1px #0340a3">
	</div>




	<div class="footer"  align="center">
		<span style="font-size:10px"><?php echo $nome_sistema ?> Whatsapp: <?php echo $tel_sistema ?></span> 
	</div>




	</body>

	</html>
