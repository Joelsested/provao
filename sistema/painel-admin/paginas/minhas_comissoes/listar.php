<?php
require_once("../../../conexao.php");
$tabela = 'pagar';
$tabela_vendedor = 'comissoes_pagar';
@session_start();
$id_usuario = $_SESSION['id'];

$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];
$pago = @$_POST['pago'];

echo <<<HTML
<small>
HTML;

$total_pago = 0;
$total_a_pagar = 0;
$totalValorAReceber = 0;
$totalValorPago = 0;

// $query = $pdo->query("SELECT * FROM $tabela where  pago = '$pago' and professor = '$id_usuario' ORDER BY id desc");
// $res = $query->fetchAll(PDO::FETCH_ASSOC);
// $total_reg = @count($res);

$query1 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query1->execute([':id' => $id_usuario]);
$res = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_vendedor = $res[0]['id_pessoa'] ?? null;

$queryVendedorInfo = $pdo->prepare("SELECT nome, comissao FROM vendedores WHERE id = :id");
$queryVendedorInfo->execute([':id' => $id_vendedor]);
$dadosVendedor = $queryVendedorInfo->fetch(PDO::FETCH_ASSOC);
$nomeVendedor = $dadosVendedor['nome'] ?? '';
$comissaoVendedor = $dadosVendedor['comissao'] ?? 0;

// Busca os IDs dos alunos do vendedor
$queryAlunosDoVendedor = $pdo->prepare("SELECT id FROM alunos WHERE usuario = :usuario");
$queryAlunosDoVendedor->execute([':usuario' => $id_usuario]);
$alunosDoVendedor = $queryAlunosDoVendedor->fetchAll(PDO::FETCH_ASSOC);

// Array para armazenar todas as matrículas
$todasMatriculas = [];

// Prepared statements reutilizÇveis
$stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa = :id_pessoa");
$stmtMatriculas = $pdo->prepare("SELECT * FROM matriculas WHERE aluno = :aluno AND status = 'Aguardando'");
$stmtMatriculasValorAReceber = $pdo->prepare("SELECT SUM(valor) AS totalValor FROM matriculas WHERE aluno = :aluno AND status = 'Aguardando'");
$stmtMatriculasValorPago = $pdo->prepare("SELECT SUM(valor) AS totalValor FROM matriculas WHERE aluno = :aluno AND status = 'Matriculado'");
$stmtNomeCurso = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id");
$stmtNomeUsuario = $pdo->prepare("SELECT nome FROM usuarios WHERE id_pessoa = :id_pessoa");

// Para cada aluno, busca o ID do usuário correspondente e suas matrículas
foreach ($alunosDoVendedor as $aluno) {
	$idAluno = $aluno['id'];

	// Consulta o ID do usuário onde id_pessoa é igual ao ID do aluno
	$stmtUsuario->execute([':id_pessoa' => $idAluno]);
	$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

	if ($usuario) {
		$idUsuario = $usuario['id'];

		// Consulta as matrículas do aluno atual
		$stmtMatriculas->execute([':aluno' => $idUsuario]);
		$matriculasDoAluno = $stmtMatriculas->fetchAll(PDO::FETCH_ASSOC);

		$stmtMatriculasValorAReceber->execute([':aluno' => $idUsuario]);
		$valorMatriculasAReceber = $stmtMatriculasValorAReceber->fetch(PDO::FETCH_ASSOC);
		$totalValorAReceber = $valorMatriculasAReceber['totalValor'] ?? 0;


		$stmtMatriculasValorPago->execute([':aluno' => $idUsuario]);
		$valorMatriculasPago = $stmtMatriculasValorPago->fetch(PDO::FETCH_ASSOC);
		$totalValorPago = $valorMatriculasPago['totalValor'] ?? 0;


		foreach ($matriculasDoAluno as $matricula) {
			$curso_id = $matricula['id_curso'];

			// Consulta para buscar o nome do curso
			$stmtNomeCurso->execute([':id' => $curso_id]);
			$nomeCurso = $stmtNomeCurso->fetchColumn(); // Usando fetchColumn() para pegar apenas o nome

			// Consulta para buscar o nome do Vendedor

			// Consulta para buscar comissão do Vendedor


			$stmtNomeUsuario->execute([':id_pessoa' => $idAluno]);
			$nomeUsuario = $stmtNomeUsuario->fetchColumn();

			// Adiciona o nome do curso à matrícula
			$matricula['nome_curso'] = $nomeCurso;  // Insere o nome do curso no array de matrícula
			$matricula['nome_vendedor'] = $nomeVendedor;  //
			$matricula['comissao_vendedor'] = $comissaoVendedor;  //
			$matricula['nome_aluno'] = $nomeUsuario;  //
			$matricula['id_usuario'] = $idUsuario;  // Adiciona o id_usuario à matrícula

			// Adiciona a matrícula com o nome do curso no array final
			$todasMatriculas[] = $matricula;
		}
	}
}



