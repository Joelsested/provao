<?php

require_once("../../../conexao.php");
require_once(__DIR__ . '/../../aluno_context.php');

@session_start();

$idCurso = (int) ($_POST['id'] ?? 0);
if ($idCurso <= 0) {
    echo '<small>Curso inválido.</small>';
    exit;
}

$idAluno = (int) ($_SESSION['id'] ?? 0);
$alunoContextoIds = aluno_context_ids($pdo);

$params = [':curso' => $idCurso];
$whereAluno = aluno_context_bind_in('aluno', $alunoContextoIds, $params, 'aluno_ctx');
$queryMat = $pdo->prepare("SELECT id FROM matriculas WHERE id_curso = :curso AND {$whereAluno} AND status != 'Aguardando' LIMIT 1");
$queryMat->execute($params);
$matriculaOk = (int) ($queryMat->fetchColumn() ?: 0) > 0;

if (!$matriculaOk) {
    @error_log("LISTAR_APOSTILAS_BLOQUEADO usuario={$idAluno} curso={$idCurso} sessao=" . session_id());
    echo '<small>Você não está matriculado neste curso.</small>';
    exit;
}

$query = $pdo->prepare("
    SELECT a.id, a.num_aula, a.nome, a.apostila, a.sequencia_aula, s.nome AS nome_sessao
    FROM aulas a
    LEFT JOIN sessao s ON s.id = a.sessao
    WHERE a.curso = :curso
      AND TRIM(IFNULL(a.apostila, '')) <> ''
    ORDER BY COALESCE(NULLIF(a.sequencia_aula, 0), 999999), COALESCE(NULLIF(a.num_aula, 0), 999999), a.id ASC
");
$query->execute([':curso' => $idCurso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) === 0) {
    echo '<small>Este curso não possui apostilas cadastradas.</small>';
    exit;
}

echo '<small>';
echo '<table class="table table-hover" style="margin-bottom: 0;">';
echo '<thead><tr>';
echo '<th style="width: 45%;">Aula</th>';
echo '<th style="width: 35%;">Arquivo</th>';
echo '<th style="width: 20%;">Ações</th>';
echo '</tr></thead><tbody>';

foreach ($res as $row) {
    $numAula = (string) ($row['num_aula'] ?? '');
    $nomeAula = (string) ($row['nome'] ?? '');
    $nomeSessao = trim((string) ($row['nome_sessao'] ?? ''));
    $apostila = trim((string) ($row['apostila'] ?? ''));

    $tituloAula = 'Aula ' . $numAula . ' - ' . $nomeAula;
    if ($nomeSessao !== '') {
        $tituloAula = $nomeSessao . ' - ' . $tituloAula;
    }

    $arquivoLabel = basename(str_replace('\\', '/', $apostila));
    $arquivoLabelSemData = preg_replace('/^\d{2}-\d{2}-\d{4}-\d{2}-\d{2}-\d{2}-/u', '', $arquivoLabel);
    if ($arquivoLabelSemData === null || $arquivoLabelSemData === '') {
        $arquivoLabelSemData = $arquivoLabel;
    }
    $arquivoUrl = rawurlencode($apostila);
    $urlAbrir = $url_sistema . 'sistema/painel-aluno/paginas/cursos/abrir-apostila.php?arquivo=' . $arquivoUrl . '&download=0';
    $urlBaixar = $url_sistema . 'sistema/painel-aluno/paginas/cursos/abrir-apostila.php?arquivo=' . $arquivoUrl . '&download=1';
    $urlAbrirJs = json_encode($urlAbrir, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $urlBaixarJs = json_encode($urlBaixar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tituloJs = json_encode($arquivoLabelSemData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    echo '<tr>';
    echo '<td>' . htmlspecialchars($tituloAula, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($arquivoLabelSemData, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>';
    echo '<button type="button" class="btn btn-xs btn-primary" onclick="return abrirArquivoNoApp(' . $urlAbrirJs . ', ' . $tituloJs . ');" title="Abrir apostila aqui no app"><i class="fa fa-external-link"></i> Abrir</button>';
    echo '&nbsp;';
    echo '<button type="button" class="btn btn-xs btn-success" onclick="return baixarArquivoNoApp(' . $urlBaixarJs . ');" title="Baixar apostila"><i class="fa fa-download"></i> Baixar</button>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</small>';
