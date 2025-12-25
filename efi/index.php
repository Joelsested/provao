<?php

require_once('../vendor/autoload.php');
require_once("../sistema/conexao.php");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

@session_start();

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

// Parâmetros recebidos via GET
$forma_de_pagamento = $_GET['formaDePagamento'] ?? '';
$billingType = strtoupper((string) $forma_de_pagamento);
$quantidadeParcelas = isset($_GET['quantidadeParcelas']) ? (int) $_GET['quantidadeParcelas'] : 1;
if ($quantidadeParcelas < 1) {
    $quantidadeParcelas = 1;
}
if ($billingType === 'BOLETO_PARCELADO') {
    if ($quantidadeParcelas > 6) {
        $quantidadeParcelas = 6;
    }
} else {
    $quantidadeParcelas = 1;
}

//Busca dados para atualização da situação da matricula
$id_do_aluno = @$_SESSION['id'];
$id_do_curso_pag = $_GET['id_do_curso'];
$nome_curso_titulo = $_GET['nome_do_curso'];

$is_pacote = $_GET['pacote'] ?? null;

if ($is_pacote == 'Sim') {
    $curso_pacote = "Sim";
} else {
    $curso_pacote = "Não";
}

$form_post = [
    'forma_de_pagamento' => $billingType,
    'quantidadeParcelas' => $quantidadeParcelas,
    'id_do_aluno' => $id_do_aluno,
    'id_do_curso_pag' => $id_do_curso_pag,
    'nome_curso_titulo' => $nome_curso_titulo,
    'pacote' => $curso_pacote
];

// echo '<pre>';
// echo json_encode($_GET, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;

$options = require_once 'options.php';


// Configurações da EFI
$config = [
    'client_id' => $options['clientId'],
    'client_secret' => $options['clientSecret'],
    'certificate_path' => $options['certificate'], // Apenas para PIX
    'chave_pix' => $options['pixKey'] ?? '', // Sua chave PIX
    'sandbox' => $options['sandbox'] // true para teste, false para produção
];

$queryConfig = $pdo->query("SELECT * FROM config");
$resConfig = $queryConfig->fetchAll(PDO::FETCH_ASSOC);

$comissao_tesoureiro = $resConfig[0]['comissao_tesoureiro'];
$comissao_secretario = $resConfig[0]['comissao_secretario'];


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
        $nome_aluno = normalizarUnicode($res3[0]['nome'] ?? '');
        $email_aluno = $res3[0]['email'] ?? '';
        $cpf_aluno = preg_replace('/\\D/', '', $res3[0]['cpf'] ?? '');
        $telefone_aluno = preg_replace('/\\D/', '', $res3[0]['telefone'] ?? '');
        $nivel_responsavel_pelo_cadastro_do_aluno = $res3[0]['usuario'] ?? '';
    }
    if (empty($nome_aluno)) {
        $nome_aluno = normalizarUnicode($res2[0]['nome'] ?? '');
    }
    if (empty($email_aluno)) {
        $email_aluno = $res2[0]['usuario'] ?? '';
    }
    if (empty($cpf_aluno)) {
        $cpf_aluno = preg_replace('/\\D/', '', $res2[0]['cpf'] ?? '');
    }
    if (empty($telefone_aluno)) {
        $telefone_aluno = preg_replace('/\\D/', '', $res2[0]['telefone'] ?? '');
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
        // $valor_a_pagar = $valor_curso;
    } elseif ($billingType == "PIX") {
        $valor_a_pagar = $valor_curso - ($valor_curso * ($descontoPix / 100));
    }
}
// echo 'ValorF FLoatVal - ';
// echo floatval($valorF ?? 0);

