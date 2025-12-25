<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$limite = (isset($_GET['limite'])) ? (int) $_GET['limite'] : 5; 
$pagina = (isset($_GET['pagina'])) ? (int) $_GET['pagina'] : 1; 

$inicio = ($limite * $pagina) - $limite; 

$query = $pdo->prepare("SELECT * FROM alunos ORDER BY id DESC LIMIT :inicio, :limite");
$query->bindValue(":inicio", $inicio, PDO::PARAM_INT);
$query->bindValue(":limite", $limite, PDO::PARAM_INT);
$query->execute();
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
    $result = json_encode(array('success'=>true, 'resultado'=>@$dados, 'totalItems'=>@count($dados) + ($inicio)));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0'));
}

echo $result;

?>
