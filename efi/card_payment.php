<?php

require_once('../vendor/autoload.php');
require_once("../sistema/conexao.php");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

@session_start();

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

$is_pacote = $_GET['pacote'] ?? null;

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

if (@count($res2) > 0) {
    $id_pessoa = $res2[0]['id_pessoa'];
    $query3 = $pdo->prepare("SELECT * FROM alunos where id = :id");
    $query3->execute([':id' => $id_pessoa]);
    $res3 = $query3->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res3) > 0) {
        $nome_aluno = $res3[0]['nome'];
        $email_aluno = $res3[0]['email'];
        $cpf_aluno = str_replace('-', '', str_replace('.', '', $res3[0]['cpf']));
        $nivel_responsavel_pelo_cadastro_do_aluno = $res3[0]['usuario'];
    }
}



//BUSCA DADOS DA MATRICULA
$query = $pdo->prepare("SELECT * FROM matriculas where id_curso = :id_curso and aluno = :aluno");
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



//encontra o usuario comum do professor
$consulta_usuario = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$consulta_usuario->execute([':id' => $id_usuario_professor]);
$reposta_cosulta_usuario = $consulta_usuario->fetchAll(PDO::FETCH_ASSOC);

$nivel_do_vendedor_do_curso = isset($reposta_cosulta_usuario[0]['nivel']) ? $reposta_cosulta_usuario[0]['nivel'] : 'sem nível';
$wallet_id_do_vendedor_do_curso = isset($reposta_cosulta_usuario[0]['wallet_id']) ? $reposta_cosulta_usuario[0]['wallet_id'] : '0';

//encontra porcentagem para split para o vendedor
$consulta_comissoes = $pdo->prepare("SELECT * from comissoes where nivel = :nivel");
$consulta_comissoes->execute([':nivel' => $nivel_do_vendedor_do_curso]);
$resposta_comissoes = $consulta_comissoes->fetchAll(PDO::FETCH_ASSOC);
$porcentagem_vendedor = isset($resposta_comissoes[0]['porcentagem']) ? $resposta_comissoes[0]['porcentagem'] : 0;

//econtra os niveis (perfis) que recebem comissoes fixas por TODAS as vendas
$consulta_comissoes_que_recebem_fixo = $pdo->query("SELECT * from comissoes where recebeSempre = 1 ");
$resposta_comissoes_que_recebem_fixo = $consulta_comissoes_que_recebem_fixo->fetchAll(PDO::FETCH_ASSOC);

$lista_cargos_recebem_fixo = [];
foreach ($resposta_comissoes_que_recebem_fixo as $registro) {
    array_push($lista_cargos_recebem_fixo, $registro['nivel']);
}

$lista_cargos_recebem_fixo_str = implode("','", $lista_cargos_recebem_fixo);
$lista_cargos_recebem_fixo_str = "'" . $lista_cargos_recebem_fixo_str . "'";

$consulta_usuarios_que_recebem_fixo = $pdo->query(
    "SELECT usuarios.wallet_id, comissoes.porcentagem 
    FROM usuarios 
    INNER JOIN comissoes ON comissoes.nivel = usuarios.nivel 
    WHERE usuarios.nivel IN ($lista_cargos_recebem_fixo_str) 
    AND usuarios.wallet_id IS NOT NULL"
);
$lista_de_usuarios_que_recebem_fixo = $consulta_usuarios_que_recebem_fixo->fetchAll(PDO::FETCH_ASSOC);

//Wallet ids e respectivas porcentagens para repasses
$repasses = array();
$fixos_wallet_ids = array_filter(array_map(function ($item) {
    if (!empty($item['wallet_id'])) {
        return [
            'payee_code' => $item['wallet_id'],
            'percentage' => $item['porcentagem'] * 100
        ];
    }
    return null;
}, $lista_de_usuarios_que_recebem_fixo));

$fixos_wallet_ids = array_values($fixos_wallet_ids);
if ($wallet_id_do_vendedor_do_curso)
    array_push($fixos_wallet_ids, [
        'payee_code' => $wallet_id_do_vendedor_do_curso,
        'percentage' => $porcentagem_vendedor * 100
    ]);

//VERIFICA SE O NIVEL RESPONSAVEL PELO CADASTRO DO ALUNO POSSUI OS DADOS DE COMISSÃO REGISTRADO
$consulta_comissao_nivel_responsavel = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$consulta_comissao_nivel_responsavel->execute([':id' => $nivel_responsavel_pelo_cadastro_do_aluno]);
$resposta_comissao_nivel_responsavel = $consulta_comissao_nivel_responsavel->fetchAll(PDO::FETCH_ASSOC);
$wallet_id_nivel_responsavel_pelo_cadastro = isset($resposta_comissao_nivel_responsavel[0]['wallet_id']) ? $resposta_comissao_nivel_responsavel[0]['wallet_id'] : 0;

$vendedor_id = $resposta_comissao_nivel_responsavel[0]['id_pessoa'];

