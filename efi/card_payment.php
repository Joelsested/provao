<?php

require_once('../vendor/autoload.php');
require_once("../sistema/conexao.php");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

@session_start();

function obterUsuarioResponsavelAluno(array $aluno): int
{
    $responsavelId = isset($aluno['responsavel_id']) ? (int) $aluno['responsavel_id'] : 0;
    if ($responsavelId > 0) {
        return $responsavelId;
    }

    return (int) ($aluno['usuario'] ?? 0);
}

function tabelaTemColunaLocal(PDO $pdo, string $tabela, string $coluna): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
    $stmt->execute([':coluna' => $coluna]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function featureAutoatendimentoVendedorAtiva(): bool
{
    $flag = env('FEATURE_AUTOATENDIMENTO_VENDEDOR', '1');
    return in_array(strtolower((string) $flag), ['1', 'true', 'on', 'sim'], true);
}

function vendedorPodeLoginComoAluno(PDO $pdo, int $idPessoaVendedor): bool
{
    if ($idPessoaVendedor <= 0) {
        return false;
    }
    if (!tabelaTemColunaLocal($pdo, 'vendedores', 'pode_login_como_aluno')) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT pode_login_como_aluno FROM vendedores WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $idPessoaVendedor]);
    return (int) ($stmt->fetchColumn() ?: 0) === 1;
}

function existeVinculoVendedorAluno(PDO $pdo, int $usuarioVendedorId, int $usuarioAlunoId): bool
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

function registrarAuditoriaAutoatendimento(string $origem, int $usuarioAlunoId, int $usuarioVendedorId, int $matriculaId): void
{
    $linha = date('Y-m-d H:i:s')
        . " origem={$origem}"
        . " aluno_user={$usuarioAlunoId}"
        . " vendedor_user={$usuarioVendedorId}"
        . " matricula={$matriculaId}"
        . PHP_EOL;
    @file_put_contents(__DIR__ . '/split_autoatendimento.log', $linha, FILE_APPEND);
}
header('Content-Type: application/json; charset=utf-8');
$json = file_get_contents('php://input');
$data = json_decode($json, true);


// Parâmetros recebidos via GET
$forma_de_pagamento = $data['payment_method'] ?? '';
$billingType = strtoupper((string) $forma_de_pagamento);
$quantidadeParcelas = isset($data['installments']) ? (int) $data['installments'] : 1;
if ($quantidadeParcelas < 1) {
    $quantidadeParcelas = 1;
}
$maxParcelas = 1;
if ($billingType === 'DEBIT_CARD') {
    $maxParcelas = 6;
} elseif ($billingType === 'CREDIT_CARD') {
    $maxParcelas = 12;
}
if ($quantidadeParcelas > $maxParcelas) {
    $quantidadeParcelas = $maxParcelas;
}

//Busca dados para atualização da situação da matricula
$id_do_aluno = @$_SESSION['id'];
$id_do_curso_pag = $data['id_do_curso'];
$nome_curso_titulo = $data['nome_do_curso'];

$is_pacote = $data['pacote'] ?? ($_GET['pacote'] ?? null);

if ($is_pacote == 'Sim') {
    $curso_pacote = "Sim";
} else {
    $curso_pacote = "Não";
}



$options = require_once 'options.php';


// Configurações da EFI
$config = [
    'client_id' => $options['clientId'],
    'client_secret' => $options['clientSecret'],
    'certificate_path' => $options['certificate'], // Apenas para PIX
    'chave_pix' => $options['pixKey'] ?? '', // Sua chave PIX
    'sandbox' => $options['sandbox'] // true para teste, false para produção
];


$queryPix = $pdo->query("SELECT desconto_pix FROM config");
$resPix = $queryPix->fetchAll(PDO::FETCH_ASSOC);

$descontoPix = json_encode($resPix[0]['desconto_pix']);

$query2 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query2->execute([':id' => $id_do_aluno]);
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$nivel_responsavel_pelo_cadastro_do_aluno = 0;
$usuario_atendente_do_aluno = 0;

