<?php 
require_once("../conexao.php");
require_once(__DIR__ . "/../../config/upload.php");

$nome_sistema = $_POST['nome_sistema'];
$email_sistema = $_POST['email_sistema'];
$tel_sistema = $_POST['tel_sistema'];
$cnpj_sistema = $_POST['cnpj_sistema'];
$tipo_chave_pix = $_POST['tipo_chave_pix_sistema'];
$chave_pix = $_POST['chave_pix'];
$facebook_sistema = $_POST['facebook_sistema'];
$instagram_sistema = $_POST['instagram_sistema'];
$youtube_sistema = $_POST['youtube_sistema'];
$itens_pag = $_POST['itens_pag'];
$video_sobre = $_POST['video_sobre'];
$aulas_lib = $_POST['aulas_lib'];
$itens_rel = $_POST['itens_rel'];
$desconto_pix = $_POST['desconto_pix'];
$acrescimo_cartao_credito = $_POST['acrescimo_cartao_credito'];
$email_adm_mat = $_POST['email_adm_mat'];
$cartoes_fidelidade = $_POST['cartoes_fidelidade'];
$taxa_mp = $_POST['taxa_mp'];
$taxa_paypal = $_POST['taxa_paypal'];
$taxa_boleto = $_POST['taxa_boleto'];
$valor_max_cartao = $_POST['valor_max_cartao'];
$total_emails_por_envio = $_POST['total_emails_por_envio'];
$intervalo_envio_email = $_POST['intervalo_envio_email'];
$dias_email_matricula = $_POST['dias_email_matricula'];
$dias_excluir_matricula = $_POST['dias_excluir_matricula'];
$professor_cad = $_POST['professor_cad'];
$comissao_professor = $_POST['comissao_professor'];
$dia_pgto_comissao = $_POST['dia_pgto_comissao'];
$questionario = $_POST['questionario'];
$media = $_POST['media'];
$verso = $_POST['verso'];
$api_cartao = $_POST['api_cartao'];





$comissao_tesoureiro = $_POST['comissao_tesoureiro'];
$comissao_secretario = $_POST['comissao_secretario'];
$comissao_tutor = @$_POST['comissao_tutor'];
$comissao_parceiro = @$_POST['comissao_parceiro'];
$comissao_assessor = @$_POST['comissao_assessor'];
$comissao_vendedor = @$_POST['comissao_vendedor'];

$taxa_mp = str_replace(',', '.', $taxa_mp);
$taxa_paypal = str_replace(',', '.', $taxa_paypal);
$taxa_boleto = str_replace(',', '.', $taxa_boleto);
$valor_max_cartao = str_replace(',', '.', $valor_max_cartao);

//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$baseDir = __DIR__ . '/../img';
$uploadLogo = upload_handle_fixed($_FILES['logo'] ?? [], $baseDir . '/logo.png', ['png'], ['image/png'], 2 * 1024 * 1024, true);
if (!$uploadLogo['ok']) {
	echo $uploadLogo['error'];
	exit();
}

$uploadFavicon = upload_handle_fixed($_FILES['favicon'] ?? [], $baseDir . '/favicon.ico', ['ico'], ['image/x-icon', 'image/vnd.microsoft.icon'], 512 * 1024, true);
if (!$uploadFavicon['ok']) {
	echo $uploadFavicon['error'];
	exit();
}

$uploadRel = upload_handle_fixed($_FILES['imgRel'] ?? [], $baseDir . '/logo_rel.jpg', ['jpg', 'jpeg'], ['image/jpeg'], 2 * 1024 * 1024, true);
if (!$uploadRel['ok']) {
	echo $uploadRel['error'];
	exit();
}

$uploadQr = upload_handle_fixed($_FILES['imgQRCode'] ?? [], $baseDir . '/qrcode.jpg', ['jpg', 'jpeg'], ['image/jpeg'], 2 * 1024 * 1024, true);
if (!$uploadQr['ok']) {
	echo $uploadQr['error'];
	exit();
}


//atualizar os dados do config
$query = $pdo->prepare("UPDATE config SET nome_sistema = :nome_sistema, tel_sistema = :tel_sistema, email_sistema = :email_sistema, cnpj_sistema = :cnpj_sistema, tipo_chave_pix = '$tipo_chave_pix', chave_pix = :chave_pix, logo = 'logo.png', icone = 'favicon.ico', logo_rel = 'logo_rel.jpg', qrcode_pix = 'qrcode.jpg', facebook = :facebook, instagram = :instagram, youtube = :youtube, itens_pag = '$itens_pag', video_sobre = :video_sobre, aulas_liberadas = '$aulas_lib', itens_relacionados = '$itens_rel', desconto_pix = '$desconto_pix', acrescimo_cartao_credito = '$acrescimo_cartao_credito', email_adm_mat = '$email_adm_mat', cartoes_fidelidade = '$cartoes_fidelidade', taxa_mp = :taxa_mp, taxa_paypal = :taxa_paypal, taxa_boleto = :taxa_boleto, valor_max_cartao = :valor_max_cartao, total_emails_por_envio = '$total_emails_por_envio', intervalo_envio_email = '$intervalo_envio_email', dias_email_matricula = '$dias_email_matricula', dias_excluir_matricula = '$dias_excluir_matricula', professor_cad = '$professor_cad', comissao_professor = '$comissao_professor', dia_pgto_comissao = '$dia_pgto_comissao', questionario = '$questionario', media = '$media' , verso = '$verso', api_cartao = '$api_cartao', comissao_tesoureiro = '$comissao_tesoureiro', comissao_secretario = '$comissao_secretario', comissao_tutor = '$comissao_tutor', comissao_parceiro = '$comissao_parceiro', comissao_assessor = '$comissao_assessor', comissao_vendedor = '$comissao_vendedor' ");
 

$query->bindValue(":nome_sistema", "$nome_sistema");
$query->bindValue(":email_sistema", "$email_sistema");
$query->bindValue(":tel_sistema", "$tel_sistema");
$query->bindValue(":cnpj_sistema", "$cnpj_sistema");
$query->bindValue(":chave_pix", "$chave_pix");
$query->bindValue(":facebook", "$facebook_sistema");
$query->bindValue(":instagram", "$instagram_sistema");
$query->bindValue(":youtube", "$youtube_sistema");
$query->bindValue(":video_sobre", "$video_sobre");
$query->bindValue(":taxa_mp", "$taxa_mp");
$query->bindValue(":taxa_paypal", "$taxa_paypal");
$query->bindValue(":taxa_boleto", "$taxa_boleto");
$query->bindValue(":valor_max_cartao", "$valor_max_cartao");
$query->execute();

echo 'Editado com Sucesso';

 ?>