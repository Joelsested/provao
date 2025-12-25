<?php 
//envio para o aluno
$destinatario = $email_aluno;
$assunto = 'Matrícula no Curso ' .$nome_curso;
$url_cursos = $url_sistema.'cursos';
$url_painel_aluno = $url_sistema.'sistema/painel-aluno';
$url_logo = $url_sistema.'sistema/img/logo-email.png';

$mensagem = "

                      Olá $nome_aluno, obrigado por se matricular no curso $nome_curso!! 

                      <br><br> Assim que seu pagamento for aprovado o curso será liberado em seu painél do Aluno. </b> 

                      <br><br> Ir Para o Painel do Aluno -> <a href='$url_painel_aluno' target='_blank'> Clique Aqui </a>

                      <br><br> Ir Para os Cursos -> <a href='$url_cursos' target='_blank'> Clique Aqui </a>
                      <br><br><br>
                      
                      <a href='$url_sistema' target='_blank'><img src='$url_logo' width='300px'></a><br>

                       <i>Nosso Site - <a href='$url_sistema' target='_blank'>$url_sistema</a></i>
                      <br>
                      WhatsApp -> <a href='http://api.whatsapp.com/send?1=pt_BR&phone=55$tel_sistema' alt='$tel_sistema' target='_blank'><i class='fab fa-whatsapp'></i>$tel_sistema</a>

                      <br><br>
                    
                      

                      ";

$remetente = $email_sistema;
$cabecalhos = 'MIME-Version: 1.0' . "\r\n";
$cabecalhos .= 'Content-type: text/html; charset=utf-8;' . "\r\n";
$cabecalhos .= "From: " .$remetente;

if (filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
	$enviado = @mail($destinatario, $assunto, $mensagem, $cabecalhos);
	if (!$enviado) {
		error_log('Falha ao enviar email de matricula para ' . $destinatario);
	}
}





if($email_adm_mat == 'Sim'){
//envio para o administrador
$destinatario = $email_sistema;
$assunto = 'Nova Matrícula no Curso ' .$nome_curso;

$mensagem = "Novo Aluno $nome_aluno, iniciou sua matrícula no curso $nome_curso!!";

$remetente = $email_sistema;
$cabecalhos = 'MIME-Version: 1.0' . "\r\n";
$cabecalhos .= 'Content-type: text/html; charset=utf-8;' . "\r\n";
$cabecalhos .= "From: " .$remetente;

if (filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
	$enviado = @mail($destinatario, $assunto, $mensagem, $cabecalhos);
	if (!$enviado) {
		error_log('Falha ao enviar email de matricula para administrador: ' . $destinatario);
	}
}
}

 ?>
