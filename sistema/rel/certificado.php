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
$cpf = preg_replace('/\\D+/', '', (string) ($res[0]['cpf'] ?? ''));
$naturalidade = trim((string) ($res[0]['naturalidade'] ?? ''));
$estado = trim((string) ($res[0]['estado'] ?? ''));
$nascimento = trim((string) ($res[0]['nascimento'] ?? ''));

$documento_identificacao = 'nao informado';
if (strlen($cpf) === 11) {
	$documento_identificacao = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
} elseif ($cpf !== '') {
	$documento_identificacao = $cpf;
}

$naturalidade_normalizada = str_replace(["\\'", '\\"'], ["'", '"'], $naturalidade);
$naturalidade_exibir = $naturalidade_normalizada !== '' ? $naturalidade_normalizada : 'nao informado';
$estado_exibir = $estado !== '' ? mb_strtoupper($estado) : 'nao informado';

$nascimento_exibir = 'nao informado';
if ($nascimento !== '') {
	$timestampNascimento = strtotime($nascimento);
	if ($timestampNascimento !== false) {
		$nascimento_exibir = date('d/m/Y', $timestampNascimento);
	}
}

date_default_timezone_set('America/Porto_Velho');
$data_hoje = formatar_data_extenso_ptbr('today');
$esc = static function ($valor) {
	return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

$resolverArquivoCertificado = static function (array $candidatos): ?string {
	$raizProjeto = dirname(__DIR__, 2);
	foreach ($candidatos as $relativo) {
		$caminho = $raizProjeto . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim((string) $relativo, '/\\'));
		if (is_file($caminho)) {
			return $caminho;
		}
	}
	return null;
};

$arquivoParaDataUri = static function (?string $caminhoArquivo): ?string {
	if ($caminhoArquivo === null || !is_file($caminhoArquivo)) {
		return null;
	}
	$conteudo = @file_get_contents($caminhoArquivo);
	if ($conteudo === false || $conteudo === '') {
		return null;
	}
	$mime = 'image/jpeg';
	$ext = strtolower((string) pathinfo($caminhoArquivo, PATHINFO_EXTENSION));
	if ($ext === 'png') {
		$mime = 'image/png';
	} elseif ($ext === 'webp') {
		$mime = 'image/webp';
	}
	return 'data:' . $mime . ';base64,' . base64_encode($conteudo);
};

$numero_registro_exibir = $numero_registro !== '' ? $esc($numero_registro) : '';
$folha_livro_exibir = $folha_livro !== '' ? $esc($folha_livro) : '';
$numero_livro_exibir = $numero_livro !== '' ? $esc($numero_livro) : '';

$arquivoFrente = $resolverArquivoCertificado([
	'database/cdh/certificado_frente.jpg',
	'database/cdh/certificado_frente.JPG',
	'database/cdh/Certificado_frente.jpg',
	'database/cdh/Certificado_frente.JPG',
	'database/cdh/certificado_frente.png',
	'database/cdh/Certificado_frente.png',
	'database/certificado_frente.jpg',
	'database/certificado_frente.JPG',
	'database/Certificado_frente.jpg',
	'database/Certificado_frente.JPG',
	'database/certificado_frente.png',
	'database/Certificado_frente.png',
]);

$arquivoVerso = $resolverArquivoCertificado([
	'database/cdh/certificado_verso.jpg',
	'database/cdh/certificado_verso.JPG',
	'database/cdh/Certificado_verso.jpg',
	'database/cdh/certificado_verso.jpg',
	'database/cdh/Certificado_verso.JPG',
	'database/cdh/certificado_verso.JPG',
	'database/cdh/Certificado_verso.png',
	'database/cdh/certificado_verso.png',
	'database/certificado_verso.jpg',
	'database/certificado_verso.JPG',
	'database/Certificado_verso.jpg',
	'database/Certificado_verso.JPG',
	'database/certificado_verso.png',
	'database/Certificado_verso.png',
]);

$urlImagemFrente = $arquivoParaDataUri($arquivoFrente) ?: ($url_sistema . 'database/cdh/certificado_frente.jpg');
$urlImagemVerso = $arquivoParaDataUri($arquivoVerso) ?: ($url_sistema . 'database/cdh/Certificado_verso.jpg');
$temFundoFrente = $arquivoFrente !== null;
$temFundoVerso = $arquivoVerso !== null;



