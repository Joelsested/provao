<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../helpers.php");
$tabela = 'matriculas';
@session_start();

$id_usuario = $_SESSION['id'] ?? null;
$id_curso = $_POST['id_curso'] ?? null;
$forma_pgto = $_POST['forma_pgto'] ?? null;
$id_matricula = $_POST['id'] ?? ($_POST['id_matricula'] ?? null);

// Se você já tiver essas informações no POST ou sessão, pode pegar de lá.
$nome_do_curso = $_POST['nome_do_curso'] ?? 'Pagamento Curso';
$pacote = $_POST['pacote'] ?? '';
$quantidadeParcelas = $_POST['quantidadeParcelas'] ?? 1; // valor padrao

header('Content-Type: application/json; charset=utf-8');

$startedTransaction = false;

function idadeCompletaEmAnos(string $dataNascimento, ?DateTimeImmutable $hojeRef = null): int
{
    $dataNormalizada = function_exists('normalizeDate') ? normalizeDate($dataNascimento) : trim($dataNascimento);
    if ($dataNormalizada === '' || $dataNormalizada === '0000-00-00') {
        return -1;
    }

    $hoje = $hojeRef ?: new DateTimeImmutable('today');
    try {
        $nascimento = new DateTimeImmutable($dataNormalizada);
    } catch (Throwable $e) {
        return -1;
    }

    if ($nascimento > $hoje) {
        return -1;
    }

    return (int) $nascimento->diff($hoje)->y;
}

function colunaExisteTabela(PDO $pdo, string $tabela, string $coluna): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
        $stmt->execute([':coluna' => $coluna]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

function garantirColunaLiberacaoMenor(PDO $pdo): void
{
    if (colunaExisteTabela($pdo, 'alunos', 'liberado_menor_18')) {
        return;
    }
    try {
        $pdo->exec("ALTER TABLE alunos ADD COLUMN liberado_menor_18 TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {
        // Mantem fluxo sem interromper caso nao consiga alterar estrutura.
    }
}

try {
    if (($_SESSION['nivel'] ?? '') === 'Vendedor') {
        throw new Exception("Você não pode comprar como vendedor. Entre como aluno.");
    }

    $forma_pgto = strtoupper(trim((string)$forma_pgto));
    $formasPermitidas = ['BOLETO', 'BOLETO_PARCELADO', 'CARTAO_DE_CREDITO'];
    if (!in_array($forma_pgto, $formasPermitidas, true)) {
        throw new Exception("Forma de pagamento inválida para o fluxo EFY.");
    }
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

    if (!$id_usuario || !$forma_pgto) {
        throw new Exception("Dados incompletos");
    }

    $id_curso = (int) $id_curso;
    $id_matricula = (int) $id_matricula;
    if ($id_curso <= 0 && $id_matricula > 0) {
        $stmtMatBase = $pdo->prepare("SELECT id_curso FROM $tabela WHERE id = :id LIMIT 1");
        $stmtMatBase->execute([':id' => $id_matricula]);
        $id_curso = (int) ($stmtMatBase->fetchColumn() ?: 0);
    }
    if ($id_curso <= 0) {
        throw new Exception("Dados incompletos");
    }

    garantirColunaLiberacaoMenor($pdo);
    $stmtAluno = $pdo->prepare("
        SELECT a.nascimento, COALESCE(a.liberado_menor_18, 0) AS liberado_menor_18
        FROM usuarios u
        INNER JOIN alunos a ON a.id = u.id_pessoa
        WHERE u.id = :id_usuario
        LIMIT 1
    ");
    $stmtAluno->execute([':id_usuario' => (int) $id_usuario]);
    $dadosAluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$dadosAluno) {
        throw new Exception("Aluno não encontrado.");
    }
    $idade = idadeCompletaEmAnos((string) ($dadosAluno['nascimento'] ?? ''));
    $liberadoMenor = (int) ($dadosAluno['liberado_menor_18'] ?? 0) === 1;
    if ($idade >= 0 && $idade < 18 && !$liberadoMenor) {
        throw new Exception("Aluno menor de 18 anos. Só admin pode liberar matrículas para alunos menores.");
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
        throw new Exception("Matrícula não encontrada");
    }

    foreach ($matriculas as $matricula) {
        $status = strtoupper(trim((string)($matricula['status'] ?? '')));
        $total_recebido = (float)($matricula['total_recebido'] ?? 0);
        if ($status !== 'AGUARDANDO' || $total_recebido > 0) {
            throw new Exception("Pagamento já confirmado. Não é possível alterar.");
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
