<?php

require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../../helpers.php");
@session_start();
$hoje = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_pgto_comissao = $ano_atual.'-'.$mes_atual.'-'.$dia_pgto_comissao;

$forma_pgto = strtoupper(trim((string) ($_POST['forma_pgto'] ?? '')));
$subtotal = $_POST['valor'];
$subtotal = str_replace(',', '.', $subtotal);
$obs = $_POST['obs'];
$cartao = $_POST['cartao'];
$id_mat = $_POST['id_mat'];
$nivel_usuario_logado = (string) ($_SESSION['nivel'] ?? '');

$formasPermitidas = ['BOLETO', 'BOLETO_PARCELADO', 'CARTAO_DE_CREDITO', 'CARTAO_RECORRENTE'];
if (!in_array($forma_pgto, $formasPermitidas, true)) {
    $forma_pgto = 'BOLETO';
}

function idadeCompletaEmAnos(string $dataNascimento, ?DateTimeImmutable $hojeRef = null): int
{
    $dataNormalizada = function_exists('normalizeDate') ? normalizeDate($dataNascimento) : trim($dataNascimento);
    if ($dataNormalizada === '' || $dataNormalizada === '0000-00-00') {
        return -1;
    }

    $hoje = $hojeRef ?: new DateTimeImmutable('today');
    try {
        $nascimento = new DateTimeImmutable($dataNormalizada);
    } catch (Throwable $e) {
        return -1;
    }

    if ($nascimento > $hoje) {
        return -1;
    }

    return (int) $nascimento->diff($hoje)->y;
}

function colunaExisteTabela(PDO $pdo, string $tabela, string $coluna): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
        $stmt->execute([':coluna' => $coluna]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

function garantirColunaLiberacaoMenor(PDO $pdo): void
{
    if (colunaExisteTabela($pdo, 'alunos', 'liberado_menor_18')) {
        return;
    }
    try {
        $pdo->exec("ALTER TABLE alunos ADD COLUMN liberado_menor_18 TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {
        // Mantem fluxo caso nao consiga alterar estrutura.
    }
}

$total_recebido = $subtotal;
if ($forma_pgto === 'BOLETO' || $forma_pgto === 'BOLETO_PARCELADO') {
    $total_recebido = $subtotal - $taxa_boleto;
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
    garantirColunaLiberacaoMenor($pdo);
	$cartoes = $res[0]['cartao'];
	$usuario_comissao = $res[0]['usuario'];
    $nascimento_aluno = (string) ($res[0]['nascimento'] ?? '');
    $liberado_menor = (int) ($res[0]['liberado_menor_18'] ?? 0) === 1;
    $idade_aluno = idadeCompletaEmAnos($nascimento_aluno);
    $eh_menor = ($idade_aluno >= 0 && $idade_aluno < 18);
    if ($eh_menor && !$liberado_menor && $nivel_usuario_logado !== 'Administrador') {
        echo 'Aluno menor de 18 anos. Só admin pode liberar matrículas para alunos menores.';
        exit;
    }
    if ($eh_menor && $nivel_usuario_logado === 'Administrador' && !$liberado_menor) {
        $stmtLiberacao = $pdo->prepare("UPDATE alunos SET liberado_menor_18 = 1 WHERE id = ?");
        $stmtLiberacao->execute([(int) $id_pessoa_aluno]);
    }
}





$stmtUsuarioComissao = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtUsuarioComissao->execute([(int) $usuario_comissao]);
$res = $stmtUsuarioComissao->fetchAll(PDO::FETCH_ASSOC);
$id_pessoa_comissao = 0;
if(@count($res) > 0){
	$nivel_do_usu = $res[0]['nivel'];
	$id_pessoa_comissao = (int) ($res[0]['id_pessoa'] ?? 0);
}




$stmtProfessor = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtProfessor->execute([(int) $professor]);
$res = $stmtProfessor->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$email_professor = $res[0]['usuario'];
}





//ATUALIZANDO A MATRÍCULA
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

				
		$stmtMatAlunoCurso = $pdo->prepare("SELECT * FROM matriculas WHERE id_curso = ? AND aluno = ? AND (pacote != 'Sim' OR pacote IS NULL OR pacote = '')");
		$stmtMatAlunoCurso->execute([(int) $id_do_curso, (int) $aluno]);
		$res3 = $stmtMatAlunoCurso->fetchAll(PDO::FETCH_ASSOC);
		

		if(@count($res3) > 0){	
			$id_mat = @$res3[0]['id'];
			//excluir a matrÇðcula do curso se ela jÇ­ existir
			$stmtDelete = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
			$stmtDelete->execute([(int) $id_mat]);
		}
			//inserir a matrÇðcula do curso caso ela nÇœo exista
			$stmtInsertMat = $pdo->prepare("INSERT INTO matriculas SET id_curso = :curso, aluno = :aluno, professor = :professor, aulas_concluidas = '1', data = curDate(), status = 'Matriculado', pacote = 'Nao', id_pacote = :id_pacote, obs = 'Pacote' ");
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
			$valor_comissao = (float) $comissao_tutor;
			if($id_pessoa_comissao > 0){
				$stmtColTutor = $pdo->prepare("SHOW COLUMNS FROM tutores LIKE 'comissao_meus_alunos'");
				$stmtColTutor->execute();
				$temColunaMeusTutor = (bool) $stmtColTutor->fetchColumn();

				if($temColunaMeusTutor){
					$stmtTutorComissao = $pdo->prepare("SELECT COALESCE(comissao_meus_alunos, comissao) FROM tutores WHERE id = ? LIMIT 1");
				}else{
					$stmtTutorComissao = $pdo->prepare("SELECT comissao FROM tutores WHERE id = ? LIMIT 1");
				}
				$stmtTutorComissao->execute([(int) $id_pessoa_comissao]);
				$valorTutor = $stmtTutorComissao->fetchColumn();
				if($valorTutor !== false && $valorTutor !== null){
					$valor_comissao = (float) $valorTutor;
				}
			}
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
	var_dump('teste');

	$stmtComissao = $pdo->prepare("INSERT INTO pagar SET descricao = 'ComissÇœo',  valor = :valor, data = curDate(), vencimento = :vencimento, pago = 'NÇœo', arquivo = 'sem-foto.png', professor = :professor, curso = :curso");
	$stmtComissao->bindValue(":valor", $valor_comissao_pagar);
	$stmtComissao->bindValue(":vencimento", $data_venc);
	$stmtComissao->bindValue(":professor", (int) $usuario_comissao, PDO::PARAM_INT);
	$stmtComissao->bindValue(":curso", $nome_curso);
	$stmtComissao->execute();
}else{
	echo "string";
}


echo 'Matriculado com Sucesso';

//ENVIAR EMAIL PARA O ADM, PROFESSOR E ALUNO
require_once('../../../../pagamentos/email-aprovar-matricula.php');







?>