?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<style>
	@page {
		size: A4 landscape;
		margin: 0;
	}

	* {
		box-sizing: border-box;
	}

	html,
	body {
		margin: 0;
		padding: 0;
		font-family: "Times New Roman", Times, serif;
	}

	body {
		font-size: 0;
		line-height: 0;
	}

		.pagina {
			position: relative;
			width: 297mm;
			height: 210mm;
			page-break-inside: avoid;
			overflow: hidden;
			font-size: 0;
		}

		.pagina.frente {
			page-break-after: always;
		}

		.pagina.verso {
			page-break-after: auto;
		}

	.fundo {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
		z-index: 0;
	}

	.moldura-certificado {
		position: absolute;
		top: 5mm;
		left: 5mm;
		right: 5mm;
		bottom: 5mm;
		border: 0.8mm solid #111111;
		z-index: 1;
		pointer-events: none;
	}

	.marca-dagua-certificado {
		position: absolute;
		left: 50%;
		transform: translateX(-50%);
		font-family: Arial, Helvetica, sans-serif;
		font-weight: 700;
		letter-spacing: 1px;
		color: rgba(0, 180, 220, 0.18);
		z-index: 1;
		pointer-events: none;
		white-space: nowrap;
	}

	.marca-dagua-frente {
		bottom: 17mm;
		font-size: 62pt;
	}

	.marca-dagua-verso {
		bottom: 19mm;
		font-size: 56pt;
	}

	.id {
		position: absolute;
		top: 8mm;
		right: 12mm;
		font-size: 8pt;
		color: #ffffff;
		opacity: 0.25;
	}

		.cabecalho-frente {
			position: absolute;
			top: 15mm;
			left: 0;
			width: 100%;
			text-align: center;
			color: #000000;
		}

	.cabecalho-frente .logo {
		width: 42mm;
		height: 31.5mm;
		margin-bottom: 1.2mm;
	}

	.cabecalho-frente .titulo-sistema {
		font-size: 20pt;
		font-weight: 700;
		color: #0c66b5;
		line-height: 1.1;
		margin-bottom: 1.5mm;
	}

	.cabecalho-frente .linha {
		font-size: 8.6pt;
		line-height: 1.2;
	}

	.titulo-certificado-frente {
		position: absolute;
		top: 78mm;
		left: 0;
		width: 100%;
		text-align: center;
		font-size: 25pt;
		font-weight: 700;
		color: #000000;
	}

	.introducao-fixa-frente {
		position: absolute;
		top: 84mm;
		left: 16mm;
		right: 16mm;
		font-size: 12.5pt;
		line-height: 1.3;
		text-align: justify;
		color: #000000;
	}

	.nome-aluno {
		position: absolute;
		top: 106mm;
		left: 0;
		width: 100%;
		text-align: center;
		font-size: 25pt;
		font-weight: 700;
		color: #000000;
		letter-spacing: 0.4px;
	}

	.descricao {
		position: absolute;
		top: 116mm;
		left: 16mm;
		right: 16mm;
		font-size: 12pt;
		line-height: 1.32;
		text-align: justify;
		color: #000000;
	}

		.data-frente {
			position: absolute;
			top: 133mm;
			right:18mm;
			width: 100%;
			text-align: right;
			font-size: 11.8pt;
			color: #000000;
		}

		.assinaturas-frente {
			position: absolute;
			top: 172mm;
			left: 18mm;
			width: 261mm;
			height: 26mm;
			z-index: 5;
		}

		.assinatura-coluna {
			position: absolute;
			top: 0;
			width: 83mm;
			text-align: center;
			color: #000000;
		}

		.assinatura-esquerda {
			left: 0;
		}

		.assinatura-centro {
			left: 89mm;
		}

		.assinatura-direita {
			left: 178mm;
		}

		.assinatura-coluna .linha-assinatura {
			width: 100%;
			border-top: 1px solid #000000;
			margin-bottom: 1.5mm;
		}

		.assinatura-esquerda .linha-assinatura,
		.assinatura-direita .linha-assinatura {
			width: 82%;
			margin-left: auto;
			margin-right: auto;
		}

		.assinatura-coluna .nome {
			font-size: 10.5pt;
			font-weight: 700;
			line-height: 1.2;
		}

		.assinatura-coluna .documento {
			font-size: 9pt;
			line-height: 1.2;
		}

		.assinatura-coluna .cargo {
			font-size: 11pt;
			font-weight: 700;
			line-height: 1.2;
		}

		.assinatura-coluna.assinatura-concluinte .cargo {
			font-size: 10.5pt;
			font-weight: 500;
		}

		.cabecalho-verso {
			position: absolute;
			top: 16mm;
			left: 0;
			width: 100%;
			text-align: center;
			color: #000000;
		}

		.cabecalho-verso .logo {
			width:42mm;
			height: 32mm;
			margin-bottom: 2mm;
		}

		.cabecalho-verso .titulo {
			font-size: 14pt;
			font-weight: 700;
			line-height: 1.2;
			margin-bottom: 3mm;
		}

		.cabecalho-verso .linha {
			font-size: 11pt;
			line-height: 1.25;
		}

		.registro-verso {
			position: absolute;
			top: 102mm;
			left: 0;
			width: 100%;
			text-align: center;
			font-size: 12pt;
			color: #000000;
		}

		.registro-verso .linha-registro {
			display: table;
			table-layout: fixed;
			width: 136mm;
			margin: 0 auto;
		}

		.registro-verso .item-registro {
			display: table-cell;
			width: 52mm;
			text-align: center;
		}

		.registro-verso .rotulo-registro {
			display: inline-block;
			font-weight: 700;
			font-size: 13.2pt;
		}

		.registro-verso .valor-registro {
			display: inline-block;
			min-width: 12mm;
			margin-left: 1.5mm;
			text-align: center;
			font-weight: 700;
			font-size: 13.2pt;
		}

		.registro-verso .texto-legal {
			margin-top: 6mm;
			padding: 0 20mm;
			text-align: center;
			font-size: 11.2pt;
			line-height: 1.35;
		}

		.data-verso {
			margin-top: 4mm;
			padding-right: 40mm;
			font-size: 12pt;
			line-height: 1.2;
			color: #000000;
			text-align: right;
		}

		.assinaturas-verso {
			position: absolute;
			top: 172mm;
			left: 18mm;
			width: 261mm;
			height: 26mm;
			z-index: 5;
		}
	</style>
