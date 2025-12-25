<?php
require_once('../../sistema/conexao.php');

$email = $_POST['email'];

if($email == ""){
    echo 'Preencha o Campo Email!';
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM emails WHERE email = ?");
$stmt->execute([$email]);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(@count($dados) > 0){
    
    $stmt = $pdo->prepare("UPDATE emails SET enviar = 'nǜo' WHERE email = ?");
    $stmt->execute([$email]);
    echo 'Descadastrado da Lista com Sucesso!';
}else{
   echo 'Este email nǜo estǭ cadastrado!';

}

?>
