<?php 
require_once("../../../conexao.php");
$tabela = 'cupons';

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
	<th>Código</th>
	<th class="esc">Tipo</th> 	
	<th class="esc">Valor</th> 	
	<th class="esc">Validade</th> 	
	<th class="esc">Quantidade</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$codigo = $res[$i]['codigo'];
	$valor = $res[$i]['valor'];	
	$valorF = @number_format($valor, 2, ',', '.');
	$tipo = $res[$i]['tipo'];	
	$quantidade = $res[$i]['quantidade'];	
	$data_validade = $res[$i]['data_validade'];	

	$data_validadeF = implode('/', array_reverse(@explode('-', $data_validade)));

	$valor_porcent = @number_format($valor, 1, ',', '.');

	if($tipo == '%'){
		$caracter_valor = $valor_porcent.'%';
		$nome_tipo = 'Porcentagem';
	}else{
		$caracter_valor = 'R$ '.$valorF;
		$nome_tipo = 'Valor Fixo R$';
	}

	
echo <<<HTML
<tr> 
		<td>{$codigo}</td> 		
		<td class="esc">{$nome_tipo}</td>	
		<td class="esc">{$caracter_valor}</td>	
		<td class="esc">{$data_validadeF}</td>	
		<td class="esc">{$quantidade}</td>		
				
		<td>
		<big><a href="#" onclick="editar('{$id}', '{$codigo}', '{$valor}', '{$tipo}', '{$data_validade}', '{$quantidade}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

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
	
	function editar(id, codigo, valor, tipo, data, quantidade){

		$('#id').val(id);
		$('#codigo').val(codigo);
		$('#valor').val(valor);
		$('#tipo').val(tipo).change();
		$('#data').val(data);
		$('#quantidade').val(quantidade);
			
		
		$('#tituloModal').text('Editar Registro');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}


	

	function limparCampos(){
		$('#id').val('');
		$('#codigo').val('');
		$('#valor').val('');
		$('#data').val('');
		$('#quantidade').val('1');
		
		
	}




</script>

