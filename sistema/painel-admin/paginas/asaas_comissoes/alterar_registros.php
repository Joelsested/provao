<?php

require_once("../../../conexao.php");

if (!isset($_POST['acao']))
    header('Location: ../../index.php?pagina=asaas_comissoes');


$acao = $_POST['acao'];
$id_exclusao = $_POST['id_exclusao'];
$registros = $_POST['registros'];

// print_r($_POST['registros']);

if ($acao == 'excluir') {
    $stmt = $pdo->prepare("DELETE FROM comissoes WHERE id = ?");
    $stmt->execute([$id_exclusao]);

    header('Location: ../../index.php?pagina=asaas_comissoes');

} elseif ($acao == 'editar') {

    foreach ($registros as $registro) {
        $stmt = $pdo->prepare("UPDATE comissoes SET porcentagem = ?, recebeSempre = ? WHERE id = ?");
        $stmt->execute([$registro['porcentagem'], $registro['recebeSempre'], $registro['id']]);
    }


    header('Location: ../../index.php?pagina=asaas_comissoes');
} else {
    echo 'Ação inválida.';
    sleep(3);
    header('Location: ../../index.php?pagina=asaas_comissoes');
}
?>
