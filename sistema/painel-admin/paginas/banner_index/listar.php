<?php 
require_once("../../../conexao.php");
$tabela = 'banner_index';

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
	<th>Título</th>
	<th class="esc">Descrição</th>
	<th class="esc">Link</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$titulo = $res[$i]['titulo'];
	$descricao = $res[$i]['descricao'];
	$link = $res[$i]['link'];	
	$foto = $res[$i]['foto'];
	$ativo = $res[$i]['ativo'];

	$descricaoF = mb_strimwidth($descricao, 0, 40, "...");

	if($ativo == 'Sim'){
			$icone = 'fa-check-square';
			$titulo_link = 'Desativar Item';
			$acao = 'Não';
			$classe_linha = '';
		}else{
			$icone = 'fa-square-o';
			$titulo_link = 'Ativar Item';
			$acao = 'Sim';
			$classe_linha = 'text-muted';
		}

echo <<<HTML
<tr class="{$classe_linha}"> 
		<td>
		<img src="img/banners/{$foto}" width="27px" class="mr-2">
		{$titulo}	
		</td> 		
		<td class="esc">{$descricaoF}</td>
		<td class="esc">{$link}</td>		
				
		<td>
		<big><a href="#" onclick="editar('{$id}', '{$titulo}', '{$descricao}', '{$link}','{$foto}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>	


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



		<big><a href="#" onclick="ativar('{$id}', '{$acao}')" title="{$titulo_link}"><i class="fa {$icone} text-success"></i></a></big>


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
	
	function editar(id, titulo, descricao, link, foto){

		$('#id').val(id);
		$('#titulo').val(titulo);
		$('#descricao').val(descricao);
		$('#link').val(link);
				
		$('#foto').val('');
		$('#target').attr('src','img/banners/' + foto);		
		
		$('#tituloModal').text('Editar Registro');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}


	

	function limparCampos(){
		$('#id').val('');
		$('#titulo').val('');
		$('#descricao').val('');
		$('#link').val('');
			
		$('#foto').val('');
		$('#target').attr('src','img/banners/sem-foto.png');		
	}




</script>

