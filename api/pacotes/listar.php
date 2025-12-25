<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$limite = (isset($_GET['limite'])) ? (int) $_GET['limite'] : 5; 
$pagina = (isset($_GET['pagina'])) ? (int) $_GET['pagina'] : 1; 

$inicio = ($limite * $pagina) - $limite; 

$query = $pdo->prepare("SELECT * FROM pacotes ORDER BY id DESC LIMIT :inicio, :limite");
$query->bindValue(":inicio", $inicio, PDO::PARAM_INT);
$query->bindValue(":limite", $limite, PDO::PARAM_INT);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);


for ($i=0; $i < count($res); $i++) { 

$nome = $res[$i]['nome'];  
$valor = $res[$i]['valor'];  
$categoria = $res[$i]['linguagem'];   
$promocao = $res[$i]['promocao'];
    

    $stmtLing = $pdo->prepare("SELECT * FROM linguagens WHERE id = ?");
    $stmtLing->execute([(int) $categoria]);
    $res2 = $stmtLing->fetchAll(PDO::FETCH_ASSOC);
    if(@count($res2) > 0){
        $nome_cat = $res2[0]['nome'];     
       
    }else{
        $nome_cat = "";
    }

    if($promocao > 0){
        $valor = $promocao;
    }

               
    //FORMATAR VALORES
    $valorF = number_format($valor, 2, ',', '.');
      

      $dados[] = array(
        'id' => $res[$i]['id'],
        'nome' => $nome,        
        'categoria' => $nome_cat,
        'valor' => $valorF,       
        'foto' => $res[$i]['imagem'],              
    );

}


if(count($res) > 0){
    $result = json_encode(array('success'=>true, 'resultado'=>@$dados, 'totalItems'=>@count($dados) + ($inicio)));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0'));
}

echo $result;

?>
