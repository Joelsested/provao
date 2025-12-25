<?php 
include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$id_mat = @$postjson['id'];
$subtotal = @$postjson['valor'];
$forma_pgto = @$postjson['pgto'];
$obs = @$postjson['obs'];
$cartao = @$postjson['cartao'];
$subtotal = str_replace(',', '.', $subtotal);

$hoje = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_pgto_comissao = $ano_atual.'-'.$mes_atual.'-'.$dia_pgto_comissao;



$total_recebido = $subtotal;

if($forma_pgto == 'MP'){
$total_recebido = $subtotal - ($subtotal * ($taxa_mp / 100));
}

if($forma_pgto == 'Boleto'){
$total_recebido = $subtotal - $taxa_boleto;
}

if($forma_pgto == 'Paypal'){		
	$total_recebido = $subtotal - ($subtotal * ($taxa_paypal / 100)); ;
}


$stmtMatricula = $pdo->prepare("SELECT * FROM matriculas WHERE id = ?");
$stmtMatricula->execute([(int) $id_mat]);
$res = $stmtMatricula->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){	
	$pacote = $res[0]['pacote'];
	$aluno = $res[0]['aluno'];
	$id_curso = $res[0]['id_curso'];
	$status_mat = $res[0]['status'];
	$professor = $res[0]['professor'];
	
	if($pacote == 'Sim'){
		$tab = 'pacotes';
	}else{
		$tab = 'cursos';
	}

}

$stmtAlunoUsuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtAlunoUsuario->execute([(int) $aluno]);
$res = $stmtAlunoUsuario->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$nome_aluno = $res[0]['nome'];
	$email_aluno = $res[0]['usuario'];
	$id_pessoa_aluno = $res[0]['id_pessoa'];
}

$stmtAluno = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
$stmtAluno->execute([(int) $id_pessoa_aluno]);
$res = $stmtAluno->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$cartoes = $res[0]['cartao'];
	$usuario_comissao = $res[0]['usuario'];
}





$stmtUsuarioComissao = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtUsuarioComissao->execute([(int) $usuario_comissao]);
$res = $stmtUsuarioComissao->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$nivel_do_usu = $res[0]['nivel'];
}


$stmtProfessor = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtProfessor->execute([(int) $professor]);
$res = $stmtProfessor->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$email_professor = $res[0]['usuario'];
}





//ATUALIZANDO A MATRÇ?CULA
$query = $pdo->prepare("UPDATE matriculas SET status = 'Matriculado', forma_pgto = :forma_pgto, total_recebido = :total_recebido, data = curDate(), obs = :obs  where id = :id");

$query->bindValue(":total_recebido", "$total_recebido");
$query->bindValue(":forma_pgto", "$forma_pgto");
$query->bindValue(":obs", "$obs");
$query->bindValue(":id", (int) $id_mat, PDO::PARAM_INT);
$query->execute();


if($cartao == 'Sim'){
	//ADICIONAR MAIS UM CARTÇŸO PARA O ALUNO
$cartoes += 1;
	$stmtCartao = $pdo->prepare("UPDATE alunos SET cartao = ? WHERE id = ?");
	$stmtCartao->execute([(int) $cartoes, (int) $id_pessoa_aluno]);
}



