<?php
require_once dirname(__DIR__, 3) . '/conexao.php';
require_once dirname(__DIR__, 2) . '/verificar.php';
require_once dirname(__DIR__, 4) . '/efi/boleto.php';
$options = require_once dirname(__DIR__, 4) . '/efi/options.php';

@session_start();

$nivel = $_SESSION['nivel'] ?? '';
$idUsuario = (int) ($_SESSION['id'] ?? 0);
$niveisPermitidos = ['Administrador', 'Vendedor', 'Tutor', 'Parceiro'];

if (!in_array($nivel, $niveisPermitidos, true)) {
    echo 'Nao autorizado';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Metodo invalido';
    exit();
}

$tipo = $_POST['tipo'] ?? '';
$vencimento = $_POST['vencimento'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$data = DateTime::createFromFormat('Y-m-d', $vencimento);
if (!$data || $data->format('Y-m-d') !== $vencimento) {
    echo 'Data de vencimento invalida';
    exit();
}
$data->setTime(0, 0, 0);
$minData = new DateTime('tomorrow');
if ($data < $minData) {
    echo 'A data de vencimento deve ser futura';
    exit();
}

if (!$id || !in_array($tipo, ['parcela', 'boleto'], true)) {
    echo 'Parametros invalidos';
    exit();
}

$config = [
    'client_id' => $options['clientId'],
    'client_secret' => $options['clientSecret'],
    'sandbox' => $options['sandbox']
];

$boletoPayment = new EFIBoletoPayment(
    $config['client_id'],
    $config['client_secret'],
    $config['sandbox']
);

function tabelaTemColuna(PDO $pdo, string $tabela, string $coluna): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
    $stmt->execute([':coluna' => $coluna]);
    return (bool) $stmt->fetchColumn();
}

$campoResponsavelAluno = tabelaTemColuna($pdo, 'alunos', 'responsavel_id')
    ? "COALESCE(NULLIF(a.responsavel_id, 0), a.usuario)"
    : "a.usuario";

function normalizarUnicode($texto)
{
    if (!is_string($texto) || $texto === '') {
        return $texto;
    }

    $texto = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($m) {
        $decoded = json_decode("\"\\u" . $m[1] . "\"");
        return $decoded !== null ? $decoded : $m[0];
    }, $texto);

    $texto = preg_replace_callback('/(^|[^\\\\])u([0-9a-fA-F]{4})/', function ($m) {
        $decoded = json_decode("\"\\u" . $m[2] . "\"");
        return $decoded !== null ? ($m[1] . $decoded) : $m[0];
    }, $texto);

    return $texto;
}

function normalizarTelefone($telefone): string
{
    $digits = preg_replace('/\D/', '', (string) $telefone);
    if ($digits === '') {
        return '';
    }

    if (strpos($digits, '55') === 0 && strlen($digits) > 11) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) > 10 && $digits[0] === '0') {
        $digits = substr($digits, 1);
    }

    if (strlen($digits) > 11) {
        $digits = substr($digits, -11);
    }

    if (!preg_match('/^[1-9]{2}9?[0-9]{8}$/', $digits)) {
        return '';
    }

    return $digits;
}

function montarUrlWebhook($url)
{
    $token = env('WEBHOOK_TOKEN', '');
    if ($token === '') {
        return $url;
    }
    $sep = strpos($url, '?') === false ? '?' : '&';
    return $url . $sep . 'token=' . urlencode($token);
}

function featureAutoatendimentoVendedorAtivaBoletos(): bool
{
    $flag = env('FEATURE_AUTOATENDIMENTO_VENDEDOR', '1');
    return in_array(strtolower((string) $flag), ['1', 'true', 'on', 'sim'], true);
}

function vendedorPodeLoginComoAlunoBoletos(PDO $pdo, int $idPessoaVendedor): bool
{
    if ($idPessoaVendedor <= 0) {
        return false;
    }
    if (!tabelaTemColuna($pdo, 'vendedores', 'pode_login_como_aluno')) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT pode_login_como_aluno FROM vendedores WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $idPessoaVendedor]);
    return (int) ($stmt->fetchColumn() ?: 0) === 1;
}