if ($is_pacote == 'Sim') {
    
    
    $valorP = str_replace(['.', ','], ['', '.'], $valorF);
    $valorEmCentavos = (int) ($valorP * 100);
//   $valorF = $valorF = number_format($valor_curso, 2, '.', '');
//   $valorEmCentavos = intval(round($valorF * 100));
//  $valorA = str_replace(['.', ','], ['', '.'], $valorF);
//   $valorF = $valorA;
} 
else {
    //  $valorEmCentavos = intval(round($valorF * 100));
     $valorP = str_replace(['.', ','], ['', '.'], $valorF);
    $valorEmCentavos = (int) ($valorP * 100);
}




// OLD VENDEDOR COMISSAO
// //encontra o usuario comum do professor
// $consulta_usuario = $pdo->query("SELECT * FROM usuarios where id = '$id_usuario_professor' ");
// $reposta_cosulta_usuario = $consulta_usuario->fetchAll(PDO::FETCH_ASSOC);

// $nivel_do_vendedor_do_curso = isset($reposta_cosulta_usuario[0]['nivel']) ? $reposta_cosulta_usuario[0]['nivel'] : 'sem nível';
// $wallet_id_do_vendedor_do_curso = isset($reposta_cosulta_usuario[0]['wallet_id']) ? $reposta_cosulta_usuario[0]['wallet_id'] : '0';

// //encontra porcentagem para split para o vendedor
// $consulta_comissoes = $pdo->query("SELECT * from comissoes where nivel = '$nivel_do_vendedor_do_curso' ");
// $resposta_comissoes = $consulta_comissoes->fetchAll(PDO::FETCH_ASSOC);
// $porcentagem_vendedor = isset($resposta_comissoes[0]['porcentagem']) ? $resposta_comissoes[0]['porcentagem'] : 0;


//encontra o usuario comum do professor
$consulta_usuario = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$consulta_usuario->execute([':id' => $id_aluno]);
$reposta_cosulta_usuario = $consulta_usuario->fetchAll(PDO::FETCH_ASSOC);


// echo '<pre>';
// echo json_encode($reposta_cosulta_usuario, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;

$id_pessoa = $reposta_cosulta_usuario[0]['id_pessoa'] ?? null;


if ($id_pessoa) {
    $consulta_usuario_aluno = $pdo->prepare("SELECT usuario FROM alunos where id = :id");
    $consulta_usuario_aluno->execute([':id' => $id_pessoa]);
    $reposta_cosulta_usuario_aluno = $consulta_usuario_aluno->fetchColumn();

    $consulta_nivel_responsavel = $pdo->prepare("SELECT * FROM usuarios where id = :id");
    $consulta_nivel_responsavel->execute([':id' => $reposta_cosulta_usuario_aluno]);
    $reposta_cosulta_nivel_responsavel = $consulta_nivel_responsavel->fetchAll(PDO::FETCH_ASSOC);
}



$nivel_do_vendedor_do_curso = isset($reposta_cosulta_nivel_responsavel[0]['nivel']) ? $reposta_cosulta_nivel_responsavel[0]['nivel'] : 'sem nível';
$wallet_id_do_vendedor_do_curso = isset($reposta_cosulta_nivel_responsavel[0]['wallet_id']) ? $reposta_cosulta_nivel_responsavel[0]['wallet_id'] : '0';

$comissao_vendedor_config = 0;

switch ($nivel_do_vendedor_do_curso) {
    case 'Tesoureiro':
        $comissao_vendedor_config = $resConfig[0]['comissao_tesoureiro'];
        break;
    case 'Secretario':
        $comissao_vendedor_config = $resConfig[0]['comissao_secretario'];
        break;
    case 'Tutor':
        $comissao_vendedor_config = $resConfig[0]['comissao_tutor'];
        break;
    default:
        $comissao_vendedor_config = 0;
        break;
}



