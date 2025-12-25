<!DOCTYPE html>
<html>
<head>
	<title>Script Email</title>
</head>
<body>

</body>
</html>

<?php 
require_once("../../../conexao.php");

echo "<meta HTTP-EQUIV='refresh' CONTENT='1200;URL=script-enviar.php'>"; 
$agora = date('Y-m-d H:i:s');
$email_adm = $email_sistema;

//PEGAR TOTAL DE EMAILS DO BANCO
$query = $pdo->query("SELECT * FROM emails where enviar = 'sim' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_emails = @count($res);


$query = $pdo->query("SELECT * FROM envios where id = '1' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$data = $res[0]["data"];
	$final = $res[0]["final"];
	$mensagem = $res[0]["mensagem"];
	$assunto = $res[0]["assunto"];
	$link = $res[0]["link"];

	if ($final == 0){
		echo 'Não tem mais emails pendentes, todos já foram enviados!';
	}else{

		if($agora >= $data){

			//VER SE JÁ PASSOU TODA A LISTA DE EMAILS

			if($final >= $total_emails){
				$query = $pdo->prepare("UPDATE envios SET data = :data, final = '0', assunto = :assunto, mensagem = :mensagem, link = :link where id = 1");
				$query->execute([
					':data' => $agora,
					':assunto' => $assunto,
					':mensagem' => $mensagem,
					':link' => $link,
				]);
				
				exit();
			}


			$inicio = $final;
			$final_novo = $total_emails_por_envio + $final;


				//APÓS ENVIAR O EMAIL É PRECISO SALVAR A HORA NA TABELA DE ENVIOS
			$nova_hora = date('Y-m-d H:i:s', strtotime('+'.$intervalo_envio_email.'minute', strtotime($agora)));


			$query = $pdo->prepare("UPDATE envios SET data = :data, final = :final, assunto = :assunto, mensagem = :mensagem, link = :link where id = 1");
			$query->execute([
				':data' => $nova_hora,
				':final' => $final_novo,
				':assunto' => $assunto,
				':mensagem' => $mensagem,
				':link' => $link,
			]);


			$url_s = $url_sistema;
			$url_nova = $url_sistema . $link;
			$url_cursos = $url_sistema.'cursos';
			$url_painel_aluno = $url_sistema.'sistema/painel-aluno';
			$url_logo = $url_sistema.'sistema/img/logo-email.png';
			$url_descadastrar = $url_sistema . 'descadastrar.php';



			//DISPARAR EMAIL PARA OS CLIENTES DE HORA EM HORA
			$query_emails = $pdo->prepare("SELECT * from emails where enviar = 'sim' and (id > :inicio and id <= :final)");
			$query_emails->execute([
				':inicio' => $inicio,
				':final' => $final_novo,
			]);
			$res_emails = $query_emails->fetchAll(PDO::FETCH_ASSOC);

			for ($i=0; $i < count($res_emails); $i++) { 
			foreach ($res_emails[$i] as $key => $value) {

			}
			

			$nome_aluno_email = $res_emails[$i]['nome'];
			$aluno_email = $res_emails[$i]['email'];
			$id_email = $res_emails[$i]['id'];
				
			$to = $aluno_email;
			$subject = "$assunto";
			$message = "


				Olá <b>$nome_aluno_email</b>, <br><br>
				$mensagem !


				<br><br> <i> <a title='$url_nova' href='$url_nova' target='_blank'>Clique aqui </a> para ver o treinamento em nosso site !!</i> <br><br>

				<a title='$url_nova' href='$url_nova' target='_blank'>$url_nova</a>
				

				<br><br><br>
				   <a href='$url_sistema' target='_blank'><img src='$url_logo' width='300px'></a><br>

                       <i>Nosso Site - <a href='$url_sistema' target='_blank'>$url_sistema</a></i>
                      <br>
                      WhatsApp -> <a href='http://api.whatsapp.com/send?1=pt_BR&phone=55$tel_sistema' alt='$tel_sistema' target='_blank'><i class='fab fa-whatsapp'></i>$tel_sistema</a>

                    
				<br><br><br>
       <i> Caso não queira mais receber nossos emails <a href='$url_descadastrar' target='_blank'> clique aqui </a> para se descadastrar!</i> <br><br>


				";


				$dest = $email_sistema;
				$headers = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=utf-8;' . "\r\n";

				if($to != $dest){
					$headers .= "From: " .$dest;
				}

				@mail($to, $subject, $message, $headers);
				

			}


	//ENVIAR EMAIL PARA O ADMIM INFORMANDO SOBRE O ULTIMO ENVIO DE EMAIL
	$destinatario = $email_sistema;
	$assunto = "Disparo Email Inicio $inicio e Final $final_novo ";    
    $cabecalhos = 'MIME-Version: 1.0' . "\r\n";
	$cabecalhos .= 'Content-type: text/html; charset=utf-8;' . "\r\n";
    $cabecalhos .= "From: ".$email_sistema;
    @mail($destinatario, $assunto, $mensagem, $cabecalhos);

		}

	}

?>