function existeVinculoVendedorAlunoBoletos(PDO $pdo, int $usuarioVendedorId, int $usuarioAlunoId): bool
{
    if ($usuarioVendedorId <= 0 || $usuarioAlunoId <= 0) {
        return false;
    }
    $stmtTabela = $pdo->query("SHOW TABLES LIKE 'usuarios_vinculos'");
    if (!$stmtTabela || !$stmtTabela->fetch(PDO::FETCH_NUM)) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id FROM usuarios_vinculos WHERE usuario_vendedor_id = :vendedor AND usuario_aluno_id = :aluno LIMIT 1");
    $stmt->execute([
        ':vendedor' => $usuarioVendedorId,
        ':aluno' => $usuarioAlunoId,
    ]);
    return (bool) $stmt->fetchColumn();
}

function registrarAuditoriaAutoatendimentoBoletos(int $usuarioAlunoId, int $usuarioVendedorId, int $matriculaId): void
{
    $linha = date('Y-m-d H:i:s')
        . " origem=boletos/atualizar_vencimento.php"
        . " aluno_user={$usuarioAlunoId}"
        . " vendedor_user={$usuarioVendedorId}"
        . " matricula={$matriculaId}"
        . PHP_EOL;
    @file_put_contents(dirname(__DIR__, 4) . '/efi/split_autoatendimento.log', $linha, FILE_APPEND);
}

