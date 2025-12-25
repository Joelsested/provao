<?php 
require_once("../../../conexao.php");
$tabela = 'matriculas';

$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];

echo <<<HTML
<small>
	
HTML;

$total_dia = 0;
// $query = $pdo->query("SELECT * FROM $tabela where status != 'Aguardando' and subtotal > 0 and total_recebido > 0 and data >= '$dataInicial' and data <= '$dataFinal' ORDER BY id asc");
// $query = $pdo->query("SELECT * FROM $tabela  ORDER BY id asc");

// $query = $pdo->query("
//     SELECT 
//         m.*, 
//         u1.id_pessoa, 
//         a.usuario AS id_usuario_aluno, 
//         u2.nome AS nome_professor
//     FROM matriculas m
//     LEFT JOIN usuarios u1 ON m.aluno = u1.id
//     LEFT JOIN alunos a ON u1.id_pessoa = a.id
//     LEFT JOIN usuarios u2 ON a.usuario = u2.id
// ");

// $query = $pdo->query("
//     SELECT 
//         m.*, 
//         u1.id_pessoa, 
//         a.usuario AS id_usuario_aluno, 
//         u2.nome AS nome_professor
//     FROM matriculas m
//     LEFT JOIN usuarios u1 ON m.aluno = u1.id
//     LEFT JOIN alunos a ON u1.id_pessoa = a.id
//     LEFT JOIN usuarios u2 ON a.usuario = u2.id
//     WHERE m.status != 'Aguardando'
//     AND m.subtotal > 0
//     AND m.total_recebido > 0
//     AND m.data >= '$dataInicial'
//     AND m.data <= '$dataFinal'
//     ORDER BY m.id ASC
// ");

$query = $pdo->prepare("
    SELECT 
        m.*, 
        u1.id_pessoa, 
        u1.nome AS nome_aluno,
        a.usuario AS id_usuario_aluno, 
        u2.nome AS nome_professor
    FROM matriculas m
    LEFT JOIN usuarios u1 ON m.aluno = u1.id
    LEFT JOIN alunos a ON u1.id_pessoa = a.id
    LEFT JOIN usuarios u2 ON a.usuario = u2.id
    WHERE m.status != 'Aguardando'
    AND m.subtotal > 0
    AND m.total_recebido > 0
    AND m.data >= :data_inicial
    AND m.data <= :data_final
    ORDER BY m.id ASC
");
$query->execute([
	':data_inicial' => $dataInicial,
	':data_final' => $dataFinal,
]);

// MOSTRAR APENAS Aluno, curso, vendedor, total recebido, data

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);

// echo json_encode($res);

// return;

if($total_reg > 0){
echo <<<HTML

	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Aluno</th>	
	<th class="esc">Curso</th>
	<th class="esc">Vendedor</th>
	<th class="esc">Total Recebido</th>
	<!-- <th class="esc">SubTotal</th>
	<th class="esc">Forma PGTO</th>
	<th class="esc">Total Recebido</th> 	 -->
	<th class="esc">Data</th>
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$id_curso = $res[$i]['id_curso'];
	$aluno = $res[$i]['aluno'];	
	$nome_do_aluno = $res[$i]['nome_aluno'];	
	$valor_cupom = $res[$i]['valor_cupom'];	
	$valor = $res[$i]['valor'];	
	$nome_professor = $res[$i]['nome_professor'];	
	$subtotal = $res[$i]['subtotal'];	
	$forma_pgto = $res[$i]['forma_pgto'];
	$total_recebido = $res[$i]['total_recebido'];	
	$data = $res[$i]['data'];
	$obs = $res[$i]['obs'];	
	$pacote = $res[$i]['pacote'];

	$total_dia += $total_recebido;

	if($pacote == 'Sim'){
		$tab = 'pacotes';
	}else{
		$tab = 'cursos';
	}

	$query2 = $pdo->prepare("SELECT * FROM {$tab} where id = :id");
	$query2->execute([':id' => $id_curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_curso = $res2[0]['nome'];

	$query2 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
	$query2->execute([':id' => $aluno]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_aluno = $res2[0]['nome'];
	$email_aluno = $res2[0]['usuario'];

	$valorF = number_format($valor, 2, ',', '.');
	$valor_cupomF = number_format($valor_cupom, 2, ',', '.');
	$subtotalF = number_format($subtotal, 2, ',', '.');
	$total_recebidoF = number_format($total_recebido, 2, ',', '.');
	$total_diaF = number_format($total_dia, 2, ',', '.');
	$dataF = implode('/', array_reverse(explode('-', $data)));

	$taxa_mpF = number_format($taxa_mp, 1, '.', '.');
	$taxa_paypalF = number_format($taxa_paypal, 1, '.', '.');

	if($forma_pgto == 'Boleto'){
		$desconto = '(R$ '.$taxa_boleto.')';
	}else if($forma_pgto == 'MP'){
		$desconto = '('.$taxa_mpF.')%';
	}else if($forma_pgto == 'Paypal'){
		$desconto = '('.$taxa_paypalF.')%';
	}else{
		$desconto = '';
	}

	if($obs == ""){
		$obs = 'Nenhuma!';
	}

	
echo <<<HTML
<tr> 
		<td>{$nome_do_aluno}</td> 		
		<td class="esc">{$nome_curso}</td>
		<td class="esc">{$nome_professor}</td>
		
		<!-- <td class="esc"> {$forma_pgto} <small><span class="text-danger">{$desconto}</span></small></td> -->
		<td class="esc verde">R$ {$total_recebidoF}</td>
		<td class="esc">{$dataF}</td>
		
				
		<td>
		<big><a href="#" onclick="editar('{$id}', '{$total_recebido}', '{$forma_pgto}', '{$obs}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

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
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-exclamation-circle text-primary"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p><b>Aluno: {$nome_aluno} </b> ($email_aluno) <br><br>
			<small>OBS: {$obs}</small>
		</p>
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
<div align="right">Saldo: <span class="verde">R$ {$total_diaF}</span></div>
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
	
	function editar(id, total_recebido, forma_pgto, obs){

		$('#id').val(id);
		$('#total_recebido').val(total_recebido);
		$('#forma_pgto').val(forma_pgto).change();
		$('#obs').val(obs);			
		
		$('#tituloModal').text('Editar Matrícula');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}


	

	function limparCampos(){
		$('#id').val('');
		$('#total_recebido').val('');
		$('#obs').val('');
		
	}




</script>