</head>
<body>
	<div class="pagina frente">
		<img class="fundo" src="<?php echo $esc($urlImagemFrente); ?>" alt="Certificado Frente">
		<?php if (!$temFundoFrente) { ?>
		<div class="moldura-certificado"></div>
		<div class="marca-dagua-certificado marca-dagua-frente">SESTED</div>
		<?php } ?>
		<div class="id"><?php echo (int) ($id_mat ?: $id); ?></div>
		<div class="cabecalho-frente">
			<img class="logo" src="<?php echo $esc($url_sistema . 'img/logo.jpg'); ?>" alt="Logo SESTED">
<div class="titulo-sistema">SISTEMA DE ENSINO SUPERIOR TECNOL&Oacute;GICO E EDUCACIONAL - SESTED</div>
<div class="linha">Mantenedora: SESTED - Sistema de Ensino Superior Tecnol&oacute;gico e Educacional - ME</div>
<div class="linha">Rua Nova Uni&atilde;o, n&ordm; 2024, Setor 02, Buritis/RO - CEP 76880-000 e-mail: sestedcursos@gmail.com | Tel. (69) 9.9969-4538</div>
<div class="linha">Credenciado pelo Parecer CEB/CEE/RO n&ordm; 003/24 e Resolu&ccedil;&atilde;o CEB/CEE/RO n&ordm; 909/24</div>
		</div>
<div class="titulo-certificado-frente">CERTIFICADO DE CONCLUS&Atilde;O DO ENSINO M&Eacute;DIO</div>
		<div class="introducao-fixa-frente">
