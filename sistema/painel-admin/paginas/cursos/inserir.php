<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
$tabela = 'cursos';

$ano_atual = date('Y');

@session_start();
$id_usuario = $_SESSION['id'];

$nome = $_POST['nome'];
$desc_rapida = $_POST['desc_rapida'];
$categoria = $_POST['categoria'];
$grupo = $_POST['grupo'];
$valor = $_POST['valor'];
$valor = str_replace(',', '.', $valor);
$promocao = $_POST['promocao'];
$promocao = str_replace(',', '.', $promocao);
$carga = $_POST['carga'];
$palavras = $_POST['palavras'];
$pacote = $_POST['pacote'];
@$tecnologias = $_POST['tecnologias'];
@$sistema = $_POST['sistema'];
@$arquivo = $_POST['arquivo'];
@$link = $_POST['link'];
$desc_longa = $_POST['desc_longa'];
@$comissao = $_POST['comissao'];

$desc_longa = str_replace("'", " ", $desc_longa);
$desc_longa = str_replace('"', ' ', $desc_longa);

$nome = str_replace("'", " ", $nome);
$nome = str_replace('"', ' ', $nome);

$desc_rapida = str_replace("'", " ", $desc_rapida);
$desc_rapida = str_replace('"', ' ', $desc_rapida);

$nome_novo = strtolower( preg_replace("[^a-zA-Z0-9-]", "-", 
        strtr(utf8_decode(trim($nome)), utf8_decode("áàãâéêíóôõúüñçÁÀÃÂÉÊÍÓÔÕÚÜÑÇ"),
        "aaaaeeiooouuncAAAAEEIOOOUUNC-")) );
$url = preg_replace('/[ -]+/' , '-' , $nome_novo);

$id = $_POST['id'];

//retirar espaços vazios e possívels aspas simples do textarea
$desc_longa = str_replace(array("\n", "\r", "'"), ' ', $desc_longa);

//validar nome curso duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where nome = :nome");
$query->execute([':nome' => $nome]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Curso já Cadastrado com este nome, escolha Outro!';
	exit();
}


$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['imagem'];
}else{
	$foto = 'sem-foto.png';
}



//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$destDir = __DIR__ . '/../../img/cursos';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$upload = upload_handle($_FILES['foto'] ?? [], $destDir, $allowedExt, $allowedMime, 5 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
	echo $upload['error'];
	exit();
}
if (empty($upload['skipped'])) {
	if ($foto != 'sem-foto.png') {
		@unlink($destDir . '/' . $foto);
	}
	$foto = $upload['filename'];
}


if($id == ""){

	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, desc_rapida = :desc_rapida, desc_longa = :desc_longa, valor = :valor, professor = :professor, categoria = :categoria, imagem = :imagem, status = 'Aguardando', carga = :carga, arquivo = :arquivo, ano = :ano, palavras = :palavras, grupo = :grupo, nome_url = :nome_url, pacote = :pacote, sistema = :sistema, link = :link, tecnologias = :tecnologias, promocao = :promocao, comissao = :comissao ");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, desc_rapida = :desc_rapida, desc_longa = :desc_longa, valor = :valor, professor = :professor, categoria = :categoria, imagem = :imagem, carga = :carga, arquivo = :arquivo, palavras = :palavras, grupo = :grupo, nome_url = :nome_url, pacote = :pacote, sistema = :sistema, link = :link, tecnologias = :tecnologias, promocao = :promocao, comissao = :comissao WHERE id = :id");
}

$query->bindValue(":nome", "$nome");
$query->bindValue(":desc_rapida", "$desc_rapida");
$query->bindValue(":desc_longa", "$desc_longa");
$query->bindValue(":valor", "$valor");
$query->bindValue(":professor", "$id_usuario");
$query->bindValue(":categoria", "$categoria");
$query->bindValue(":imagem", "$foto");
$query->bindValue(":carga", "$carga");
$query->bindValue(":arquivo", "$arquivo");
$query->bindValue(":ano", "$ano_atual");
$query->bindValue(":palavras", "$palavras");
$query->bindValue(":grupo", "$grupo");
$query->bindValue(":nome_url", "$url");
$query->bindValue(":pacote", "$pacote");
$query->bindValue(":sistema", "$sistema");
$query->bindValue(":link", "$link");
$query->bindValue(":tecnologias", "$tecnologias");
$query->bindValue(":promocao", "$promocao");
$query->bindValue(":comissao", "$comissao");
$query->bindValue(":id", "$id");
$query->execute();

echo 'Salvo com Sucesso';

 ?>
