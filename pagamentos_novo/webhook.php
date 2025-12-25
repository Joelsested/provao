<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

include("./config.php");
include("../sistema/conexao.php");
require_once __DIR__ . '/../config/env.php';
$mp_enabled = filter_var(env('MP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
if (!$mp_enabled) {
	http_response_code(410);
	echo json_encode(["status" => "disabled", "message" => "Mercado Pago desativado"]);
	exit;
}
require_once __DIR__ . '/../config/webhook.php';
webhook_require_token();

header("Content-Type: application/json");

// Captura os dados da requisição POST
$inputJSON = file_get_contents("php://input");

// Decodifica o JSON recebido
$inputData = json_decode($inputJSON, true);

// Verifica se os dados foram recebidos corretamente
if (!$inputData || !isset($inputData['data']['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON received"
    ]);
    exit;
}

// Salvar os dados no arquivo "webhook_mercadopago.json"
$file = 'webhook_mercadopago.json';
$entry = json_encode($inputData, JSON_PRETTY_PRINT) . ",\n";

// Abre o arquivo e adiciona a nova entrada
file_put_contents($file, $entry, FILE_APPEND);

// Enviar os dados para outro webhook
$webhookUrl = "https://webhook.sestedcursosvirtual.store/webhook/mercadopago"; // Substitua pela URL do seu webhook de destino

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($inputData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Responder ao Mercado Pago
echo json_encode([
    "status" => "success",
    "message" => "Webhook processed",
    "forwarded_status" => $httpCode
]);



$payment_id = $inputData['data']['id'];

// CONSULTAR PAGAMENTO NO MERCADO PAGO
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $payment_id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $TOKEN_MERCADO_PAGO,
    ),
));

$response_original = curl_exec($curl);
curl_close($curl);

$response = json_decode($response_original, true);

$status = isset($response['status']) ? $response['status'] : null;
$amount = isset($response['transaction_details']['net_received_amount']) ? $response['transaction_details']['net_received_amount'] : null;
$id = isset($response['id']) ? $response['id'] : null;


echo json_encode($response);

return;



// Verifica se a resposta da API é válida
if (!$response || !isset($response['status']) || !isset($response['id']) || !isset($response['transaction_details']['net_received_amount'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid response from payment API"
    ]);
    exit;
}




