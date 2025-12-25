<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$buscar = '%' .@$_GET['buscar']. '%';

$query = $pdo->prepare("SELECT * FROM cursos WHERE status = 'Aprovado' AND (nome LIKE ? OR desc_rapida LIKE ?) ORDER BY id desc LIMIT 30");
$query->execute([$buscar, $buscar]);

$res = $query->fetchAll(PDO::FETCH_ASSOC);


for ($i=0; $i < count($res); $i++) { 

$nome = $res[$i]['nome'];  
$valor = $res[$i]['valor'];  
$categoria = $res[$i]['categoria'];   
$promocao = $res[$i]['promocao'];
    

    $stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmtCat->execute([(int) $categoria]);
    $res2 = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
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
    $result = json_encode(array('success'=>true, 'resultado'=>$dados));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0'));
}

echo $result;

?>