function montarRepasses(PDO $pdo, int $idAlunoUser): array
{
    $addOrUpdatePayee = function (&$lista, $payee_code, $percentage) {
        if (empty($payee_code) || $percentage <= 0) {
            return;
        }
        foreach ($lista as &$item) {
            if ($item['payee_code'] === $payee_code) {
                $item['percentage'] += $percentage;
                return;
            }
        }
        $lista[] = [
            'payee_code' => $payee_code,
            'percentage' => $percentage
        ];
    };

    $config = $pdo->query("SELECT * FROM config")->fetch(PDO::FETCH_ASSOC) ?: [];
    $comissao_tesoureiro = (float) ($config['comissao_tesoureiro'] ?? 0);
    $comissao_tutor_config = (float) ($config['comissao_tutor'] ?? 0);

    $stmtUsuario = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id");
    $stmtUsuario->execute([':id' => $idAlunoUser]);
    $idPessoa = (int) $stmtUsuario->fetchColumn();

    if (tabelaTemColuna($pdo, 'alunos', 'responsavel_id')) {
        $stmtAluno = $pdo->prepare("SELECT COALESCE(NULLIF(responsavel_id, 0), usuario) FROM alunos WHERE id = :id");
    } else {
        $stmtAluno = $pdo->prepare("SELECT usuario FROM alunos WHERE id = :id");
    }
    $stmtAluno->execute([':id' => $idPessoa]);
    $responsavelUserId = (int) $stmtAluno->fetchColumn();

    $responsavel = [];
    if ($responsavelUserId) {
        $stmtResp = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmtResp->execute([':id' => $responsavelUserId]);
        $responsavel = $stmtResp->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $nivel_responsavel = $responsavel['nivel'] ?? '';
    $responsavelUserId = (int) ($responsavel['id'] ?? 0);
    $wallet_id_responsavel = $responsavel['wallet_id'] ?? '';
    $vendedor_id = (int) ($responsavel['id_pessoa'] ?? 0);

    $comissao_secretario_meus = 0;
    $comissao_secretario_outros = 0;
    $wallet_secretario_outros = '';

    $comissao_tutor_meus = 0;
    $comissao_tutor_outros = $comissao_tutor_config;
    $wallet_tutor_outros = '';

    if ($nivel_responsavel === 'Secretario' && $vendedor_id) {
        $stmtSecretario = $pdo->prepare("SELECT comissao_meus_alunos, comissao_outros_alunos FROM secretarios WHERE id = :id");
        $stmtSecretario->execute([':id' => $vendedor_id]);
        $secretarioRow = $stmtSecretario->fetch(PDO::FETCH_ASSOC) ?: [];
        $comissao_secretario_meus = (float) ($secretarioRow['comissao_meus_alunos'] ?? 0);
        $comissao_secretario_outros = (float) ($secretarioRow['comissao_outros_alunos'] ?? 0);
    }

    if ($nivel_responsavel === 'Tutor' && $vendedor_id) {
        $temMeus = tabelaTemColuna($pdo, 'tutores', 'comissao_meus_alunos');
        $temOutros = tabelaTemColuna($pdo, 'tutores', 'comissao_outros_alunos');

        if (!$temMeus && !$temOutros) {
            $stmtTutor = $pdo->prepare("SELECT comissao FROM tutores WHERE id = :id");
            $stmtTutor->execute([':id' => $vendedor_id]);
            $comissao_tutor_meus = (float) ($stmtTutor->fetchColumn() ?: 0);
        } else {
            $camposTutor = ['comissao'];
            if ($temMeus) {
                $camposTutor[] = 'comissao_meus_alunos';
            }
            if ($temOutros) {
                $camposTutor[] = 'comissao_outros_alunos';
            }

            $stmtTutor = $pdo->prepare("SELECT " . implode(', ', $camposTutor) . " FROM tutores WHERE id = :id");
            $stmtTutor->execute([':id' => $vendedor_id]);
            $tutorRow = $stmtTutor->fetch(PDO::FETCH_ASSOC) ?: [];

            $comissaoBaseTutor = isset($tutorRow['comissao']) ? (float) $tutorRow['comissao'] : 0.0;
            $comissao_tutor_meus = ($temMeus && isset($tutorRow['comissao_meus_alunos']) && $tutorRow['comissao_meus_alunos'] !== null)
                ? (float) $tutorRow['comissao_meus_alunos']
                : $comissaoBaseTutor;
            $comissao_tutor_outros = ($temOutros && isset($tutorRow['comissao_outros_alunos']) && $tutorRow['comissao_outros_alunos'] !== null)
                ? (float) $tutorRow['comissao_outros_alunos']
                : $comissao_tutor_config;
        }
    }

    $autoatendimentoVendedor = false;
    if (
        featureAutoatendimentoVendedorAtivaBoletos()
        && $nivel_responsavel === 'Vendedor'
        && $vendedor_id > 0
        && $responsavelUserId > 0
        && vendedorPodeLoginComoAlunoBoletos($pdo, $vendedor_id)
        && existeVinculoVendedorAlunoBoletos($pdo, $responsavelUserId, $idAlunoUser)
    ) {
        $autoatendimentoVendedor = true;
        registrarAuditoriaAutoatendimentoBoletos($idAlunoUser, $responsavelUserId, 0);
    }

    if ($nivel_responsavel === 'Vendedor' && $vendedor_id) {
        $stmtVendedor = $pdo->prepare("SELECT professor, secretario_id, tutor_id FROM vendedores WHERE id = :id");
        $stmtVendedor->execute([':id' => $vendedor_id]);
        $vendedorRow = $stmtVendedor->fetch(PDO::FETCH_ASSOC) ?: [];
        $secretario_id = (int) ($vendedorRow['secretario_id'] ?? 0);
        $tutor_id = (int) ($vendedorRow['tutor_id'] ?? 0);

        if (!empty($vendedorRow['professor']) && $secretario_id) {
            $stmtSecretario = $pdo->prepare("SELECT comissao_outros_alunos FROM secretarios WHERE id = :id");
            $stmtSecretario->execute([':id' => $secretario_id]);
            $comissao_secretario_outros = (float) ($stmtSecretario->fetchColumn() ?: 0);
            $stmtWallet = $pdo->prepare("SELECT wallet_id FROM usuarios WHERE id_pessoa = :id_pessoa AND nivel = 'Secretario' LIMIT 1");
            $stmtWallet->execute([':id_pessoa' => $secretario_id]);
            $wallet_secretario_outros = $stmtWallet->fetchColumn() ?: '';
        }

        if (!empty($vendedorRow['professor']) && $tutor_id) {
            $temOutrosTutor = tabelaTemColuna($pdo, 'tutores', 'comissao_outros_alunos');
            if ($temOutrosTutor) {
                $stmtTutor = $pdo->prepare("SELECT comissao_outros_alunos FROM tutores WHERE id = :id");
                $stmtTutor->execute([':id' => $tutor_id]);
                $comissao_tutor_outros = (float) ($stmtTutor->fetchColumn() ?: $comissao_tutor_config);
            }
            $stmtWalletTutor = $pdo->prepare("SELECT wallet_id FROM usuarios WHERE id_pessoa = :id_pessoa AND nivel = 'Tutor' LIMIT 1");
            $stmtWalletTutor->execute([':id_pessoa' => $tutor_id]);
            $wallet_tutor_outros = $stmtWalletTutor->fetchColumn() ?: '';
        }
    } elseif ($nivel_responsavel === 'Parceiro' && $vendedor_id) {
        $stmtParceiro = $pdo->prepare("SELECT professor, secretario_id, tutor_id FROM parceiros WHERE id = :id");
        $stmtParceiro->execute([':id' => $vendedor_id]);
        $parceiroRow = $stmtParceiro->fetch(PDO::FETCH_ASSOC) ?: [];
        $secretario_id = (int) ($parceiroRow['secretario_id'] ?? 0);
        $tutor_id = (int) ($parceiroRow['tutor_id'] ?? 0);

        if (!empty($parceiroRow['professor']) && $secretario_id) {
            $stmtSecretario = $pdo->prepare("SELECT comissao_outros_alunos FROM secretarios WHERE id = :id");
            $stmtSecretario->execute([':id' => $secretario_id]);
            $comissao_secretario_outros = (float) ($stmtSecretario->fetchColumn() ?: 0);
            $stmtWallet = $pdo->prepare("SELECT wallet_id FROM usuarios WHERE id_pessoa = :id_pessoa AND nivel = 'Secretario' LIMIT 1");
            $stmtWallet->execute([':id_pessoa' => $secretario_id]);
            $wallet_secretario_outros = $stmtWallet->fetchColumn() ?: '';
        }

        if (!empty($parceiroRow['professor']) && $tutor_id) {
            $temOutrosTutor = tabelaTemColuna($pdo, 'tutores', 'comissao_outros_alunos');
            if ($temOutrosTutor) {
                $stmtTutor = $pdo->prepare("SELECT comissao_outros_alunos FROM tutores WHERE id = :id");
                $stmtTutor->execute([':id' => $tutor_id]);
                $comissao_tutor_outros = (float) ($stmtTutor->fetchColumn() ?: $comissao_tutor_config);
            }
            $stmtWalletTutor = $pdo->prepare("SELECT wallet_id FROM usuarios WHERE id_pessoa = :id_pessoa AND nivel = 'Tutor' LIMIT 1");
            $stmtWalletTutor->execute([':id_pessoa' => $tutor_id]);
            $wallet_tutor_outros = $stmtWalletTutor->fetchColumn() ?: '';
        }
    }

    $comissao_vendedor_config = 0;
    if ($nivel_responsavel === 'Tesoureiro') {
        $comissao_vendedor_config = $comissao_tesoureiro;
    } elseif ($nivel_responsavel === 'Secretario') {
        $comissao_vendedor_config = $comissao_secretario_meus;
    } elseif ($nivel_responsavel === 'Tutor') {
        $comissao_vendedor_config = $comissao_tutor_meus;
    }

    $fixos_wallet_ids = [];
    $stmtFixos = $pdo->query("\n        SELECT usuarios.wallet_id, comissoes.porcentagem\n        FROM usuarios\n        INNER JOIN comissoes ON comissoes.nivel = usuarios.nivel\n        WHERE comissoes.recebeSempre = 1 AND usuarios.wallet_id IS NOT NULL\n    ");
    $fixos = $stmtFixos->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($fixos as $item) {
        if (!empty($item['wallet_id'])) {
            $addOrUpdatePayee($fixos_wallet_ids, $item['wallet_id'], (float) $item['porcentagem'] * 100);
        }
    }

    if (!empty($wallet_id_responsavel) && $comissao_vendedor_config > 0) {
        $addOrUpdatePayee($fixos_wallet_ids, $wallet_id_responsavel, (float) $comissao_vendedor_config * 100);
    }
    if ($autoatendimentoVendedor) {
        $wallet_secretario_outros = $wallet_id_responsavel;
        $wallet_tutor_outros = $wallet_id_responsavel;
    }

    if (!empty($wallet_secretario_outros) && $comissao_secretario_outros > 0) {
        $addOrUpdatePayee($fixos_wallet_ids, $wallet_secretario_outros, $comissao_secretario_outros * 100);
    }
    if (!empty($wallet_tutor_outros) && $comissao_tutor_outros > 0) {
        $addOrUpdatePayee($fixos_wallet_ids, $wallet_tutor_outros, $comissao_tutor_outros * 100);
    }

    $comissao_vendedor = 0;
    if ($nivel_responsavel === 'Vendedor') {
        $stmtComissao = $pdo->prepare("SELECT comissao FROM vendedores WHERE id = :id");
        $stmtComissao->execute([':id' => $vendedor_id]);
        $comissao_vendedor = (float) $stmtComissao->fetchColumn();
    } elseif ($nivel_responsavel === 'Parceiro') {
        $stmtComissao = $pdo->prepare("SELECT comissao FROM parceiros WHERE id = :id");
        $stmtComissao->execute([':id' => $vendedor_id]);
        $comissao_vendedor = (float) $stmtComissao->fetchColumn();
    }

    if (($nivel_responsavel === 'Vendedor' || $nivel_responsavel === 'Parceiro') && !empty($wallet_id_responsavel) && $comissao_vendedor > 0) {
        $addOrUpdatePayee($fixos_wallet_ids, $wallet_id_responsavel, $comissao_vendedor * 100);
    }

    return $fixos_wallet_ids;
}

function montarDadosBoleto(PDO $pdo, int $idMatricula, string $vencimento): array
{
    $stmtMatricula = $pdo->prepare("
        SELECT m.*, 
               CASE WHEN m.pacote = 'Sim' THEN p.nome ELSE c.nome END as nome_item,
               CASE WHEN m.pacote = 'Sim' THEN p.valor ELSE c.valor END as valor_item
        FROM matriculas m
        LEFT JOIN cursos c ON c.id = m.id_curso AND m.pacote != 'Sim'
        LEFT JOIN pacotes p ON p.id = m.id_curso AND m.pacote = 'Sim'
        WHERE m.id = :id
        LIMIT 1
    ");
    $stmtMatricula->execute([':id' => $idMatricula]);
    $matricula = $stmtMatricula->fetch(PDO::FETCH_ASSOC);
    if (!$matricula) {
        throw new Exception('Matricula nao encontrada.');
    }

    $idAlunoUser = (int) $matricula['aluno'];
    $stmtUsuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmtUsuario->execute([':id' => $idAlunoUser]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC) ?: [];

    $nome_aluno = '';
    $email_aluno = '';
    $cpf_aluno = '';
    $telefone_aluno = '';

    if (!empty($usuario['id_pessoa'])) {
        $stmtAluno = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
        $stmtAluno->execute([':id' => $usuario['id_pessoa']]);
        $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [];
        $nome_aluno = normalizarUnicode($aluno['nome'] ?? '');
        $email_aluno = $aluno['email'] ?? '';
        $cpf_aluno = preg_replace('/\D/', '', $aluno['cpf'] ?? '');
        $telefone_aluno = normalizarTelefone($aluno['telefone'] ?? '');
    }

    if ($nome_aluno === '') {
        $nome_aluno = normalizarUnicode($usuario['nome'] ?? '');
    }
    if ($email_aluno === '') {
        $email_aluno = $usuario['usuario'] ?? '';
    }
    if ($cpf_aluno === '') {
        $cpf_aluno = preg_replace('/\D/', '', $usuario['cpf'] ?? '');
    }
    if ($telefone_aluno === '') {
        $telefone_aluno = normalizarTelefone($usuario['telefone'] ?? '');
    }

    $valor_base = (float) ($matricula['subtotal'] ?? 0);
    if ($valor_base <= 0) {
        $valor_base = (float) ($matricula['valor'] ?? 0);
    }

    $valor_centavos = (int) round($valor_base * 100);
    $nome_item = $matricula['nome_item'] ?? 'Produto/Servico';

    $repasses = montarRepasses($pdo, $idAlunoUser);

    return [
        'valor' => $valor_centavos,
        'item_nome' => $nome_item,
        'quantidade' => 1,
        'nome' => $nome_aluno,
        'email' => $email_aluno,
        'cpf' => $cpf_aluno,
        'telefone' => $telefone_aluno ?: '69999694538',
        'vencimento' => $vencimento,
        'repasses' => $repasses,
        'notification_url' => montarUrlWebhook('https://www.sested-eja.com/efi_webhook_boleto.php')
    ];
}

try {
    if ($tipo === 'parcela') {
        $stmtParcela = $pdo->prepare("
            SELECT pb.*, m.aluno, {$campoResponsavelAluno} AS responsavel_id
            FROM parcelas_geradas_por_boleto pb
            JOIN matriculas m ON m.id = pb.id_matricula
            JOIN usuarios u ON u.id = m.aluno
            LEFT JOIN alunos a ON a.id = u.id_pessoa
            WHERE pb.id = :id
            LIMIT 1
        ");
        $stmtParcela->execute([':id' => $id]);
        $parcela = $stmtParcela->fetch(PDO::FETCH_ASSOC);
        if (!$parcela) {
            echo 'Parcela nao encontrada';
            exit();
        }
        if ($nivel !== 'Administrador') {
            $responsavelId = (int) ($parcela['responsavel_id'] ?? 0);
            if ($responsavelId !== $idUsuario) {
                echo 'Nao autorizado';
                exit();
            }
        }

        $chargeId = $parcela['charge_id'] ?? '';
        if ($chargeId === '') {
            echo 'Charge ID nao encontrado';
            exit();
        }

        try {
            $boletoPayment->atualizarVencimentoBoleto($chargeId, $vencimento);
            $payload = $parcela['payload'] ?? '';
            if ($payload !== '' && tabelaTemColuna($pdo, 'parcelas_geradas_por_boleto', 'payload')) {
                $payloadArray = json_decode($payload, true);
                if (is_array($payloadArray)) {
                    $payloadArray['vencimento'] = $vencimento;
                    $payloadArray['notification_url'] = montarUrlWebhook('https://www.sested-eja.com/efi_webhook_boleto_parcelado.php');
                    $payload = json_encode($payloadArray, JSON_UNESCAPED_UNICODE);
                }
                $stmtPayload = $pdo->prepare("UPDATE parcelas_geradas_por_boleto SET payload = :payload WHERE id = :id");
                $stmtPayload->execute([':payload' => $payload, ':id' => $id]);
            }
            if (tabelaTemColuna($pdo, 'parcelas_geradas_por_boleto', 'data_vencimento')) {
                $stmtData = $pdo->prepare("UPDATE parcelas_geradas_por_boleto SET data_vencimento = :vencimento WHERE id = :id");
                $stmtData->execute([':vencimento' => $vencimento, ':id' => $id]);
            }
            echo 'sucesso';
            exit();
        } catch (Exception $e) {
            $payload = $parcela['payload'] ?? '';
            $payloadArray = json_decode($payload, true);
            if (!is_array($payloadArray)) {
                echo 'Nao foi possivel reemitir o boleto desta parcela.';
                exit();
            }
            $payloadArray['vencimento'] = $vencimento;
            $payloadArray['notification_url'] = montarUrlWebhook('https://www.sested-eja.com/efi_webhook_boleto_parcelado.php');
            $payloadAtualizado = json_encode($payloadArray, JSON_UNESCAPED_UNICODE);

            $resultado = $boletoPayment->createBoletoCharge($payloadArray);
            $paymentData = $resultado['payment_data']['data']['payment']['banking_billet'] ?? [];
            $idAsaas = $paymentData['pdf']['charge'] ?? ($resultado['pdf_boleto'] ?? null);
            $transactionReceipt = $paymentData['pix']['qrcode'] ?? null;

            $sql = "UPDATE parcelas_geradas_por_boleto
                    SET charge_id = :charge_id,
                        id_asaas = :id_asaas,
                        transaction_receipt_url = :transaction_receipt_url";
            if (tabelaTemColuna($pdo, 'parcelas_geradas_por_boleto', 'payload')) {
                $sql .= ", payload = :payload";
            }
            if (tabelaTemColuna($pdo, 'parcelas_geradas_por_boleto', 'data_vencimento')) {
                $sql .= ", data_vencimento = :vencimento";
            }
            $sql .= " WHERE id = :id";

            $params = [
                ':charge_id' => $resultado['charge_id'],
                ':id_asaas' => $idAsaas,
                ':transaction_receipt_url' => $transactionReceipt,
                ':id' => $id
            ];
            if (tabelaTemColuna($pdo, 'parcelas_geradas_por_boleto', 'payload')) {
                $params[':payload'] = $payloadAtualizado;
            }
            if (tabelaTemColuna($pdo, 'parcelas_geradas_por_boleto', 'data_vencimento')) {
                $params[':vencimento'] = $vencimento;
            }

            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->execute($params);

            echo 'sucesso';
            exit();
        }
    }

    $stmtBoleto = $pdo->prepare("
        SELECT pb.*, m.aluno, {$campoResponsavelAluno} AS responsavel_id
        FROM pagamentos_boleto pb
        JOIN matriculas m ON m.id = pb.id_matricula
        JOIN usuarios u ON u.id = m.aluno
        LEFT JOIN alunos a ON a.id = u.id_pessoa
        WHERE pb.id = :id
        LIMIT 1
    ");
    $stmtBoleto->execute([':id' => $id]);
    $boleto = $stmtBoleto->fetch(PDO::FETCH_ASSOC);
    if (!$boleto) {
        echo 'Boleto nao encontrado';
        exit();
    }
    if ($nivel !== 'Administrador') {
        $responsavelId = (int) ($boleto['responsavel_id'] ?? 0);
        if ($responsavelId !== $idUsuario) {
            echo 'Nao autorizado';
            exit();
        }
    }

    $chargeId = $boleto['charge_id'] ?? '';
    if ($chargeId === '') {
        echo 'Charge ID nao encontrado';
        exit();
    }

    try {
        $boletoPayment->atualizarVencimentoBoleto($chargeId, $vencimento);
        $consulta = $boletoPayment->consultarCobranca($chargeId);
        $billet = $consulta['data']['payment']['banking_billet'] ?? [];
        $url = $billet['pdf']['charge'] ?? ($billet['link'] ?? null);
        $linha = $billet['line'] ?? null;

        if ($url || $linha) {
            $campos = [];
            $params = [':id' => $id];
            if ($url) {
                $campos[] = "url_boleto = :url_boleto";
                $params[':url_boleto'] = $url;
            }
            if ($linha) {
                $campos[] = "linha_digitavel = :linha_digitavel";
                $params[':linha_digitavel'] = $linha;
            }
            if ($campos) {
                $stmtUpdate = $pdo->prepare("UPDATE pagamentos_boleto SET " . implode(', ', $campos) . " WHERE id = :id");
                $stmtUpdate->execute($params);
            }
        }

        echo 'sucesso';
        exit();
    } catch (Exception $e) {
        $dadosBoleto = montarDadosBoleto($pdo, (int) $boleto['id_matricula'], $vencimento);
        $resultado = $boletoPayment->createBoletoCharge($dadosBoleto);
        $paymentData = $resultado['payment_data']['data']['payment']['banking_billet'] ?? [];

        $url = $paymentData['pdf']['charge'] ?? ($resultado['pdf_boleto'] ?? '');
        $linha = $paymentData['line'] ?? ($resultado['linha_digitavel'] ?? '');

        $stmtUpdate = $pdo->prepare("
            UPDATE pagamentos_boleto
            SET charge_id = :charge_id,
                nosso_numero = :nosso_numero,
                url_boleto = :url_boleto,
                linha_digitavel = :linha_digitavel
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':charge_id' => $resultado['charge_id'],
            ':nosso_numero' => $resultado['charge_id'],
            ':url_boleto' => $url,
            ':linha_digitavel' => $linha,
            ':id' => $id
        ]);

        echo 'sucesso';
        exit();
    }
} catch (Exception $e) {
    $detalhe = trim((string) $e->getMessage());
    echo 'Falha ao atualizar vencimento.' . ($detalhe !== '' ? (' ' . $detalhe) : '');
    exit();
}