A Diretora do SISTEMA DE ENSINO SUPERIOR TECNOL&Oacute;GICO E EDUCACIONAL - SESTED, no uso de suas atribui&ccedil;&otilde;es legais e de acordo com os Artigos 24, inciso VII, 37 e 38, &sect;1&ordm;, inciso II da Lei Federal n&ordm; 9394, de 20 de dezembro de 1996, confere a:
		</div>
		<div class="nome-aluno"><?php echo mb_strtoupper($esc($nome_aluno)); ?></div>
		<div class="descricao">
			Documento de Identifica&ccedil;&atilde;o Civil <?php echo $esc($documento_identificacao); ?>, nacionalidade Brasileiro(a), Natural de <?php echo $esc($naturalidade_exibir); ?>, Unidade da Federa&ccedil;&atilde;o <?php echo $esc($estado_exibir); ?>, Nascido(a) em <?php echo $esc($nascimento_exibir); ?>, o presente <strong>CERTIFICADO</strong> por haver conclu&iacute;do no ano de <?php echo $esc($ano_certificado); ?> do Ensino M&eacute;dio. Este documento atende &agrave;s exig&ecirc;ncias da <span style="color:#0B2F6B;">Lei Federal n&ordm; 7.088 de 23 de mar&ccedil;o de 1983.</span>
			</div>
			<div class="data-frente">Buritis - <?php echo $esc($data_formatada); ?></div>
			<div class="assinaturas-frente">
				<div class="assinatura-coluna assinatura-esquerda">
					<div class="linha-assinatura"></div>
					<div class="nome">Laura Maria Jonjob de Souza</div>
					<div class="documento">RG: 757423 SESDEC/RO</div>
					<div class="cargo">Diretora</div>
				</div>
				<div class="assinatura-coluna assinatura-centro assinatura-concluinte">
					<div class="linha-assinatura"></div>
					<div class="cargo">Concluinte</div>
				</div>
				<div class="assinatura-coluna assinatura-direita">
					<div class="linha-assinatura"></div>
					<div class="nome">Daniely Jonjob da Silva</div>
					<div class="documento">RG: 1480635 SESDEC/RO</div>
<div class="cargo">Secret&aacute;ria</div>
				</div>
			</div>
		</div>

		<div class="pagina verso">
			<img class="fundo" src="<?php echo $esc($urlImagemVerso); ?>" alt="Certificado Verso">
			<?php if (!$temFundoVerso) { ?>
			<div class="moldura-certificado"></div>
			<div class="marca-dagua-certificado marca-dagua-verso">SESTED</div>
			<?php } ?>
			<div class="cabecalho-verso">
				<img class="logo" src="<?php echo $esc($url_sistema . 'img/logo.jpg'); ?>" alt="Logo SESTED">
				<div class="titulo">SISTEMA DE ENSINO SUPERIOR TECNOL&Oacute;GICO E EDUCACIONAL - SESTED</div>
				<div class="linha">Parecer CEB/CEE/RO n&ordm; 003/24 e Resolu&ccedil;&atilde;o CEB/CEE/RO n&ordm; 909/24</div>
				<div class="linha">Rua Nova Uni&atilde;o, n&ordm; 2024, Setor 02, Buritis/RO - CEP 76880-000</div>
				<div class="linha">e-mail: sestedcursos@gmail.com | Tel. (69) 99694-538</div>
			</div>
			<div class="registro-verso">
					<div class="linha-registro">
						<span class="item-registro">
							<span class="rotulo-registro">N&ordm; do Registro:</span>
							<span class="valor-registro"><?php echo $numero_registro_exibir; ?></span>
						</span>
						<span class="item-registro">
							<span class="rotulo-registro">Folha (FL):</span>
							<span class="valor-registro"><?php echo $folha_livro_exibir; ?></span>
						</span>
						<span class="item-registro">
							<span class="rotulo-registro">N&ordm; do Livro:</span>
							<span class="valor-registro"><?php echo $numero_livro_exibir; ?></span>
						</span>
					</div>
				<div class="texto-legal">
					Registro efetuado de acordo com o inciso VII, Art. 24 da Lei Federal n&ordm; 9394/96 e do &sect; 2&ordm;, inciso II, Art. 3&ordm; da Resolu&ccedil;&atilde;o n&ordm; 202/05-CEE/RO.
				</div>
				<div class="data-verso">Buritis - <?php echo $esc($data_formatada); ?></div>
			</div>
			<div class="assinaturas-verso">
				<div class="assinatura-coluna assinatura-esquerda">
					<div class="linha-assinatura"></div>
					<div class="nome">Laura Maria Jonjob de Souza</div>
					<div class="documento">RG: 757423 SESDEC/RO</div>
					<div class="cargo">Diretora</div>
				</div>
				<div class="assinatura-coluna assinatura-direita">
					<div class="linha-assinatura"></div>
					<div class="nome">Daniely Jonjob da Silva</div>
					<div class="documento">RG: 1480635 SESDEC/RO</div>
					<div class="cargo">Secret&aacute;ria</div>
				</div>
			</div>
		</div>

</body>

</html>
