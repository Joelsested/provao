<?php 
require_once("../../../conexao.php");
$tabela = 'pagar';

$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];

echo <<<HTML
<small>
HTML;

$total_pago = 0;
$total_a_pagar = 0;

$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);



if($total_reg > 0){
echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
		<th class="esc">Vendedor</th>
	<th>Aluno</th>	
	<th class="esc">Curso</th>
	<th class="esc">Valor</th>
	<th class="esc">Vencimento</th>
	<th class="esc">Data PGTO</th>
	<!-- <th class="esc">Arquivo</th>	 -->
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$descricao = $res[$i]['descricao'];
	$valor = $res[$i]['valor'];	
	$data = $res[$i]['data'];	
	$vencimento = $res[$i]['vencimento'];	
	$pago = $res[$i]['pago'];	
	$data_pgto = $res[$i]['data_pgto'];
	$arquivo = $res[$i]['arquivo'];	
	$professor = $res[$i]['professor'];	
	$nome_curso = $res[$i]['curso'];	

	$query2 = $pdo->prepare("SELECT * FROM vendedores where id = :id");
	$query2->execute([':id' => $professor]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);

	if(@count($res2) > 0){
		$nome_professor = $res2[0]['nome'];
	}else{
		$nome_professor = '';
	}

	if($pago == 'Sim'){
		$total_pago += $valor;
		$classe_square = 'verde';
		$ocultar_baixa = 'ocultar';
	}else{
		$total_a_pagar += $valor;
		$classe_square = 'text-danger';
		$ocultar_baixa = '';
	}
	
	
	$valorF = number_format($valor, 2, ',', '.');	
	$total_pagoF = number_format($total_pago, 2, ',', '.');	
	$total_a_pagarF = number_format($total_a_pagar, 2, ',', '.');	

	$dataF = implode('/', array_reverse(explode('-', $data)));
	$vencimentoF = implode('/', array_reverse(explode('-', $vencimento)));
	$data_pgtoF = implode('/', array_reverse(explode('-', $data_pgto)));

	if($data_pgtoF == '00/00/0000'){
		$data_pgtoF = 'Pendente';
	}


	//extensão do arquivo
$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
if($ext == 'pdf'){
	$tumb_arquivo = 'pdf.png';
}else if($ext == 'rar' || $ext == 'zip'){
	$tumb_arquivo = 'rar.png';
}else{
	$tumb_arquivo = $arquivo;
}

	
echo <<<HTML
<tr> 
	<td class="esc"><i class="fa fa-square {$classe_square}" style="margin-right:3px"></i>{$nome_professor}</td>			
	<td>{$descricao}</td> 
		<td class="esc">{$nome_curso}</td>			
		<td class="esc">R$ {$valorF}</td>
		<td class="esc"> {$vencimentoF}</td>
		<td class="esc">{$data_pgtoF}</td>		
		<!-- <td class="esc"><a href="img/contas/{$arquivo}" target="_blank"><img src="img/contas/{$tumb_arquivo}" width="25"></a></td> -->
		
				
		<td>
		<big><a href="#" onclick="editar('{$id}', '{$descricao}', '{$valor}', '{$vencimento}', '{$tumb_arquivo}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

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



		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a title="Baixar Conta" href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-check-square verde"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Baixa na Conta? <a href="#" onclick="baixar('{$id}')"><span class="verde">Sim</span></a></p>
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
<br>	
<div align="right">Total Pago: <span class="verde">R$ {$total_pagoF}</span> </div>
<div align="right">Total à Pagar: <span class="text-danger">R$ {$total_a_pagarF}</span> </div>
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
	
	function editar(id, descricao, valor, vencimento, arquivo){

		$('#id').val(id);
		$('#descricao').val(descricao);
		$('#valor').val(valor);
		$('#vencimento').val(vencimento);		

		$('#arquivo').val('');
		$('#target').attr('src','img/contas/' + arquivo);		
		
		$('#tituloModal').text('Editar Conta');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}


	

	function limparCampos(){
		$('#id').val('');
		$('#total_recebido').val('');
		$('#obs').val('');
		
	}




</script>

