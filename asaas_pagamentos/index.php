<?php

require_once('../vendor/autoload.php');
require_once("../config/env.php");

$asaas_enabled = filter_var(env('ASAAS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
if (!$asaas_enabled) {
	http_response_code(403);
	echo 'Meio de pagamento desativado.';
	exit;
}

require_once("../sistema/conexao.php");

ini_set('display_errors', 0);

ini_set('display_startup_errors', 0);

@session_start();

$is_pacote = $_GET['pacote'] ?? null;

if($is_pacote == 'Sim') {
    $curso_pacote = "Sim";
}
else {
    $curso_pacote = "Não";
}



$forma_de_pagamento = $_GET['formaDePagamento'];

$billingType = strtoupper($forma_de_pagamento);

$quantidadeParcelas = $_GET['quantidadeParcelas'];


//Busca dados para atualização da situação da matricula

$id_do_aluno = @$_SESSION['id'];

$id_do_curso_pag = $_GET['id_do_curso'];

$nome_curso_titulo = $_GET['nome_do_curso'];

$queryPix = $pdo->query("SELECT desconto_pix FROM config");

$resPix = $queryPix->fetchAll(PDO::FETCH_ASSOC);

$descontoPix =  json_encode($resPix[0]['desconto_pix']);


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

$query = $pdo->prepare("SELECT * FROM matriculas where id_curso = :id_curso and aluno = :aluno and status = 'Aguardando' and pacote = :pacote");
$query->execute([':id_curso' => $id_do_curso_pag, ':aluno' => $id_do_aluno, ':pacote' => $curso_pacote]);

$res = $query->fetchAll(PDO::FETCH_ASSOC);

$id_da_matricula = $res[0]['id'];



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

        // $valor_a_pagar = $valor_curso - ($valor_curso *  $descontoPix); // 5% de desconto

        $valor_a_pagar = $valor_curso - ($valor_curso *  ($descontoPix / 100)); 
    }

}

