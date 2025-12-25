<?php 
require_once('../../sistema/conexao.php');
@session_start();
$id_aluno = $_SESSION['id'];

if (empty($id_aluno)) {
	echo 'Usuario nao autenticado.';
	exit();
}


$id_curso = @$_POST['id_curso_cartao'];
$id_aluno = (int) $id_aluno;
$id_curso = (int) $id_curso;

//nome do curso
$stmtCurso = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
$stmtCurso->execute([$id_curso]);
$res2 = $stmtCurso->fetchAll(PDO::FETCH_ASSOC);
$nome_curso = $res2[0]['nome'];
$valor_curso = $res2[0]['valor'];
$matriculas = $res2[0]['matriculas'];
$quantid_mat = $matriculas + 1;

//verificar se o aluno possui 5 cartoes
$stmtUsuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtUsuario->execute([$id_aluno]);
$res2 = $stmtUsuario->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$id_pessoa = $res2[0]['id_pessoa'];
	$nome_aluno = $res2[0]['nome'];
		$stmtAluno = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
		$stmtAluno->execute([(int) $id_pessoa]);
		$res3 = $stmtAluno->fetchAll(PDO::FETCH_ASSOC);
		if(@count($res3) > 0){
			$quant_cartoes = $res3[0]['cartao'];
			if(@$quant_cartoes < $cartoes_fidelidade and $valor_curso <= $valor_max_cartao){
				exit();
			}
		}
}

//libera a matricula
$stmtMat = $pdo->prepare("UPDATE matriculas SET status = 'Matriculado', subtotal = '0', obs = 'Cartǜo Fidelidade' WHERE aluno = ? AND id_curso = ?");
$stmtMat->execute([$id_aluno, $id_curso]);

//zerar o cartǜo
$stmtCartao = $pdo->prepare("UPDATE alunos SET cartao = '0' WHERE id = ?");
$stmtCartao->execute([(int) $id_pessoa]);


//incrementar um aluno na matricula
$stmtCursos = $pdo->prepare("UPDATE cursos SET matriculas = ? WHERE id = ?");
$stmtCursos->execute([(int) $quantid_mat, $id_curso]);


//mande email alertando uso do cartǜo
$destinatario = $email_sistema;
$assunto = 'Cartǜo Fidelidade usado no ' .$nome_curso;

$mensagem = "O Aluno $nome_aluno, usou um cartǜo fidelidade no curso $nome_curso!!";

$remetente = $email_sistema;
$cabecalhos = 'MIME-Version: 1.0' . "\r\n";
$cabecalhos .= 'Content-type: text/html; charset=utf-8;' . "\r\n";
$cabecalhos .= "From: " .$remetente;
@mail($destinatario, $assunto, $mensagem, $cabecalhos);

echo 'Cartǜo Utilizado';

?>
