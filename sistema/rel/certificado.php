<?php
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$id_mat = isset($_GET['id_mat']) ? (int) $_GET['id_mat'] : 0;
$data_certificado = $_GET['data'] ?? null;
$ano_certificado = $_GET['ano'] ?? null;
include('../conexao.php');

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Porto_Velho');

if (!empty($data_certificado)) {
	$timestamp = strtotime($data_certificado);
	// Se a data for inválida ou vazia, usa a data atual
	if ($timestamp === false) {
		$timestamp = strtotime('today');
	}
	$data_formatada = utf8_encode(strftime('%A, %d de %B de %Y', $timestamp));
} else {
	$data_formatada = utf8_encode(strftime('%A, %d de %B de %Y', strtotime('today')));
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

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Porto_Velho');
$data_hoje = utf8_encode(strftime('%A, %d de %B de %Y', strtotime('today')));



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
</style>

<!DOCTYPE html>

<head>


</head>



<body>
	<div class="id"> <?php echo $id_mat ?: $id; ?></div>
	<div class="nome-aluno"> <b><br><br><?php echo mb_strtoupper($nome_aluno); ?></b></div>

	<div class="descricao"><br><br> <?php echo $identidade_texto; ?>, Nacionalidade Brasileiro(a), Natural de <?php echo $naturalidade ?>, Nascido em, <?php echo $nascimento ?>, o presente
		CERTIFICADO por haver concluído no ano de <?php echo $ano_certificado; ?> o Ensino Médio, nos Exames de Finalização de Etapas – EJA –
		Educação e Jovens e Adultos. </div>


	<div class="data"> <br><br> Buritis <?php echo $data_formatada ?></div>

	<img class="imagem" src="<?php echo $url_sistema ?>sistema/img/certificado-fundo.jpg">

	<div class="verso">
		<img class="imagem2" src="<?php echo $url_sistema ?>sistema/img/certificado-verso.jpg">
		<div class="conteudo">zzzzzzzzzzzzz


		</div>
		<div class="data2"> Buritis <?php echo ($data_formatada); ?>
		</div>
	</div>

</body>

</html>
