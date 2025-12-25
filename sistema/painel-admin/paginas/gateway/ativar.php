<?php
require_once("../../../conexao.php");
@session_start();

// Verificar se o usuário é administrador
if (@$_SESSION['nivel'] != 'Administrador') {
    echo 'Acesso Negado';
    exit();
}

$id = $_POST['id'];

// Desativar todos os gateways
$pdo->query("UPDATE gateways SET ativo = 'Não'");

// Ativar o gateway selecionado
$query = $pdo->prepare("UPDATE gateways SET ativo = 'Sim' WHERE id = :id");
$query->bindValue(":id", $id);
$query->execute();

echo 'Ativado com Sucesso';
?>