//encontra porcentagem para split para o vendedor
$consulta_comissoes = $pdo->prepare("SELECT * from comissoes where nivel = :nivel");
$consulta_comissoes->execute([':nivel' => $nivel_do_vendedor_do_curso]);
$resposta_comissoes = $consulta_comissoes->fetchAll(PDO::FETCH_ASSOC);
$porcentagem_vendedor = isset($wallet_id_do_vendedor_do_curso) ? $comissao_vendedor_config : 0;







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
} 
// elseif ($nivel_responsavel == 'Tesoureiro') {
//     $tabela_comissao = 'tesoureiros';
// } 
// elseif ($nivel_responsavel == 'Secretario') {
//     $tabela_comissao = 'secretarios';
// } 
else {
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

function addOrUpdatePayee(&$fixos_wallet_ids, $payee_code, $percentage) {
    foreach ($fixos_wallet_ids as &$item) {
        if ($item['payee_code'] === $payee_code) {
            $item['percentage'] += $percentage; // soma a porcentagem
            return; // já encontrou, não precisa adicionar
        }
    }
    // se não encontrou, adiciona normalmente
    $fixos_wallet_ids[] = [
        'payee_code' => $payee_code,
        'percentage' => $percentage
    ];
}


if ($nivel_responsavel == 'Tutor') {
    array_push($fixos_wallet_ids, [
        'payee_code' => $wallet_id_tutor,
        'percentage' => $comissao_vendedor * 100
    ]);
}

if ($nivel_responsavel == 'Tesoureiro') {
    addOrUpdatePayee($fixos_wallet_ids, $wallet_id_nivel_responsavel_pelo_cadastro, $comissao_tesoureiro * 100);
}
if ($nivel_responsavel == 'Secretario') {
    addOrUpdatePayee($fixos_wallet_ids, $wallet_id_nivel_responsavel_pelo_cadastro, $comissao_secretario * 100);
}


// if ($nivel_responsavel == 'Tesoureiro') {
//     array_push($fixos_wallet_ids, [
//         'payee_code' => $wallet_id_nivel_responsavel_pelo_cadastro,
//         'percentage' => $comissao_tesoureiro
//     ]);
// }
// if ($nivel_responsavel == 'Secretario') {
//     array_push($fixos_wallet_ids, [
//         'payee_code' => $wallet_id_nivel_responsavel_pelo_cadastro,
//         'percentage' => $comissao_secretario
//     ]);
// }

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
$clientId = $options['clientId'] ?? '';
$clientSecret = $options['clientSecret'] ?? '';
$sandbox = !empty($options['sandbox']);
$baseUrl = $sandbox ? 'https://api-pix-h.gerencianet.com.br' : 'https://api-pix.gerencianet.com.br';
$baseUrlBoleto = $sandbox ? 'https://cobrancas-h.api.efipay.com.br' : 'https://cobrancas.api.efipay.com.br';
$certificadoPath = $options['certificate'] ?? '';


// Autenticação - obtenção do token de acesso
function obterTokenEfi($clientId, $clientSecret, $baseUrl, $certificadoPath)
{
    $url = $baseUrl . '/oauth/token';

    $headers = [
        'Content-Type: application/json'
    ];

    $data = [
        'grant_type' => 'client_credentials'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSLCERT => $certificadoPath,
        CURLOPT_SSLCERTPASSWD => '',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERPWD => $clientId . ':' . $clientSecret
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new Exception("Erro na autenticação com a Efí: " . $err);
    }

    $respostaDecodificada = json_decode($response, true);
    if (!isset($respostaDecodificada['access_token'])) {
        throw new Exception("Erro ao obter token da Efí: " . json_encode($respostaDecodificada));
    }

    return $respostaDecodificada['access_token'];
}


// Função para registrar cliente na Efí
function registrarClienteEfi($token, $baseUrl, $certificadoPath, $nome, $cpf, $email)
{
    $url = $baseUrl . '/v2/customers';

    $dados = [
        'nome' => $nome,
        'cpf' => $cpf,
        'email' => $email
    ];

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSLCERT => $certificadoPath,
        CURLOPT_SSLCERTPASSWD => '',
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new Exception("Erro ao registrar cliente na Efí: " . $err);
    }

    return json_decode($response, true);
}

