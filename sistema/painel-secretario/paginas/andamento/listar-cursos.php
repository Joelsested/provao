<?php 
require_once("../../../conexao.php");
$tabela = 'matriculas';

@session_start();
$id_usuario = $_SESSION['id'];

$id_pacote = '%'.@$_POST['id'].'%';

echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela where aluno = :aluno and pacote != 'Sim' and status != 'Finalizado' and aulas_concluidas > 1 ORDER BY id desc");
$query->execute([':aluno' => $id_usuario]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Curso</th>
	<th class="esc">Professor</th> 
	<th class="esc">Aulas</th> 
	<th class="esc">Progresso</th> 
	<th class="esc">Valor</th> 	
	<th class="esc">Data</th>
	<th class="esc">Status</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$curso = $res[$i]['id_curso'];
	$aulas_concluidas = $res[$i]['aulas_concluidas'];	
	$valor = $res[$i]['subtotal'];	
	$data = $res[$i]['data'];	
	$status = $res[$i]['status'];
	$professor = $res[$i]['professor'];	
	$pacote = $res[$i]['pacote'];
	$boleto = $res[$i]['boleto'];
	$nota = $res[$i]['nota'];


	if($boleto != "" and $status == 'Aguardando'){
		//require("../../../../pagamentos/boletos/notificacoes.php");
	}	

	if($pacote == 'Sim'){
		$tab = 'pacotes';
		$link = 'cursos-do-';
	}else{
		$tab = 'cursos';
		$link = 'curso-de-';
	}
	

	$query2 = $pdo->prepare("SELECT * FROM $tab where id = :id");
	$query2->execute([':id' => $curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res2) > 0){
		$nome_curso = $res2[0]['nome'];
		$nome_url = $res2[0]['nome_url'];
		$url_do_curso = $link.$nome_url;
		$id_do_curso = $res2[0]['id'];
		$link = $res2[0]['link'];

	}else{
		$nome_curso = "";
	}


	$query2 = $pdo->prepare("SELECT * FROM professores where id = :id");
	$query2->execute([':id' => $professor]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res2) > 0){
		$nome_professor = $res2[0]['nome'];		
	}else{
		$nome_professor = "";
	}

	
	$query2 = $pdo->prepare("SELECT * FROM aulas where curso = :curso");
	$query2->execute([':curso' => $curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$aulas = @count($res2);


	//verificar se o curso já foi avaliado
	$query2 = $pdo->prepare("SELECT * FROM avaliacoes where curso = :curso and aluno = :aluno");
	$query2->execute([
		':curso' => $curso,
		':aluno' => $id_usuario,
	]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$avaliacoes = @count($res2);
	if($avaliacoes > 0){
		$ocultar_avaliar = 'ocultar';
	}else{
		$ocultar_avaliar = '';
	}

	$porcentagemAulas = 0;

	if($aulas_concluidas > 0 && $aulas > 0){
		$porcentagemAulas = ($aulas_concluidas / $aulas) * 100;
		if($porcentagemAulas > 100){
			$porcentagemAulas = 100;
		}
	}

	$porcentagemAulasF = round($porcentagemAulas, 2);



	if($status == 'Aguardando'){
		$excluir = '';
		$icone = 'fa-square';		
		$classe_square = 'text-danger';
		$classe_nome = 'text-muted';
		$ocultar_aulas = 'ocultar';
		$ocultar_pagar = '';
		$classe_progress = '';
		$icones_finalizados = 'ocultar';
	}else if($status == 'Finalizado'){
		$excluir = 'ocultar';
		$icone = 'fa-square';		
		$classe_square = 'azul';
		$classe_nome = 'verde_claro';
		$ocultar_aulas = '';
		$ocultar_pagar = 'ocultar';
		$classe_progress = '#015e23';
		$icones_finalizados = '';
	}
	else{
		$excluir = 'ocultar';
		$icone = 'fa-square';		
		$classe_square = 'verde';
		$classe_nome = 'verde_claro';
		$ocultar_aulas = '';
		$ocultar_pagar = 'ocultar';
		$classe_progress = '';
		$icones_finalizados = 'ocultar';
	}


	
	//FORMATAR VALORES
	$valorF = number_format($valor, 2, ',', '.');
	$dataF = implode('/', array_reverse(explode('-', $data)));


$classe_quest = 'ocultar';

	//pegar o id da matricula
$query_m = $pdo->prepare("SELECT * FROM matriculas where id = :id");
$query_m->execute([':id' => $id]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$id_mat = $res_m[0]['id'];
$aulas_conc = $res_m[0]['aulas_concluidas'];
$status_mat = $res_m[0]['status'];

//verificar total de aulas do curso
$query_m = $pdo->prepare("SELECT * FROM aulas where curso = :curso");
$query_m->execute([':curso' => $curso]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$total_aulas = @count($res_m);

if($questionario_config == 'Sim'){
	//mudo o status do curso para finalizado
	if($status_mat != 'Finalizado' and $total_aulas == $aulas_conc){
		$classe_quest = '';
	}
}

if($nota <= $media_config and $nota != ""){
	$classe_nota = '';
}else{
	$classe_nota = 'ocultar';
}

	
	
echo <<<HTML
<tr> 
		<td>		
		<a href="#" onclick="abrirAulas('{$id}', '{$nome_curso}', '{$aulas}', '{$id_do_curso}', '{$link}')" class="{$classe_nome} $ocultar_aulas">	
		{$nome_curso}
		<small><i class="fa fa-video-camera text-dark"></i>	</small>
		</a>

			
		
		
		<form method="post" action="../../{$url_do_curso}" target="_blank" class="{$ocultar_pagar}">

		<span class="text-muted">{$nome_curso}</span>
							
									<button  type="submit" style="background-color: transparent;  border:none!important;"><i class="fa fa-money text-danger" ></i><span class="text-danger" style="margin-left:2px">Pagar</span>
									</button>
									<input type="hidden" name="painel_aluno" value="sim">
									

								
							</form>

		

		</td> 		
		<td class="esc">{$nome_professor}</td>		
		<td class="esc">{$aulas_concluidas} / {$aulas}</td>
		<td class="esc">
			<div class="progress" style="height:15px; ">
  				<div class="progress-bar" role="progressbar" style="width: {$porcentagemAulas}%; background: {$classe_progress}" aria-valuenow="{$porcentagemAulas}" aria-valuemin="0" aria-valuemax="100">{$porcentagemAulasF}%</div>
			</div>
		</td>
		<td class="esc">R$ {$valorF} </td>
		<td class="esc">{$dataF}</td>
		<td class="esc"><i class="fa {$icone} $classe_square"></i></td>				
		<td>
		
		<li class="dropdown head-dpdn2 {$excluir}" style="display: flex;">
		<a href="#" class="dropdown-toggle {$excluir}" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>

		<form method="post" action="../rel/rel_certificado.php" target="_blank" class="{$icones_finalizados}">		
		<button  type="submit" style="background-color: transparent;  border:none!important;"><img src="img/certificado.png" width="30">
		</button>
		<input type="hidden" name="id_mat" value="{$id}">

		<big><a class="{$icones_finalizados} {$ocultar_avaliar}" href="#" onclick="avaliar('{$curso}', '{$nome_curso}')" title="Avaliar Curso"><i class="fa fa-star amarelo"></i></a></big>

		

		</form>

		
		<big><a class="{$classe_quest}" href="#" onclick="questionario('{$curso}', '{$nome_curso}', '{$id}')" title="Iniciar Questionário"><i class="fa fa-question-circle-o verde"></i></a></big>

		<small><span class="text-danger {$classe_nota}">Nota: {$nota}%</span></small>


		</td>
</tr>
HTML;

}

echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>	
HTML;

}else{
	echo 'Não possui nenhum curso matriculado!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

	$(document).ready( function () {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
		});
		$('#tabela_filter label input').focus();
	} );
	
	


</script>

