<?php
require_once("../../../conexao.php");

header('Content-Type: application/json');



try {
    $id_curso = $_POST['id_curso'] ?? '';
    $id_aluno = $_POST['id_aluno'] ?? '';
    $id_matricula = $_POST['id_matricula'] ?? '';
    $respostas = json_decode($_POST['respostas'] ?? '[]', true);

    // Validações básicas
    if (empty($id_aluno)) {
        echo json_encode(['success' => false, 'message' => 'Sessão expirada, atualize a página']);
        exit();
    }

    if (empty($respostas)) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma resposta foi enviada']);
        exit();
    }

    // Iniciar transação para garantir consistência
    $pdo->beginTransaction();

    // ETAPA 1: Salvar todas as respostas
    $tabela = 'perguntas_respostas';
    $acertos = 0;
    $total_questoes = count($respostas);
    $detalhes_respostas = [];

    foreach ($respostas as $index => $resposta_data) {
    $pergunta_id = $resposta_data['questionId'];
    $pergunta_texto = $resposta_data['questionText'];
    $resposta_id = $resposta_data['answerId'];
    $resposta_texto = $resposta_data['answerText'];

    // Corrigido: normalizar o valor de isCorrect
    $is_correta = ($resposta_data['isCorrect'] === 'Sim') ? 'Sim' : 'Não';

    $numeracao = $index + 1;
    
    // Buscar a letra da alternativa
    $query_letra = $pdo->prepare("SELECT * FROM alternativas WHERE pergunta = :pergunta ORDER BY id asc");
    $query_letra->execute(['pergunta' => $pergunta_id]);
    $alternativas = $query_letra->fetchAll(PDO::FETCH_ASSOC);
    $letra = '';
    
    foreach ($alternativas as $i => $alt) {
        if ($alt['id'] == $resposta_id) {
            $letra = chr(65 + $i); // A, B, C, D...
            break;
        }
    }

    // Verificar se já existe resposta para esta pergunta
    $query_existe = $pdo->prepare("SELECT * FROM {$tabela} WHERE id_curso = :curso AND id_aluno = :aluno AND pergunta = :pergunta");
    $query_existe->execute(['curso' => $id_curso, 'aluno' => $id_aluno, 'pergunta' => $pergunta_id]);
    $existe = $query_existe->fetchAll(PDO::FETCH_ASSOC);

    if (count($existe) > 0) {
        // Atualizar resposta existente
        $query = $pdo->prepare("UPDATE $tabela SET 
            resposta = :resposta, 
            letra = :letra, 
            numeracao = :numeracao, 
            correta = :correta 
            WHERE pergunta = :pergunta_id AND id_curso = :id_curso AND id_aluno = :id_aluno");
    } else {
        // Inserir nova resposta
        $query = $pdo->prepare("INSERT INTO $tabela SET 
            pergunta = :pergunta_id, 
            id_curso = :id_curso, 
            id_aluno = :id_aluno, 
            resposta = :resposta, 
            letra = :letra, 
            numeracao = :numeracao, 
            correta = :correta");
    }

    $query->bindValue(":pergunta_id", $pergunta_id);
    $query->bindValue(":id_curso", $id_curso);
    $query->bindValue(":id_aluno", $id_aluno);
    $query->bindValue(":resposta", $resposta_id);
    $query->bindValue(":letra", $letra);
    $query->bindValue(":numeracao", $numeracao);
    $query->bindValue(":correta", $is_correta);
    
    if (!$query->execute()) {
        throw new Exception("Erro ao salvar resposta da questão $numeracao");
    }

    // Contar acertos
    if ($is_correta === 'Sim') {
        $acertos++;
    }

    // Adicionar detalhes da resposta para o retorno
    $detalhes_respostas[] = [
        'questao' => $numeracao,
        'pergunta' => $pergunta_texto,
        'resposta_selecionada' => $resposta_texto,
        'letra' => $letra,
        'correta' => $is_correta === 'Sim',
        'resposta_id' => $resposta_id
    ];
}


    // ETAPA 2: Calcular nota e resultado
    $nota = ($acertos / $total_questoes) * 100;
    $nota_formatada = number_format($nota, 2, ',', '.');

    // Buscar média de aprovação (assumindo que existe uma configuração global)
    $media_config = 60; // Valor padrão, ajuste conforme sua configuração
    
    // Tentar buscar a média da configuração se existir
    try {
        $query_config = $pdo->query("SELECT valor FROM configuracoes WHERE nome = 'media_aprovacao' LIMIT 1");
        $config = $query_config->fetch(PDO::FETCH_ASSOC);
        if ($config) {
            $media_config = (float)$config['valor'];
        }
    } catch (Exception $e) {
        // Se não existir tabela de configurações, usar valor padrão
    }

    $aprovado = $nota >= $media_config;
    $status = $aprovado ? 'Finalizado' : 'Reprovado';
    $resultado_texto = $aprovado ? 'Aprovado' : 'Reprovado';

    // ETAPA 3: Atualizar matrícula
    if (!empty($id_matricula)) {
        $update_matricula = $pdo->prepare("UPDATE matriculas SET nota = :nota WHERE id = :id_matricula");
        $update_matricula->bindValue(":nota", $nota);
        $update_matricula->bindValue(":id_matricula", $id_matricula);
        
        if (!$update_matricula->execute()) {
            throw new Exception("Erro ao atualizar nota da matrícula");
        }

        // Se aprovado, atualizar status e data de conclusão
        if ($aprovado) {
            $update_status = $pdo->prepare("UPDATE matriculas SET status = 'Finalizado', data_conclusao = CURDATE() WHERE id = :id_matricula");
            $update_status->bindValue(":id_matricula", $id_matricula);
            
            if (!$update_status->execute()) {
                throw new Exception("Erro ao atualizar status da matrícula");
            }
        }
    }

    // Commit da transação
    $pdo->commit();

    // ETAPA 4: Preparar resposta com resultado completo
    $resposta_final = [
        'success' => true,
        'resultado' => [
            'status' => $resultado_texto,
            'aprovado' => $aprovado,
            'nota' => $nota,
            'nota_formatada' => $nota_formatada,
            'acertos' => $acertos,
            'total_questoes' => $total_questoes,
            'percentual_acerto' => round(($acertos / $total_questoes) * 100, 1),
            'media_necessaria' => $media_config
        ],
        'detalhes' => $detalhes_respostas,
        'estatisticas' => [
            'questoes_certas' => $acertos,
            'questoes_erradas' => $total_questoes - $acertos,
            'total_questoes' => $total_questoes
        ],
        'message' => "Questionário processado com sucesso! Resultado: $resultado_texto com nota $nota_formatada"
    ];

    echo json_encode($resposta_final, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar questionário: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
