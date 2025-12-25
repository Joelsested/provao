<?php 
require_once("../../../conexao.php");
$tabela = 'cupons';

$codigo = $_POST['codigo'];
$valor = $_POST['valor'];
$id = $_POST['id'];
$valor = str_replace(',', '.', $valor);
$quantidade = $_POST['quantidade'];
$tipo = $_POST['tipo'];
$data = $_POST['data'];

//validar codigo duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where codigo = :codigo");
$query->execute([':codigo' => $codigo]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Cupon jù½ Cadastrado com este cù©digo, escolha Outro!';
	exit();
}

if($data != ""){
	$sql_data = ", data_validade = :data_validade";
}else{
	$sql_data = " ";
}


if($id == ""){

	$query = $pdo->prepare("INSERT INTO $tabela SET codigo = :codigo, valor = :valor, quantidade = :quantidade, tipo = :tipo $sql_data");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET codigo = :codigo, valor = :valor, quantidade = :quantidade, tipo = :tipo $sql_data WHERE id = :id");
}

$params = [
	':codigo' => $codigo,
	':valor' => $valor,
	':quantidade' => $quantidade,
	':tipo' => $tipo,
];

if($data != ""){
	$params[':data_validade'] = $data;
}

if($id != ""){
	$params[':id'] = $id;
}

$query->execute($params);

echo 'Salvo com Sucesso';

 ?>
