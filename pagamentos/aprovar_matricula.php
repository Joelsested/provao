<?php 
require("../sistema/conexao.php");

$hoje = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_pgto_comissao = $ano_atual.'-'.$mes_atual.'-'.$dia_pgto_comissao;

if($forma_pgto == 'MP'){
@session_start();
$id_aluno = $_SESSION['id'];

$query = $pdo->prepare("SELECT * FROM matriculas where id_curso = :id_curso and aluno = :aluno");
$query->execute([':id_curso' => $id_matricula, ':aluno' => $id_aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$subtotal = $res[0]['subtotal'];
$id_matricula = $res[0]['id'];
$total_recebido = $subtotal - ($subtotal * ($taxa_mp / 100));

}

$query = $pdo->prepare("SELECT * FROM matriculas where id = :id");
$query->execute([':id' => $id_matricula]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$subtotal = $res[0]['subtotal'];
	$pacote = $res[0]['pacote'];
	$aluno = $res[0]['aluno'];
	$id_curso = $res[0]['id_curso'];
	$status_mat = $res[0]['status'];
	$professor = $res[0]['professor'];

	if($status_mat != 'Aguardando'){		
		exit();
	}


	if($pacote == 'Sim'){
		$tab = 'pacotes';
	}else{
		$tab = 'cursos';
	}

}

$query = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query->execute([':id' => $aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$nome_aluno = $res[0]['nome'];
	$email_aluno = $res[0]['usuario'];
	$id_pessoa_aluno = $res[0]['id_pessoa'];
}

$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
$query->execute([':id' => $id_pessoa_aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$cartoes = $res[0]['cartao'];
	$usuario_comissao = $res[0]['usuario'];
}



$query = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query->execute([':id' => $usuario_comissao]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$nivel_do_usu = $res[0]['nivel'];
}



$query = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query->execute([':id' => $professor]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$email_professor = $res[0]['usuario'];
}


if($forma_pgto == 'Paypal'){		
	$total_recebido = $subtotal - ($subtotal * ($taxa_paypal / 100));
}

if($forma_pgto == 'Pix' and @$pix_api == 'Sim'){		
	$total_recebido = $subtotal - ($subtotal * (($desconto_pix + 1) / 100));
}


//ATUALIZANDO A MATRÍCULA
$query = $pdo->prepare("UPDATE matriculas SET status = 'Matriculado', forma_pgto = :forma_pgto, total_recebido = :total_recebido where id = :id");
$query->execute([':forma_pgto' => $forma_pgto, ':total_recebido' => $total_recebido, ':id' => $id_matricula]);


//ADICIONAR MAIS UM CARTÃO PARA O ALUNO
$cartoes += 1;
$stmtCartao = $pdo->prepare("UPDATE alunos SET cartao = :cartao where id = :id");
$stmtCartao->execute([':cartao' => $cartoes, ':id' => $id_pessoa_aluno]);


//LIBERAR OS CURSOS SE FOR UM PACOTE
if($pacote == 'Sim'){
	$query = $pdo->prepare("SELECT * FROM cursos_pacotes where id_pacote = :id_pacote order by id desc");
	$query->execute([':id_pacote' => $id_curso]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);

	if($total_reg > 0){
		for($i=0; $i < $total_reg; $i++){
		foreach ($res[$i] as $key => $value){}
		$id_cursos_pacotes = $res[$i]['id'];
		$id_do_curso = $res[$i]['id_curso'];

		$query2 = $pdo->prepare("SELECT * FROM cursos where id = :id");
		$query2->execute([':id' => $id_do_curso]);
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$matriculas = $res2[0]['matriculas'];
		$id_professor = $res2[0]['professor'];
		$quant_mat = $matriculas + 1; 

				
		$query3 = $pdo->prepare("SELECT * FROM matriculas where id_curso = :id_curso and aluno = :aluno");
		$query3->execute([':id_curso' => $id_do_curso, ':aluno' => $aluno]);
		$res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
		

		if(@count($res3) > 0){	
			$id_mat = @$res3[0]['id'];
			//excluir a matrícula do curso se ela já existir
			$stmtDel = $pdo->prepare("DELETE FROM matriculas where id = :id");
			$stmtDel->execute([':id' => $id_mat]);
		}
			//inserir a matrícula do curso caso ela não exista
			$stmtInsert = $pdo->prepare("INSERT INTO matriculas SET id_curso = :id_curso, aluno = :aluno, professor = :professor, aulas_concluidas = '1', data = curDate(), status = 'Matriculado', pacote = 'Não', id_pacote = :id_pacote, obs = 'Pacote' ");
			$stmtInsert->execute([
				':id_curso' => $id_do_curso,
				':aluno' => $aluno,
				':professor' => $id_professor,
				':id_pacote' => $id_curso,
			]);


			//atualizar matriculas do curso
			$stmtCursos = $pdo->prepare("UPDATE cursos SET matriculas = :matriculas where id = :id");
			$stmtCursos->execute([':matriculas' => $quant_mat, ':id' => $id_do_curso]);
		

		

		}
	}

}

//ADICIONAR MAIS UMA VENDA AO CURSO OU PACOTE


// $query2 = $pdo->query("SELECT * FROM $tab where id = '$id_curso'");
// $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
// $matriculas = $res2[0]['matriculas'];
// $valor_comissao = 0;
// $quantid_mat = $matriculas + 1;
// $nome_curso = $res2[0]['nome'];
// $pdo->query("UPDATE $tab SET matriculas = '$quantid_mat' where id = '$id_curso'");


// if($nivel_do_usu == 'Professor'){
// 			$valor_comissao = $comissao_professor;
// 		}

// 		if($nivel_do_usu == 'Tutor'){
// 			$valor_comissao = $comissao_tutor;
// 		}

// 		if($nivel_do_usu == 'Parceiro'){
// 			$valor_comissao = $comissao_parceiro;
// 		}


// //LANÇAR COMISSÃO DO PROFESSOR
// $valor_comissao_pagar = ($valor_comissao * $subtotal) / 100;
// if(strtotime($hoje) < strtotime($data_pgto_comissao)){
// 	$data_venc = $data_pgto_comissao;
// }else{
// 	$data_venc = date('Y-m-d', strtotime("+1 month",strtotime($data_pgto_comissao)));
// }

// if($valor_comissao_pagar > 0){
// 	$query = $pdo->query("INSERT INTO pagar SET descricao = 'Comissão',  valor = '$valor_comissao_pagar', data = curDate(), vencimento = '$data_venc', pago = 'Não', arquivo = 'sem-foto.png', professor = '$usuario_comissao', curso = '$nome_curso'");
// }


//ENVIAR EMAIL PARA O ADM, PROFESSOR E ALUNO
// require_once('email-aprovar-matricula.php');

 ?>
