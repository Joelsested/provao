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
	// Se a data for invalida ou vazia, usa a data atual
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
$total_reg = count($res);
$nome = $res[0]['nome'];
$pessoa = $res[0]['id_pessoa'];

$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
$query->execute([':id' => $pessoa]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$rg = $res[0]['rg'];
$nascimento = $res[0]['nascimento'];
$naturalidade = $res[0]['naturalidade'];
$pai = $res[0]['pai'];
$mae = $res[0]['mae'];

date_default_timezone_set('America/Sao_Paulo');
$data_hoje = formatar_data_extenso_ptbr('today');


?>


	
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">
	<link href="<?php echo $url_sistema ?>sistema/rel/css/declaracoes.css" rel="stylesheet">
<style>

		@page {
			margin:05px;

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
margin-top: 455px;
text-align:left:;
color:black;
font-size:16px;
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
font-size: 16px;
width:90%;
margin-left: 55px;
}





.carimbo {
position: absolute;
top: 52px;
right: 40px;
width: 195px;
border: 2px solid #000;
padding: 4px 6px;
text-align: center;
line-height: 1.05;
font-family: Arial, Helvetica, sans-serif;
color: #000;
background: #fff;
}

.carimbo .titulo {
font-size: 16px;
font-weight: 700;
}

.carimbo .linha {
font-size: 10.8px;
font-weight: 700;
}
</style>

<!DOCTYPE html>

<head>
    
    
</head>



<body>


<div class="carimbo">
	<div class="titulo">SESTED</div>
	<div class="linha">Autoriza&ccedil;&atilde;o de Funcionamento</div>
	<div class="linha">Parecer CEB/CEE/RO n&ordm; 003/24</div>
	<div class="linha">Resolu&ccedil;&atilde;o n&ordm; 011/23 - CEE/RO</div>
	<div class="linha">CNPJ 07.158.229/0001-06</div>
	<div class="linha">BURITIS - RO</div>
</div>


<div class="descricao"><br><br> Declaramos para os devidos fins que o(a) estudante, <?php echo $nome ?>, <?php echo $rg ?>, nascido(a) em, <?php echo $nascimento ?>, Na cidade de <?php echo $naturalidade ?>, Filho(a) de <?php echo $pai ?> e <?php echo $mae ?>, CONCLUIU O Ensino M&eacute;dio nos exames de Conclus&atilde;o de Etapas, da modalidade Ensino Jovens e Adultos (EJA), no ano de <?php echo $ano_certificado ?> nesta Institui&ccedil;&atilde;o de Ensino Escolar. <br><br>Por ser express&atilde;o da verdade, firmamos a presente declara&ccedil;&atilde;o em duas vias de igual forma e teor. </div>



<div class="data"> <br><br> Buritis - <?php echo $data_formatada ?></div>

<img class="imagem" src="<?php echo $url_sistema ?>sistema/img/declaracao-medio.jpg">


</body>

</html>