// Verifica o nível do responsável pelo cadastro
$nivel_responsavel = $resposta_comissao_nivel_responsavel[0]['nivel'];

// Define a tabela a ser consultada com base no nível
if ($nivel_responsavel == 'Vendedor') {
    $tabela_comissao = 'vendedores';
} elseif ($nivel_responsavel == 'Tutor') {
    $tabela_comissao = 'tutores';
} else {
    // Defina um comportamento padrão caso o nível não seja nem Vendedor nem Tutor
    $tabela_comissao = null;
}

// Se a tabela foi definida, faz a consulta
if ($tabela_comissao) {
    $consulta_comissao_nivel_responsavel = $pdo->prepare("SELECT comissao FROM {$tabela_comissao} WHERE id = :id");
    $consulta_comissao_nivel_responsavel->execute([':id' => $vendedor_id]);
    $resposta_comissao_nivel_responsavel = $consulta_comissao_nivel_responsavel->fetchAll(PDO::FETCH_ASSOC);
    // Agora você pode acessar os dados de comissão
    $comissao_vendedor = $resposta_comissao_nivel_responsavel[0]['comissao'];
} else {
    // Caso o nível não seja válido, trate o erro ou defina um valor padrão
    $comissao_vendedor = 0;
}

//OBTER COMISSAO DO TUTOR
$consulta_comissao_tutor = $pdo->query("SELECT * FROM tutores");
$resposta_consulta_comissao_tutor = $consulta_comissao_tutor->fetchAll(PDO::FETCH_ASSOC);

$consulta_vendedor_e_professor = $pdo->prepare("SELECT professor FROM vendedores where id = :id");
$consulta_vendedor_e_professor->execute([':id' => $vendedor_id]);
$resposta_consulta_vendedor_e_professor = $consulta_vendedor_e_professor->fetchAll(PDO::FETCH_ASSOC);

$valor_comissao_vendedor = $resposta_comissao_nivel_vendedor[0]['comissao'] ?? 0;
$valor_comissao_vendedor = (int) ($resposta_comissao_nivel_vendedor[0]['comissao'] ?? 0);

$consulta_vendedor_professor = $pdo->query("SELECT comissao_tutor FROM config");
$resposta_consulta_vendedor_professor = $consulta_vendedor_professor->fetch(PDO::FETCH_ASSOC)['comissao_tutor'];
$vendedor_e_professor = $resposta_consulta_vendedor_e_professor[0]['professor'];

$tutor_usuario = $resposta_consulta_comissao_tutor[0]['email'];
$comissao_tutor = $resposta_consulta_comissao_tutor[0]['comissao'];

$consulta_tutor = $pdo->prepare("SELECT * FROM usuarios where usuario = :usuario");
$consulta_tutor->execute([':usuario' => $tutor_usuario]);
$resposta_consulta_tutor = $consulta_tutor->fetchAll(PDO::FETCH_ASSOC);
$wallet_id_tutor = $resposta_consulta_tutor[0]['wallet_id'];

if ($nivel_responsavel == 'Tutor') {
    array_push($fixos_wallet_ids, [
        'payee_code' => $wallet_id_tutor,
        'percentage' => $comissao_vendedor * 100
    ]);
}

if ($vendedor_e_professor) {
    array_push($fixos_wallet_ids, [
        'payee_code' => $wallet_id_tutor,
        'percentage' => $resposta_consulta_vendedor_professor * 100
    ]);
}

//VERIFICA A PORCENTAGEM DE COMISSAO DO NIVEL RESPONSAVEL PELO CADASTRO DO ALUNO
$nivel_do_responsavel_pelo_cadastro_do_aluno = isset($resposta_comissao_nivel_responsavel[0]['nivel']) ? $resposta_comissao_nivel_responsavel[0]['nivel'] : 'sem nível';
$consulta_valor_da_comissao_do_responsavel = $pdo->prepare("SELECT * FROM comissoes where nivel = :nivel");
$consulta_valor_da_comissao_do_responsavel->execute([':nivel' => $nivel_do_responsavel_pelo_cadastro_do_aluno]);
$resposta_valor_da_comissao_do_responsavel = $consulta_valor_da_comissao_do_responsavel->fetchAll(PDO::FETCH_ASSOC);

$porcentagem_de_pagamento_para_responsavel = isset($resposta_valor_da_comissao_do_responsavel[0]['porcentagem']) ? $resposta_valor_da_comissao_do_responsavel[0]['porcentagem'] : 0;

if ($wallet_id_nivel_responsavel_pelo_cadastro && $nivel_responsavel == 'Vendedor')
    array_push($fixos_wallet_ids, [
        'payee_code' => $wallet_id_nivel_responsavel_pelo_cadastro,
        'percentage' => intval($comissao_vendedor) * 100
    ]);






// Configurações da API da Efí (antiga GerenciaNet)
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
        'notification_url' => 'https://sestedcursos.com/efi_webhook_boleto.php'
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
