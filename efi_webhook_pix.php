<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/webhook.php';
webhook_require_token();

require_once("sistema/conexao.php");

require_once 'efi/pix.php';


// Lê o conteúdo bruto enviado
$input = file_get_contents('php://input');

// Tenta decodificar como JSON
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = ['raw' => $input];
}



function consultarTransacaoPix($txid)
{

    $options = require_once 'efi/options.php';

    $config = [
        'client_id' => $options['clientId'],
        'client_secret' => $options['clientSecret'],
        'certificate_path' => $options['certificate'], // Apenas para PIX
        'chave_pix' => $options['pixKey'] ?? '', // Sua chave PIX
        'sandbox' => $options['sandbox'] // true para teste, false para produção
    ];

    $pixWebhook = new EFIPixPayment(
        $config['client_id'],
        $config['client_secret'],
        $config['certificate_path'],
        $config['sandbox']
    );

    $result = $pixWebhook->consultarCobranca($txid);

    return $result;
}


// Função para log de webhook
function logWebhook($pdo, $eventType, $payload, $receivedAt)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO webhook_logs (event_type, payload, received_at) VALUES (?, ?, ?)");
        $stmt->execute([$eventType, $payload, $receivedAt]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao salvar log: " . $e->getMessage());
        return false;
    }
}

// Função para atualizar status do pagamento PIX
function atualizarStatusPagamentoPix($pdo, $txid, $status)
{
    try {
        $stmt = $pdo->prepare("UPDATE pagamentos_pix SET status = ? WHERE txid = ?");
        return $stmt->execute([$status, $txid]);
    } catch (PDOException $e) {
        error_log("Erro ao atualizar pagamento PIX: " . $e->getMessage());
        return false;
    }
}

