<?php
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$id_mat = isset($_GET['id_mat']) ? (int) $_GET['id_mat'] : 0;
$data_certificado = $_GET['data'] ?? null;
$ano_certificado = $_GET['ano'] ?? null;
$numero_registro = trim((string) ($_GET['numero_registro'] ?? ''));
$folha_livro = trim((string) ($_GET['folha_livro'] ?? ''));
$numero_livro = trim((string) ($_GET['numero_livro'] ?? ''));
include('../conexao.php');

date_default_timezone_set('America/Porto_Velho');

if (!function_exists('formatar_data_extenso_ptbr')) {
	function formatar_data_extenso_ptbr($data = 'today')
	{
		$timestamp = is_int($data) ? $data : strtotime((string) $data);
		if ($timestamp === false) {
			$timestamp = strtotime('today');
		}

		$meses = [
			1 => 'janeiro',
			2 => 'fevereiro',
			3 => 'março',
			4 => 'abril',
			5 => 'maio',
			6 => 'junho',
			7 => 'julho',
			8 => 'agosto',
			9 => 'setembro',
			10 => 'outubro',
			11 => 'novembro',
			12 => 'dezembro',
		];

		return ((int) date('d', $timestamp)) . ' de ' . ($meses[(int) date('n', $timestamp)] ?? '') . ' de ' . date('Y', $timestamp);
	}
}

if (!empty($data_certificado)) {
	$timestamp = strtotime($data_certificado);
	// Se a data for inválida ou vazia, usa a data atual
	if ($timestamp === false) {
		$timestamp = strtotime('today');
	}
	$data_formatada = formatar_data_extenso_ptbr($timestamp);
} else {
	$data_formatada = formatar_data_extenso_ptbr('today');
}

if ($numero_registro !== '') {
	$numero_registro = mb_substr(preg_replace('/\s+/u', ' ', $numero_registro), 0, 30);
}
if ($folha_livro !== '') {
	$folha_livro = mb_substr(preg_replace('/\s+/u', ' ', $folha_livro), 0, 20);
}
if ($numero_livro !== '') {
	$numero_livro = mb_substr(preg_replace('/\s+/u', ' ', $numero_livro), 0, 20);
}

