<?php 
require_once("../../../conexao.php");
$tabela = 'grupos';

$assunto = $_POST['assunto_email'];
$link = $_POST['link_email'];
$mensagem = $_POST['mensagem'];
$inicio = 0;
$final = $total_emails_por_envio;

if($assunto == ""){
	echo 'Preencha o Campo Assunto!';
	exit();
}

if($mensagem == ""){
	echo 'Preencha o Campo Mensagem!';
	exit();
}



//SALVAR NA TABELA ENVIO EMAILS
$agora = date('Y-m-d H:i:s');

$nova_hora = date('Y-m-d H:i:s', strtotime('+'.$intervalo_envio_email.' minute', strtotime($agora)));
$query = $pdo->prepare("UPDATE envios SET data = :data, final = :final, assunto = :assunto, mensagem = :mensagem, link = :link where id = 1");
$query->execute([
	':data' => $nova_hora,
	':final' => $final,
	':assunto' => $assunto,
	':mensagem' => $mensagem,
	':link' => $link,
]);


$query = $pdo->prepare("SELECT * FROM emails where enviar = 'sim' order by id asc limit :limite");
$query->bindValue(':limite', (int)$final, PDO::PARAM_INT);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$url_s = $url_sistema;
$url_nova = $url_sistema . $link;
$url_cursos = $url_sistema.'cursos';
$url_painel_aluno = $url_sistema.'sistema/painel-aluno';
$url_logo = $url_sistema.'sistema/img/logo-email.png';


for ($i=0; $i < count($res); $i++) { 
	foreach ($res[$i] as $key => $value) {

	}
		$to = $res[$i]['email'];
		$nome_aluno = $res[$i]['nome'];
		$subject = "$assunto";
		$url_descadastrar = $url_sistema . 'descadastrar.php';
		$message = "


				Olá <b>$nome_aluno</b>, <br><br>
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


				$remetente = $email_sistema;
				$headers = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=utf-8;' . "\r\n";

				if($to != $remetente){
					$headers .= "From: " .$remetente;
				}

				@mail($to, $subject, $message, $headers);



	}




	//ENVIAR EMAIL PARA O ADMIM INFORMANDO SOBRE O ULTIMO ENVIO DE EMAIL
	$destinatario = $email_sistema;
	$assunto = $nome_sistema . ' - Campanha Email';
    $mensagem = 'Email enviado até o email de número '.$final;
    $cabecalhos = "From: ".$email_sistema;
    @mail($destinatario, $assunto, $mensagem, $cabecalhos);



echo 'Enviado com Sucesso';

?>
