<?php
require_once("../../../conexao.php");
@session_start();

// Verificar se o usuário é administrador
if (@$_SESSION['nivel'] != 'Administrador') {
    echo 'Acesso Negado';
    exit();
}

$nome = $_POST['nome'];
$chave_api = $_POST['chave_api'];
$chave_secreta = $_POST['chave_secreta'];
$webhook_url = $_POST['webhook_url'];
$acao = $_POST['acao'];
$ativar = isset($_POST['ativar_gateway']) ? 'Sim' : 'Não';

// Validações básicas
if ($nome == "") {
    echo 'O nome do gateway é obrigatório!';
    exit();
}

if ($chave_api == "") {
    echo 'A chave API é obrigatória!';
    exit();
}

if ($chave_secreta == "") {
    echo 'A chave secreta é obrigatória!';
    exit();
}

// Se for ativar este gateway, desativa todos os outros
if ($ativar == 'Sim') {
    $pdo->query("UPDATE gateways SET ativo = 'Não'");
}

// Inserir ou atualizar gateway
if ($acao == 'inserir') {
    // Verificar se já existe um gateway com este nome
    $query = $pdo->prepare("SELECT * FROM gateways WHERE nome = :nome");
    $query->bindValue(":nome", $nome);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        echo 'Já existe um gateway com este nome!';
        exit();
    }

    $query = $pdo->prepare("INSERT INTO gateways (nome, chave_api, chave_secreta, webhook_url, ativo, data_cadastro) VALUES (:nome, :chave_api, :chave_secreta, :webhook_url, :ativo, NOW())");

} else {
    $id = @$_POST['id-gateway'];

    // Verificar se já existe outro gateway com este nome
    $query = $pdo->prepare("SELECT * FROM gateways WHERE nome = :nome AND id != :id");
    $query->bindValue(":nome", $nome);
    $query->bindValue(":id", $id);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        echo 'Já existe outro gateway com este nome!';
        exit();
    }

    // Verificar se o gateway que está sendo editado era o único ativo
    if ($ativar == 'Não') {
        $query = $pdo->prepare("SELECT * FROM gateways WHERE id = :id AND ativo = 'Sim'");
        $query->bindValue(":id", $id);
        $query->execute();
        $res = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            // Verificar se existe algum outro gateway para ser definido como ativo
            $query = $pdo->prepare("SELECT * FROM gateways WHERE id != :id LIMIT 1");
            $query->bindValue(":id", $id);
            $query->execute();
            $res = $query->fetchAll(PDO::FETCH_ASSOC);

            if (count($res) > 0) {
                $outro_id = $res[0]['id'];
                // Define outro gateway como ativo
                $stmt = $pdo->prepare("UPDATE gateways SET ativo = 'Sim' WHERE id = :id");
                $stmt->execute([':id' => $outro_id]);
            }
        }
    }

    $query = $pdo->prepare("UPDATE gateways SET nome = :nome, chave_api = :chave_api, chave_secreta = :chave_secreta, webhook_url = :webhook_url, ativo = :ativo WHERE id = :id");
    $query->bindValue(":id", $id);
}

$query->bindValue(":nome", $nome);
$query->bindValue(":chave_api", $chave_api);
$query->bindValue(":chave_secreta", $chave_secreta);
$query->bindValue(":webhook_url", $webhook_url);
$query->bindValue(":ativo", $ativar);

$query->execute();

echo 'Salvo com Sucesso';
?>