if ($forma_de_pagamento == 'boleto' and $quantidadeParcelas != 1) {

    if (@count($res) > 0) {

        $id_matricula = $res[0]['id'];

        $consulta_dados_da_parcela = $pdo->prepare("SELECT * FROM boletos_parcelados WHERE id_matricula = :id_matricula");
        $consulta_dados_da_parcela->execute([':id_matricula' => $id_matricula]);

        $resposta_consulta_dados_da_parcela = $consulta_dados_da_parcela->fetchAll(PDO::FETCH_ASSOC);


        if (@count($resposta_consulta_dados_da_parcela) > 0) {

            echo '

                  <script src="https://cdn.tailwindcss.com"></script>

                    <div class="w-full min-h-screen bg-gray-100 flex justify-center items-center">

                	<div

                		class="w-3/5 bg-blue-100 rounded-lg shadow-sm p-5 border-dashed border border-blue-500 flex flex-col sm:flex-row justify-between items-center gap-2 sm:gap-0">

                		<div class="flex flex-col sm:flex-row justify-start items-center gap-4">

                			<div class="bg-blue-200 flex p-2 rounded-md"><svg xmlns="http://www.w3.org/2000/svg"

                					class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">

                					<path fill-rule="evenodd"

                						d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"

                						clip-rule="evenodd"></path>

                				</svg></div>

                			<div class="text-center sm:text-left">

                				<h1 class="text-gray-700 font-bold tracking-wider">Processo de matrícula já inicializado!</h1>

                				<p class="text-gray-500 font-semibold">O processo de matrícula foi iniciado. Para mais informações, acompanhe o painel do aluno ou consulte seus dados financeiros.</p>

                			</div>

                		</div>

                		<div><a href="../sistema/painel-aluno/index.php"><button class="bg-blue-500 py-2 px-4 text-white font-bold rounded-md hover:bg-blue-600">Acessar</button></a>

                		</div>

                	</div>

                </div>

            ';

        }

        $registro_dados_da_parcela = $pdo->prepare("INSERT INTO boletos_parcelados (qtd_parcelas, id_matricula) VALUES (:qtd_parcelas, :id_matricula)");
        $registro_dados_da_parcela->execute([
            ':qtd_parcelas' => $quantidadeParcelas,
            ':id_matricula' => $id_matricula,
        ]);

        $id_registro_dados_da_parcela = $pdo->lastInsertId();

        $valor_unitario_parcelas = round($valor_curso / $quantidadeParcelas, 2);

        for ($i = 0; $i < $quantidadeParcelas; $i++) {

            $ordemParcela = $i + 1;

            $stmtParcela = $pdo->prepare("INSERT INTO parcelas_geradas_por_boleto (ordem_parcela, id_boleto_parcelado, valor_parcela, situacao) VALUES (:ordem_parcela, :id_boleto_parcelado, :valor_parcela, '0')");
            $stmtParcela->execute([
                ':ordem_parcela' => $ordemParcela,
                ':id_boleto_parcelado' => $id_registro_dados_da_parcela,
                ':valor_parcela' => $valor_unitario_parcelas,
            ]);

        }
        header("Location: ../sistema/painel-aluno/index.php?pagina=parcelas");

        exit();
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



//Wallet ids e respectivas porcentagens

$fixos_wallet_ids = array_filter(array_map(function ($item) {

    if (!empty($item['wallet_id'])) {

        return ['walletId' => $item['wallet_id'], 'percentualValue' => $item['porcentagem']];

    }

    return null;

}, $lista_de_usuarios_que_recebem_fixo));



$fixos_wallet_ids = array_values($fixos_wallet_ids);

if ($wallet_id_do_vendedor_do_curso)

    array_push($fixos_wallet_ids, ['walletId' => $wallet_id_do_vendedor_do_curso, 'percentualValue' => $porcentagem_vendedor]);



$clienteGuzzle = new \GuzzleHttp\Client();



// Token Asaas via .env
$chaveAsaas = env('ASAAS_TOKEN', '');
if ($chaveAsaas === '') {
	http_response_code(500);
	echo 'Token Asaas nao configurado.';
	exit;
}


//VERIFICA SE O NIVEL RESPONSAVEL PELO CADASTRO DO ALUNO POSSUI OS DADOS DE COMISSÃO REGISTRADO

$consulta_comissao_nivel_responsavel = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$consulta_comissao_nivel_responsavel->execute([':id' => $nivel_responsavel_pelo_cadastro_do_aluno]);

$resposta_comissao_nivel_responsavel = $consulta_comissao_nivel_responsavel->fetchAll(PDO::FETCH_ASSOC);

$wallet_id_nivel_responsavel_pelo_cadastro = isset($resposta_comissao_nivel_responsavel[0]['wallet_id']) ? $resposta_comissao_nivel_responsavel[0]['wallet_id'] : 0;


$vendedor_id = $resposta_comissao_nivel_responsavel[0]['id_pessoa'];









// // VERIFICAR PORCENTAGEM COMISSAO VENDEDOR

// $consulta_comissao_vendedor = $pdo->query("SELECT comissao FROM vendedores where id = '$vendedor_id'");

// $resposta_comissao_nivel_vendedor = $consulta_comissao_vendedor->fetchAll(PDO::FETCH_ASSOC);



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

$consulta_vendedor_professor = $pdo->query("SELECT comissao_tutor FROM config");
$resposta_consulta_vendedor_professor = $consulta_vendedor_professor->fetch(PDO::FETCH_ASSOC)['comissao_tutor'];

$vendedor_e_professor = 0;
$tutor_atendente_id = null;
if ($nivel_responsavel == 'Vendedor' && !empty($vendedor_id)) {
    $consulta_vendedor_e_professor = $pdo->prepare("SELECT professor, tutor_id FROM vendedores where id = :id");
    $consulta_vendedor_e_professor->execute([':id' => $vendedor_id]);
    $resposta_consulta_vendedor_e_professor = $consulta_vendedor_e_professor->fetch(PDO::FETCH_ASSOC) ?: [];
    $vendedor_e_professor = $resposta_consulta_vendedor_e_professor['professor'] ?? 0;
    $tutor_atendente_id = $resposta_consulta_vendedor_e_professor['tutor_id'] ?? null;
}

$tutor_wallet_id = null;
if (!empty($tutor_atendente_id)) {
    $consulta_tutor = $pdo->prepare("SELECT wallet_id FROM usuarios WHERE id_pessoa = :id_pessoa AND nivel = 'Tutor' LIMIT 1");
    $consulta_tutor->execute([':id_pessoa' => $tutor_atendente_id]);
    $tutor_wallet_id = $consulta_tutor->fetchColumn();
}

if ($nivel_responsavel == 'Tutor') {
    if (!empty($wallet_id_nivel_responsavel_pelo_cadastro)) {
        array_push($fixos_wallet_ids, ['walletId' => $wallet_id_nivel_responsavel_pelo_cadastro, 'percentualValue' => $comissao_vendedor]);
    }
}

if ($vendedor_e_professor && $tutor_wallet_id) {
    array_push($fixos_wallet_ids, ['walletId' => $tutor_wallet_id, 'percentualValue' => $resposta_consulta_vendedor_professor]);
}

//VERIFICA A PORCENTAGEM DE COMISSAO DO NIVEL RESPONSAVEL PELO CADASTRO DO ALUNO

$nivel_do_responsavel_pelo_cadastro_do_aluno = isset($resposta_comissao_nivel_responsavel[0]['nivel']) ? $resposta_comissao_nivel_responsavel[0]['nivel'] : 'sem nível';

$consulta_valor_da_comissao_do_responsavel = $pdo->prepare("SELECT * FROM comissoes where nivel = :nivel");
$consulta_valor_da_comissao_do_responsavel->execute([':nivel' => $nivel_do_responsavel_pelo_cadastro_do_aluno]);

$resposta_valor_da_comissao_do_responsavel = $consulta_valor_da_comissao_do_responsavel->fetchAll(PDO::FETCH_ASSOC);



$porcentagem_de_pagamento_para_responsavel = isset($resposta_valor_da_comissao_do_responsavel[0]['porcentagem']) ? $resposta_valor_da_comissao_do_responsavel[0]['porcentagem'] : 0;



if ($wallet_id_nivel_responsavel_pelo_cadastro && $nivel_responsavel == 'Vendedor' )

    array_push($fixos_wallet_ids, ['walletId' => $wallet_id_nivel_responsavel_pelo_cadastro, 'percentualValue' => intval($comissao_vendedor)]);


$dados_matricula = [

 'billingType' => $billingType,

        'customer' => $asaasCliente['id'],

        'value' => intval($valor_a_pagar),

        'dueDate' => $dataVencimento,

        'description' => 'Pagamento referente a' . $nome_curso_titulo . ' (ID: ' . $id_do_curso_pag . '). Com o valor de: '. $valor_curso .'. Valor total de pagamento é R$' . $valor_a_pagar . ' e o ID do aluno é ' . $id_do_aluno,

        'externalReference' => $id_do_curso_pag,

        'split' => $fixos_wallet_ids
];

// TODO REMOVER
$fixos_wallet_ids = [];

// echo json_encode($fixos_wallet_ids);
// return;

try {

    // Cria cliente Asaas para compra

    // $responseCliente = $clienteGuzzle->request('POST', 'https://api.asaas.com/v3/customers', [

        $responseCliente = $clienteGuzzle->request('POST', 'https://api-sandbox.asaas.com/v3/customers', [

        'body' => json_encode([

            'name' => $nome_aluno,

            'cpfCnpj' => $cpf_aluno,

            'email' => $email_aluno

        ]),

        'headers' => [

            'accept' => 'application/json',

            'access_token' => $chaveAsaas,

            'content-type' => 'application/json',

        ],

    ]);



    $asaasCliente = json_decode($responseCliente->getBody()->getContents(), true);



    // Gera cobrança no Asaas

    $dataVencimento = ((new DateTime())->modify('+7 days'))->format('Y-m-d');



    $corpoCobranca = [

        'billingType' => $billingType,

        'customer' => $asaasCliente['id'],

        'value' => $valor_a_pagar,

        'dueDate' => $dataVencimento,

        'description' => 'Pagamento referente ao ' . $nome_curso_titulo . ' (ID: ' . $id_do_curso_pag . '). Valor total de pagamento é R$' . $valor_curso . ' e o ID do aluno é ' . $id_do_aluno,

        'externalReference' => $id_do_curso_pag,

        'split' => $fixos_wallet_ids

    ];



    if ($quantidadeParcelas >= 2) {

        $corpoCobranca = [

            'billingType' => 'CREDIT_CARD',

            'customer' => $asaasCliente['id'],

            'value' => $valor_curso,

            'dueDate' => $dataVencimento,

            'description' => 'Pagamento referente ao ' . $nome_curso_titulo . ' (ID: ' . $id_do_curso_pag . '). Valor de pagamento é R$' . $valor_curso . ' e o ID do aluno é ' . $id_do_aluno,

            'externalReference' => $id_do_curso_pag,

            'split' => $fixos_wallet_ids,

            'totalValue' => $valor_curso,

            'installmentCount' => isset($quantidadeParcelas) ? $quantidadeParcelas : 1,

        ];

    }



    // $responseCobranca = $clienteGuzzle->request('POST', 'https://api.asaas.com/v3/payments', [

        $responseCobranca = $clienteGuzzle->request('POST', 'https://api-sandbox.asaas.com/v3/payments', [

        'body' => json_encode($corpoCobranca),

        'headers' => [

            'User-Agent' => 'MinhaIntegracao/1.0',

            'accept' => 'application/json',

            'access_token' => $chaveAsaas,

            'content-type' => 'application/json',

        ],

    ]);



    $asaasCobranca = json_decode($responseCobranca->getBody()->getContents(), true);

    // NOVO UPDATE

    // Atualiza id_asaas e forma_pgto na tabela matriculas

    $pdo = new PDO("mysql:host=$servidor;dbname=$banco", $usuario, $senha);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



    $sql = "UPDATE matriculas

        SET id_asaas = :id_asaas, forma_pgto = :forma_pgto

        WHERE id = :id_matricula

          AND aluno = :id_do_aluno";



    $stmt = $pdo->prepare($sql);



    $stmt->execute([

        ':id_asaas' => $asaasCobranca['id'],

        ':forma_pgto' => $billingType, // Atualizando a forma de pagamento

        ':id_matricula' => $id_da_matricula,

        ':id_do_aluno' => $id_do_aluno

    ]);







    //Redireciona para tela de checkout do Asaas

    header("Location: " . $asaasCobranca['invoiceUrl']);

    exit();

} catch (\GuzzleHttp\Exception\RequestException $e) {

    echo '

    

  <script src="https://cdn.tailwindcss.com"></script>

    <div class="lg:px-24 lg:py-24 md:py-20 md:px-44 px-4 py-24 items-center flex justify-center flex-col-reverse lg:flex-row md:gap-28 gap-16">

        <div class="xl:pt-24 w-full xl:w-1/2 relative pb-12 lg:pb-0">

            <div class="relative">

                <div class="absolute">

                    <div>

                        <h1 class="my-2 text-gray-800 font-bold text-2xl">

                            Erro ao tentar conectar com o meio de pagamento Asaas.

                        </h1>

                        <p class="my-2 text-gray-800">' . $e->getMessage() . '</p>

                    </div>

                </div>

                <div>

                    <img src="https://i.ibb.co/G9DC8S0/404-2.png" alt="404 Error" />

                </div>

            </div>

        </div>

        <div>

            <img src="https://i.ibb.co/ck1SGFJ/Group.png" alt="Group" />

        </div>

    </div>

';

} catch (\Exception $e) {



    echo '

    

  <script src="https://cdn.tailwindcss.com"></script>

    <div class="lg:px-24 lg:py-24 md:py-20 md:px-44 px-4 py-24 items-center flex justify-center flex-col-reverse lg:flex-row md:gap-28 gap-16">

        <div class="xl:pt-24 w-full xl:w-1/2 relative pb-12 lg:pb-0">

            <div class="relative">

                <div class="absolute">

                    <div>

                        <h1 class="my-2 text-gray-800 font-bold text-2xl">

                            Erro inesperado ao tentar conectar com o meio de pagamento Asaas.

                        </h1>

                        <p class="my-2 text-gray-800">' . $e->getMessage() . '</p>

                    </div>

                </div>

                <div>

                    <img src="https://i.ibb.co/G9DC8S0/404-2.png" alt="404 Error" />

                </div>

            </div>

        </div>

        <div>

            <img src="https://i.ibb.co/ck1SGFJ/Group.png" alt="Group" />

        </div>

    </div>

';

}