// Função para criar cobrança PIX
function criarCobrancaPix($token, $baseUrl, $certificadoPath, $cliente_id, $valor, $descricao, $id_curso, $repasses = [])
{
    // Valor em centavos
    $valorCentavos = intval($valor * 100);

    $url = $baseUrl . '/v2/cob';

    $dados = [
        'calendario' => [
            'expiracao' => 3600 // Expiração em segundos (1 hora)
        ],
        'devedor' => [
            'cpf' => $cliente_id,
            'nome' => 'Gabriel Ramos Luciano da Silva'
        ],
        'valor' => [
            'original' => number_format($valor, 2, '.', '')
        ],
        'chave' => 'bda40203-4fc1-43b1-b058-b783d6921a37', // Sua chave PIX registrada na Efí
        'solicitacaoPagador' => $descricao
    ];

    //     Adiciona splits se existirem
    if (!empty($repasses)) {
        $dados['repasses'] = $repasses;
    }

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSLCERT => $certificadoPath,
        CURLOPT_SSLCERTPASSWD => '',
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new Exception("Erro ao criar cobrança PIX: " . $err);
    }

    return json_decode($response, true);
}

// Função para criar boleto
function criarBoleto($token, $baseUrl, $certificadoPath, $cliente_id, $valor, $descricao, $id_curso, $repasses = [])
{

    $data = [
        'token' => $token,
        'baseUrl' => $baseUrl,
        'cert' => $certificadoPath,
        'client_id' => $cliente_id,
        'valor' => $valor,
        'description' => $descricao,
        'id_curso' => $id_curso,
        'repasses' => $repasses = []
    ];

    //    return $data;
    // Valor em centavos
    $valorCentavos = intval($valor * 100);
    $dataVencimento = date('Y-m-d', strtotime('+7 days'));

    $url = $baseUrl . '/v1/charge/one-step';


    $dados = [
        'items' => [
            [
                'name' => $descricao,
                'value' => $valorCentavos,
                'amount' => 1
            ]
        ],
        'payment' => [
            'banking_billet' => [
                'customer' => [
                    'name' => 'Gabriel Ralusi',
                    'cpf' => '13294939663',
                    'email' => 'gabrielralusi@gmail.com'
                ],
                'expire_at' => $dataVencimento,
                'message' => $descricao,
                'custom_id' => $id_curso
            ]
        ]
    ];


    // Adiciona splits se existirem
//    if (!empty($repasses)) {
//        $dados['repasses'] = $repasses;
//    }

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSLCERT => $certificadoPath,
        CURLOPT_SSLCERTPASSWD => '',
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    // echo '<pre>';
    // echo 'RESPOSTA - ';
    // echo json_encode($response, JSON_PRETTY_PRINT);
    // echo '</pre>';

    // echo '<pre>';
    // echo 'ERROS - ';
    // echo json_encode($err, JSON_PRETTY_PRINT);
    // echo '</pre>';

    // return;

    if ($err) {
        throw new Exception("Erro ao criar boleto: " . $err);
    }

    return json_decode($response, true);
}

// Função para criar cobrança de cartão de crédito com parcelamento
function criarCobrancaCartao($token, $baseUrl, $certificadoPath, $cliente_id, $valor, $descricao, $id_curso, $parcelas, $repasses = [])
{
    // Valor em centavos
    $valorCentavos = intval($valor * 100);

    $url = $baseUrl . '/v2/charge';

    $dados = [
        'items' => [
            [
                'name' => 'Pagamento do curso: ' . $nome_curso_titulo,
                'value' => $valorCentavos,
                'amount' => 1
            ]
        ],
        'payment' => [
            'credit_card' => [
                'installments' => $parcelas,
                'payment_token' => '{{payment_token}}', // Será preenchido pelo checkout da Efí
                'billing_address' => [] // Será preenchido pelo checkout da Efí
            ]
        ],
        'customer' => [
            'name' => $nome_aluno,
            'cpf' => $cpf_aluno,
            'email' => $email_aluno
        ],
        'message' => $descricao,
        'custom_id' => $id_curso
    ];

    // Adiciona splits se existirem
    if (!empty($repasses)) {
        $dados['repasses'] = $repasses;
    }

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSLCERT => $certificadoPath,
        CURLOPT_SSLCERTPASSWD => '',
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new Exception("Erro ao criar cobrança de cartão: " . $err);
    }

    return json_decode($response, true);
}



