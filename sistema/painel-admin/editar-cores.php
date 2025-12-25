<?php
require '../conexao.php';
header('Content-Type: application/json');

try {
    if ($_POST['acao'] !== 'salvar_cor') {
        throw new Exception('Ação inválida');
    }

    $nomes = $_POST['nome_classe'] ?? [];
    $cores = $_POST['valor_cor'] ?? [];
    $ids = $_POST['id_cor'] ?? [];

    $respostas = [];

    foreach ($nomes as $i => $nomeClasse) {
        $valorCor = $cores[$i] ?? '';
        $id = $ids[$i] ?? null;

        if (!trim($nomeClasse) || !trim($valorCor)) {
            continue;
        }

        // Verifica se já existe um registro com esse nome_classe
        $stmt = $pdo->prepare("SELECT id FROM cores_sistema WHERE nome_classe = ?");
        $stmt->execute([$nomeClasse]);
        $registroExistente = $stmt->fetch();

        if ($registroExistente) {
            // Se existe, atualiza o registro existente
            $idExistente = $registroExistente['id'];
            $stmt = $pdo->prepare("UPDATE cores_sistema SET valor_cor = ?, data_atualizacao = NOW() WHERE id = ?");
            $executado = $stmt->execute([$valorCor, $idExistente]);
            $respostas[] = $executado ? "Cor atualizada para '$nomeClasse'" : "Erro ao atualizar cor '$nomeClasse'";
        } else {
            // Se não existe, insere novo registro
            $stmt = $pdo->prepare("INSERT INTO cores_sistema (nome_classe, valor_cor, data_criacao, data_atualizacao) VALUES (?, ?, NOW(), NOW())");
            $executado = $stmt->execute([$nomeClasse, $valorCor]);
            $respostas[] = $executado ? "Nova cor '$nomeClasse' inserida" : "Erro ao inserir nova cor '$nomeClasse'";
        }
    }

    echo json_encode([
        'status' => 'success',
        'mensagem' => 'Cores salvas com sucesso.',
        'detalhes' => $respostas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'danger',
        'mensagem' => 'Erro: ' . $e->getMessage()
    ]);
}