$total_reg_matriculas = @count($todasMatriculas);

$query = $pdo->prepare("SELECT * FROM $tabela_vendedor where vendedor = :vendedor ORDER BY id desc");
$query->execute([':vendedor' => $id_vendedor]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);

if ($total_reg > 0) {
	echo <<<HTML
    <h3>Matrículas Pagas</h1>

    <div style="margin-top: 10px;">
        <small><div align="left">Matrículas de alunos que já realizaram o pagamento.</div></small>
    </div>
    <br>
    <table class="table table-hover" id="tabela">
        <thead> 
            <tr> 
                <th>Curso</th>   
                <th class="esc">Aluno</th>
                <th class="esc">Valor Curso</th>
                <!-- <th class="esc">ID Curso</th> -->
                <th class="esc">Status</th>
                <th class="esc">Data PGTO</th>  
                <!-- <th class="esc">Vendedor</th> -->
              	<!--   <th class="esc">Comissão</th> -->
              <!--  <th class="esc">Valor a Receber</th> -->
            </tr> 
        </thead> 
        <tbody>
HTML;

	for ($i = 0; $i < $total_reg; $i++) {
		foreach ($res[$i] as $key => $value) {
		}

		$id = $res[$i]['id'];
		$descricao = $res[$i]['descricao'];
		$valor_curso = $res[$i]['valor_curso'];
		$valor_cursoF = str_replace('.', ',', $valor_curso);
		$curso = $res[$i]['curso'];
		$id_curso = $res[$i]['id_curso'];
		$status = $res[$i]['status'];
		$data_pgto = $res[$i]['data_pgto'];
		$vendedor = $res[$i]['vendedor'];
		$porcentagem_comissao = $res[$i]['porcentagem_comissao'];
		$valor_pagar = $res[$i]['valor_pagar'];
		$valor_pagarF = str_replace('.', ',', $valor_pagar);
		$data_formatada = date("d/m/Y - h:m", $data_pgto);

		$total_pago = str_replace('.', ',', $totalValorPago);

		echo <<<HTML
            <tr> 
                <td><i class="fa fa-square" style="color: green;"></i> {$curso}</td> 
                <td class="esc">{$descricao}</td>            
                <td class="esc">R$ {$valor_cursoF}</td>
                <!-- <td class="esc"> {$id_curso}</td> -->
                <td class="esc">{$status}</td>      
                <td class="esc">{$data_formatada}</td>       
                <!-- <td class="esc">{$vendedor}</td>       -->
               <!--  <td class="esc" id="moverProLado">{$porcentagem_comissao}%</td> -->        
               <!--  <td class="esc">R$ {$valor_pagarF}</td>       -->
            </tr>
HTML;
	}

	echo <<<HTML
        </tbody>
        <small><div align="center" id="mensagem-excluir"></div></small>
    </table>
    <br>    
    <div align="right">Total Pago: <span class="verde">R$ 0,00</span> </div>
    <!-- <div align="right">Total à Receber: <span class="text-danger">R$ 0,00</span> </div> -->
HTML;
} else {
	echo 'Não possui nenhum registro cadastrado!';
}

echo <<<HTML
</small>
HTML;