if (@count($res2) > 0) {
    $id_pessoa = $res2[0]['id_pessoa'];
    $query3 = $pdo->prepare("SELECT * FROM alunos where id = :id");
    $query3->execute([':id' => $id_pessoa]);
    $res3 = $query3->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res3) > 0) {
        $nome_aluno = $res3[0]['nome'];
        $email_aluno = $res3[0]['email'];
        $cpf_aluno = str_replace('-', '', str_replace('.', '', $res3[0]['cpf']));
        $nivel_responsavel_pelo_cadastro_do_aluno = obterUsuarioResponsavelAluno($res3[0]);
        $usuario_atendente_do_aluno = (int) ($res3[0]['usuario'] ?? 0);
    }
}



//BUSCA DADOS DA MATRICULA
if ($is_pacote == 'Sim') {
    $sqlMat = "SELECT * FROM matriculas WHERE id_curso = :id_curso AND aluno = :aluno AND pacote = 'Sim' ORDER BY id DESC LIMIT 1";
} else {
    $sqlMat = "SELECT * FROM matriculas WHERE id_curso = :id_curso AND aluno = :aluno AND (pacote <> 'Sim' OR pacote IS NULL OR pacote = '') ORDER BY id DESC LIMIT 1";
}
$query = $pdo->prepare($sqlMat);
$query->execute([':id_curso' => $id_do_curso_pag, ':aluno' => $id_do_aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (@count($res) > 0) {
    $valor_curso = $res[0]['subtotal'];
    $status_mat = $res[0]['status'];
    $id_venda = $res[0]['id'];
    $id_usuario_professor = $res[0]['professor'];
    $valorF = number_format($valor_curso, 2, ',', '.');

    // Verifica o tipo de pagamento e define o valor a pagar
    if ($billingType == "BOLETO") {
        $valor_a_pagar = $valor_curso;
    } elseif ($billingType == "PIX") {
        $valor_a_pagar = $valor_curso - ($valor_curso * ($descontoPix / 100));
    } else {
        $valor_a_pagar = $valor_curso;
    }
}



// Centraliza regras de repasse por dono comercial (responsavel_id) e atendente operacional (alunos.usuario).
$queryConfig = $pdo->query("SELECT * FROM config");
$resConfig = $queryConfig->fetchAll(PDO::FETCH_ASSOC);

$consulta_comissao_nivel_responsavel = $pdo->prepare("SELECT id, nivel, id_pessoa, wallet_id FROM usuarios WHERE id = :id LIMIT 1");
$consulta_comissao_nivel_responsavel->execute([':id' => $nivel_responsavel_pelo_cadastro_do_aluno]);
$responsavelUser = $consulta_comissao_nivel_responsavel->fetch(PDO::FETCH_ASSOC) ?: [];

$nivel_responsavel = (string) ($responsavelUser['nivel'] ?? '');
$id_pessoa_responsavel = (int) ($responsavelUser['id_pessoa'] ?? 0);
$wallet_id_nivel_responsavel_pelo_cadastro = trim((string) ($responsavelUser['wallet_id'] ?? ''));

$usuario_atendente_do_aluno = (int) ($usuario_atendente_do_aluno ?? 0);
if ($usuario_atendente_do_aluno <= 0) {
    $usuario_atendente_do_aluno = $nivel_responsavel_pelo_cadastro_do_aluno;
}

$consulta_usuario_atendente = $pdo->prepare("SELECT id, nivel, id_pessoa, wallet_id FROM usuarios WHERE id = :id LIMIT 1");
$consulta_usuario_atendente->execute([':id' => $usuario_atendente_do_aluno]);
$atendenteUser = $consulta_usuario_atendente->fetch(PDO::FETCH_ASSOC) ?: [];

$nivel_atendente = (string) ($atendenteUser['nivel'] ?? '');
$id_pessoa_atendente = (int) ($atendenteUser['id_pessoa'] ?? 0);
$wallet_id_atendente = trim((string) ($atendenteUser['wallet_id'] ?? ''));

$autoatendimentoVendedor = false;
if (
    featureAutoatendimentoVendedorAtiva()
    && $nivel_responsavel === 'Vendedor'
    && (int) $id_do_aluno > 0
    && (int) ($responsavelUser['id'] ?? 0) > 0
    && vendedorPodeLoginComoAluno($pdo, $id_pessoa_responsavel)
    && existeVinculoVendedorAluno($pdo, (int) $responsavelUser['id'], (int) $id_do_aluno)
) {
    $autoatendimentoVendedor = true;
    $wallet_id_atendente = $wallet_id_nivel_responsavel_pelo_cadastro;
    registrarAuditoriaAutoatendimento('efi/card_payment.php', (int) $id_do_aluno, (int) $responsavelUser['id'], (int) ($id_venda ?? 0));
}

$consulta_comissoes_que_recebem_fixo = $pdo->query("SELECT * FROM comissoes WHERE recebeSempre = 1");
$resposta_comissoes_que_recebem_fixo = $consulta_comissoes_que_recebem_fixo->fetchAll(PDO::FETCH_ASSOC);

$lista_cargos_recebem_fixo = [];
foreach ($resposta_comissoes_que_recebem_fixo as $registro) {
    $lista_cargos_recebem_fixo[] = $registro['nivel'];
}

$fixos_wallet_ids = [];
if (!empty($lista_cargos_recebem_fixo)) {
    $lista_cargos_recebem_fixo_str = "'" . implode("','", $lista_cargos_recebem_fixo) . "'";
    $consulta_usuarios_que_recebem_fixo = $pdo->query(
        "SELECT usuarios.wallet_id, comissoes.porcentagem
         FROM usuarios
         INNER JOIN comissoes ON comissoes.nivel = usuarios.nivel
         WHERE usuarios.nivel IN ($lista_cargos_recebem_fixo_str)
           AND usuarios.wallet_id IS NOT NULL"
    );
    $lista_de_usuarios_que_recebem_fixo = $consulta_usuarios_que_recebem_fixo->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lista_de_usuarios_que_recebem_fixo as $item) {
        if (!empty($item['wallet_id'])) {
            $fixos_wallet_ids[] = [
                'payee_code' => $item['wallet_id'],
                'percentage' => (float) $item['porcentagem'] * 100,
            ];
        }
    }
}

function addOrUpdatePayee(&$fixos_wallet_ids, $payee_code, $percentage)
{
    $payee_code = trim((string) $payee_code);
    $percentage = (float) $percentage;

    if ($payee_code === '' || $percentage <= 0) {
        return;
    }

    foreach ($fixos_wallet_ids as &$item) {
        if (($item['payee_code'] ?? '') === $payee_code) {
            $item['percentage'] = (float) ($item['percentage'] ?? 0) + $percentage;
            return;
        }
    }

    $fixos_wallet_ids[] = [
        'payee_code' => $payee_code,
        'percentage' => $percentage,
    ];
}

function normalizarRepasses(array $repasses): array
{
    $agrupado = [];

    foreach ($repasses as $item) {
        $payeeCode = trim((string) ($item['payee_code'] ?? ''));
        $percentage = isset($item['percentage']) && is_numeric($item['percentage']) ? (float) $item['percentage'] : 0.0;

        if ($payeeCode === '' || $percentage <= 0) {
            continue;
        }

        if (!isset($agrupado[$payeeCode])) {
            $agrupado[$payeeCode] = 0.0;
        }

        $agrupado[$payeeCode] += $percentage;
    }

    $normalizado = [];
    foreach ($agrupado as $payeeCode => $percentageTotal) {
        $normalizado[] = [
            'payee_code' => $payeeCode,
            'percentage' => (int) round($percentageTotal),
        ];
    }

    return $normalizado;
}

$comissao_dono = 0.0;
if ($nivel_responsavel === 'Vendedor' && $id_pessoa_responsavel > 0) {
    $stmtComissaoResp = $pdo->prepare("SELECT comissao FROM vendedores WHERE id = :id");
    $stmtComissaoResp->execute([':id' => $id_pessoa_responsavel]);
    $comissao_dono = (float) ($stmtComissaoResp->fetchColumn() ?: 0);
} elseif ($nivel_responsavel === 'Parceiro' && $id_pessoa_responsavel > 0) {
    $stmtComissaoResp = $pdo->prepare("SELECT comissao FROM parceiros WHERE id = :id");
    $stmtComissaoResp->execute([':id' => $id_pessoa_responsavel]);
    $comissao_dono = (float) ($stmtComissaoResp->fetchColumn() ?: 0);
} elseif ($nivel_responsavel === 'Secretario' && $id_pessoa_responsavel > 0) {
    $stmtSecMeus = $pdo->prepare("SELECT comissao_meus_alunos FROM secretarios WHERE id = :id");
    $stmtSecMeus->execute([':id' => $id_pessoa_responsavel]);
    $comissao_dono = (float) ($stmtSecMeus->fetchColumn() ?: 0);
} elseif ($nivel_responsavel === 'Tutor' && $id_pessoa_responsavel > 0) {
    $temMeusTutor = tabelaTemColunaLocal($pdo, 'tutores', 'comissao_meus_alunos');
    if ($temMeusTutor) {
        $stmtTutorMeus = $pdo->prepare("SELECT COALESCE(comissao_meus_alunos, comissao, 0) FROM tutores WHERE id = :id");
    } else {
        $stmtTutorMeus = $pdo->prepare("SELECT COALESCE(comissao, 0) FROM tutores WHERE id = :id");
    }
    $stmtTutorMeus->execute([':id' => $id_pessoa_responsavel]);
    $comissao_dono = (float) ($stmtTutorMeus->fetchColumn() ?: 0);
} elseif ($nivel_responsavel === 'Tesoureiro') {
    $comissao_dono = (float) ($resConfig[0]['comissao_tesoureiro'] ?? 0);
}

if (!empty($wallet_id_nivel_responsavel_pelo_cadastro) && $comissao_dono > 0) {
    addOrUpdatePayee($fixos_wallet_ids, $wallet_id_nivel_responsavel_pelo_cadastro, $comissao_dono * 100);
}

$vendedor_e_professor = 0;
if (($nivel_responsavel === 'Vendedor' || $nivel_responsavel === 'Parceiro') && $id_pessoa_responsavel > 0) {
    $tabelaResp = $nivel_responsavel === 'Vendedor' ? 'vendedores' : 'parceiros';
    $stmtResp = $pdo->prepare("SELECT professor FROM {$tabelaResp} WHERE id = :id");
    $stmtResp->execute([':id' => $id_pessoa_responsavel]);
    $vendedor_e_professor = (int) ($stmtResp->fetchColumn() ?: 0);
}

if ($vendedor_e_professor === 1 && !empty($wallet_id_atendente)) {
    if ($nivel_atendente === 'Secretario' && $id_pessoa_atendente > 0) {
        $stmtSecOutros = $pdo->prepare("SELECT comissao_outros_alunos FROM secretarios WHERE id = :id");
        $stmtSecOutros->execute([':id' => $id_pessoa_atendente]);
        $comissao_secretario_outros = (float) ($stmtSecOutros->fetchColumn() ?: 0);

        if ($comissao_secretario_outros > 0) {
            addOrUpdatePayee($fixos_wallet_ids, $wallet_id_atendente, $comissao_secretario_outros * 100);
        }
    } elseif ($nivel_atendente === 'Tutor' && $id_pessoa_atendente > 0) {
        $temOutrosTutor = tabelaTemColunaLocal($pdo, 'tutores', 'comissao_outros_alunos');
        if ($temOutrosTutor) {
            $stmtTutorOutros = $pdo->prepare("SELECT COALESCE(comissao_outros_alunos, 0) FROM tutores WHERE id = :id");
            $stmtTutorOutros->execute([':id' => $id_pessoa_atendente]);
            $comissao_tutor_outros = (float) ($stmtTutorOutros->fetchColumn() ?: 0);
        } else {
            $comissao_tutor_outros = (float) ($resConfig[0]['comissao_tutor'] ?? 0);
        }

        if ($comissao_tutor_outros > 0) {
            addOrUpdatePayee($fixos_wallet_ids, $wallet_id_atendente, $comissao_tutor_outros * 100);
        }
    }
}

$fixos_wallet_ids = normalizarRepasses($fixos_wallet_ids);

// Configurações da API da Ef? (antiga GerenciaNet)
$clientId = env('EFI_CARD_CLIENT_ID', $options['clientId'] ?? '');
$clientSecret = env('EFI_CARD_CLIENT_SECRET', $options['clientSecret'] ?? '');


$sandbox = filter_var(env('EFI_CARD_SANDBOX', !empty($options['sandbox']) ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
$baseUrl = $sandbox ? 'https://api-pix-h.gerencianet.com.br' : 'https://api-pix.gerencianet.com.br';
$baseUrlBoleto = $sandbox ? 'https://cobrancas-h.api.efipay.com.br' : 'https://cobrancas.api.efipay.com.br';
$certificadoPath = env('EFI_CARD_CERT_PATH', $options['certificate'] ?? '');



try {

  

    require_once 'card.php'; // arquivo que contém EFICreditCardPayment

    $cardPayment = new EFICreditCardPayment(
        $clientId,
        $clientSecret,
        $sandbox
    );

    // Preparar dados do pagamento com cartão
    $dadosCartao = [
        'valor' => floatval($valor_a_pagar ?? 0),
        'item_nome' => $nome_curso_titulo ?? 'Produto/Serviço',
        'quantidade' => 1,
        'nome' => $res2[0]['nome'] ?? '',
        'email' => $res2[0]['usuario'] ?? '',
        'cpf' => $res2[0]['cpf'] ?? '',
        'telefone' => $res2[0]['telefone'] ?? '',
        'credit_card_token' => $data['payment_token'], // token gerado pelo SDK JS da Gerencianet
        'installments' => $quantidadeParcelas,
        'street' => $data['street'] ?? null,
        'number' => $data['number'] ?? null,
        'neighborhood' => $data['neighborhood'] ?? null,
        'zipcode' => $data['zipcode'] ?? null,
        'city' => $data['city'] ?? null,
        'state' => $data['state'] ?? null,
        'notification_url' => 'https://www.sested-eja.com/efi_webhook_boleto.php',
        'repasses' => $fixos_wallet_ids
    ];

    // Validações básicas
    if (empty($dadosCartao['nome']) || empty($dadosCartao['email']) || empty($dadosCartao['cpf']) || empty($dadosCartao['credit_card_token'])) {
        throw new Exception('Dados do cartão incompletos.');
    }

    // Criar cobrança com cartão
    $resultado = $cardPayment->createCreditCardCharge($dadosCartao);

    
    // Formatar resposta
    $response = [
        'success' => true,
        'type' => 'CREDIT_CARD',
        'data' => [
            'charge_id' => $resultado['charge_id'],
            'status' => $resultado['status'],
            'total' => $resultado['total'],
            'payment_data' => $resultado['payment_data']
        ]
    ];

    echo json_encode($response);
    return;



    // // Armazenar informações do pagamento no banco de dados
    // $query = $pdo->prepare("INSERT INTO pagamentos_cartao (id_matricula, charge_id, valor, status) 
    //                         VALUES (?, ?, ?, ?)");
    // $query->execute([$id_venda, $resultado['charge_id'], $resultado['total'], $resultado['status']]);
    // $id_pagamento_cartao = $pdo->lastInsertId();

    // $update = $pdo->prepare("UPDATE matriculas SET forma_pgto = 'cartao_de_credito' WHERE id = ?");
    // $update->execute([$id_venda]);


} catch (Exception $e) {
    $response = [
        'success' => false,
        'type' => 'CREDIT_CARD',
        'error' => "Não foi possível processar o pagamento.",
        'data' => [
            'charge_id' => "CHARGE_ID",
            'status' => "STATUS",
            'total' => "TOTAL",
            'payment_data' => "PAYMENT_DATA"
        ]
    ];
    echo json_encode($response);
    return;
}

?>


