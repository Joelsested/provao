<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$data = @$_GET['data'];
$data1 = @$_GET['data1'];


$query = $pdo->prepare("SELECT * FROM receber WHERE vencimento >= ? AND vencimento <= ? ORDER BY vencimento ASC, id asc");
$query->execute([$data, $data1]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);


for ($i=0; $i < count($res); $i++) { 



    $data_vencF = implode('/', array_reverse(explode('-', $res[$i]['vencimento'])));
    $data_lancF = implode('/', array_reverse(explode('-', $res[$i]['data'])));
    $data_pgtoF = implode('/', array_reverse(explode('-', $res[$i]['data_pgto'])));
    $valorF = number_format($res[$i]['valor'], 2, ',', '.');

    if($data_pgtoF == '00/00/0000'){
        $data_pgtoF = 'Pendente';
    }
    

    $descricaoF = mb_strimwidth($res[$i]['descricao'], 0, 32, "...");

     if($res[$i]['pago'] == 'Não'){
        $icone = 'red';
        $card = 'red';
        $texto_pago = 'Conta Pendente';
    }else{
        $icone = 'green';
        $card = 'green';
         $texto_pago = 'Paga em '.$data_pgtoF;
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



    $dados[] = array(
        'id' => $res[$i]['id'],        
        'descricaoF' => $descricaoF,
        'descricao' => $res[$i]['descricao'],
        'valor' => $valorF,
        'data_lanc' => $data_lancF,
        'data_venc' => $data_vencF,
        'data_pgto' => $data_pgtoF,        
        'arquivo' => $res[$i]['arquivo'],
        'pago' => $res[$i]['pago'],        
        'card' => $card,
        'icone' => $icone,
        'tumb' => $tumb_arquivo,
        
         'texto_pago' => $texto_pago,
       
    );

}



if(count($res) > 0){
    $result = json_encode(array('success'=>true, 'resultado'=>@$dados));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0'));
}

echo $result;

?>
