<?php
require_once __DIR__ . '/config/env.php';
$asaas_enabled = filter_var(env('ASAAS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
if (!$asaas_enabled) {
	http_response_code(410);
	echo json_encode(["status" => "disabled", "message" => "Asaas desativado"]);
	exit;
}

require_once __DIR__ . '/config/webhook.php';
webhook_require_token();

require("./sistema/conexao.php");

header("Content-Type: application/json");

// Configuração de log para debug
$logFile = './webhook_logs/webhook_log.txt';
$errorLogFile = './webhook_logs/error_log.txt';

// Função para registrar logs
function logMessage($message, $file = null)
{
    global $logFile;
    $file = $file ?? $logFile;
    file_put_contents($file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// Captura os dados da requisição POST
$inputJSON = file_get_contents("php://input");
logMessage("Webhook recebido: " . $inputJSON);

// Decodifica o JSON recebido
$inputData = json_decode($inputJSON, true);

// Verifica se a decodificação do JSON foi bem-sucedida
if (json_last_error() !== JSON_ERROR_NONE) {
    logMessage("Erro ao decodificar JSON: " . json_last_error_msg(), $errorLogFile);
    echo json_encode(["status" => "error", "message" => "Erro ao decodificar JSON"]);
    exit;
}

// Enviar os dados para outro webhook
$webhookUrl = "https://webhook.site/f704a324-f9fe-4c0d-82e2-e6f6acbbeda9";

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

$data = $inputData;
$eventType = isset($data['event']) ? $data['event'] : 'UNKNOWN_EVENT';
$payloadJson = json_encode($data);

// Registra o evento recebido
logMessage("Evento recebido: " . $eventType);

try {
    // Insere o webhook na tabela webhook_logs
    $sql_log_webhook = "INSERT INTO webhook_logs (event_type, payload) VALUES (:event_type, :payload)";
    $stmt_log = $pdo->prepare($sql_log_webhook);
    $stmt_log->execute([
        ':event_type' => $eventType,
        ':payload' => $payloadJson,
    ]);
    logMessage("Log de webhook salvo com sucesso");
} catch (PDOException $e) {
    logMessage("Erro ao salvar webhook: " . $e->getMessage(), $errorLogFile);
}

// Verifica se o evento recebido é relevante
if (!isset($data['event']) || !in_array($data['event'], ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED', 'PAYMENT_OVERDUE'])) {
    logMessage("Evento ignorado: " . $eventType);
    echo json_encode(["status" => "ignored", "message" => "Evento não processado"]);
    exit;
}

// Verifica se existem dados de pagamento
if (!isset($data['payment'])) {
    logMessage("Dados de pagamento ausentes", $errorLogFile);
    echo json_encode(["status" => "error", "message" => "Dados de pagamento ausentes"]);
    exit;
}

// Extrai informações do pagamento
$statusPagamento = $data['payment']['status'];
$idDoPagamento = $data['payment']['id'];
$billingType = $data['payment']['billingType'];
$asaas_id = $idDoPagamento;
$valor_pago = $data['payment']['value'];
$split = isset($data['payment']['split']) ? $data['payment']['split'] : [];

// Verifica se existe a matrícula correspondente
try {
    $queryMatriculas = $pdo->prepare("SELECT * FROM matriculas WHERE id_asaas = :asaas_id");
    $queryMatriculas->execute([':asaas_id' => $asaas_id]);
    $resultMatriculas = $queryMatriculas->fetch(PDO::FETCH_ASSOC);

    if (!$resultMatriculas) {
        logMessage("Matrícula não encontrada para o ID ASAAS: " . $asaas_id, $errorLogFile);
        echo json_encode(["status" => "error", "message" => "Matrícula não encontrada"]);
        exit;
    }

    logMessage("Matrícula encontrada: " . json_encode($resultMatriculas));

    // Extrai informações da matrícula
    $valor_curso = $valor_pago;
    $id_curso = $resultMatriculas['id_curso'];
    $aluno_id = $resultMatriculas['aluno'];
    $pacote = $resultMatriculas['pacote']; // Verifica se é um pacote

    // Busca detalhes do curso
    $query_curso = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id_curso");
    $query_curso->execute([':id_curso' => $id_curso]);
    $curso_result = $query_curso->fetch(PDO::FETCH_ASSOC);
    $curso = $curso_result ? $curso_result['nome'] : 'Curso não encontrado';

    // Busca nome do aluno
    $query_nome_aluno = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :aluno_id");
    $query_nome_aluno->execute([':aluno_id' => $aluno_id]);
    $aluno_result = $query_nome_aluno->fetch(PDO::FETCH_ASSOC);
    $nome_aluno = $aluno_result ? $aluno_result['nome'] : 'Aluno não encontrado';

    // Busca id_pessoa do aluno
    $query_vendedor = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :aluno_id");
    $query_vendedor->execute([':aluno_id' => $aluno_id]);
    $vendedor_result = $query_vendedor->fetch(PDO::FETCH_ASSOC);

    if (!$vendedor_result) {
        logMessage("ID pessoa do aluno não encontrado: " . $aluno_id, $errorLogFile);
        $id_pessoa_aluno = null;
    } else {
        $id_pessoa_aluno = $vendedor_result['id_pessoa'];

        // Busca o id do vendedor
        $query_vendedor_id = $pdo->prepare("SELECT usuario FROM alunos WHERE id = :id_pessoa_aluno");
        $query_vendedor_id->execute([':id_pessoa_aluno' => $id_pessoa_aluno]);
        $vendedor_id_result = $query_vendedor_id->fetch(PDO::FETCH_ASSOC);

        if (!$vendedor_id_result) {
            logMessage("Vendedor não encontrado para o aluno: " . $id_pessoa_aluno, $errorLogFile);
            $vendedor_id = null;
        } else {
            $vendedor_id = $vendedor_id_result['usuario'];

            // Busca detalhes do vendedor
            $query_vendedor = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :vendedor_id");
            $query_vendedor->execute([':vendedor_id' => $vendedor_id]);
            $vendedor_result = $query_vendedor->fetch(PDO::FETCH_ASSOC);

            if (!$vendedor_result) {
                logMessage("Dados do vendedor não encontrados: " . $vendedor_id, $errorLogFile);
                $vendedor_nome = null;
            } else {
                $vendedor_nome = $vendedor_result['id_pessoa'];

                // Busca wallet do vendedor
                $query_vendedor_wallet = $pdo->prepare("SELECT wallet_id FROM usuarios WHERE id = :vendedor_id");
                $query_vendedor_wallet->execute([':vendedor_id' => $vendedor_id]);
                $wallet_result = $query_vendedor_wallet->fetch(PDO::FETCH_ASSOC);
                $vendedor_wallet = $wallet_result ? $wallet_result['wallet_id'] : null;

                // Busca informações do vendedor
                $query_vendedor_res = $pdo->prepare("SELECT * FROM vendedores WHERE id = :vendedor_nome");
                $query_vendedor_res->execute([':vendedor_nome' => $vendedor_nome]);
                $vendedor_res = $query_vendedor_res->fetch(PDO::FETCH_ASSOC);

                if (!$vendedor_res) {
                    logMessage("Detalhes do vendedor não encontrados: " . $vendedor_nome, $errorLogFile);
                    $nome_vendedor = $vendedor_nome;
                    $comissao_do_vendedor = 0;
                } else {
                    $nome_vendedor = $vendedor_res['id'];
                    $comissao_do_vendedor = $vendedor_res['comissao'];
                }
            }
        }
    }

    // Calcula valores de comissão
    $splits_do_vendedor = array_filter($split, function ($splitItem) use ($vendedor_wallet) {
        return isset($splitItem['walletId']) && $splitItem['walletId'] === $vendedor_wallet;
    });

    $total_vendedor = array_reduce($splits_do_vendedor, function ($carry, $splitItem) {
        return $carry + $splitItem['totalValue'];
    }, 0);

    $valor_pagar = number_format($total_vendedor, 2, '.', '');
    $valor_pagar_tabela = ($valor_curso * $comissao_do_vendedor) / 100;
    $data_pgto = date("d:m:Y");
    $data_pgto2 = date("Y-m-d");

    // Processa o pagamento com base no status
    if ($statusPagamento === 'RECEIVED' || $statusPagamento === 'CONFIRMED') {
        $novo_status = 'Matriculado';

        logMessage("Processando pagamento recebido/confirmado: " . $idDoPagamento);

        // Atualiza parcelas se existirem
        $consulta_dados_parcela = $pdo->prepare("SELECT * FROM parcelas_geradas_por_boleto WHERE id_asaas = :id_asaas");
        $consulta_dados_parcela->execute([':id_asaas' => $idDoPagamento]);
        $resposta_dados_parcela = $consulta_dados_parcela->fetchAll(PDO::FETCH_ASSOC);

        if (count($resposta_dados_parcela) > 0) {
            logMessage("Atualizando parcelas para o pagamento: " . $idDoPagamento);

            if (isset($data['payment']['transactionReceiptUrl'])) {
                $transactionReceiptUrl = $data['payment']['transactionReceiptUrl'];

                $sql_atualiza_parcelas = "UPDATE parcelas_geradas_por_boleto
                    SET situacao = :novo_status, transaction_receipt_url = :transactionReceiptUrl
                    WHERE id_asaas = :id_asaas";

                $stmt = $pdo->prepare($sql_atualiza_parcelas);
                $stmt->execute([
                    ':novo_status' => 1,
                    ':transactionReceiptUrl' => $transactionReceiptUrl,
                    ':id_asaas' => $idDoPagamento,
                ]);

                logMessage("Parcelas atualizadas com URL do comprovante: " . $transactionReceiptUrl);
            } else {
                $sql_atualiza_parcelas = "UPDATE parcelas_geradas_por_boleto
                    SET situacao = :novo_status
                    WHERE id_asaas = :id_asaas";

                $stmt = $pdo->prepare($sql_atualiza_parcelas);
                $stmt->execute([
                    ':novo_status' => 1,
                    ':id_asaas' => $idDoPagamento,
                ]);

                logMessage("Parcelas atualizadas sem URL do comprovante");
            }
        }

        // Adiciona comissão na tabela comissoes_pagar
        $stmt_insert = $pdo->prepare("INSERT INTO comissoes_pagar (descricao, valor_curso, curso, id_curso, status, data_pgto, vendedor, porcentagem_comissao, valor_pagar)
            VALUES (:nome_aluno, :valor_curso, :curso, :id_curso, 'RECEBIDO', :data_pgto, :vendedor, :porcentagem_comissao, :valor_pagar)");

        $stmt_insert->bindParam(':nome_aluno', $nome_aluno, PDO::PARAM_STR);
        $stmt_insert->bindParam(':valor_curso', $valor_curso, PDO::PARAM_STR);
        $stmt_insert->bindParam(':curso', $curso, PDO::PARAM_STR);
        $stmt_insert->bindParam(':id_curso', $id_curso, PDO::PARAM_INT);
        $stmt_insert->bindParam(':data_pgto', $data_pgto2, PDO::PARAM_STR);
        $stmt_insert->bindParam(':vendedor', $nome_vendedor, PDO::PARAM_STR);
        $stmt_insert->bindParam(':porcentagem_comissao', $comissao_do_vendedor, PDO::PARAM_STR);
        $stmt_insert->bindParam(':valor_pagar', $valor_pagar, PDO::PARAM_STR);

        if ($stmt_insert->execute()) {
            logMessage("Comissão registrada com sucesso");
        } else {
            logMessage("Erro ao registrar comissão", $errorLogFile);
        }

        // Adiciona na tabela pagar
        $stmt_insert_pagar = $pdo->prepare("INSERT INTO pagar (descricao, valor, data, vencimento, pago, professor, curso)
            VALUES (:nome_aluno, :valor_pagar, :data_pgto, :vencimento, 'Nao', :professor, :curso)");

        $stmt_insert_pagar->bindParam(':nome_aluno', $nome_aluno, PDO::PARAM_STR);
        $stmt_insert_pagar->bindParam(':valor_pagar', $valor_pagar_tabela, PDO::PARAM_STR);
        $stmt_insert_pagar->bindParam(':data_pgto', $data_pgto2, PDO::PARAM_STR);
        $stmt_insert_pagar->bindParam(':vencimento', $data_pgto2, PDO::PARAM_STR);
        $stmt_insert_pagar->bindParam(':professor', $nome_vendedor, PDO::PARAM_STR);
        $stmt_insert_pagar->bindParam(':curso', $curso, PDO::PARAM_STR);

        if ($stmt_insert_pagar->execute()) {
            logMessage("Conta a pagar registrada com sucesso");
        } else {
            logMessage("Erro ao registrar conta a pagar", $errorLogFile);
        }

        // IMPORTANTE: Aqui está a atualização do status da matrícula - Principal problema do código original
        logMessage("Tentando atualizar a matrícula para status: " . $novo_status);

        $sql = "UPDATE matriculas
                SET status = :novo_status, forma_pgto = :forma_pgto
                WHERE id_asaas = :id_asaas";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':novo_status' => $novo_status,
            ':forma_pgto' => $billingType,
            ':id_asaas' => $idDoPagamento,
        ]);

        if ($result) {
            $rowsAffected = $stmt->rowCount();
            logMessage("Matrícula atualizada com sucesso. Linhas afetadas: " . $rowsAffected);
            logMessage("Passou aqui");
            // NOVA FUNCIONALIDADE: Verificar se é um pacote e liberar os cursos individuais
            if ($pacote == 'Sim') {

                logMessage("Matrícula é um pacote. Iniciando liberação automática dos cursos individuais.");

                try {
                    // Desativa temporariamente o modo safe update
                    $pdo->query("SET SQL_SAFE_UPDATES = 0");

                    // Cria uma tabela temporária com os cursos do pacote
                    $pdo->query("CREATE TEMPORARY TABLE temp_cursos_pacote AS
                        SELECT 
                            cp.id AS id_cursos_pacotes,
                            cp.id_curso AS id_do_curso,
                            c.matriculas,
                            c.professor AS id_professor
                        FROM 
                            cursos_pacotes cp
                        JOIN 
                            cursos c ON cp.id_curso = c.id
                        WHERE 
                            cp.id_pacote = {$id_curso}");

                    // Verifica se há registros na tabela temporária
                    $query_count = $pdo->query("SELECT COUNT(*) FROM temp_cursos_pacote");
                    $total_registros = $query_count->fetchColumn();

                    logMessage("Encontrados {$total_registros} cursos no pacote {$id_curso}");

                    if ($total_registros > 0) {


                        $pdo->query("CREATE TEMPORARY TABLE temp_matriculas_existentes AS
                            SELECT id_curso
                            FROM matriculas
                            WHERE aluno = {$aluno_id} AND id_pacote = {$id_curso}");

                        // Insere novas matrículas apenas para os cursos que o aluno não está matriculado ainda
                        $stmt_insert_matriculas = $pdo->prepare("INSERT INTO matriculas 
                            (id_curso, aluno, professor, aulas_concluidas, data, status, pacote, id_pacote, obs)
                            SELECT 
                                tcp.id_do_curso,
                                :aluno_id,
                                tcp.id_professor,
                                1,
                                CURDATE(),
                                'Matriculado',
                                'Não',
                                :id_curso,
                                'Pacote'
                            FROM 
                                temp_cursos_pacote tcp
                            LEFT JOIN
                                temp_matriculas_existentes tme ON tcp.id_do_curso = tme.id_curso
                            WHERE 
                                tme.id_curso IS NULL");

                        $stmt_insert_matriculas->execute([
                            ':aluno_id' => $aluno_id,
                            ':id_curso' => $id_curso
                        ]);

                        $novas_matriculas = $stmt_insert_matriculas->rowCount();
                        logMessage("Adicionadas {$novas_matriculas} novas matrículas para os cursos do pacote");

                        // Atualiza contador de matrículas apenas para os cursos onde novas matrículas foram adicionadas
                        $pdo->query("UPDATE cursos c
                            JOIN temp_cursos_pacote tcp ON c.id = tcp.id_do_curso
                            LEFT JOIN temp_matriculas_existentes tme ON tcp.id_do_curso = tme.id_curso
                            SET c.matriculas = c.matriculas + 1
                            WHERE tme.id_curso IS NULL");

                        // Limpa as tabelas temporárias
                        $pdo->query("DROP TEMPORARY TABLE IF EXISTS temp_cursos_pacote");
                        $pdo->query("DROP TEMPORARY TABLE IF EXISTS temp_matriculas_existentes");

                        // Reativa o modo safe update
                        $pdo->query("SET SQL_SAFE_UPDATES = 1");

                        logMessage("Liberação automática de cursos do pacote concluída com sucesso");
                    } else {
                        logMessage("Nenhum curso encontrado para este pacote", $errorLogFile);
                    }
                } catch (PDOException $e) {
                    logMessage("Erro ao liberar cursos do pacote: " . $e->getMessage(), $errorLogFile);
                }
            }

            if ($rowsAffected === 0) {
                logMessage("ATENÇÃO: A atualização não afetou nenhum registro. Verifique o id_asaas: " . $idDoPagamento, $errorLogFile);
            }

            echo json_encode([
                "status" => "success",
                "message" => "Matrícula atualizada com sucesso",
                "rowsAffected" => $rowsAffected
            ]);
        } else {
            logMessage("Erro ao atualizar matrícula: " . implode(", ", $stmt->errorInfo()), $errorLogFile);
            echo json_encode([
                "status" => "error",
                "message" => "Erro ao atualizar matrícula",
                "sqlError" => $stmt->errorInfo()
            ]);
        }

    } elseif ($statusPagamento === 'OVERDUE') {
        logMessage("Processando pagamento em atraso: " . $idDoPagamento);

        // Atualiza a tabela matriculas
        $sql = "UPDATE matriculas
                SET id_asaas = NULL
                WHERE id_asaas = :id_asaas";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_asaas' => $idDoPagamento,
        ]);

        if ($stmt->rowCount()) {
            logMessage("id_asaas removido da tabela matriculas");
        } else {
            logMessage("Nenhum registro da tabela matriculas afetado", $errorLogFile);
        }

        // Atualiza a tabela parcelas_geradas_por_boleto
        $sql_parcelas = "UPDATE parcelas_geradas_por_boleto
                         SET id_asaas = NULL
                         WHERE id_asaas = :id_asaas";

        $stmt_parcelas = $pdo->prepare($sql_parcelas);
        $stmt_parcelas->execute([
            ':id_asaas' => $idDoPagamento,
        ]);

        if ($stmt_parcelas->rowCount()) {
            logMessage("id_asaas removido da tabela parcelas_geradas_por_boleto");
        } else {
            logMessage("Nenhum registro da tabela parcelas_geradas_por_boleto afetado", $errorLogFile);
        }

        echo json_encode(["status" => "success", "message" => "Pagamento em atraso processado"]);
    }

} catch (PDOException $e) {
    logMessage("Erro de banco de dados: " . $e->getMessage(), $errorLogFile);
    echo json_encode(["status" => "error", "message" => "Erro de banco de dados: " . $e->getMessage()]);
}
