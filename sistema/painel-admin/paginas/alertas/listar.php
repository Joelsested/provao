<?php 
require_once("../../../conexao.php");
$tabela = 'alertas';
$hoje = date('Y-m-d');
@session_start();

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
	<th class="esc">Link</th> 
	<th class="esc">Imagem</th> 	
	<th class="esc">Data Expirar</th> 	

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
	$imagem = $res[$i]['imagem'];	
	$data = $res[$i]['data'];	
	$video = $res[$i]['video'];	

	

	//FORMATAR VALORES
	$tituloF = mb_strimwidth($titulo, 0, 30, "...");
	$linkF = mb_strimwidth($link, 0, 30, "...");
	$dataF = implode('/', array_reverse(explode('-', $data)));

	if(strtotime($data) > strtotime($hoje)){
		$classe_linha = 'verde';
	}else{
		$classe_linha = '';
	}

	
		
echo <<<HTML
<tr class="{$classe_linha}"> 
		<td>	
		{$tituloF}	
		
		</td> 
		
		<td class="esc">{$link}</td>		
		<td class="esc"><img src="img/alertas/{$imagem}" width="27px" class="mr-2"></td>
		<td class="esc">{$dataF}</td>
			
		<td>
		<big><a href="#" onclick="editar('{$id}', '{$titulo}', '{$descricao}', '{$link}' , '{$imagem}' , '{$video}' , '{$data}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>


		<big><a href="#" onclick="mostrar('{$titulo}', '{$descricao}','{$link}','{$imagem}','{$video}','{$dataF}')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>


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
	
	function editar(id, titulo, descricao, link, foto, video, data){	

	
		$('#id').val(id);
		$('#titulo').val(titulo);		
		nicEditors.findEditor("area").setContent(descricao);	
		$('#link').val(link);
		$('#data').val(data);
			
	
		$('#foto').val('');
		$('#target').attr('src','img/alertas/' + foto);	

		$('#video').val(video);
		$('#target-video').attr('src', video);	
		
		$('#tituloModal').text('Editar Registro');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}




	function mostrar(titulo, descricao, link, foto, video, data){	


		$('#nome_mostrar').text(titulo);
		$('#descricao_mostrar').html(descricao);
		$('#link_mostrar').text(link);		
		$('#data_mostrar').text(data);
				
				
		$('#target_mostrar').attr('src','img/alertas/' + foto);
		$('#target_video_mostrar').attr('src', video);		

		$('#modalMostrar').modal('show');
		
	}





	function limparCampos(){
		$('#id').val('');
		$('#titulo').val('');		
		nicEditors.findEditor("area").setContent('');				
		$('#link').val('');			
		$('#target').attr('src','img/cursos/sem-foto.png');		
		$('#target-video').attr('src','');		
	}




</script>