// NOVO
if ($total_reg_matriculas > 0) {
	echo <<<HTML
    <h3>Matrículas Pendentes</h1>

    <div style="margin-top: 10px;">
        <small><div align="left">Matrículas de alunos que ainda não realizaram o pagamento.</div></small>
    </div>
    <br>

    <table class="table table-hover" id="tabela_pendentes">
        <thead> 
            <tr> 
                <th>Curso</th>   
                <th class="esc">Aluno</th>
                <th class="esc">Valor Curso</th>
                <th class="esc">Valor Pago</th>  
                <!-- <th class="esc">ID Curso</th> -->
                <th class="esc">Status</th>
                <!-- <th class="esc">Vendedor</th> -->
                <!-- <th class="esc">Comissão</th> -->
                  <!--<th class="esc">Valor a Receber</th> -->
            </tr> 
        </thead> 
        <tbody>
HTML;

	for ($i = 0; $i < $total_reg_matriculas; $i++) {
		foreach ($todasMatriculas[$i] as $key => $value) {
		}

		$id = $todasMatriculas[$i]['id'];
		$valor_curso = $todasMatriculas[$i]['subtotal'];
		$valor_cursoF = str_replace('.', ',', $valor_curso);
		$curso = $todasMatriculas[$i]['nome_curso'];
		$id_curso = $todasMatriculas[$i]['id_curso'];
		$status = $todasMatriculas[$i]['status'];
		$data_pgto = $todasMatriculas[$i]['data'];
		$vendedor = $todasMatriculas[$i]['professor'];
		$nomeDoAluno = $todasMatriculas[$i]['nome_aluno'];
		$porcentagem_comissao = $todasMatriculas[$i]['comissao_vendedor'];
		$valor_pagar = $todasMatriculas[$i]['subtotal'];

		// $data_formatada = date("d/m/Y - h:m", $data_pgto);

		$valor_pagar_C = ($valor_curso * $porcentagem_comissao) / 100;

		$valor_pagar_comissao = number_format($valor_pagar_C, 2, '.', '');
		$valor_pagarF = str_replace('.', ',', $valor_pagar_comissao);
		$total_a_receber = str_replace('.', ',', $totalValorAReceber);
		echo <<<HTML
            <tr> 
                <td><i class="fa fa-square" style="color: red;"></i><small> {$curso}</small></td> 
                <td class="esc"><small>{$nomeDoAluno}</small></td>            
                <td class="esc">R$ <small>{$valor_cursoF}</small></td>
                <td class="esc">R$ <small>0,00</small></td>
                <!-- <td class="esc"> <small>{$id_curso}</small></td> -->
                <td class="esc"><small>{$status}</small></td>      
                <!-- <td class="esc"><small>{$data_pgto}</small></td>        -->
                <!-- <td class="esc"><small>{$vendedor}</small></td>       -->
               <!--  <td class="esc" id="moverProLado"><small>{$porcentagem_comissao}%</small></td>   -->    
              <!--   <td class="esc">R$ <small>{$valor_pagarF}</small></td>      -->  
            </tr>
HTML;
	}

	echo <<<HTML
        </tbody>
        <small><div align="center" id="mensagem-excluir"></div></small>
    </table>
    <br>    
    <div align="right">Total à Receber: <span class="text-danger">R$ 0,00</span> </div>
HTML;
} else {
	echo 'Nenhuma matrícula pendente!';
}

echo <<<HTML
</small>
HTML;
?>

<script type="text/javascript">
	$(document).ready(function() {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
		});
		$('#tabela_filter label input').focus();
	});

	$(document).ready(function() {
		$('#tabela_pendentes').DataTable({
			"ordering": false,
			"stateSave": true,
		});
		$('#tabela_filter label input').focus();
	});

	function editar(id, descricao, valor, vencimento, arquivo) {
		$('#id').val(id);
		$('#descricao').val(descricao);
		$('#valor').val(valor);
		$('#vencimento').val(vencimento);

		$('#arquivo').val('');
		$('#target').attr('src', 'img/contas/' + arquivo);

		$('#tituloModal').text('Editar Conta');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}

	function limparCampos() {
		$('#id').val('');
		$('#total_recebido').val('');
		$('#obs').val('');
	}
</script>
