<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$data = @$_GET['data'];
$data1 = @$_GET['data1'];

$total_dia = 0;
$query = $pdo->prepare("SELECT * FROM matriculas WHERE status != 'Aguardando' AND subtotal > 0 AND total_recebido > 0 AND (data BETWEEN ? AND ?) ORDER BY id desc");
$query->execute([$data, $data1]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

for($i=0; $i < @count($res); $i++){
    foreach ($res[$i] as $key => $value){}
    $id = $res[$i]['id'];
    $id_curso = $res[$i]['id_curso'];
    $aluno = $res[$i]['aluno']; 
    $valor_cupom = $res[$i]['valor_cupom']; 
    $valor = $res[$i]['valor']; 
    $subtotal = $res[$i]['subtotal'];   
    $forma_pgto = $res[$i]['forma_pgto'];
    $total_recebido = $res[$i]['total_recebido'];   
    $data = $res[$i]['data'];
    $obs = $res[$i]['obs']; 
    $pacote = $res[$i]['pacote'];

    $total_dia += $total_recebido;

    if($pacote == 'Sim'){
        $tab = 'pacotes';
    }else{
        $tab = 'cursos';
    }

    $stmtCurso = $pdo->prepare("SELECT * FROM $tab WHERE id = ?");
    $stmtCurso->execute([(int) $id_curso]);
    $res2 = $stmtCurso->fetchAll(PDO::FETCH_ASSOC);
    $nome_curso = $res2[0]['nome'];

    $stmtAluno = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtAluno->execute([(int) $aluno]);
    $res2 = $stmtAluno->fetchAll(PDO::FETCH_ASSOC);
    $nome_aluno = $res2[0]['nome'];
    $email_aluno = $res2[0]['usuario'];

    $valorF = number_format($valor, 2, ',', '.');
    $valor_cupomF = number_format($valor_cupom, 2, ',', '.');
    $subtotalF = number_format($subtotal, 2, ',', '.');
    $total_recebidoF = number_format($total_recebido, 2, ',', '.');
    $total_diaF = number_format($total_dia, 2, ',', '.');
    $dataF = implode('/', array_reverse(explode('-', $data)));

    $taxa_mpF = number_format($taxa_mp, 1, '.', '.');
    $taxa_paypalF = number_format($taxa_paypal, 1, '.', '.');

    if($forma_pgto == 'Boleto'){
        $desconto = '(R$ '.$taxa_boleto.')';
    }else if($forma_pgto == 'MP'){
        $desconto = '('.$taxa_mpF.')%';
    }else if($forma_pgto == 'Paypal'){
        $desconto = '('.$taxa_paypalF.')%';
    }else{
        $desconto = '';
    }

    if($obs == ""){
        $obs = 'Nenhuma!';
    }

   

      $dados[] = array(
        'id' => $res[$i]['id'],
        'curso' => $nome_curso,        
        'valor' => $valor,
        'cupom' => $valor_cupomF,
        'subtotal' => $subtotalF,
        'forma_pgto' => $forma_pgto,  
        'desconto' => $desconto,     
        'total_recebido' => $total_recebido,  
        'data' => $dataF, 
        'aluno' => $nome_aluno, 
        'email' => $email_aluno, 
        'obs' => $obs, 
        'valorF' => $valorF,
        'total_recebidoF' => $total_recebidoF,
              
    );

}


if(count($res) > 0){
    $result = json_encode(array('success'=>true, 'resultado'=>@$dados, 'total_vendido'=>@$total_diaF));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0', 'total_vendido'=>@$total_diaF));
}

echo $result;

?>
