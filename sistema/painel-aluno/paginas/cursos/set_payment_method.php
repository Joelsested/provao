<?php
require_once("../../../conexao.php");
$tabela = 'matriculas';
@session_start();

$id_usuario = $_SESSION['id'] ?? null;
$id_curso = $_POST['id_curso'] ?? null;
$forma_pgto = $_POST['forma_pgto'] ?? null;
$id_matricula = $_POST['id'] ?? ($_POST['id_matricula'] ?? null);

// Se voce ja tiver essas infos no POST ou sessao, pode pegar de la
$nome_do_curso = $_POST['nome_do_curso'] ?? 'Pagamento Curso';
$pacote = $_POST['pacote'] ?? '';
$quantidadeParcelas = $_POST['quantidadeParcelas'] ?? 1; // valor padrao

header('Content-Type: application/json; charset=utf-8');

$startedTransaction = false;

try {
    $forma_pgto = strtoupper(trim((string)$forma_pgto));
    $quantidadeParcelas = (int)$quantidadeParcelas;
    if ($quantidadeParcelas < 1) {
        $quantidadeParcelas = 1;
    }
    if ($forma_pgto === 'BOLETO_PARCELADO') {
        if ($quantidadeParcelas > 6) {
            $quantidadeParcelas = 6;
        }
    } else {
        $quantidadeParcelas = 1;
    }

    if (!$id_usuario || !$id_curso || !$forma_pgto) {
        throw new Exception("Dados incompletos");
    }

    $pacote_normalizado = strtolower(trim((string)$pacote));
    $is_pacote = ($pacote_normalizado === 'sim');
    $pacote = $is_pacote ? 'Sim' : 'Nao';

    $where = "aluno = :aluno AND id_curso = :id_curso";
    $params = [
        ':aluno' => $id_usuario,
        ':id_curso' => $id_curso,
    ];

    if ($is_pacote) {
        $where .= " AND pacote = 'Sim'";
    } else {
        $where .= " AND (pacote <> 'Sim' OR pacote IS NULL)";
    }

    $stmt = $pdo->prepare("SELECT id, status, total_recebido, forma_pgto FROM $tabela WHERE $where");
    $stmt->execute($params);
    $matriculas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$matriculas && $id_matricula !== null && $id_matricula !== '') {
        $stmt = $pdo->prepare("SELECT id, status, total_recebido, forma_pgto FROM $tabela WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id_matricula]);
        $matricula = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($matricula) {
            $matriculas = [$matricula];
            $where = "id = :id";
            $params = [':id' => $matricula['id']];
        }
    }

    if (!$matriculas) {
        throw new Exception("Matricula nao encontrada");
    }

    foreach ($matriculas as $matricula) {
        $status = strtoupper(trim((string)($matricula['status'] ?? '')));
        $total_recebido = (float)($matricula['total_recebido'] ?? 0);
        if ($status !== 'AGUARDANDO' || $total_recebido > 0) {
            throw new Exception("Pagamento ja confirmado. Nao e possivel alterar.");
        }
    }

    $forma_atual = strtoupper(trim((string)($matriculas[0]['forma_pgto'] ?? '')));
    $mesma_forma = true;
    foreach ($matriculas as $matricula) {
        $forma_mat = strtoupper(trim((string)($matricula['forma_pgto'] ?? '')));
        if ($forma_mat !== $forma_atual) {
            $mesma_forma = false;
            break;
        }
    }

    if (!$mesma_forma || $forma_atual !== $forma_pgto) {
        $ids = array_column($matriculas, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $pdo->beginTransaction();
        $startedTransaction = true;

        $pdo->prepare("DELETE FROM pagamentos_pix WHERE id_matricula IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM pagamentos_boleto WHERE id_matricula IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM parcelas_geradas_por_boleto WHERE id_matricula IN ($placeholders)")->execute($ids);
        $pdo->prepare("DELETE FROM boletos_parcelados WHERE id_matricula IN ($placeholders)")->execute($ids);

        $update = $pdo->prepare("UPDATE $tabela SET forma_pgto = :forma_pgto, id_asaas = NULL, boleto = NULL, ref_api = NULL, dump = NULL WHERE $where");
        $params[':forma_pgto'] = $forma_pgto;
        $update->execute($params);

        $pdo->commit();
        $startedTransaction = false;
    }

    // Definir pagina de redirecionamento com base na forma
    $redirectUrl = null;
    switch ($forma_pgto) {
        case 'BOLETO':
            // monta URL com os parametros exigidos pelo /efi/index.php
            $redirectUrl = $url_sistema . "efi/index.php?" . http_build_query([
                "formaDePagamento" => $forma_pgto,
                "billingType" => strtoupper($forma_pgto),
                "quantidadeParcelas" => $quantidadeParcelas,
                "id_do_curso" => $id_curso,
                "nome_do_curso" => $nome_do_curso,
                "pacote" => $pacote
            ]);
            break;
        case 'BOLETO_PARCELADO':
            // monta URL com os parametros exigidos pelo /efi/index.php
            $redirectUrl = $url_sistema . "efi/index.php?" . http_build_query([
                "formaDePagamento" => $forma_pgto,
                "billingType" => strtoupper($forma_pgto),
                "quantidadeParcelas" => $quantidadeParcelas,
                "id_do_curso" => $id_curso,
                "nome_do_curso" => $nome_do_curso,
                "pacote" => $pacote
            ]);
            break;
        case 'CARTAO_DE_CREDITO':
            $redirectUrl = $url_sistema . "sistema/painel-aluno/index.php?pagina=parcelas_cartao";
            break;
        default:
            $redirectUrl = null;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Forma de pagamento salva com sucesso!",
        "redirect" => $redirectUrl
    ]);
} catch (Exception $e) {
    if ($startedTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