// Verifica se o pagamento foi aprovado
if ($response['status'] === "approved") {
    sleep(5);
    $id_api = $response['id'];
    $total_recebido = $response['transaction_details']['net_received_amount'];
    $ref_api = $inputData['data']['id']; // O valor de ref_api é o mesmo que payment_id

    // Busca a matrícula com o ref_api correspondente
    $query = $pdo->prepare("SELECT * FROM matriculas WHERE ref_api = :ref_api");
    $query->execute([':ref_api' => $ref_api]);
    $result = $query->fetch(PDO::FETCH_ASSOC); // Pega o resultado como um array associativo

    // Verifica se a matrícula foi encontrada
    if ($result) {
        // Atualiza a matrícula com os dados do pagamento
        $stmt_update = $pdo->prepare("UPDATE matriculas SET status = 'Matriculado', total_recebido = :total_recebido WHERE ref_api = :ref_api");
        $stmt_update->bindParam(':total_recebido', $total_recebido, PDO::PARAM_STR);
        $stmt_update->bindParam(':ref_api', $ref_api, PDO::PARAM_STR);

        if ($stmt_update->execute()) {
            echo json_encode(["status" => "success", "message" => "Matrícula atualizada com sucesso"]);

            // Agora, vamos inserir os dados na tabela 'comissoes_pagar'

            // Parâmetros necessários para a inserção
            $valor_curso = $result['valor']; // Valor da matrícula
            $id_curso = $result['id_curso'];
            $aluno_id = $result['aluno']; // ID do aluno (vendedor)
            $id_pessoa = $result['id_pessoa']; // ID da pessoa (vendedor)

            // Busca o nome do curso na tabela 'cursos'
            $query_curso = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id");
            $query_curso->execute([':id' => $id_curso]);
            $curso = $query_curso->fetch(PDO::FETCH_ASSOC)['nome'];

            $query_nome_aluno = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
            $query_nome_aluno->execute([':id' => $aluno_id]);
            $nome_aluno = $query_nome_aluno->fetch(PDO::FETCH_ASSOC)['nome'];


            // Busca o id_pessoa do usuario (aluno) na tabela 'usuarios'
            $query_vendedor = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id");
            $query_vendedor->execute([':id' => $aluno_id]);
            $id_pessoa_aluno = $query_vendedor->fetch(PDO::FETCH_ASSOC)['id_pessoa'];

            // Busca o id do vendedor do usuario (aluno) na tabela 'usuarios'
            $query_vendedor_id = $pdo->prepare("SELECT usuario FROM alunos WHERE id = :id");
            $query_vendedor_id->execute([':id' => $id_pessoa_aluno]);
            $vendedor_id = $query_vendedor_id->fetch(PDO::FETCH_ASSOC)['usuario'];

            // Busca o  vendedor  'usuarios'
            $query_vendedor = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id");
            $query_vendedor->execute([':id' => $vendedor_id]);
            $vendedor_nome = $query_vendedor->fetch(PDO::FETCH_ASSOC)['id_pessoa'];

            // Busca o  vendedor em 'vendedores'
            $query_vendedor_res = $pdo->prepare("SELECT * FROM vendedores WHERE id = :id");
            $query_vendedor_res->execute([':id' => $vendedor_nome]);
            $vendedor_res = $query_vendedor_res->fetch(PDO::FETCH_ASSOC);

            $nome_vendedor = $vendedor_res['id'];
            $comissao_do_vendedor = $vendedor_res['comissao'];





            // Valor fixo a ser pago
            $valor_pagar = ($valor_curso * $comissao_do_vendedor) / 100;

            // Formata o valor como dinheiro
            $valor_pagar = number_format($valor_pagar, 2, '.', '');
            $valor_pagar_tabela = ($valor_curso * $comissao_do_vendedor) / 100;
            $data_pgto2 = date("Y-m-d");
                
            // Formata a data para "dd:mm:yyyy"
            $data_pgto = date("d:m:Y");


            // Prepara a inserção na tabela 'comissoes_pagar'
            $stmt_insert = $pdo->prepare("INSERT INTO comissoes_pagar (descricao, valor_curso, curso, id_curso, status, data_pgto, vendedor, porcentagem_comissao, valor_pagar)
                VALUES (:nome_aluno, :valor_curso, :curso, :id_curso, 'DISPONIVEL', :data_pgto, :vendedor, :porcentagem_comissao, :valor_pagar)");

            $stmt_insert->bindParam(':nome_aluno', $nome_aluno, PDO::PARAM_STR);
            $stmt_insert->bindParam(':valor_curso', $valor_curso, PDO::PARAM_STR);
            $stmt_insert->bindParam(':curso', $curso, PDO::PARAM_STR);
            $stmt_insert->bindParam(':id_curso', $id_curso, PDO::PARAM_INT);
            $stmt_insert->bindParam(':data_pgto', time(), PDO::PARAM_STR); // Timestamp atual
            $stmt_insert->bindParam(':vendedor', $nome_vendedor, PDO::PARAM_STR);
            $stmt_insert->bindParam(':porcentagem_comissao', $comissao_do_vendedor, PDO::PARAM_STR);
            $stmt_insert->bindParam(':valor_pagar', $valor_pagar, PDO::PARAM_STR);

            if ($stmt_insert->execute()) {
                echo json_encode(["status" => "success", "message" => "Comissão registrada com sucesso"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Erro ao registrar comissão"]);
            }
            
            try {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
                $stmt_insert_pagar = $pdo->prepare("INSERT INTO pagar (descricao, valor, data, vencimento, pago, professor, curso)
                    VALUES (:nome_aluno, :valor_pagar, :data_pgto, :vencimento, 'Nao', :professor, :curso)");
            
                $stmt_insert_pagar->bindParam(':nome_aluno', $nome_aluno, PDO::PARAM_STR);
                $stmt_insert_pagar->bindParam(':valor_pagar', $valor_pagar_tabela, PDO::PARAM_STR);
                $stmt_insert_pagar->bindParam(':data_pgto', $data_pgto2, PDO::PARAM_STR);
                $stmt_insert_pagar->bindParam(':vencimento', $data_pgto2, PDO::PARAM_STR); // Corrigido
                $stmt_insert_pagar->bindParam(':professor', $nome_vendedor, PDO::PARAM_STR);
                $stmt_insert_pagar->bindParam(':curso', $curso, PDO::PARAM_STR);
            
                if ($stmt_insert_pagar->execute()) {
                    echo json_encode(["status" => "success", "message" => "CONTAS A PAGAR CADASTRADA"]);
                } else {
                    echo json_encode(["status" => "error", "message" => "CONTAS A PAGAR ERROR"]);
                }
            } catch (PDOException $e) {
                echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            }
            
            
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao atualizar matrícula"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Matrícula não encontrada"]);
    }
} else {
    echo json_encode(["status" => "pending", "message" => "Pagamento ainda não aprovado"]);
}
