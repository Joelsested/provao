<?php
$id = $_GET['id'];
$data_certificado = $_GET['data'];
$ano_certificado = $_GET['ano'];

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
	if ($timestamp === false) {
		$timestamp = strtotime('today');
	}
	$data_formatada = formatar_data_extenso_ptbr($timestamp);
} else {
	$data_formatada = formatar_data_extenso_ptbr('today');
}

$query = $pdo->prepare("SELECT * from usuarios where id_pessoa = :id_pessoa order by id desc ");
$query->execute([':id_pessoa' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$nome = (string) ($res[0]['nome'] ?? '');
$pessoa = (int) ($res[0]['id_pessoa'] ?? 0);

$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
$query->execute([':id' => $pessoa]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$rg = (string) ($res[0]['rg'] ?? '');
$orgao_expedidor = (string) ($res[0]['orgao_expedidor'] ?? '');
$rg_completo = trim($rg);
if ($orgao_expedidor !== '') {
	$rg_completo = trim($rg_completo . ' ' . $orgao_expedidor);
}
$nascimento = (string) ($res[0]['nascimento'] ?? '');
$naturalidade = (string) ($res[0]['naturalidade'] ?? '');
$pai = (string) ($res[0]['pai'] ?? '');
$mae = (string) ($res[0]['mae'] ?? '');

date_default_timezone_set('America/Sao_Paulo');
$fundo_declaracao = rtrim((string) $url_sistema, '/') . '/sistema/img/' . rawurlencode('declaração-matriculado-medio.jpg');
?>

<link href="<?php echo $url_sistema ?>sistema/rel/css/declaracoes.css" rel="stylesheet">
<style>
@page {
	margin: 5px;
}

body {
	margin-top: 0;
	font-family: Times, "Times New Roman", Georgia, serif;
}

.imagem {
	width: 100%;
}

.descricao {
	position: absolute;
	margin-top: 460px;
	text-align: justify;
	text-justify: inter-word;
	text-indent: 0;
	color: #000;
	font-size: 16px;
	left: 55px;
	right: 55px;
	width: auto;
	margin-left: 0;
	margin-right: 0;
	line-height: 1.35;
	overflow-wrap: break-word;
	white-space: normal;
	word-spacing: normal;
	letter-spacing: normal;
	hyphens: auto;
}

.titulo-declaracao {
	position: absolute;
	top: 410px;
	left: 55px;
	right: 55px;
	text-align: center;
	font-size: 28px;
	font-weight: 700;
	color: #000;
	font-family: Times, "Times New Roman", Georgia, serif;
}

.data {
	position: absolute;
	margin-top: 564px;
	text-align: left;
	color: #000;
	font-size: 16px;
	width: 80%;
	margin-left: 55px;
}

.assinatura {
	position: absolute;
	top: 720px;
	left: 0;
	width: 100%;
	text-align: center;
	color: #000;
	font-family: Arial, Helvetica, sans-serif;
}

.assinatura .linha-assinatura {
	width: 260px;
	border-top: 1px solid #666;
	margin: 0 auto 10px;
}

.assinatura .nome {
	font-size: 14px;
	font-weight: 400;
	line-height: 1.1;
}

.assinatura .rg {
	font-size: 14px;
	font-weight: 400;
	line-height: 1.1;
}

.assinatura .cargo {
	font-size: 14px;
	font-weight: 400;
	line-height: 1.1;
}

.cabecalho-novo {
	position: absolute;
	top: 30px;
	left: 40px;
	width: 715px;
	background: #fff;
	z-index: 5;
	text-align: center;
	padding: 0;
}

.cabecalho-novo .cabecalho-logo-wrap {
	text-align: center;
	margin: 0 0 12px;
}

.cabecalho-novo .cabecalho-logo {
	width: 160px;
	height: 120px;
	display: inline-block;
	margin: 62px;
}

.cabecalho-novo .cabecalho-titulo {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 17px;
	font-weight: 700;
	line-height: 1.18;
	letter-spacing: 0;
	margin: 0 0 10px;
	text-transform: none;
}

.cabecalho-novo .cabecalho-linha {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;
	line-height: 1.2;
	margin: 0;
}

.cabecalho-novo .cabecalho-linha + .cabecalho-linha {
	margin-top: 1px;
}
</style>

<!DOCTYPE html>
<head>
</head>

<body>
<div class="cabecalho-novo">
	<div class="cabecalho-logo-wrap">
		<img class="cabecalho-logo" src="<?php echo $url_sistema ?>img/logo.jpg" alt="Logo SESTED">
	</div>
	<div class="cabecalho-titulo">SISTEMA DE ENSINO SUPERIOR TECNOL&Oacute;GICO E EDUCACIONAL - SESTED</div>
	<p class="cabecalho-linha">Mantenedora: SESTED - Sistema de Ensino Superior Tecnol&oacute;gico e Educacional - ME</p>
	<p class="cabecalho-linha">CNPJ: 07.158.229/0001-06</p>
	<p class="cabecalho-linha">Rua Nova Uni&atilde;o, n&ordm; 2024, Setor 02, Buritis/RO - CEP 76880-000</p>
	<p class="cabecalho-linha">e-mail: sestedcursos@gmail.com | Tel. (69) 99694-538</p>
	<p class="cabecalho-linha">Credenciado pelo Parecer CEB/CEE/RO n&ordm; 003/24 e Resolu&ccedil;&atilde;o CEB/CEE/RO n&ordm; 909/24</p>
</div>

<div class="titulo-declaracao">Declara&ccedil;&atilde;o de Matr&iacute;cula</div>

<div class="descricao">Declaramos para os devidos fins que o(a) estudante, <?php echo $nome ?>, <?php echo $rg_completo ?>, nascido(a) em <?php echo $nascimento ?>, na cidade de <?php echo $naturalidade ?>, filho(a) de <?php echo $pai ?> e <?php echo $mae ?>, est&aacute; MATRICULADO(A) no Ensino M&eacute;dio nos Exames de Conclus&atilde;o de Etapas, da modalidade Ensino Jovens e Adultos (EJA), no ano de <?php echo $ano_certificado ?> nesta Institui&ccedil;&atilde;o de Ensino Escolar. Por express&atilde;o da verdade, firmamos a presente declara&ccedil;&atilde;o em duas vias de igual forma e teor.</div>

<div class="data"><br><br> Buritis - <?php echo $data_formatada ?></div>

<div class="assinatura">
	<div class="linha-assinatura"></div>
	<div class="nome">Daniely Jonjob da Silva</div>
	<div class="rg">RG: 1480635 SESDEC/RO</div>
	<div class="cargo">Secret&aacute;ria</div>
</div>

<img class="imagem" src="<?php echo $fundo_declaracao; ?>">
</body>
</html>
