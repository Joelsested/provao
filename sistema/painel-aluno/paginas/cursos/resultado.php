<?php
require_once("../../../conexao.php");
require_once(__DIR__ . '/../../aluno_context.php');
@session_start();

$id_curso = (int) ($_POST['id_curso'] ?? 0);
$id_mat = (int) ($_POST['id_mat'] ?? 0);
$id_aluno = (int) ($_SESSION['id'] ?? 0);

$acertos = 0;
$mediaAprovacao = isset($media_config) ? (float) $media_config : 60.0;

if ($id_curso <= 0 || $id_mat <= 0) {
    echo 'Dados inválidos para processar a prova.';
    exit();
}

if (!empty($id_mat)) {
    $paramsMat = [':id' => $id_mat];
    $ctxIds = aluno_context_ids($pdo);
    $whereAluno = aluno_context_bind_in('aluno', $ctxIds, $paramsMat, 'al_ctx');
    $queryMat = $pdo->prepare("SELECT nota FROM matriculas WHERE id = :id AND {$whereAluno} LIMIT 1");
    $queryMat->execute($paramsMat);
    $notaAtual = (float) ($queryMat->fetchColumn() ?: 0);
    if ($notaAtual >= $mediaAprovacao) {
        $notaEscala10 = number_format($notaAtual / 10, 1, ',', '.');
        echo 'Prova ja feita com aprovacao***' . $notaEscala10;
        exit();
    }
}

$query = $pdo->prepare("SELECT id FROM perguntas_quest WHERE curso = :curso ORDER BY id ASC");
$query->execute(['curso' => $id_curso]);
$perguntas = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = count($perguntas);

if ($total_reg <= 0) {
    echo 'Nenhuma questão cadastrada para este curso.';
    exit();
}

for ($i = 0; $i < $total_reg; $i++) {
    $id_pergunta = (int) $perguntas[$i]['id'];
    $id_alt = (int) ($_POST[$id_pergunta] ?? 0);

    if ($id_alt <= 0) {
        echo 'Preencha Todas as Questoes!';
        exit();
    }

    $queryAlt = $pdo->prepare("SELECT id, correta FROM alternativas WHERE pergunta = :pergunta ORDER BY id ASC");
    $queryAlt->execute(['pergunta' => $id_pergunta]);
    $alternativas = $queryAlt->fetchAll(PDO::FETCH_ASSOC);

    $correta = 'Nao';
    $letra = '';
    foreach ($alternativas as $idxAlt => $alt) {
        if ((int) $alt['id'] === $id_alt) {
            $correta = (($alt['correta'] ?? 'Nao') === 'Sim') ? 'Sim' : 'Nao';
            $letra = chr(65 + $idxAlt);
            break;
        }
    }

    if ($letra === '') {
        echo 'Alternativa invalida na questao ' . ($i + 1) . '.';
        exit();
    }

    if ($correta === 'Sim') {
        $acertos += 1;
    }

    // Garante persistencia das respostas para o relatorio de gabarito.
    if ($id_aluno > 0) {
        $numeracao = $i + 1;
        $stmtExiste = $pdo->prepare("SELECT id FROM perguntas_respostas WHERE id_curso = :curso AND id_aluno = :aluno AND pergunta = :pergunta LIMIT 1");
        $stmtExiste->execute([
            'curso' => $id_curso,
            'aluno' => $id_aluno,
            'pergunta' => $id_pergunta
        ]);
        $idResposta = (int) ($stmtExiste->fetchColumn() ?: 0);

        if ($idResposta > 0) {
            $stmtSalvar = $pdo->prepare("UPDATE perguntas_respostas SET resposta = :resposta, letra = :letra, numeracao = :numeracao, correta = :correta WHERE id = :id");
            $stmtSalvar->execute([
                'resposta' => $id_alt,
                'letra' => $letra,
                'numeracao' => $numeracao,
                'correta' => $correta,
                'id' => $idResposta
            ]);
        } else {
            $stmtSalvar = $pdo->prepare("INSERT INTO perguntas_respostas SET pergunta = :pergunta, id_curso = :curso, id_aluno = :aluno, resposta = :resposta, letra = :letra, numeracao = :numeracao, correta = :correta");
            $stmtSalvar->execute([
                'pergunta' => $id_pergunta,
                'curso' => $id_curso,
                'aluno' => $id_aluno,
                'resposta' => $id_alt,
                'letra' => $letra,
                'numeracao' => $numeracao,
                'correta' => $correta
            ]);
        }
    }
}

$nota = ($acertos / $total_reg) * 100;
$notaF = number_format($nota, 2, ',', '.');

$atualizaNota = $pdo->prepare("UPDATE matriculas SET nota = :nota WHERE id = :id");
$atualizaNota->execute(['nota' => $nota, 'id' => $id_mat]);

if ($nota >= $mediaAprovacao) {
    echo 'Aprovado***' . $notaF;
    $atualizaStatus = $pdo->prepare("UPDATE matriculas SET status = 'Finalizado', data_conclusao = curDate() WHERE id = :id");
    $atualizaStatus->execute(['id' => $id_mat]);
} else {
    echo 'Reprovado***' . $notaF;
}

?>