//LIBERAR OS CURSOS SE FOR UM PACOTE
if($pacote == 'Sim'){
	$stmtPacotesCursos = $pdo->prepare("SELECT * FROM cursos_pacotes WHERE id_pacote = ? order by id desc");
	$stmtPacotesCursos->execute([(int) $id_curso]);
	$res = $stmtPacotesCursos->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);

	if($total_reg > 0){
		for($i=0; $i < $total_reg; $i++){
		foreach ($res[$i] as $key => $value){}
		$id_cursos_pacotes = $res[$i]['id'];
		$id_do_curso = $res[$i]['id_curso'];

		$stmtCurso = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
		$stmtCurso->execute([(int) $id_do_curso]);
		$res2 = $stmtCurso->fetchAll(PDO::FETCH_ASSOC);
		$matriculas = $res2[0]['matriculas'];
		$id_professor = $res2[0]['professor'];
		$quant_mat = $matriculas + 1; 

				
		$stmtMatAlunoCurso = $pdo->prepare("SELECT * FROM matriculas WHERE id_curso = ? AND aluno = ?");
		$stmtMatAlunoCurso->execute([(int) $id_do_curso, (int) $aluno]);
		$res3 = $stmtMatAlunoCurso->fetchAll(PDO::FETCH_ASSOC);
		

		if(@count($res3) > 0){	
			$id_mat = @$res3[0]['id'];
			//excluir a matrÇðcula do curso se ela jÇ­ existir
			$stmtDelete = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
			$stmtDelete->execute([(int) $id_mat]);
		}
			//inserir a matrÇðcula do curso caso ela nÇœo exista
			$stmtInsertMat = $pdo->prepare("INSERT INTO matriculas SET id_curso = :curso, aluno = :aluno, professor = :professor, aulas_concluidas = '1', data = curDate(), status = 'Matriculado', pacote = 'NÇœo', id_pacote = :id_pacote, obs = 'Pacote' ");
			$stmtInsertMat->bindValue(":curso", (int) $id_do_curso, PDO::PARAM_INT);
			$stmtInsertMat->bindValue(":aluno", (int) $aluno, PDO::PARAM_INT);
			$stmtInsertMat->bindValue(":professor", (int) $id_professor, PDO::PARAM_INT);
			$stmtInsertMat->bindValue(":id_pacote", (int) $id_curso, PDO::PARAM_INT);
			$stmtInsertMat->execute();


			//atualizar matriculas do curso
			$stmtUpdateCurso = $pdo->prepare("UPDATE cursos SET matriculas = ? WHERE id = ?");
			$stmtUpdateCurso->execute([(int) $quant_mat, (int) $id_do_curso]);
				

		}
	}

}

//ADICIONAR MAIS UMA VENDA AO CURSO OU PACOTE
$stmtItem = $pdo->prepare("SELECT * FROM $tab WHERE id = ?");
$stmtItem->execute([(int) $id_curso]);
$res2 = $stmtItem->fetchAll(PDO::FETCH_ASSOC);
$matriculas = $res2[0]['matriculas'];
$valor_comissao = 0;
$quantid_mat = $matriculas + 1;
$nome_curso = $res2[0]['nome'];
$stmtUpdateItem = $pdo->prepare("UPDATE $tab SET matriculas = ? WHERE id = ?");
$stmtUpdateItem->execute([(int) $quantid_mat, (int) $id_curso]);


if($nivel_do_usu == 'Professor'){
			$valor_comissao = $comissao_professor;
		}

		if($nivel_do_usu == 'Tutor'){
			$valor_comissao = $comissao_tutor;
		}

		if($nivel_do_usu == 'Parceiro'){
			$valor_comissao = $comissao_parceiro;
		}


//LANÇÎAR COMISSÇŸO DO PROFESSOR
$valor_comissao_pagar = ($valor_comissao * $subtotal) / 100;
if(strtotime($hoje) < strtotime($data_pgto_comissao)){
	$data_venc = $data_pgto_comissao;
}else{
	$data_venc = date('Y-m-d', strtotime("+1 month",strtotime($data_pgto_comissao)));
}



if($valor_comissao_pagar > 0){
	$stmtComissao = $pdo->prepare("INSERT INTO pagar SET descricao = 'ComissÇœo',  valor = :valor, data = curDate(), vencimento = :vencimento, pago = 'NÇœo', arquivo = 'sem-foto.png', professor = :professor, curso = :curso");
	$stmtComissao->bindValue(":valor", $valor_comissao_pagar);
	$stmtComissao->bindValue(":vencimento", $data_venc);
	$stmtComissao->bindValue(":professor", (int) $usuario_comissao, PDO::PARAM_INT);
	$stmtComissao->bindValue(":curso", $nome_curso);
	$stmtComissao->execute();
}



require_once('../../pagamentos/email-aprovar-matricula.php');

$result = json_encode(array('mensagem'=>'Aprovada com sucesso!', 'sucesso'=>true));

echo $result;
?>