// Função para buscar id_matricula pelo txid
function buscarIdMatricula($pdo, $txid)
{
    try {
        $stmt = $pdo->prepare("SELECT id_matricula FROM pagamentos_pix WHERE txid = ?");
        $stmt->execute([$txid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id_matricula'] : null;
    } catch (PDOException $e) {
        error_log("Erro ao buscar id_matricula: " . $e->getMessage());
        return null;
    }
}

// Função para atualizar status da matrícula
function atualizarStatusMatricula($pdo, $idMatricula, $status)
{
    try {
        $stmt = $pdo->prepare("UPDATE matriculas SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $idMatricula]);
    } catch (PDOException $e) {
        error_log("Erro ao atualizar matrícula: " . $e->getMessage());
        return false;
    }
}

// Função para log de mensagens
function logMessage($message, $errorLogFile = null)
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;

    if ($errorLogFile) {
        error_log($logEntry, 3, $errorLogFile);
    } else {
        error_log($message);
    }
}

// Função para verificar se matrícula é um pacote e buscar dados necessários
function verificarDadosMatricula($pdo, $idMatricula): mixed
{
    try {
        $stmt = $pdo->prepare("SELECT id_curso, aluno, pacote FROM matriculas WHERE id = ?");
        $stmt->execute([$idMatricula]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao verificar dados da matrícula: " . $e->getMessage());
        return null;
    }
}

// Função para verificar se curso é um pacote
function verificarSeCursoEPacote($pdo, $idMatricula)
{
    try {
        $stmt = $pdo->prepare("SELECT pacote FROM matriculas WHERE id = ?");
        $stmt->execute([$idMatricula]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['pacote'] : null;
    } catch (PDOException $e) {
        error_log("Erro ao verificar se curso é pacote: " . $e->getMessage());
        return null;
    }
}

// Função para ativar cursos do pacote
function ativarCursosDoPacote($pdo, $idCurso, $alunoId)
{
    try {
        logMessage("Matrícula é um pacote. Iniciando liberação automática dos cursos individuais.");

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
                cp.id_pacote = {$idCurso}");

        // Verifica se há registros na tabela temporária
        $query_count = $pdo->query("SELECT COUNT(*) FROM temp_cursos_pacote");
        $total_registros = $query_count->fetchColumn();

        logMessage("Encontrados {$total_registros} cursos no pacote {$idCurso}");

        if ($total_registros > 0) {
            $pdo->query("CREATE TEMPORARY TABLE temp_matriculas_existentes AS
                SELECT id_curso
                FROM matriculas
                WHERE aluno = {$alunoId} AND id_pacote = {$idCurso}");

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
                ':aluno_id' => $alunoId,
                ':id_curso' => $idCurso
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
            return true;
        } else {
            logMessage("Nenhum curso encontrado para este pacote");
            return false;
        }
    } catch (PDOException $e) {
        logMessage("Erro ao liberar cursos do pacote: " . $e->getMessage());
        return false;
    }
}

// Processar webhook
try {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        exit;
    }

    // Ler dados do POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido']);
        exit;
    }

    // Verificar se existe o array 'pix' nos dados
    if (!isset($data['pix']) || !is_array($data['pix']) || empty($data['pix'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados PIX não encontrados']);
        exit;
    }

    $receivedAt = date('Y-m-d H:i:s');
    $processedCount = 0;

    // Processar cada transação PIX
    foreach ($data['pix'] as $pixData) {
        if (!isset($pixData['txid'])) {
            error_log("TXID não encontrado em um dos registros PIX");
            continue;
        }

        $txid = $pixData['txid'];

        try {
            // Consultar dados da transação
            $transacaoData = consultarTransacaoPix($txid);

            if (!$transacaoData) {
                error_log("Não foi possível consultar dados para TXID: $txid");
                logWebhook($pdo, 'pix_error', json_encode(['txid' => $txid, 'error' => 'Consulta falhou']), $receivedAt);
                continue;
            }

            $status = $transacaoData['status'] ?? '';

            if ($status === 'CONCLUIDA') {
                // Transação concluída - processar pagamento
                $pdo->beginTransaction();

                try {
                    // Atualizar status do pagamento PIX
                    if (!atualizarStatusPagamentoPix($pdo, $txid, 'CONCLUIDA')) {
                        throw new Exception("Falha ao atualizar pagamento PIX");
                    }

                    // Buscar id_matricula
                    $idMatricula = buscarIdMatricula($pdo, $txid);

                    if ($idMatricula) {
                        // Atualizar status da matrícula
                        if (!atualizarStatusMatricula($pdo, $idMatricula, 'Matriculado')) {
                            throw new Exception("Falha ao atualizar matrícula");
                        }

                        // Verificar se a matrícula é de um pacote e ativar cursos individuais
                        $dadosMatricula = verificarDadosMatricula($pdo, $idMatricula);

                        if ($dadosMatricula) {
                            $idCurso = $dadosMatricula['id_curso'];
                            $alunoId = $dadosMatricula['aluno'];

                            // Verificar se o curso é um pacote
                            $pacote = verificarSeCursoEPacote($pdo, $idMatricula);

                            if ($pacote === 'Sim') {
                                logMessage("Detectado pagamento de pacote. TXID: $txid, Curso: $idCurso, Aluno: $alunoId");

                                if (!ativarCursosDoPacote($pdo, $idCurso, $alunoId)) {
                                    logMessage("Falha ao ativar cursos do pacote para TXID: $txid");
                                } else {
                                    logMessage("Cursos do pacote ativados com sucesso para TXID: $txid");
                                }
                            }
                        }
                    } else {
                        error_log("ID da matrícula não encontrado para TXID: $txid");
                    }

                    $pdo->commit();
                    $processedCount++;

                    // Log de sucesso
                    logWebhook($pdo, 'pix_concluida', json_encode([
                        'txid' => $txid,
                        'id_matricula' => $idMatricula,
                        'status' => 'processado_com_sucesso'
                    ]), $receivedAt);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("Erro ao processar transação concluída: " . $e->getMessage());
                    logWebhook($pdo, 'pix_error', json_encode([
                        'txid' => $txid,
                        'error' => $e->getMessage()
                    ]), $receivedAt);
                }

            } else {
                // Status diferente de CONCLUIDA - apenas fazer log
                logWebhook($pdo, 'pix_status_' . strtolower($status), json_encode([
                    'txid' => $txid,
                    'status' => $status,
                    'dados_completos' => $transacaoData
                ]), $receivedAt);

                $processedCount++;
            }

        } catch (Exception $e) {
            error_log("Erro ao processar TXID $txid: " . $e->getMessage());
            logWebhook($pdo, 'pix_error', json_encode([
                'txid' => $txid,
                'error' => $e->getMessage()
            ]), $receivedAt);
        }
    }

    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'processed' => $processedCount,
        'pacote' => $pacote,
        'total_received' => count($data['pix'])
    ]);

} catch (Exception $e) {
    error_log("Erro geral no processamento do webhook: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}


?>
