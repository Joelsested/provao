<?php
$id = (int) ($_GET['id'] ?? 0);
$curso = (int) ($_GET['curso'] ?? 0);
include('../conexao.php');

$nome = 'Aluno';
$id_aluno = 0;
$query = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = :id ORDER BY id DESC");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (count($res) > 0) {
    $nome = $res[0]['nome'];
    $id_aluno = (int) $res[0]['id'];
}

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Porto_Velho');
$data_hoje = utf8_encode(strftime('%A, %d de %B de %Y', strtotime('today')));

$nome_curso = '';
$nota = 'sem Nota';
$respostas = [];

if ($id_aluno > 0 && $curso > 0) {
    $queryMat = $pdo->prepare("SELECT id_curso, nota FROM matriculas WHERE aluno = :aluno AND id_curso = :curso ORDER BY id DESC LIMIT 1");
    $queryMat->execute([':aluno' => $id_aluno, ':curso' => $curso]);
    $mat = $queryMat->fetch(PDO::FETCH_ASSOC);

    if ($mat) {
        $notaBruta = $mat['nota'];
        if ($notaBruta !== null && $notaBruta !== '') {
            $nota = number_format((float) $notaBruta, 2, '.', '');
        }

        $queryCurso = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id ORDER BY id DESC");
        $queryCurso->execute([':id' => (int) $mat['id_curso']]);
        $cursoRow = $queryCurso->fetch(PDO::FETCH_ASSOC);
        $nome_curso = $cursoRow['nome'] ?? '';

        $queryResp = $pdo->prepare("SELECT numeracao, letra FROM perguntas_respostas WHERE id_curso = :id_curso AND id_aluno = :id_aluno ORDER BY numeracao ASC, id ASC");
        $queryResp->execute([':id_curso' => (int) $mat['id_curso'], ':id_aluno' => $id_aluno]);
        $respostas = $queryResp->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Relatorio de Avaliacoes</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: Times, "Times New Roman", Georgia, serif; }
        .cabecalho { padding: 10px; margin-bottom: 20px; width: 100%; border-bottom: solid 1px #0340a3; }
        .titulo_img { position: absolute; margin-top: 10px; margin-left: 10px; color: #0340a3; font-size: 20px; }
        .data_img { position: absolute; margin-top: 45px; margin-left: 10px; border-bottom: 1px solid #000; font-size: 10px; }
        .imagem { width: 200px; position: absolute; right: 20px; top: 10px; }
        .conteudo { padding: 10px 20px 40px; }
        .curso { text-align: center; font-size: 34px; margin: 10px 0 8px; }
        .resp-wrap { text-align: center; margin: 8px 0; }
        .tabela-respostas { border-collapse: collapse; border: 1px solid black; width: 85px; margin: auto; }
        .tabela-respostas td { border: 1px solid black; text-align: left; padding: 2px 4px; font-size: 28px; }
        .tabela-nota { border-collapse: collapse; border: 1px solid black; width: 120px; margin: 10px auto 0; }
        .tabela-nota td { border: 1px solid black; text-align: center; padding: 3px 6px; font-size: 36px; }
        .sem-respostas { text-align: center; font-size: 16px; margin-top: 12px; }
        .footer { margin-top: 20px; width: 100%; background-color: #ebebeb; padding: 5px; position: absolute; bottom: 0; text-align: center; }
        .footer span { font-size: 10px; }
    </style>
</head>
<body>
    <div class="titulo_img"><u>GABARITO <?php echo $nome; ?></u></div>
    <div class="data_img"><?php echo mb_strtoupper($data_hoje); ?></div>
    <img class="imagem" src="<?php echo $url_sistema; ?>/sistema/img/logo_rel.jpg" width="200" height="47">
    <br><br><br>
    <div class="cabecalho"></div>

    <div class="conteudo">
        <?php if ($id_aluno > 0 && $curso > 0) { ?>
            <div class="curso"><?php echo $nome_curso !== '' ? $nome_curso : 'Curso'; ?></div>

            <?php if (count($respostas) > 0) { ?>
                <div class="resp-wrap">
                    <table class="tabela-respostas">
                        <?php foreach ($respostas as $resp) { ?>
                            <tr>
                                <td><?php echo str_pad((string) ((int) ($resp['numeracao'] ?? 0)), 2, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars((string) ($resp['letra'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } else { ?>
                <div class="sem-respostas">Sem respostas registradas para este curso.</div>
            <?php } ?>

            <table class="tabela-nota">
                <tr>
                    <td><?php echo $nota; ?></td>
                </tr>
            </table>
        <?php } else { ?>
            <div class="sem-respostas">Ainda nao ha gabarito para este aluno/curso.</div>
        <?php } ?>
    </div>

    <div class="footer">
        <span><?php echo $nome_sistema; ?> Whatsapp: <?php echo $tel_sistema; ?></span>
    </div>
</body>
</html>
