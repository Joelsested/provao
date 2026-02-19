<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$buscar = '%' .@$_GET['buscar']. '%';

$query = $pdo->prepare("SELECT * FROM alunos WHERE nome LIKE ? OR email LIKE ? ORDER BY id desc");
$query->execute([$buscar, $buscar]);

$res = $query->fetchAll(PDO::FETCH_ASSOC);

for ($i=0; $i < count($res); $i++) { 

$email = $res[$i]['email'];
$data = $res[$i]['data'];

$stmtUsuario = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmtUsuario->execute([$email]);
$res2 = $stmtUsuario->fetchAll(PDO::FETCH_ASSOC);
$senha = $res2[0]['senha'];

$dataF = implode('/', array_reverse(explode('-', $data)));

      $dados[] = array(
        'id' => $res[$i]['id'],
        'nome' => $res[$i]['nome'],        
        'email' => $res[$i]['email'],
        'telefone' => $res[$i]['telefone'],
        'endereco' => $res[$i]['endereco'],
        'cpf' => $res[$i]['cpf'],
        'cidade' => $res[$i]['cidade'],
        'estado' => $res[$i]['estado'],
        'pais' => $res[$i]['pais'],
        'foto' => $res[$i]['foto'],
        'data' => $dataF,
        'cartao' => $res[$i]['cartao'],
        'ativo' => $res[$i]['ativo'],
        'senha' => $senha,
    );

}

if(count($res) > 0){
    $result = json_encode(array('success'=>true, 'resultado'=>$dados));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0'));
}

echo $result;

?>