// Tratamento para boletos parcelados
if ($forma_de_pagamento == 'BOLETO_PARCELADO' and $quantidadeParcelas > 1) {





    if (@count($res) > 0) {
        $id_matricula = $res[0]['id'];

        $consulta_dados_da_parcela = $pdo->prepare("SELECT * FROM boletos_parcelados WHERE id_matricula = :id_matricula");
        $consulta_dados_da_parcela->execute([':id_matricula' => $id_matricula]);
        $resposta_consulta_dados_da_parcela = $consulta_dados_da_parcela->fetchAll(PDO::FETCH_ASSOC);

        if (@count($resposta_consulta_dados_da_parcela) > 0) {
            header("Location: ../sistema/painel-aluno/index.php?pagina=parcelas");
            exit();
        }

        $registro_dados_da_parcela = $pdo->prepare("INSERT INTO boletos_parcelados (qtd_parcelas, id_matricula) VALUES (:qtd_parcelas, :id_matricula)");
        $registro_dados_da_parcela->execute([
            ':qtd_parcelas' => $quantidadeParcelas,
            ':id_matricula' => $id_matricula,
        ]);
        $id_registro_dados_da_parcela = $pdo->lastInsertId();


        $valor_unitario_parcelas = round($valor_curso / $quantidadeParcelas, 2);

        $dadosBoleto = [
            'valor' => floatval($valor_unitario_parcelas ?? 0),
            'item_nome' => $nome_curso_titulo ?? 'Produto/Serviço',
            'quantidade' => 1,
            'nome' => $nome_aluno ?? '',
            'email' => $email_aluno ?? '',
            'cpf' => $cpf_aluno ?? '',
            'telefone' => ($telefone_aluno ?? '') ?: '69999694538',
            // 'nascimento' => $res2[0]['nascimento'] ?? '27/10/1995',
            'vencimento' => $res2[0]['vencimento'] ?? '+7 days',
            'repasses' => $fixos_wallet_ids,
            'notification_url' => 'https://sested-eja.com/efi_webhook_boleto_parcelado.php'
        ];

        $payload = json_encode($dadosBoleto, JSON_UNESCAPED_UNICODE);

        for ($i = 0; $i < $quantidadeParcelas; $i++) {
            $ordemParcela = $i + 1;
            $stmtParcela = $pdo->prepare("INSERT INTO parcelas_geradas_por_boleto (ordem_parcela, id_boleto_parcelado, valor_parcela, situacao, payload, id_matricula) VALUES (:ordem_parcela, :id_boleto_parcelado, :valor_parcela, '0', :payload, :id_matricula)");
            $stmtParcela->execute([
                ':ordem_parcela' => $ordemParcela,
                ':id_boleto_parcelado' => $id_registro_dados_da_parcela,
                ':valor_parcela' => $valor_unitario_parcelas,
                ':payload' => $payload,
                ':id_matricula' => $id_matricula,
            ]);
        }

        header("Location: ../sistema/painel-aluno/index.php?pagina=parcelas");
        exit();
    }






    // echo '<pre>';
    // echo json_encode($dadosBoleto, JSON_PRETTY_PRINT);
    // return;
}

