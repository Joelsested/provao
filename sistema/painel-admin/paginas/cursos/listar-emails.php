<?php 
require_once("../../../conexao.php");
$tabela = 'cursos';

$id_curso = $_POST['id'];

$total_emails = 30;
$i2 = $total_emails;

$query = $pdo->prepare("SELECT * from matriculas where id_curso = :id_curso and status != 'Aguardando' order by id desc");
$query->execute([':id_curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
echo 'Total de Matriculas : '.$total_reg;
echo '<br><br>';
for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$aluno = $res[$i]['aluno'];

$query2 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query2->execute([':id' => $aluno]);
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res2) > 0){
		$nome_aluno = $res2[0]['nome'];
		$email_aluno = $res2[0]['usuario'];
		
	}
	
	echo $email_aluno.'; ';	

	if($i == $i2){
		echo '<br><br><br>';
		$i2 += $total_emails;
	}

		
	
	


	}



?>
