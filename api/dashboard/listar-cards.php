<?php 

include_once('../conexao.php');

$data_hoje = date('Y-m-d');
$data_ontem = date('Y-m-d', strtotime("-1 days",strtotime($data_hoje)));

$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_mes = $ano_atual."-".$mes_atual."-01";

$postjson = json_decode(file_get_contents('php://input'), true);

$id_usu = @$_GET['user'];

$total_usuarios = 0;

$query = $pdo->query("SELECT * from alunos ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_alunos = @count($res);


$query = $pdo->query("SELECT * FROM matriculas where status = 'Aguardando'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_mat_pendentes = @count($res);

$query = $pdo->prepare("SELECT * FROM matriculas WHERE status != 'Aguardando' AND data >= ? AND data <= curDate()");
$query->execute([$data_mes]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_mat_aprovadas = @count($res);


$total_vendas_dia = 0;
$total_vendas_diaF = 0;
$query = $pdo->query("SELECT * FROM matriculas where status != 'Aguardando' and subtotal > 0 and data = curDate() ORDER BY id asc");
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_reg = @count($res);
		if($total_reg > 0){
			for($i=0; $i < $total_reg; $i++){
			foreach ($res[$i] as $key => $value){}
				$total_recebido = $res[$i]['total_recebido'];
				$total_vendas_dia += $total_recebido;
			}
		}
		$total_vendas_diaF = number_format($total_vendas_dia, 2, ',', '.');


		$query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' ");
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_cursos = @count($res);



$result = json_encode(array('success'=>true, 
	'total_alunos'=>$total_alunos,
	'mat_pendentes'=>$total_mat_pendentes,
	'mat_aprovadas'=>$total_mat_aprovadas,
	'vendas_hoje'=>$total_vendas_diaF,
	'total_cursos'=>$total_cursos


));

echo $result;

?>
