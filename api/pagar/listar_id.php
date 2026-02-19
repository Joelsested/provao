<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$query = $pdo->prepare("SELECT * FROM pagar WHERE id = ?");
$query->execute([$id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

for ($i=0; $i < count($res); $i++) { 
    foreach ($res[$i] as $key => $value) {
    }    


    $arquivo = $res[$i]['arquivo'];
    //extensão do arquivo
$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
if($ext == 'pdf'){
    $tumb_arquivo = 'pdf.png';
}else if($ext == 'rar' || $ext == 'zip'){
    $tumb_arquivo = 'rar.png';
}else if($ext == 'doc' || $ext == 'docx'){
    $tumb_arquivo = 'word.png';
}else{
    $tumb_arquivo = $arquivo;
}

    $dados = array(
    'id' => $res[$i]['id'],       
        'descricao' => $res[$i]['descricao'],
        'valor' => $res[$i]['valor'],
        'data_lanc' => $res[$i]['data'],  
        'data_venc' => $res[$i]['vencimento'],
        'data_pgto' => $res[$i]['data_pgto'],
        'arquivo' => $res[$i]['arquivo'],
        'pago' => $res[$i]['pago'],
        'foto' => $tumb_arquivo,       

    );
}

if(count($res) > 0){
    $result = json_encode(array('success'=>true, 'dados'=>$dados));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0'));
}

echo $result;

?>