if ($numero_registro === '' || $folha_livro === '' || $numero_livro === '') {
	try {
		$stmtRegistroLivro = $pdo->prepare("
			SELECT numero_registro, folha_livro, numero_livro
			FROM certificados_livro_registro
			WHERE aluno_id = :aluno_id AND categoria = 'medio'
			LIMIT 1
		");
		$stmtRegistroLivro->execute([':aluno_id' => $id]);
		$registroLivro = $stmtRegistroLivro->fetch(PDO::FETCH_ASSOC);
		if ($registroLivro) {
			if ($numero_registro === '') {
				$numero_registro = (string) ($registroLivro['numero_registro'] ?? '');
			}
			if ($folha_livro === '') {
				$folha_livro = (string) ($registroLivro['folha_livro'] ?? '');
			}
			if ($numero_livro === '') {
				$numero_livro = (string) ($registroLivro['numero_livro'] ?? '');
			}
		}
	} catch (Throwable $e) {
		// Nao interrompe o certificado se a tabela ainda nao existir.
	}
}

$numero_registro_exibir = $numero_registro !== '' ? htmlspecialchars($numero_registro, ENT_QUOTES, 'UTF-8') : '---';
$folha_livro_exibir = $folha_livro !== '' ? htmlspecialchars($folha_livro, ENT_QUOTES, 'UTF-8') : '---';
$numero_livro_exibir = $numero_livro !== '' ? htmlspecialchars($numero_livro, ENT_QUOTES, 'UTF-8') : '---';



$query = $pdo->prepare("SELECT * from usuarios where id_pessoa = :id_pessoa order by id desc ");
$query->execute([':id_pessoa' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = count($res);
if ($total_reg === 0) {
	echo 'Aluno nao encontrado.';
	exit;
}
$nome_aluno = $res[0]['nome'];
$pessoa = $res[0]['id_pessoa'];

$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
$query->execute([':id' => $pessoa]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$rg = trim($res[0]['rg'] ?? '');
$orgao_expedidor = trim($res[0]['orgao_expedidor'] ?? '');
$expedicao = trim($res[0]['expedicao'] ?? '');
$naturalidade = $res[0]['naturalidade'] ?? '';
$nascimento = $res[0]['nascimento'] ?? '';

$rg_completo = $rg;
if ($rg_completo !== '' && $orgao_expedidor !== '') {
	$rg_completo .= ' ' . $orgao_expedidor;
}

$identidade_texto = 'Identidade';
if ($rg_completo !== '') {
	$identidade_texto .= ', ' . $rg_completo;
}
if ($expedicao !== '') {
	$identidade_texto .= ', expedida em ' . $expedicao;
}

date_default_timezone_set('America/Porto_Velho');
$data_hoje = formatar_data_extenso_ptbr('today');



?>



<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet"
	integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">
<style>
	@page {
		margin: 00px;

	}





	.imagem {
		width: 100%;
	}




	.descricao {
		position: absolute;
		margin-top: 415px;
		text-align: left:;
		color: black;
		font-size: 16px;
		width: 90%;
		margin-left: 55px;
	}



	.data {
		position: absolute;
		margin-top: 470px;
		text-align: center;
		color: black;
		font-size: 16px;
		width: 100%;
		margin-left: 55px;
	}

	.descricao2 {
		position: absolute;
		margin-top: 560px;
		text-align: left:;
		color: #473e3a;
		font-size: 12px;
		width: 90%;
		margin-left: 05px;
	}

	.data2 {
		position: absolute;
		margin-top: 455px;
		text-align: center;
		color: black;
		font-size: 16px;
		width: 100%;
		margin-left: 55px;
	}

	.imagem2 {
		width: 100%;
		position: absolute;
	}

	.nome-aluno {
		position: absolute;
		margin-top: 345px;
		text-align: center;
		color: #000;
		font-size: 26px;
		width: 100%;

	}

	.id {
		position: absolute;
		margin-top: 50px;
		margin-left: 965px;
		text-align: center;
		color: #fff;
		font-size: 16px;
		width: 100%;
		opacity: 0.1;
	}

	.carimbo-verso {
		position: absolute;
		top: 45px;
		right: 40px;
		width: 225px;
		border: 2px solid #000;
		padding: 7px 10px;
		text-align: center;
		line-height: 1.05;
		font-family: Arial, Helvetica, sans-serif;
		color: #000;
		background: #fff;
		z-index: 20;
	}

	.carimbo-verso .titulo {
		font-size: 22px;
		font-weight: 700;
	}

	.carimbo-verso .linha {
		font-size: 14px;
		font-weight: 700;
	}

	.conteudo {
		position: absolute;
		top: 130px;
		left: 45px;
		width: 720px;
		text-align: center;
		color: #000;
		font-size: 20px;
		font-weight: 700;
		font-family: "Times New Roman", Times, serif;
		line-height: 1.35;
	}
</style>

<!DOCTYPE html>

<head>


</head>



<body>
	<div class="id"> <?php echo $id_mat ?: $id; ?></div>
	<div class="nome-aluno"> <b><br><br><?php echo mb_strtoupper($nome_aluno); ?></b></div>

	<div class="descricao"><br><br> <?php echo $identidade_texto; ?>, Nacionalidade Brasileiro(a), Natural de <?php echo $naturalidade ?>, Nascido(a) em, <?php echo $nascimento ?>, o presente
		CERTIFICADO por haver concluído no ano de <?php echo $ano_certificado; ?> o Ensino Médio, nos Exames de Finalização de Etapas – EJA –
		Educação e Jovens e Adultos. </div>


	<div class="data"> <br><br> Buritis - <?php echo $data_formatada ?></div>

	<img class="imagem" src="<?php echo $url_sistema ?>sistema/img/certificado-fundo.jpg">

	<div class="verso">
		<img class="imagem2" src="<?php echo $url_sistema ?>sistema/img/certificado-verso.jpg">
		<div class="carimbo-verso">
			<div class="titulo">SESTED</div>
			<div class="linha">Autoriza&ccedil;&atilde;o de Funcionamento</div>
			<div class="linha">Parecer CEB/CEE/RO n&ordm; 003/24</div>
			<div class="linha">Resolu&ccedil;&atilde;o n&ordm; 011/23 - CEE/RO</div>
			<div class="linha">CNPJ 07.158.229/0001-06</div>
			<div class="linha">BURITIS - RO</div>
		</div>
		<div class="conteudo">
			Registro N&ordm; <?php echo $numero_registro_exibir; ?> &nbsp;&nbsp;&nbsp; FL <?php echo $folha_livro_exibir; ?> &nbsp;&nbsp;&nbsp; Livro N&ordm; <?php echo $numero_livro_exibir; ?>
		</div>
		<div class="data2"> Buritis - <?php echo ($data_formatada); ?>
		</div>
	</div>

</body>

</html>