try {


    if ($billingType == 'PIX') {
        require_once 'pix.php';

        $pixPayment = new EFIPixPayment(
            $config['client_id'],
            $config['client_secret'],
            $config['certificate_path'],
            $config['sandbox']
        );

        // Preparar dados para PIX
        $dadosPix = [
            'cpf' => $data['cpf'] ?? '13294939663',
            'nome' => $data['nome'] ?? 'Gabriel Ramos',
            'valor' => floatval($valor_a_pagar ?? 0),
            'chave_pix' => $config['chave_pix'],
            'descricao' => $data['descricao'] ?? 'Pagamento PIX',
            'expiracao' => $data['expiracao'] ?? 3600
        ];



        // Validações específicas do PIX
        if (empty($dadosPix['cpf'])) {
            throw new Exception('CPF é obrigatório para PIX');
        }
        if (empty($dadosPix['nome'])) {
            throw new Exception('Nome é obrigatório para PIX');
        }
        if ($dadosPix['valor'] <= 0) {
            throw new Exception('Valor deve ser maior que zero');
        }

        // Adicionar informações adicionais se fornecidas
        if (isset($data['infoAdicionais'])) {
            $dadosPix['infoAdicionais'] = $data['infoAdicionais'];
        }

        // Criar cobrança PIX
        $resultado = $pixPayment->createPixCharge($dadosPix);

        // Formatar resposta PIX
        $response = [
            'success' => true,
            'type' => 'PIX',
            'data' => [
                'txid' => $resultado['txid'],
                'status' => $resultado['status'],
                'valor' => $resultado['valor'],
                'qr_code' => $resultado['qr_code'],
                'qr_code_image' => $resultado['qr_code_image'],
                'link_pagamento' => $resultado['link_pagamento'],
                'vencimento' => $resultado['vencimento']
            ]
        ];

        $qrCodeData = json_decode($qrResponse, true);

        $urlPagamento = $resultado['link_pagamento']; // Aqui continua o código
        $imagemQrCode = $resultado['qr_code_image'];
        $textoCopiaCola = $resultado['qr_code'];
        $txid = $resultado['txid'];

        // Armazenar informações do pagamento PIX no banco de dados
        $query = $pdo->prepare("INSERT INTO pagamentos_pix (id_matricula, txid, qrcode_url, texto_copia_cola, valor, status) 
                               VALUES (?, ?, ?, ?, ?, 'pendente')");
        $query->execute([$id_venda, $txid, $imagemQrCode, $textoCopiaCola, $valor_a_pagar]);
        $id_pagamento_pix = $pdo->lastInsertId();

        // Atualizar forma_pgto na tabela matricula
        $update = $pdo->prepare("UPDATE matriculas SET forma_pgto = 'PIX' WHERE id = ?");
        $update->execute([$id_venda]);


        echo '
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pagamento PIX</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        </head>
        <body class="bg-gray-100 font-sans">
            <div class="container mx-auto px-4 py-10 max-w-3xl">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h1 class="text-2xl font-bold text-center mb-4 text-blue-600">Pagamento via PIX</h1>
                    <div class="border-t border-b border-gray-200 py-4 mb-4">
                        <p class="text-center text-lg mb-2">Valor com desconto: <span class="font-bold text-green-600">R$ ' . number_format($resultado['valor'], 2, ',', '.') . '</span></p>
                        <p class="text-center text-sm text-gray-600">Escaneie o QR Code abaixo ou copie o código PIX</p>
                    </div>
                    <div class="flex flex-col items-center justify-center mb-6">
                        <img src="' . $resultado['qr_code_image'] . '" alt="QR Code PIX" class="w-64 h-64 mb-4">
                        <div class="w-full">
                            <div class="relative">
                                <input type="text" id="pix-code" value="' . $resultado['link_pagamento'] . '" readonly class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 text-sm" />
                                <button onclick="copiarCodigo()" class="absolute inset-y-0 right-0 px-4 bg-blue-500 text-white rounded-r-lg hover:bg-blue-600">
                                    Copiar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    O pagamento é processado automaticamente. Após o pagamento, aguarde alguns instantes para a matrícula ser liberada.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <a href="../sistema/painel-aluno/index.php" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                            Voltar ao Painel
                        </a>
                        <button id="verificar-pagamento" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded ml-2">
                            Verificar Pagamento
                        </button>
                    </div>
                </div>
            </div>
            <script>
                function copiarCodigo() {
                    var codigoInput = document.getElementById("pix-code");
                    codigoInput.select();
                    codigoInput.setSelectionRange(0, 99999);
                    document.execCommand("copy");
                    alert("Código PIX copiado para a área de transferência!");
                }
                
                document.getElementById("verificar-pagamento").addEventListener("click", function() {
                    // Fazer uma requisição AJAX para verificar o status do pagamento
                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "verificar_pagamento_pix.php?id_pagamento=' . $resultado['txid'] . '", true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            var response = JSON.parse(xhr.responseText);
                            if (response.status === "aprovado") {
                                alert("Pagamento confirmado! Redirecionando para o painel...");
                                window.location.href = "../sistema/painel-aluno/index.php";
                            } else {
                                alert("Pagamento ainda não foi confirmado. Por favor, tente novamente em alguns instantes.");
                            }
                        }
                    };
                    xhr.send();
                });
            </script>
        </body>
        </html>';


        // return $response;

    } elseif ($billingType == 'BOLETO') {

        require_once 'boleto.php';

        $boletoPayment = new EFIBoletoPayment(
            $config['client_id'],
            $config['client_secret'],
            $config['sandbox']
        );

        // Preparar dados para Boleto
        $dadosBoleto = [
            'valor1' => $valorF,
            'valor' => $valorEmCentavos,
            'item_nome' => $nome_curso_titulo ?? 'Produto/Serviço',
            'quantidade' => 1,
            'nome' => $nome_aluno ?? '',
            'email' => $email_aluno ?? '',
            'cpf' => $cpf_aluno ?? '',
            'telefone' => ($telefone_aluno ?? '') ?: '69999694538',
            // 'nascimento' => $res2[0]['nascimento'] ?? '27/10/1995',
            'vencimento' => $res2[0]['vencimento'] ?? '+7 days',
            'repasses' => $fixos_wallet_ids,
            'notification_url' => 'https://sested-eja.com/efi_webhook_boleto.php'
        ];


        //   echo '<pre>';
        // echo json_encode($dadosBoleto, JSON_PRETTY_PRINT);
        // echo '</pre>';
        // return;


       

        // Validações específicas do Boleto
        if (empty($dadosBoleto['nome'])) {
            throw new Exception('Nome é obrigatório para Boleto');
        }
        if (empty($dadosBoleto['email'])) {
            throw new Exception('Email é obrigatório para Boleto');
        }
        if (empty($dadosBoleto['cpf'])) {
            throw new Exception('CPF é obrigatório para Boleto');
        }
        if ($dadosBoleto['valor'] <= 0) {
            throw new Exception('Valor deve ser maior que zero');
        }

        // Adicionar endereço se fornecido
        if (isset($data['endereco'])) {
            $dadosBoleto['endereco'] = $data['endereco'];
        }

        // Configurações opcionais do boleto
        if (isset($data['instrucoes'])) {
            $dadosBoleto['instrucoes'] = $data['instrucoes'];
        }
        if (isset($data['multa'])) {
            $dadosBoleto['multa'] = floatval($data['multa']);
        }
        if (isset($data['juros'])) {
            $dadosBoleto['juros'] = floatval($data['juros']);
        }
        if (isset($data['desconto'])) {
            $dadosBoleto['desconto'] = $data['desconto'];
        }
        if (isset($data['metadata'])) {
            $dadosBoleto['metadata'] = $data['metadata'];
        }



        // Criar cobrança Boleto
        $resultado = $boletoPayment->createBoletoCharge($dadosBoleto);


        // Formatar resposta Boleto
        $response = [
            'success' => true,
            'type' => 'BOLETO',
            'data' => [
                'charge_id' => $resultado['charge_id'],
                'status' => $resultado['status'],
                'total' => $resultado['total'],
                'vencimento' => $resultado['vencimento'],
                'linha_digitavel' => $resultado['linha_digitavel'],
                'codigo_barras' => $resultado['codigo_barras'],
                'link_boleto' => $resultado['link_boleto'],
                'pdf_boleto' => $resultado['pdf_boleto']
            ],
            'payment_data' => $resultado['payment_data']
        ];

        $payment_data = $resultado['payment_data']['data']['payment']['banking_billet'];

        // Recuperar URL do boleto e linha digitável
        $urlPagamento = $payment_data['billet_link'];
        $urlBoleto = $payment_data['pdf']['charge'];
        // $linhaDigitavel = $payment_data['barcode'];
        $linhaDigitavel = $payment_data['pix']['qrcode'];
        // $nossoNumero = '11961722303';
        $nossoNumero = $resultado['charge_id'];

        // $das = [
        //     'url' => $urlPagamento,
        //     'linha' => $linhaDigitavel,
        //     'numero' => $nossoNumero
        // ];

        // echo '<pre>';
        // echo json_encode($das, JSON_PRETTY_PRINT);
        // echo '</pre>';
        // return;

        // // Armazenar informações do boleto no banco de dados
        $query = $pdo->prepare("INSERT INTO pagamentos_boleto (id_matricula, charge_id, nosso_numero, url_boleto, linha_digitavel, valor, status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'pendente')");
        $query->execute([$id_venda, $nossoNumero, $nossoNumero, $urlBoleto, $linhaDigitavel, $resultado['total']]);
        $id_pagamento_boleto = $pdo->lastInsertId();

        // Atualizar forma_pgto na tabela matricula
        $update = $pdo->prepare("UPDATE matriculas SET forma_pgto = 'BOLETO' WHERE id = ?");
        $update->execute([$id_venda]);

        echo '
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pagamento por Boleto</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        </head>
        <body class="bg-gray-100 font-sans">
            <div class="container mx-auto px-4 py-10 max-w-3xl">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h1 class="text-2xl font-bold text-center mb-4 text-blue-600">Pagamento por Boleto</h1>
                    <div class="border-t border-b border-gray-200 py-4 mb-4">
                        <p class="text-center text-lg mb-2">Valor: <span class="font-bold">R$ ' . number_format($valor_curso, 2, ',', '.') . '</span></p>
                        <p class="text-center text-sm text-gray-600">Utilize o código abaixo para pagar o boleto ou faça download do PDF</p>
                    </div>
                    <div class="mb-6">
                        <div class="relative mb-4">
                            <input type="text" id="boleto-code" value="' . $resultado['payment_data']['data']['payment']['banking_billet']['barcode'] . '" readonly class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 text-sm" />
                            <button onclick="copiarCodigoBoleto()" class="absolute inset-y-0 right-0 px-4 bg-blue-500 text-white rounded-r-lg hover:bg-blue-600">
                                Copiar
                            </button>
                        </div>
                        <div class="text-center">
                            <a href="' . $resultado['payment_data']['data']['payment']['banking_billet']['billet_link'] . '" target="_blank" class="inline-block bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                                Download do Boleto
                            </a>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    O boleto tem vencimento em 7 dias. Após o pagamento, a confirmação pode levar até 3 dias úteis.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <a href="../sistema/painel-aluno/index.php" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                            Voltar ao Painel
                        </a>
                    </div>
                </div>
            </div>
            <script>
                function copiarCodigoBoleto() {
                    var codigoInput = document.getElementById("boleto-code");
                    codigoInput.select();
                    codigoInput.setSelectionRange(0, 99999);
                    document.execCommand("copy");
                    alert("Código do boleto copiado para a área de transferência!");
                }
            </script>
        </body>
        </html>';

    } elseif ($billingType == 'CONSULTAR_BOLETO') {




    }

} catch (Exception $e) {

}

// Arquivo de callback para webhook (a ser implementado em arquivo separado)
// Este arquivo receberá notificações da Efí quando o status do pagamento for alterado

/*
 * Arquivo webhook_efi.php (implementar separadamente)
 * Este arquivo receberá as notificações da Efí sobre mudanças no status dos pagamentos
 * e deverá atualizar os status no banco de dados, liberar acesso ao curso quando confirmado, etc.
 */
?>
