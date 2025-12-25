<?php 
require_once("../../../conexao.php");

$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];

echo <<<HTML

HTML;

$total_vendas = 0;
$total_pago = 0;
$total_recebido = 0;
$saldo_total = 0;

$total_vendasF = 0;
$total_pagoF = 0;
$total_recebidoF = 0;
$saldo_totalF = 0;

echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Descrição</th>	
	<th class="esc">Tipo</th>
	<th class="esc">Valor</th>
	<th class="esc">Data</th>	
	
	</tr> 
	</thead> 
	<tbody>
HTML;


$query = $pdo->prepare("SELECT * FROM pagar where data_pgto >= :data_inicial and data_pgto <= :data_final and pago = 'Sim' ORDER BY id desc");
$query->execute([
	':data_inicial' => $dataInicial,
	':data_final' => $dataFinal,
]);
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
	

echo <<<HTML
<tr> 
		<td>{$descricao}</td> 		
		<td class="esc">Conta Paga</td>
		<td class="esc text-danger">R$ {$valorF}</td>
		<td class="esc">{$data_pgtoF}</td>
		
</tr>
HTML;

}

}







$query = $pdo->prepare("SELECT * FROM receber where data_pgto >= :data_inicial and data_pgto <= :data_final and pago = 'Sim' ORDER BY id desc");
$query->execute([
	':data_inicial' => $dataInicial,
	':data_final' => $dataFinal,
]);
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
	

echo <<<HTML
<tr> 
		<td>{$descricao}</td> 		
		<td class="esc">Conta Recebida</td>
		<td class="esc text-primary">R$ {$valorF}</td>
		<td class="esc">{$data_pgtoF}</td>
		
</tr>
HTML;

}

}









$query = $pdo->prepare("SELECT * FROM matriculas where status != 'Aguardando' and subtotal > 0 and total_recebido > 0 and data >= :data_inicial and data <= :data_final ORDER BY id asc");
$query->execute([
	':data_inicial' => $dataInicial,
	':data_final' => $dataFinal,
]);
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

	$query2 = $pdo->prepare("SELECT * FROM $tab where id = :id");
	$query2->execute([':id' => $id_curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_curso = $res2[0]['nome'];

	
	$total_recebF = number_format($total_receb, 2, ',', '.');
	$total_vendasF = number_format($total_vendas, 2, ',', '.');
	$dataF = implode('/', array_reverse(explode('-', $data)));



	
echo <<<HTML
<tr> 
		<td>{$nome_curso}</td> 		
		<td class="esc">Venda de Curso</td>
		<td class="esc verde">R$ {$total_recebF}</td>
		<td class="esc">{$dataF}</td>
		
</tr>
HTML;

}

}

$saldo_total = $total_vendas + $total_recebido - $total_pago;
$saldo_totalF = number_format($saldo_total, 2, ',', '.');
if($saldo_total >= 0){
	$classe_saldo = 'verde';
}else{
	$classe_saldo = 'text-danger';
}

echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>

</table>
<br>	
<div align="right">
<span style="margin-right: 10px">Contas Pagas: <span class="text-danger">R$ {$total_pagoF}</span></span>
<span style="margin-right: 10px">Contas Recebidas: <span class="text-primary">R$ {$total_recebidoF}</span></span>
<span style="margin-right: 30px">Total de Vendas: <span class="verde">R$ {$total_vendasF}</span></span>
<span style="margin-right: 10px"><b><u>Saldo Total: <span class="{$classe_saldo}">R$ {$saldo_totalF}</span></u></b></span>
</div>
HTML;


echo <<<HTML

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

