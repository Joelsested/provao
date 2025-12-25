<?php 
require_once('../../sistema/conexao.php');
@session_start();
$id_aluno = $_SESSION['id'];

if (empty($id_aluno)) {
	echo 'Usuario nao autenticado.';
	exit();
}

$data_hoje = date('Y-m-d');

$codigo = $_POST['cupom'];
$curso = $_POST['id_curso_cupom'];

$quantidade = 0;

$id_aluno = (int) $id_aluno;
$curso = (int) $curso;

$stmtUsuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtUsuario->execute([$id_aluno]);
$res = $stmtUsuario->fetchAll(PDO::FETCH_ASSOC);
$id_pessoa = $res[0]['id_pessoa'];

$stmtAluno = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
$stmtAluno->execute([(int) $id_pessoa]);
$res = $stmtAluno->fetchAll(PDO::FETCH_ASSOC);
$cartoes = $res[0]['cartao'];

$valor_cupom = 0;
$codigo_cartao = '';

if($cartoes >= $cartoes_fidelidade and ($codigo == 'cartao' || $codigo == 'cartÇœo')){
	$valor_cupom = $valor_max_cartao;
	$codigo_cartao = 'aprovado';

	//apagar cartÇæes do aluno
	$stmtZerar = $pdo->prepare("UPDATE alunos SET cartao = '0' WHERE id = ?");
	$stmtZerar->execute([(int) $id_pessoa]);
}



$stmtCupom = $pdo->prepare("SELECT * FROM cupons WHERE codigo = ?");
$stmtCupom->execute([$codigo]);
$res = $stmtCupom->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 || $codigo_cartao == 'aprovado'){
	if(@count($res) > 0){
		$valor_cupom = $res[0]['valor'];
		$tipo = $res[0]['tipo'];
		$data_validade = $res[0]['data_validade'];
		$quantidade = $res[0]['quantidade'];
	}

	if(@count($res) > 0){
		if($data_validade != ""){
			if(strtotime($data_validade) < strtotime($data_hoje)){
				echo 'Cupom Vencido!';
				exit();
			}
		}
		

		if($quantidade <= 0){
			echo 'Quantidade de Cupom zerada!';
			exit();
		}



	}
	

	//abater o valor na matricula
	$stmtMat = $pdo->prepare("SELECT * FROM matriculas WHERE id_curso = ? AND aluno = ?");
	$stmtMat->execute([$curso, $id_aluno]);
	$res2 = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
	$valor_mat = $res2[0]['subtotal'];
	$id_mat = $res2[0]['id'];
	$valor_cupom_mat = $res2[0]['valor_cupom'];
	$pacote = $res2[0]['pacote'];

	if($pacote == 'Sim'){
		$tab = 'pacotes';
	}else{
		$tab = 'cursos';
	}

	if($valor_cupom_mat > 0){
		echo 'VocÇ¦ jÇ­ utilizou um cupom para este curso, nÇœo pode utilizar outro!';
		exit();
	}

	if(@$tipo == '%'){
			$valor_desconto = $valor_mat - ($valor_mat * $valor_cupom / 100);
			
		}else{
			$valor_desconto = $valor_mat - $valor_cupom;
		}

	

	$valor_pix = $valor_desconto;
	if($desconto_pix > 0){
			$valor_pix = $valor_desconto - ($valor_desconto * ($desconto_pix / 100));
		}

		$valor_descontoF = number_format($valor_desconto, 2, ',', '.');	
		$valor_pixF = number_format($valor_pix, 2, ',', '.');	
		$valor_cupomF = number_format($valor_cupom, 2, ',', '.');		


	$stmtUpdateMat = $pdo->prepare("UPDATE matriculas SET valor_cupom = ?, subtotal = ? WHERE id = ?");
	$stmtUpdateMat->execute([$valor_cupom, $valor_desconto, (int) $id_mat]);

	$nova_quantidade = $quantidade - 1;
	//abater 1 na quantidade do cupom
	$stmtUpdateCupom = $pdo->prepare("UPDATE cupons SET quantidade = ? WHERE codigo = ?");
	$stmtUpdateCupom->execute([(int) $nova_quantidade, $codigo]);


	$stmtAlunoNome = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
	$stmtAlunoNome->execute([$id_aluno]);
	$res2 = $stmtAlunoNome->fetchAll(PDO::FETCH_ASSOC);
	$nome_aluno = $res2[0]['nome'];

	$stmtCursoNome = $pdo->prepare("SELECT * FROM $tab WHERE id = ?");
	$stmtCursoNome->execute([$curso]);
	$res2 = $stmtCursoNome->fetchAll(PDO::FETCH_ASSOC);
	$nome_curso = $res2[0]['nome'];

	//email de uso do cupom
	$destinatario = $email_sistema;
	$assunto = 'Cupom Usado no Curso ' .$nome_curso;
	$mensagem = "Aluno $nome_aluno, acaba de usar um cupom de valor $valor_cupomF no curso $nome_curso!!";

	$remetente = $email_sistema;
	$cabecalhos = 'MIME-Version: 1.0' . "\r\n";
	$cabecalhos .= 'Content-type: text/html; charset=utf-8;' . "\r\n";
	$cabecalhos .= "From: " .$remetente;

	@mail($destinatario, $assunto, $mensagem, $cabecalhos);


	echo 'Cupom Utilizado-'.$valor_descontoF.'-'.$valor_pixF;
}else{
	echo 'CÇüdigo Incorreto, cupom Inexistente!';
}

?>
