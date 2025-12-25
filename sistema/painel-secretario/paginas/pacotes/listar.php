<?php 
require_once("../../../conexao.php");
$tabela = 'matriculas';

@session_start();
$id_usuario = $_SESSION['id'];


echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela where aluno = :aluno and pacote = 'Sim' ORDER BY id desc");
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
	<th class="esc">Cursos</th> 	
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
	$valor = $res[$i]['subtotal'];	
	$data = $res[$i]['data'];	
	$status = $res[$i]['status'];
	$professor = $res[$i]['professor'];	
	$pacote = $res[$i]['pacote'];	

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

	
	$query2 = $pdo->prepare("SELECT * FROM cursos_pacotes where id_pacote = :id_pacote");
	$query2->execute([':id_pacote' => $curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$cursos = @count($res2);

	

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

	
	
echo <<<HTML
<tr> 
		<td>		
		<form method="post" action="index.php?pagina=cursos" class="{$classe_nome} $ocultar_aulas">	
		<button  type="submit" style="background-color: transparent;  border:none!important;"><span class="{$classe_nome}" style="margin-left:2px">{$nome_curso}</span>
		</button>
		<input type="hidden" name="id_pacote" value="{$curso}">	
		</form>	
		
		<form method="post" action="../../{$url_do_curso}" target="_blank" class="{$ocultar_pagar}">

		<span class="text-muted">{$nome_curso}</span>
							
									<button  type="submit" style="background-color: transparent;  border:none!important;"><i class="fa fa-money text-danger" ></i><span class="text-danger" style="margin-left:2px">Pagar</span>
									</button>
									<input type="hidden" name="painel_aluno" value="sim">
									

								
		</form>

		

		</td> 		
		<td class="esc">{$nome_professor}</td>		
		<td class="esc">$cursos</td>
		
		<td class="esc">R$ {$valorF} </td>
		<td class="esc">{$dataF}</td>
		<td class="esc"><i class="fa {$icone} $classe_square"></i></td>				
		<td>
		
		<li class="dropdown head-dpdn2 {$excluir}" style="display: inline-block;">
		<a href="#" class="dropdown-toggle {$excluir}" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>



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
	echo 'Não possui nenhum pacote matrículado!';
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
	
	

	function aulas(id, nome, aulas, id_curso){		
		$('#nome_aula_titulo').text(nome);
		$('#aulas_aula').text(aulas);		
		$('#modalAulas').modal('show');
		$('#id_da_matricula').val(id);
		$('#id_do_curso').val(id_curso);
		listarAulas(id_curso, id);
		//listarPerguntas(id);		


	}

	
	

</script>

