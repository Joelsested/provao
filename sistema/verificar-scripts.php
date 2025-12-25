<?php 

//verificações das matriculas para enviar email após x dias
$hoje = date('Y-m-d');
$data_anterior = date('Y/m/d', strtotime("-$dias_email_matricula day",strtotime($hoje))); 

$query = $pdo->prepare("SELECT * FROM matriculas where data <= :data_anterior and status = 'Aguardando' and alertado is null ");
$query->execute([':data_anterior' => $data_anterior]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id_mat = $res[$i]['id'];
	$id_curso = $res[$i]['id_curso'];
	$aluno = $res[$i]['aluno'];
	$pacote = $res[$i]['pacote'];


	$query2 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
	$query2->execute([':id' => $aluno]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_aluno = $res2[0]['nome'];
	$aluno_email = $res2[0]['usuario'];

	if($pacote != 'Sim'){
		$tabela = 'cursos';	
		$url_amig = 'curso-de-';		
	}else{
		$tabela = 'pacotes';
		$url_amig = 'cursos-do-';
	}

		$query2 = $pdo->prepare("SELECT * FROM {$tabela} where id = :id");
		$query2->execute([':id' => $id_curso]);
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$nome_curso = $res2[0]['nome'];
		$url_curso = $res2[0]['nome_url'];
		$url_pagamento = $url_sistema.$url_amig.$url_curso;
		$url_cursos = $url_sistema.'cursos';
		$url_painel_aluno = $url_sistema.'sistema/painel-aluno';
		$url_logo = $url_sistema.'sistema/img/logo-email.png';

		$url_login = $url_sistema.'sistema';

		//COLOCAR O CAMPO DE ALERTADO COMO SIM PARA O EMAIL NÃO SER ENVIADO NOVAMENTE
		$stmtAlertado = $pdo->prepare("UPDATE matriculas set alertado = 'sim' where id = :id");
		$stmtAlertado->execute([':id' => $id_mat]);


		//ENVIAR O EMAIL PARA O ALUNO
		$to = $aluno_email;
		$subject = "Lembrete de Matrícula no Curso $nome_curso";
		$message = "

		Olá <b>$nome_aluno</b>, <br>
		Verificamos que iniciou sua matrícula em um de nossos cursos, porém o pagamento ainda não foi confirmado, acesse nosso site <a title='$url_login' href='$url_login' target='_blank'>$url_login</a> e faça seu login para efetuar o pagamento direto em seu painel, o curso será liberado imediatamente, qualquer dúvida entre em contato conosco!

		<br><br>
		  <a href='$url_sistema' target='_blank'><img src='$url_logo' width='300px'></a><br>

                       <i>Nosso Site - <a href='$url_sistema' target='_blank'>$url_sistema</a></i>
                      <br>
                      WhatsApp -> <a href='http://api.whatsapp.com/send?1=pt_BR&phone=55$tel_sistema' alt='$tel_sistema' target='_blank'><i class='fab fa-whatsapp'></i>$tel_sistema</a>



		";


		$dest = $email_sistema;
		$headers = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8;' . "\r\n";

		if($to != $dest){
			$headers .= "From: " .$dest;
		}

		@mail($to, $subject, $message, $headers);
		

}

}


$data_anterior = date('Y/m/d', strtotime("-$dias_excluir_matricula day",strtotime($hoje))); 
//EXCLUIR A MATRICULA QUE ESTÁ AGUARDANDO APÓS X DIAS
$query = $pdo->prepare("SELECT * FROM matriculas where data <= :data_anterior and status = 'Aguardando'");
$query->execute([':data_anterior' => $data_anterior]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
		$id_mat = $res[$i]['id'];
		$stmtDelete = $pdo->prepare("DELETE FROM matriculas where id = :id");
		$stmtDelete->execute([':id' => $id_mat]);

	}
}



//ATUALIZAR NO CONFIG O CAMPO SCRIPT DIA PARA O DIA ATUAL
$pdo->query("UPDATE config set script_dia = curDate()");

 ?>
