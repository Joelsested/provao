<?php 
require_once("../../../conexao.php");
$tabela = 'matriculas';

echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela where status = 'Aguardando' ORDER BY id desc");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th> Curso</th>
	<th class="esc">Aluno</th> 	
	<th class="esc">Email</th> 	
	<th class="esc">SubTotal</th> 	
	<th class="esc">Data</th>	 	
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
	$pacote = $res[$i]['pacote'];
	$aluno = $res[$i]['aluno'];
	$obs = $res[$i]['obs'];


	if($pacote == 'Sim'){
		$tab = 'pacotes';
		$item_curso = ' (Pacote)';
		$classe_curso = 'text-primary';		
	}else{
		$tab = 'cursos';
		$item_curso = '';
		$classe_curso = '';		
	}
	

	$query2 = $pdo->prepare("SELECT * FROM $tab where id = :id");
	$query2->execute([':id' => $curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res2) > 0){
		$nome_curso = $res2[0]['nome'];		
		$id_do_curso = $res2[0]['id'];

	}else{
		$nome_curso = "";
	}

	$query2 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
	$query2->execute([':id' => $aluno]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res2) > 0){
		$nome_aluno = $res2[0]['nome'];
		$email_aluno = $res2[0]['usuario'];
		
	}
			
	//FORMATAR VALORES
	$valorF = number_format($valor, 2, ',', '.');
	$dataF = implode('/', array_reverse(explode('-', $data)));

	
	
echo <<<HTML
<tr> 
		<td>
		<i class="fa fa-square text-danger"></i>
		{$nome_curso}
		<span class="{$classe_curso}">{$item_curso}</span>		
		</a>	
		</td> 		
		<td class="esc">{$nome_aluno}</td>		
		<td class="esc">{$email_aluno}</td>		
		<td class="esc">R$ {$valorF} </td>
		<td class="esc">{$dataF}</td>
					
		<td>
		
		<li class="dropdown head-dpdn2 " style="display: inline-block;">
		<a href="#" class="dropdown-toggle " data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>

		
		<big><a href="#" onclick="aprovar('{$id}', '{$nome_curso}', '{$valor}', '{$obs}')" title="Editar Dados"><i class="fa fa-check-square verde"></i></a></big>



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



	function aprovar(id, curso, valor, obs){

		$('#id_mat').val(id);
		$('#nome_curso').text(curso);
		$('#valor').val(valor);
		$('#obs').val(obs);
					
		$('#tituloModal').text('Editar Registro');
		$('#modalAprovar').modal('show');
		$('#mensagem').text('');
	}
	
	


</script>

