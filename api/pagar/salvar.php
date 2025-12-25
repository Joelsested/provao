<?php 

require_once("../conexao.php");
$tabela = 'pagar';

$postjson = json_decode(file_get_contents('php://input'), true);

$id = @$postjson['id'];
$valor = @$postjson['valor'];
$valor = str_replace(',', '.', $valor);
$descricao = @$postjson['descricao'];
$data_venc = @$postjson['data_venc'];
$foto = @$postjson['foto'];


if($id != "" and $foto == ""){
	$stmt = $pdo->prepare("SELECT arquivo FROM $tabela WHERE id = ?");
	$stmt->execute([(int) $id]);
	$res = $stmt->fetch(PDO::FETCH_ASSOC);
	if($res){
		$foto = $res['arquivo'];
	}else{
		$foto = 'sem-foto.png';
	}

}



if($id == "" || $id == "0"){
	$res = $pdo->prepare("INSERT INTO $tabela SET descricao = :descricao, valor = :valor, vencimento = :vencimento, data = curDate(), arquivo = :arquivo, pago = 'Nǜo'");
	

}else{
	$res = $pdo->prepare("UPDATE $tabela SET descricao = :descricao, valor = :valor, vencimento = :vencimento, arquivo = :arquivo WHERE id = :id");
	
}


$res->bindValue(":descricao", "$descricao");
$res->bindValue(":valor", "$valor");
$res->bindValue(":vencimento", "$data_venc");
$res->bindValue(":arquivo", "$foto");
if($id != "" && $id != "0"){
	$res->bindValue(":id", (int) $id, PDO::PARAM_INT);
}
$res->execute();


$result = json_encode(array('mensagem'=>'Salvo com sucesso!', 'sucesso'=>true));

echo $result;

?>
