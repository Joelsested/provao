<?php 
require_once("../../../conexao.php");
$tabela = 'categorias';

echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Nome</th>
	<th class="esc">Descrição</th> 
	<th class="esc">Cursos</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$nome = $res[$i]['nome'];
	$descricao = $res[$i]['descricao'];	
	$foto = $res[$i]['imagem'];

	$query2 = $pdo->prepare("SELECT * FROM cursos where categoria = :categoria");
	$query2->execute([':categoria' => $id]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$cursos = @count($res2);

	
echo <<<HTML
<tr> 
		<td>
		<img src="img/categorias/{$foto}" width="27px" class="mr-2">
		{$nome}	
		</td> 
		<td class="esc">
		{$descricao}		
		</td>
		<td class="esc">{$cursos}</td>		
				
		<td>
		<big><a href="#" onclick="editar('{$id}', '{$nome}', '{$descricao}', '{$foto}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

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
	echo 'Não possui nenhum registro cadastrado!';
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
	
	function editar(id, nome, descricao, foto){

		$('#id').val(id);
		$('#nome').val(nome);
		$('#descricao').val(descricao);
	
		$('#foto').val('');
		$('#target').attr('src','img/categorias/' + foto);		
		
		$('#tituloModal').text('Editar Registro');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}


	

	function limparCampos(){
		$('#id').val('');
		$('#nome').val('');
		$('#descricao').val('');		
		$('#foto').val('');
		$('#target').attr('src','img/categorias/sem-foto.png');		
	}




</script>

