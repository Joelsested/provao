<?php
$id = $_GET['id'];
$data_certificado = $_GET['data'];
$ano_certificado = $_GET['ano'];

include('../conexao.php');

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Porto_Velho');

if (!empty($data_certificado)) {
	$timestamp = strtotime($data_certificado);
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
$nome = $res[0]['nome'];
$pessoa = $res[0]['id_pessoa'];

$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
$query->execute([':id' => $pessoa]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$rg = $res[0]['rg'];
$orgao_expedidor = $res[0]['orgao_expedidor'] ?? '';
$rg_completo = trim($rg);
if ($orgao_expedidor !== '') {
	$rg_completo = trim($rg_completo . ' ' . $orgao_expedidor);
}
$nascimento = $res[0]['nascimento'];
$naturalidade = $res[0]['naturalidade'];
$pai = $res[0]['pai'];
$mae = $res[0]['mae'];

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
$data_hoje = utf8_encode(strftime('%A, %d de %B de %Y', strtotime('today')));
?>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">
<style>

		@page {
			margin: 10px;
		}

		body{
			margin-top:0px;
			font-family:Times, "Times New Roman", Georgia, serif;
		}	
	


.imagem {
width: 100%;
}   




.descricao {
position: absolute;
margin-top: 445px;
text-align:left:;
color:black;
font-size:17px;
width:85%;
margin-left: 55px;
}



.data {
position: absolute;
margin-top: 675px;
text-align:left;
color:black;
font-size:16px;
width:80%;
margin-left: 55px;
}

.descricao2 {
position: absolute;
margin-top: 570px;
text-align:left:;
color:#473e3a;
font-size:17px;
width:90%;
margin-left: 55px;
}





</style>

<!DOCTYPE html>

<head>
    
    
</head>



<body>



<div class="descricao"><br><br> Declaramos para os devidos fins que o(a) estudante, <?php echo $nome ?>, <?php echo $rg_completo ?>, nascido(a) em <?php echo $nascimento ?>, na cidade de <?php echo $naturalidade ?>, filho(a) de <?php echo $pai ?> e <?php echo $mae ?>, est&aacute; MATRICULADO(A) no Ensino Fundamental nos Exames de Conclus&atilde;o de Etapas, da modalidade Ensino Jovens e Adultos (EJA), no ano de <?php echo $ano_certificado ?> nesta Institui&ccedil;&atilde;o de Ensino Escolar. <br><br>Por express&atilde;o da verdade, firmamos a presente declara&ccedil;&atilde;o em duas vias de igual forma e teor. </div>



<div class="data"> <br><br> Buritis <?php echo $data_formatada ?></div>

<img class="imagem" src="<?php echo $url_sistema ?>sistema/img/declaracao-medio.jpg">


</body>

</html>
