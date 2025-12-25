<?php
require_once('../../../vendor/autoload.php');
require_once("../../conexao.php");

require_once '../../../efi/boleto_p.php';

$options = require_once '../../../efi/options.php';

// Configurações da EFI
$config = [
    'client_id' => $options['clientId'],
    'client_secret' => $options['clientSecret'],
    'certificate_path' => $options['certificate'], // Apenas para PIX
    'chave_pix' => $options['pixKey'] ?? '', // Sua chave PIX
    'sandbox' => $options['sandbox'] // true para teste, false para produção
];

$data = $_POST;
$pay = $data['payload'];
$payload = json_decode($pay, true);

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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['valor_parcela'])) {
        //CHAVE ASAAS
        $chaveAsaas = '';
        //DADOS RECEBIDOS
        $valor_parcela = $_POST['valor_parcela'];
        $id_parcela = isset($_POST['id_parcela']) ? (int) $_POST['id_parcela'] : 0;
        $id_boleto_parcelado = isset($_POST['id_boleto_parcelado']) ? (int) $_POST['id_boleto_parcelado'] : 0;
        $ordem_parcela = isset($_POST['ordem_parcela']) ? (int) $_POST['ordem_parcela'] : 0;
        $id_matricula = (int) $_POST['id_matricula']; // USAR O ID DA MATRÍCULA PASSADO VIA POST



        //INFORMAÇÕES DO ALUNO
        $id_do_aluno = $_POST['id_aluno'];



        $consulta_dados_aluno = $pdo->prepare("SELECT * FROM usuarios where id = :id");
        $consulta_dados_aluno->execute([':id' => $id_do_aluno]);
        $resposta_dados_aluno = $consulta_dados_aluno->fetchAll(PDO::FETCH_ASSOC);

        if (@count($resposta_dados_aluno) > 0) {
            $id_pessoa = $resposta_dados_aluno[0]['id_pessoa'];
            $consulta_aluno = $pdo->prepare("SELECT * FROM alunos where id = :id");
            $consulta_aluno->execute([':id' => $id_pessoa]);
            $resposta_consulta_aluno = $consulta_aluno->fetchAll(PDO::FETCH_ASSOC);

            if (@count($resposta_consulta_aluno) > 0) {
                $nome_aluno = normalizarUnicode($resposta_consulta_aluno[0]['nome'] ?? '');
                $email_aluno = $resposta_consulta_aluno[0]['email'] ?? '';
                $phone_aluno = preg_replace('/\\D/', '', $resposta_consulta_aluno[0]['telefone'] ?? '');
                $cpf_aluno = preg_replace('/\\D/', '', $resposta_consulta_aluno[0]['cpf'] ?? '');
                $nivel_responsavel_pelo_cadastro_do_aluno = $resposta_consulta_aluno[0]['usuario'] ?? '';
            }
        }

        // BUSCAR DADOS DA MATRÍCULA ESPECÍFICA PASSADA VIA POST
        $consulta_matricula = $pdo->prepare("
    SELECT 
        matriculas.*,
        CASE 
            WHEN matriculas.pacote = 'Sim' THEN pacotes.nome 
            ELSE cursos.nome 
        END as nome_item,
        CASE 
            WHEN matriculas.pacote = 'Sim' THEN pacotes.valor 
            ELSE cursos.valor 
        END as valor_item,
        CASE 
            WHEN matriculas.pacote = 'Sim' THEN 'pacote' 
            ELSE 'curso' 
        END as tipo_item
    FROM matriculas 
    LEFT JOIN cursos ON cursos.id = matriculas.id_curso AND matriculas.pacote != 'Sim'
    LEFT JOIN pacotes ON pacotes.id = matriculas.id_curso AND matriculas.pacote = 'Sim'
    WHERE matriculas.id = :id_matricula AND matriculas.aluno = :aluno
");

        $consulta_matricula->execute(['id_matricula' => $id_matricula, 'aluno' => $id_do_aluno]);
        $resposta_matricula = $consulta_matricula->fetchAll(PDO::FETCH_ASSOC);

        if (empty($nome_aluno)) {
            $nome_aluno = normalizarUnicode($resposta_dados_aluno[0]['nome'] ?? '');
        }
        if (empty($email_aluno)) {
            $email_aluno = $resposta_dados_aluno[0]['usuario'] ?? '';
        }
        if (empty($cpf_aluno)) {
            $cpf_aluno = preg_replace('/\\D/', '', $resposta_dados_aluno[0]['cpf'] ?? '');
        }
        if (empty($phone_aluno)) {
            $phone_aluno = preg_replace('/\\D/', '', $resposta_dados_aluno[0]['telefone'] ?? '');
        }
        if (!is_array($payload)) {
            die("Erro: Payload do boleto invalido.");
        }
        $payload['nome'] = $nome_aluno ?? ($payload['nome'] ?? '');
        $payload['email'] = $email_aluno ?? ($payload['email'] ?? '');
        $payload['cpf'] = $cpf_aluno ?? ($payload['cpf'] ?? '');
        $payload['telefone'] = ($phone_aluno ?? '') ?: ($payload['telefone'] ?? '69999694538');

        if (count($resposta_matricula) == 0) {
            die("Erro: Matrícula não encontrada ou não pertence ao aluno logado.");
        }

      

        if (count($resposta_matricula) == 0) {
            die("Erro: Matrícula não encontrada ou não pertence ao aluno logado.");
        }

        $id_curso = $resposta_matricula[0]['id_curso'];

        $clienteGuzzle = new \GuzzleHttp\Client();

        // BUSCA DADOS DA MATRICULA ESPECÍFICA
        $consulta_dados_matricula = $pdo->prepare("SELECT * FROM matriculas where id = :id and aluno = :aluno");
        $consulta_dados_matricula->execute([':id' => $id_matricula, ':aluno' => $id_do_aluno]);
        $resposta_dados_matricula = $consulta_dados_matricula->fetchAll(PDO::FETCH_ASSOC);

        try {
            $boletoPayment = new EFIBoletoPayment(
                $config['client_id'],
                $config['client_secret'],
                $config['sandbox']
            );

            $resultado = $boletoPayment->createBoletoCharge($payload);

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

            try {
               $pdo = new PDO("mysql:host=$servidor;dbname=$banco", $usuario, $senha);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                //parcelas_geradas_por_boleto
                if ($ordem_parcela > 0) {
                    $where_boleto = '';
                    $params = [
                        ':id_asaas' => $resultado['payment_data']['data']['payment']['banking_billet']['pdf']['charge'],
                        ':charge_id' => $resultado['charge_id'],
                        ':id_matricula' => $id_matricula,
                        ':transaction_receipt_url' => $resultado['payment_data']['data']['payment']['banking_billet']['pix']['qrcode'],
                        ':ordem_parcela' => $ordem_parcela,
                    ];
                    if ($id_boleto_parcelado > 0) {
                        $where_boleto = ' AND id_boleto_parcelado = :id_boleto_parcelado';
                        $params[':id_boleto_parcelado'] = $id_boleto_parcelado;
                    }
                    $sql = "UPDATE parcelas_geradas_por_boleto
                            SET id_asaas = :id_asaas, charge_id = :charge_id, id_matricula = :id_matricula, transaction_receipt_url = :transaction_receipt_url
                            WHERE id_matricula = :id_matricula AND ordem_parcela = :ordem_parcela{$where_boleto}";
                } elseif ($id_parcela > 0) {
                    $sql = "UPDATE parcelas_geradas_por_boleto
                            SET id_asaas = :id_asaas, charge_id = :charge_id, id_matricula = :id_matricula, transaction_receipt_url = :transaction_receipt_url
                            WHERE id = :id_parcela AND id_matricula = :id_matricula";
                    $params = [
                        ':id_asaas' => $resultado['payment_data']['data']['payment']['banking_billet']['pdf']['charge'],
                        ':charge_id' => $resultado['charge_id'],
                        ':id_matricula' => $id_matricula,
                        ':transaction_receipt_url' => $resultado['payment_data']['data']['payment']['banking_billet']['pix']['qrcode'],
                        ':id_parcela' => $id_parcela,
                    ];
                } else {
                    die("Erro: Parcela invalida.");
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                //matriculas
                $sql_matricula = "UPDATE matriculas SET id_asaas = :id_asaas, forma_pgto = :forma_pgto WHERE id = :id";

                $stmt = $pdo->prepare($sql_matricula);
                $stmt->execute([
                    ':id_asaas' => $resultado['payment_data']['data']['payment']['banking_billet']['pdf']['charge'],
                    ':forma_pgto' => 'BOLETO',
                    ':id' => $id_matricula,
                ]);

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
                        <p class="text-center text-lg mb-2">Valor: <span class="font-bold">R$ ' . number_format($resultado['total'], 2, ',', '.') . '</span></p>
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
                        <a href="/sistema/painel-aluno/index.php?pagina=parcelas" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
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

            } catch (PDOException $e) {
                echo "Erro: " . $e->getMessage();
            }

            exit();
        } catch (RequestException $e) {
            echo "Erro na requisição: " . $e->getMessage();
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage();
        }
    }
}
?>
