<?php
require_once("../../../conexao.php");
@session_start();

// Verificar se o usuário é administrador
if (@$_SESSION['nivel'] != 'Administrador') {
    echo 'Acesso Negado';
    exit();
}

$id = $_POST['id'];

// Verificar se o gateway que está sendo excluído é o ativo
$query = $pdo->prepare("SELECT * FROM gateways WHERE id = :id");
$query->bindValue(":id", $id);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) > 0) {
    $gateway = $res[0];

    // Se o gateway for o ativo, verificar se existe outro para ativar
    if ($gateway['ativo'] == 'Sim') {
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

    // Excluir o gateway
    $query = $pdo->prepare("DELETE FROM gateways WHERE id = :id");
    $query->bindValue(":id", $id);
    $query->execute();

    echo 'Excluído com Sucesso';
} else {
    echo 'Gateway não encontrado!';
}
?>
