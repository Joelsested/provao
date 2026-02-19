<?php 

include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$buscar = '%' .@$_GET['buscar']. '%';

$query = $pdo->prepare("SELECT m.id, m.id_curso, m.aluno, m.aulas_concluidas, m.subtotal, m.data, m.pacote, m.obs, u.nome, u.usuario FROM matriculas as m INNER JOIN usuarios as u ON m.aluno = u.id WHERE m.status = 'Aguardando' AND (u.nome LIKE ? OR u.usuario LIKE ?) ORDER BY m.id desc LIMIT 30");
$query->execute([$buscar, $buscar]);

$res = $query->fetchAll(PDO::FETCH_ASSOC);


for ($i=0; $i < count($res); $i++) { 

$curso = $res[$i]['id_curso'];  
$valor = $res[$i]['subtotal'];  
$data = $res[$i]['data'];   
$pacote = $res[$i]['pacote'];
$aluno = $res[$i]['aluno'];
$obs = $res[$i]['obs'];


if($pacote == 'Sim'){
        $tab = 'pacotes';
        $item_curso = ' (Pacote)';
        $classe_curso = 'text-primary';     
    }else{
        $tab = 'cursos';
        $item_curso = '';
        $classe_curso = '';     
    }
    

    $stmtCurso = $pdo->prepare("SELECT * FROM $tab WHERE id = ?");
    $stmtCurso->execute([(int) $curso]);
    $res2 = $stmtCurso->fetchAll(PDO::FETCH_ASSOC);
    if(@count($res2) > 0){
        $nome_curso = $res2[0]['nome'];     
        $id_do_curso = $res2[0]['id'];
        $foto = $res2[0]['imagem'];

    }else{
        $nome_curso = "";
    }

    $stmtAluno = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtAluno->execute([(int) $aluno]);
    $res2 = $stmtAluno->fetchAll(PDO::FETCH_ASSOC);
    if(@count($res2) > 0){
        $nome_aluno = $res2[0]['nome'];
        $email_aluno = $res2[0]['usuario'];
        
    }
            
    //FORMATAR VALORES
    $valorF = number_format($valor, 2, ',', '.');
    $dataF = implode('/', array_reverse(explode('-', $data)));

   

      $dados[] = array(
        'id' => $res[$i]['id'],
        'aluno' => $nome_aluno,        
        'email' => $email_aluno,
        'curso' => $nome_curso,
        'valor' => $valor,
        'data' => $dataF,  
        'obs' => $obs,     
        'foto' => $foto,  
        'tab' => $tab, 
        'valorF' => $valorF,        
    );

}


if(count($res) > 0){
    $result = json_encode(array('success'=>true, 'resultado'=>$dados));
}else{
    $result = json_encode(array('success'=>false, 'resultado'=>'0'));
}

echo $result;

?>
