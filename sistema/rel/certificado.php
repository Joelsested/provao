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

$numero_registro_exibir = $numero_registro !== '' ? htmlspecialchars($numero_registro, ENT_QUOTES, 'UTF-8') : '';
$folha_livro_exibir = $folha_livro !== '' ? htmlspecialchars($folha_livro, ENT_QUOTES, 'UTF-8') : '';
$numero_livro_exibir = $numero_livro !== '' ? htmlspecialchars($numero_livro, ENT_QUOTES, 'UTF-8') : '';



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

	.conteudo {
		position: absolute;
		top: 326px;
		left: 90;
		width: 100%;
		height: 30px;
		color: #000;
		font-size: 19px;
		font-weight: 500;
		font-family: "Times New Roman", Times, serif;
	}

	.conteudo .campo-registro {
		position: absolute;
		left: 286px;
		width: 90px;
		text-align: center;
	}

	.conteudo .campo-folha {
		position: absolute;
		left: 388px;
		width: 70px;
		text-align: center;
	}

	.conteudo .campo-livro {
		position: absolute;
		left: 498px;
		width: 95px;
		text-align: center;
	}
</style>

<!DOCTYPE html>

<head>


</head>



<body>
	<div class="id"> <?php echo $id_mat ?: $id; ?></div>
	<div class="nome-aluno"> <b><br><br><?php echo mb_strtoupper($nome_aluno); ?></b></div>

	<div class="descricao"><br><br> <?php echo $identidade_texto; ?>, Nacionalidade Brasileiro(a), Natural de <?php echo $naturalidade ?>, Nascido(a) em, <?php echo $nascimento ?>, o presente
		CERTIFICADO por haver concluído no ano de <?php echo $ano_certificado; ?> o Ensino Médio, nos Exames de Finalização de Etapas - EJA -
		Educação e Jovens e Adultos. </div>


	<div class="data"> <br><br> Buritis - <?php echo $data_formatada ?></div>

	<img class="imagem" src="<?php echo $url_sistema ?>sistema/img/certificado-fundo.jpg">

	<div class="verso">
		<img class="imagem2" src="<?php echo $url_sistema ?>sistema/img/certificado-verso.jpg">
		<div class="conteudo">
			<span class="campo-registro"><?php echo $numero_registro_exibir; ?></span>
			<span class="campo-folha"><?php echo $folha_livro_exibir; ?></span>
			<span class="campo-livro"><?php echo $numero_livro_exibir; ?></span>
		</div>
		<div class="data2"> Buritis - <?php echo ($data_formatada); ?>
		</div>
	</div>

</body>

</html>
