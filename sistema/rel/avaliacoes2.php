<?php 
$id = $_GET['id'];
$curso = $_GET['curso'];
include('../conexao.php');





$query = $pdo->prepare("SELECT * from usuarios where id = :id order by id desc ");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = count($res);
$nome = $res[0]['nome'];
$id_aluno = $res[0]['id'];	

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Porto_Velho');
$data_hoje = utf8_encode(strftime('%A, %d de %B de %Y', strtotime('today')));


?>

<!DOCTYPE html>
<html>
<head>
	<title>Relatório de Avaliações</title>	

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



<div class="titulo_cab titulo_img"><u>GABARITO <?php echo $nome  ?>  </u></div>	
	<div class="data_img"><?php echo mb_strtoupper($data_hoje) ?></div>

	<img class="imagem" src="<?php echo $url_sistema ?>/sistema/img/logo_rel.jpg" width="200px" height="47">

	
	<br><br><br>
	<div class="cabecalho" style="border-bottom: solid 1px #0340a3">
	</div>

	<div class="mx-2" style="padding-top:10px ">

		

		<br>

		<?php 
		

		$query = $pdo->prepare("SELECT * from matriculas where aluno = :aluno and id_curso = :id_curso order by id desc ");
		$query->execute([':aluno' => $id_aluno, ':id_curso' => $curso]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_reg = count($res);
		if($total_reg > 0){
			?>

				<?php 
					for($i=0; $i < $total_reg; $i++){
	
	
	$nota = @$res[$i]['nota'];
	$curso = @$res[$i]['id_curso'];	
	$pacote = @$res[$i]['id_pacote'];

	


$queryPacote = $pdo->prepare("SELECT * FROM pacotes WHERE id = :id ORDER BY id DESC");
$queryPacote->execute([':id' => $pacote]);
    $resPacote = $queryPacote->fetchAll(PDO::FETCH_ASSOC);
    $nome_pacote = @$resPacote[0]['nome']; 

    // Consulta para obter detalhes do curso
    $queryCurso = $pdo->prepare("SELECT * FROM cursos WHERE id = :id ORDER BY id DESC");
    $queryCurso->execute([':id' => $curso]);
    $resCurso = $queryCurso->fetchAll(PDO::FETCH_ASSOC);
    $nome_curso = @$resCurso[0]['nome']; 

    
	
	
	if ($nota == '') {
		$nota = 'sem Nota';
	}





				 ?>


		
						
						<div class="linha-cab">				
							
								<div  style="text-align: center; font-size: 20px;"><?php echo @$nome_curso ?></div>
							
<?php $query9 = $pdo->prepare("SELECT * from perguntas_respostas where id_curso = :id_curso and id_aluno = :id_aluno ORDER BY numeracao ASC");
	$query9->execute([':id_curso' => $curso, ':id_aluno' => $id_aluno]);
	$res9 = $query9->fetchAll(PDO::FETCH_ASSOC);


	 foreach ($res9 as $pergunta_resposta) {
        $pergunta = $pergunta_resposta['pergunta'];
        $resposta = $pergunta_resposta['resposta'];
        $letra = $pergunta_resposta['letra'];
        $numeracao = $pergunta_resposta['numeracao'];
        $correta = $pergunta_resposta['correta'];


if ($correta == 'Sim') {
	$cor = '#adfc03';
}else{
	$cor = '#fc0356';
}
   

	 ?>							<div style="text-align:center">
							<table style="border-collapse: collapse; border: 1px solid black; width:50px;margin: auto; ">
}
    <tr>
        <td style="border: 1px solid black; text-align: left;" >0<?php echo$numeracao?></td>
        <td style="border: 1px solid black; text-align: left; "><?php echo $letra?></td>
    </tr>

</table>


</div>
							
							<?php } ?>

			<table style="border-collapse: collapse; border: 1px solid black; width:50px;margin: auto;  ">
    <tr>
        <td style="border: 1px solid black; text-align: left;" ><?php echo$nota?></td>
       
    </tr>
    
</table>				
							
													

						</div>
				
					

				<?php } ?>

			</small>



		</div>


		

	<?php }else{
		echo '<div style="margin:8px"><small><small>Ainda Não a Gabarito!</small></small></div>';
	} ?>





	



	<div class="footer"  align="center">
		<span style="font-size:10px"><?php echo $nome_sistema ?> Whatsapp: <?php echo $tel_sistema ?></span> 
	</div>




	</body>

	</html>
