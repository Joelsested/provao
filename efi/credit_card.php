<?php

require_once('../vendor/autoload.php');
require_once("../sistema/conexao.php");
require_once("../config/env.php");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

@session_start();

$id_aluno = $_GET['id_aluno'];
$id = $_GET['id'];
$nome_curso = $_GET['nome_curso'];
$modoRecorrente = (($_GET['modo'] ?? '') === 'recorrente');
$subscriptionIdRecorrente = (int) ($_GET['subscription_id'] ?? 0);
$somenteRecorrencia = $modoRecorrente && $subscriptionIdRecorrente > 0;

$sandboxRaw = strtolower((string) env('EFI_CARD_SANDBOX', env('EFI_SANDBOX', 'false')));
$efiCardSandbox = in_array($sandboxRaw, ['1', 'true', 'yes', 'on', 'sim'], true);
$efiEnvironment = $efiCardSandbox ? 'sandbox' : 'production';

$defaultAccount = trim((string) env('EFI_CARD_ACCOUNT', ''));
$accountByEnv = (string) env(
    $efiCardSandbox ? 'EFI_CARD_ACCOUNT_HOMOLOG' : 'EFI_CARD_ACCOUNT_PROD',
    $defaultAccount
);

// Importante: o token do cartao deve ser gerado com o identificador de conta da aplicacao
// (não usar payee_code/wallet_id aqui).
$efiCardAccount = trim($accountByEnv);
$efiCardAccount = preg_replace('/\s+/', '', $efiCardAccount);
if (preg_match('/^\d+$/', (string) $efiCardAccount)) {
    $efiCardAccount = '';
}



//BUSCA DADOS DA MATRICULA
$query = $pdo->query("SELECT * FROM matriculas where id = '$id' and aluno = '$id_aluno' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$response = $res[0];

// Regra de juros/taxas do cartao para empresa receber o valor integral do curso/pacote.
$valorBase = (float) ($response['subtotal'] ?? 0);
if ($valorBase <= 0) {
    $valorBase = (float) ($response['valor'] ?? 0);
}
$valorLiquidoCurso = max($valorBase, 0);
$parcelaRecorrenteAberta = null;

if ($somenteRecorrencia) {
    try {
        $stmtParcela = $pdo->prepare("
            SELECT ep.numero_parcela, ep.valor_parcela, ep.status, ep.vencimento
            FROM efi_assinaturas_cartao ea
            INNER JOIN efi_assinaturas_cartao_parcelas ep ON ep.id_assinatura = ea.id
            WHERE ea.id_matricula = :id_matricula
              AND ea.subscription_id = :subscription_id
              AND ep.status IN ('PENDENTE', 'ATRASADA')
            ORDER BY ep.numero_parcela ASC
            LIMIT 1
        ");
        $stmtParcela->execute([
            ':id_matricula' => (int) $id,
            ':subscription_id' => $subscriptionIdRecorrente,
        ]);
        $parcelaRecorrenteAberta = $stmtParcela->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($parcelaRecorrenteAberta) {
            $valorLiquidoCurso = max((float) ($parcelaRecorrenteAberta['valor_parcela'] ?? 0), 0);
        }
    } catch (Throwable $e) {
        $parcelaRecorrenteAberta = null;
    }
}

$taxaFixaCartao = (float) env('EFI_CARD_FEE_FIXED', '0.29');
$taxaPercentualCartao = ((float) env('EFI_CARD_FEE_PERCENT', '4.99')) / 100;
$jurosMensalParcelado = ((float) env('EFI_CARD_INTEREST_MONTHLY', '1.99')) / 100;

$calcularTotalCartaoCliente = static function (
    float $valorLiquido,
    int $parcelas,
    float $taxaFixa,
    float $taxaPercentual,
    float $jurosMensal
): float {
    $parcelas = max(1, $parcelas);
    $denominador = 1 - $taxaPercentual;
    $baseBruta = $denominador > 0 ? (($valorLiquido + $taxaFixa) / $denominador) : $valorLiquido;
    if ($parcelas > 1) {
        $baseBruta *= pow(1 + $jurosMensal, $parcelas - 1);
    }
    return round(max($baseBruta, 0), 2);
};

// Valor inicial mostrado: 1x (cartao de credito padrao).
$valorCheckout = $calcularTotalCartaoCliente(
    $valorLiquidoCurso,
    1,
    $taxaFixaCartao,
    $taxaPercentualCartao,
    $jurosMensalParcelado
);
$valorCheckout = round($valorCheckout, 2);
$valorCheckoutFmt = number_format($valorCheckout, 2, ',', '.');



// echo '<pre>';
// echo json_encode($res[0], JSON_PRETTY_PRINT);
// echo '</pre>';
// return;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Wizard - EFI Pay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/efipay/js-payment-token-efi/dist/payment-token-efi-umd.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'efi-blue': '#1e40af',
                        'efi-light-blue': '#3b82f6',
                    },
                    animation: {
                        'spin-slow': 'spin 2s linear infinite',
                        'bounce-slow': 'bounce 1.5s infinite',
                        'pulse-success': 'pulse-success 2s ease-in-out infinite',
                        'shake': 'shake 0.5s ease-in-out',
                    },
                    keyframes: {
                        'pulse-success': {
                            '0%, 100%': { transform: 'scale(1)', opacity: '1' },
                            '50%': { transform: 'scale(1.1)', opacity: '0.8' }
                        },
                        'shake': {
                            '0%, 100%': { transform: 'translateX(0)' },
                            '10%, 30%, 50%, 70%, 90%': { transform: 'translateX(-5px)' },
                            '20%, 40%, 60%, 80%': { transform: 'translateX(5px)' }
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Progress Bar -->
            <div class="lg:col-span-3 mb-4">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div id="progressBar" class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div
                                class="step-indicator active flex items-center justify-center w-8 h-8 rounded-full bg-efi-blue text-white text-sm font-medium">
                                1</div>
                            <span class="ml-2 text-sm font-medium text-gray-900">Método de Pagamento</span>
                        </div>
                        <div class="flex-1 mx-4 h-1 bg-gray-200 rounded">
                            <div id="progressFill" class="h-full bg-efi-blue rounded transition-all duration-500"
                                style="width: 25%"></div>
                        </div>
                        <div class="flex items-center">
                            <div
                                class="step-indicator flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-400 text-sm font-medium">
                                2</div>
                            <span class="ml-2 text-sm font-medium text-gray-400">Dados Pessoais</span>
                        </div>
                        <div class="flex-1 mx-4 h-1 bg-gray-200 rounded"></div>
                        <div class="flex items-center">
                            <div
                                class="step-indicator flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-400 text-sm font-medium">
                                3</div>
                            <span class="ml-2 text-sm font-medium text-gray-400">Confirmação</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo do Pedido -->
            <div class="bg-white rounded-lg shadow-md p-6 h-fit">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumo do Pedido</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Curso:</span>
                        <span class="font-medium"><?php echo $nome_curso; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Valor:</span>
                        <span id="valorResumoDisplay" class="font-medium text-green-600">R$ <?php echo $valorCheckoutFmt; ?></span>
                    </div>

                    <!-- Método de Pagamento Selecionado -->
                    <div id="paymentMethodSummary" class="hidden">
                        <hr class="my-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">M&eacute;todo:</span>
                            <span id="selectedMethodText" class="font-medium text-blue-600"></span>
                        </div>
                        <div id="paymentDetails" class="text-sm text-gray-500 mt-1"></div>
                    </div>

                    <hr class="my-3">
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Total:</span>
                        <span id="totalResumoDisplay" class="text-green-600">R$ <?php echo $valorCheckoutFmt; ?></span>
                    </div>
                    <?php if ($somenteRecorrencia): ?>
                        <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                            Regularização de recorrência: será cobrada a próxima parcela pendente/atrasada com o novo cartão.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Indicador de Segurança -->
                <div class="mt-6 p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                        <span class="text-sm text-green-800">Pagamento 100% seguro</span>
                    </div>
                    
                </div>
            </div>



            <!-- Formulário Step-by-Step -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                <form id="wizardForm">
                    <input type="hidden" id="credit_card_token" name="credit_card_token">
                    <input type="hidden" id="id_matricula" value="<?php echo (int) $id; ?>" name="id_matricula">
                    <input type="hidden" id="id_do_curso_pag" value="<?php echo $response['id_curso'] ?>"
                        name="id_do_curso_pag">
                    <input type="hidden" id="nome_curso_titulo" value="<?php echo $nome_curso ?>"
                        name="nome_curso_titulo">
                    <!-- Step 1: Método de Pagamento -->
                    <div id="step1" class="step-content">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6">Escolha o método de pagamento</h2>

                        <div class="space-y-3">


                            <!-- Cartão de Crédito -->
                            <label
                                class="payment-method-option flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo $somenteRecorrencia ? 'opacity-50' : ''; ?>">
                                <input type="radio" name="payment_method" value="credit_card"
                                    class="w-4 h-4 text-efi-blue" <?php echo $somenteRecorrencia ? 'disabled' : 'checked'; ?>>
                                <div class="ml-3 flex items-center flex-1">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">Cartão de crédito</div>
                                        <div class="text-sm text-gray-500">Em até 12x, aprovado em segundos</div>
                                    </div>
                                    <div class="flex space-x-1">
                                        <div>
                                            <i class="fab fa-cc-mastercard text-yellow-600 text-2xl"></i>
                                        </div>
                                        <div>
                                            <i class="fab fa-cc-visa text-blue-600 text-2xl"></i>
                                        </div>
                                        <div>
                                            <i class="fab fa-cc-amex text-gray-600 text-2xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- Pagamento Recorrente -->
                            <label
                                class="payment-method-option flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="payment_method" value="debit_card"
                                    class="w-4 h-4 text-efi-blue" <?php echo $somenteRecorrencia ? 'checked' : ''; ?>>
                                <div class="ml-3 flex items-center flex-1">
                                    <div
                                        class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">Pagamento Recorrente</div>
                                        <div class="text-sm text-gray-500">Pagamento recorrente, aprovação imediata
                                        </div>

                                    </div>
                                    <div class="flex space-x-1">
                                        <div>
                                            <i class="fab fa-cc-mastercard text-yellow-600 text-2xl"></i>
                                        </div>
                                        <div>
                                            <i class="fab fa-cc-visa text-blue-600 text-2xl"></i>
                                        </div>
                                        <div>
                                            <i class="fab fa-cc-amex text-gray-600 text-2xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </label>


                        </div>

                        <div class="flex justify-end mt-8">
                            <button type="button" id="nextStep1"
                                class="bg-efi-blue hover:bg-efi-light-blue text-white font-medium py-3 px-8 rounded-lg transition-colors">
                                Continuar
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Dados Pessoais -->
                    <div id="step2" class="step-content hidden">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6">Dados pessoais</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                                <input type="email" name="email" required id="email"
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                    placeholder="seu@email.com">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                                    <input type="text" name="cpf" required
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="000.000.000-00" maxlength="14">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                    <input type="text" name="phone" required
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="(11) 99999-9999" maxlength="15">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo *</label>
                                <input type="text" name="customer_name" required
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                    placeholder="Seu nome completo">
                            </div>


                        </div>

                        <!-- Dados do cart&atilde;o) -->
                        <div id="cardDataSection" class="space-y-4 mt-6 hidden">
                            <h3 class="text-lg font-medium text-gray-900 border-t pt-4">Dados do Cartão</h3>

                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Número do cartão *</label>
                                <input type="text" name="card_number" id="card_number" required
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                    placeholder="1234 1234 1234 1234" maxlength="19">

                                <div class="absolute right-4 top-8" id="cardIcon">
                                    <div class="hidden group relative" id="mastercard">
                                        <i class="fab fa-cc-mastercard text-yellow-600 text-2xl"></i>
                                        <span class="absolute -top-8 left-1/2 -translate-x-1/2 
                     bg-black text-white text-xs px-2 py-1 rounded opacity-0 
                     group-hover:opacity-100 transition">
                                            Mastercard
                                        </span>
                                    </div>

                                    <div class="hidden group relative" id="visa">
                                        <i class="fab fa-cc-visa text-blue-600 text-2xl"></i>
                                        <span class="absolute -top-8 left-1/2 -translate-x-1/2 
                     bg-black text-white text-xs px-2 py-1 rounded opacity-0 
                     group-hover:opacity-100 transition">
                                            Visa
                                        </span>
                                    </div>

                                    <div class="hidden group relative" id="amex">
                                        <i class="fab fa-cc-amex text-gray-600 text-2xl"></i>
                                        <span class="absolute -top-8 left-1/2 -translate-x-1/2 
                     bg-black text-white text-xs whitespace-nowrap px-2 py-1 rounded opacity-0 
                     group-hover:opacity-100 transition">
                                            American Express
                                        </span>
                                    </div>

                                    <div class="hidden group relative" id="notFound">
                                        <i class="fa fa-credit-card-alt text-green-600 text-2xl"></i>
                                        <span class="absolute -top-8 left-1/2 -translate-x-1/2 
                     bg-black text-white text-xs whitespace-nowrap px-2 py-1 rounded opacity-0 
                     group-hover:opacity-100 transition">
                                            Bandeira não identificada
                                        </span>
                                    </div>
                                </div>

                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Validade *</label>
                                    <input type="text" name="expiry" required
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="MM/AAAA" maxlength="7">
                                </div>
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CVV *</label>
                                    <input type="text" name="security_code" required
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent pr-10"
                                        placeholder="123" maxlength="4">
                                    <i class="fas fa-question-circle absolute right-3 top-10 text-gray-400 cursor-help"
                                        title="Código de 3 ou 4 digitos no verso do cartão"></i>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome no cartão *</label>
                                <input type="text" name="cardholder_name" required
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                    placeholder="Nome como está no cartão">
                            </div>


                            <div id="installments_section">
                                <select name="installments" id="installments" required
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent">
                                </select>
                            </div>



                            <div class="mt-10">
                                <label class="block text-sm font-medium text-gray-700 mb-1">CEP *</label>
                                <input type="text" name="cep" required id="cep"
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                    placeholder="00000-000" maxlength="9">
                            </div>

                            <!-- Campos adicionais inicialmente ocultos -->
                            <div id="addressFields" class="space-y-2 hidden">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Endereço *</label>
                                    <input type="text" name="address" required id="address"
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="Endereço">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                                    <input type="text" name="number" required id="number"
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="Número">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                                    <input type="text" name="neighborhood" required id="neighborhood"
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="Bairro">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                                    <input type="text" name="city" required id="city"
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="Cidade">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                                    <input type="text" name="state" required id="state"
                                        class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-efi-blue focus:border-transparent"
                                        placeholder="Estado">
                                </div>
                            </div>



                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button" id="backStep2"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 px-8 rounded-lg transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Voltar
                            </button>
                            <button type="button" id="nextStep2"
                                class="bg-efi-blue hover:bg-efi-light-blue text-white font-medium py-3 px-8 rounded-lg transition-colors">
                                Continuar
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Confirmação -->
                    <div id="step3" class="step-content hidden">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6">Confirme seus dados</h2>

                        <div id="confirmationDetails" class="space-y-6">
                            <!-- Dados serão preenchidos via JavaScript -->
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button" id="backStep3"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 px-8 rounded-lg transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Voltar
                            </button>
                            <button type="button" id="finalizePayment"
                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition-colors">
                                <i class="fas fa-lock mr-2"></i>
                                Finalizar Pagamento
                            </button>
                        </div>
                    </div>

                    <!-- Loading Screen -->
                    <div id="loadingScreen" class="step-content hidden text-center">
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="relative mb-6">
                                <div
                                    class="w-20 h-20 border-4 border-gray-200 border-t-efi-blue rounded-full animate-spin">
                                </div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <i class="fas fa-credit-card text-efi-blue text-xl animate-pulse"></i>
                                </div>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-2">Processando pagamento...</h2>
                            <p class="text-gray-600">Aguarde enquanto confirmamos sua transação</p>
                            <div class="mt-4 flex space-x-1">
                                <div class="w-2 h-2 bg-efi-blue rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-efi-blue rounded-full animate-bounce"
                                    style="animation-delay: 0.1s;"></div>
                                <div class="w-2 h-2 bg-efi-blue rounded-full animate-bounce"
                                    style="animation-delay: 0.2s;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Success Screen -->
                    <div id="successScreen" class="step-content hidden text-center">
                        <div class="flex flex-col items-center justify-center py-12">
                            <!-- Success Animation -->
                            <div class="relative mb-6">
                                <div
                                    class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center animate-pulse-success">
                                    <i class="fas fa-check text-green-600 text-3xl"></i>
                                </div>
                                <div
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center animate-bounce">
                                    <i class="fas fa-star text-white text-xs"></i>
                                </div>
                            </div>
                            <h2 class="text-2xl font-bold text-green-600 mb-2">Pagamento Aprovado!</h2>
                            <p class="text-gray-600 mb-6">Sua transação foi processada com sucesso.</p>

                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 max-w-md w-full">
                                <div class="text-sm space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Transa&ccedil;&atilde;o:</span>
                                        <span class="font-mono text-green-700" id="transactionId">TX-123456789</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">M&eacute;todo:</span>
                                        <span id="metodoPagoSucesso" class="font-semibold text-green-700">Cart&atilde;o</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Valor:</span>
                                        <span id="valorPagoSucesso" class="font-semibold text-green-700">R$ <?php echo $valorCheckoutFmt; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-center gap-3">
                                <button type="button" id="downloadReceiptBtn"
                                    class="bg-efi-blue hover:bg-efi-light-blue text-white font-medium py-3 px-8 rounded-lg transition-colors">
                                    <i class="fas fa-download mr-2"></i>
                                    Baixar Comprovante
                                </button>
                                <button type="button" id="closeCheckoutBtn"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 px-8 rounded-lg transition-colors">
                                    <i class="fas fa-times mr-2"></i>
                                    Fechar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Error Screen -->
                    <div id="errorScreen" class="step-content hidden text-center">
                        <div class="flex flex-col items-center justify-center py-12">
                            <!-- Error Animation -->
                            <div class="relative mb-6">
                                <div
                                    class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center animate-shake">
                                    <i class="fas fa-times text-red-600 text-3xl"></i>
                                </div>
                                <div
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation text-white text-xs"></i>
                                </div>
                            </div>
                            <h2 class="text-2xl font-bold text-red-600 mb-2">Pagamento Recusado</h2>
                            <p class="text-gray-600 mb-6">Não foi possível processar sua transação.</p>

                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 max-w-md w-full">
                                <div class="text-sm text-red-700">
                                    <p id="errorMessage">Verifique os Dados do cartão de
                                        pagamento.</p>
                                </div>
                            </div>

                            <div class="flex space-x-4">
                                <button type="button" id="retryPayment"
                                    class="bg-efi-blue hover:bg-efi-light-blue text-white font-medium py-3 px-6 rounded-lg transition-colors">
                                    <i class="fas fa-redo mr-2"></i>
                                    Tentar Novamente
                                </button>
                                <button type="button" id="changeMethod"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors">
                                    <i class="fas fa-exchange-alt mr-2"></i>
                                    Alterar Método
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <script>
        const cepInput = document.getElementById('cep');
        const addressFields = document.getElementById('addressFields');

        // Função de máscara de CEP
        cepInput.addEventListener('input', () => {
            let value = cepInput.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            }
            cepInput.value = value;

            if (value.length === 9) {
                // CEP completo: buscar endereço
                buscarCep(value.replace('-', ''));
            } else {
                // CEP incompleto: limpar e esconder
                limparCampos();
            }
        });

        // Função para consultar o ViaCEP
        async function buscarCep(cep) {
            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();

                if (data.erro) {
                    alert("CEP não encontrado!");
                    limparCampos();
                    return;
                }

                // Preenche os campos
                document.getElementById('address').value = data.logradouro || '';
                document.getElementById('neighborhood').value = data.bairro || '';
                document.getElementById('city').value = data.localidade || '';
                document.getElementById('state').value = data.uf || '';

                // Mostra os campos
                addressFields.classList.remove('hidden');

            } catch (error) {
                console.error("Erro ao buscar CEP:", error);
                limparCampos();
            }
        }

        // Função para limpar e esconder campos
        function limparCampos() {
            document.getElementById('address').value = '';
            document.getElementById('neighborhood').value = '';
            document.getElementById('city').value = '';
            document.getElementById('state').value = '';
            addressFields.classList.add('hidden');
        }
    </script>



    <script>

        let cardBrand = null;

        // Função para identificar a bandeira
        async function identifyBrand(cardNumber) {
            const brandIcon = document.getElementById('cardIcon');
            const mastercard = document.getElementById('mastercard');
            const visa = document.getElementById('visa');
            const amex = document.getElementById('amex');
            const notFound = document.getElementById('notFound');

            try {
                const brand = await EfiPay.CreditCard
                    .setCardNumber(cardNumber.replace(/\s+/g, '')) // remove espaços
                    .verifyCardBrand();


                cardBrand = brand.toLowerCase(); // salvar para usar no getInstallments

                switch (cardBrand) {
                    case 'visa':
                        visa.classList.remove('hidden');
                        mastercard.classList.add('hidden');
                        amex.classList.add('hidden');
                        notFound.classList.add('hidden');
                        break;
                    case 'mastercard':
                        visa.classList.add('hidden');
                        mastercard.classList.remove('hidden');
                        amex.classList.add('hidden');
                        notFound.classList.add('hidden');
                        break;
                    case 'amex':
                        visa.classList.add('hidden');
                        mastercard.classList.add('hidden');
                        amex.classList.remove('hidden');
                        notFound.classList.add('hidden');
                        break;
                    default:
                        visa.classList.add('hidden');
                        mastercard.classList.add('hidden');
                        amex.classList.add('hidden');
                        notFound.classList.remove('hidden');
                }

                // Atualiza as parcelas com a bandeira correta
                getInstallments();
            } catch (error) {
                console.error("Erro ao identificar bandeira:", error);
                cardBrand = null;
            }
        }

        // Evento para chamar a identificação quando terminar de digitar
        const cardInput = document.getElementById('card_number');
        cardInput.addEventListener('input', () => {
            const value = cardInput.value;
            if (value.replace(/\s+/g, '').length >= 6) { // verificar se pelo menos 6 dígitos foram digitados
                identifyBrand(value);
            }
        });

        const efiEnvironment = <?php echo json_encode($efiEnvironment); ?>;
        const efiCardAccount = <?php echo json_encode($efiCardAccount); ?>;
        const checkoutSomenteRecorrencia = <?php echo $somenteRecorrencia ? 'true' : 'false'; ?>;
        const checkoutSubscriptionId = <?php echo (int) $subscriptionIdRecorrente; ?>;
        const efiCardAccountError = 'Conta EFI do cartão não configurada. Defina EFI_CARD_ACCOUNT_HOMOLOG/EFI_CARD_ACCOUNT_PROD (ou EFI_CARD_ACCOUNT) no .env.';
        console.log('[EFI Checkout] Ambiente:', efiEnvironment, '| Conta:', efiCardAccount);

        // Função para buscar parcelas
        // Funcao para buscar parcelas
        const valorLiquidoCurso = <?php echo json_encode($valorLiquidoCurso); ?>;
        const taxaFixaCartao = <?php echo json_encode($taxaFixaCartao); ?>;
        const taxaPercentualCartao = <?php echo json_encode($taxaPercentualCartao); ?>;
        const jurosMensalParcelado = <?php echo json_encode($jurosMensalParcelado); ?>;

        function formatarMoedaBR(valor) {
            return Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calcularTotalClienteCartao(valorLiquido, parcelas) {
            const p = Math.max(1, Number(parcelas || 1));
            const den = 1 - Number(taxaPercentualCartao || 0);
            const baseBruta = den > 0
                ? ((Number(valorLiquido || 0) + Number(taxaFixaCartao || 0)) / den)
                : Number(valorLiquido || 0);
            const total = p > 1
                ? baseBruta * Math.pow(1 + Number(jurosMensalParcelado || 0), p - 1)
                : baseBruta;
            return Math.max(0, Math.round(total * 100) / 100);
        }

        function atualizarTotaisVisuais(total) {
            const texto = `R$ ${formatarMoedaBR(total)}`;
            const valorResumo = document.getElementById('valorResumoDisplay');
            const totalResumo = document.getElementById('totalResumoDisplay');
            const valorPago = document.getElementById('valorPagoSucesso');
            if (valorResumo) valorResumo.textContent = texto;
            if (totalResumo) totalResumo.textContent = texto;
            if (valorPago) valorPago.textContent = texto;
            formData.total_value = total;
            formData.total_value_formatted = texto;
        }

        function parseCurrencyToNumber(value) {
            if (typeof value === 'number') {
                return Number.isFinite(value) ? value : NaN;
            }

            let s = String(value ?? '').trim();
            if (!s) return NaN;

            s = s.replace(/\s+/g, '').replace(/^R\$/i, '');

            const hasComma = s.indexOf(',') !== -1;
            const hasDot = s.indexOf('.') !== -1;

            if (hasComma && hasDot) {
                // Se a ultima virgula vier depois do ultimo ponto, assume formato BR: 1.234,56
                if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
                    s = s.replace(/\./g, '').replace(',', '.');
                } else {
                    // Formato EN: 1,234.56
                    s = s.replace(/,/g, '');
                }
            } else if (hasComma) {
                s = s.replace(',', '.');
            }

            return Number.parseFloat(s);
        }

        function extrairUrlComprovante(paymentData) {
            if (!paymentData || typeof paymentData !== 'object') {
                return '';
            }

            const candidatos = [
                paymentData.transaction_receipt_url,
                paymentData.receipt_url,
                paymentData.url,
                paymentData.link,
                paymentData.pdf,
                paymentData.links?.receipt,
                paymentData.links?.pdf,
                paymentData.data?.transaction_receipt_url,
                paymentData.data?.receipt_url
            ];

            for (const c of candidatos) {
                if (typeof c === 'string' && c.trim() !== '') {
                    return c.trim();
                }
            }

            return '';
        }

        function gerarComprovanteHtmlLocal() {
            if (!ultimoPagamentoAprovado) {
                return '';
            }

            const transacao = ultimoPagamentoAprovado.transactionId || 'N/A';
            const metodo = ultimoPagamentoAprovado.metodo || 'Cartão';
            const valor = ultimoPagamentoAprovado.valorFormatado || 'R$ 0,00';
            const curso = (document.querySelector('input[name="nome_curso_titulo"]')?.value || '').trim();
            const dataHora = ultimoPagamentoAprovado.dataHora || new Date().toLocaleString('pt-BR');

            return `<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Comprovante de Pagamento</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 32px; color: #222; }
    h1 { margin: 0 0 16px; font-size: 22px; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; max-width: 640px; }
    .row { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 10px 0; }
    .row:last-child { border-bottom: 0; }
    .label { color: #555; }
    .value { font-weight: 600; }
  </style>
</head>
<body>
  <h1>Comprovante de Pagamento</h1>
  <div class="card">
    <div class="row"><span class="label">Transação</span><span class="value">${transacao}</span></div>
    <div class="row"><span class="label">Método</span><span class="value">${metodo}</span></div>
    <div class="row"><span class="label">Curso/Pacote</span><span class="value">${curso || '-'}</span></div>
    <div class="row"><span class="label">Valor</span><span class="value">${valor}</span></div>
    <div class="row"><span class="label">Data/Hora</span><span class="value">${dataHora}</span></div>
    <div class="row"><span class="label">Status</span><span class="value">PAGO</span></div>
  </div>
</body>
</html>`;
        }

        async function getInstallments() {
            const select = document.getElementById('installments');
            if (checkoutSomenteRecorrencia) {
                const totalRecorrencia = Number(valorLiquidoCurso || 0);
                select.innerHTML = '';
                const option = document.createElement('option');
                option.value = '1';
                option.dataset.total = String(totalRecorrencia);
                option.text = `1x de R$ ${formatarMoedaBR(totalRecorrencia)} (regularização)`;
                select.appendChild(option);
                select.disabled = true;
                atualizarTotaisVisuais(totalRecorrencia);
                return;
            }

            select.disabled = false;
            const isRecorrente = formData.payment_method === 'debit_card';
            const minParcelas = isRecorrente ? 2 : 1;
            const maxParcelas = isRecorrente ? 6 : 12;

            select.innerHTML = '';

            if (!Number.isFinite(valorLiquidoCurso) || valorLiquidoCurso <= 0) {
                const option = document.createElement('option');
                option.value = '';
                option.text = 'Valor inválido para calcular parcelas';
                select.appendChild(option);
                return;
            }

            for (let parcela = minParcelas; parcela <= maxParcelas; parcela++) {
                const totalCliente = calcularTotalClienteCartao(valorLiquidoCurso, parcela);
                const valorParcela = totalCliente / parcela;
                const option = document.createElement('option');
                option.value = String(parcela);
                option.dataset.total = String(totalCliente);
                option.text = `${parcela}x de R$ ${formatarMoedaBR(valorParcela)} (total R$ ${formatarMoedaBR(totalCliente)})`;
                select.appendChild(option);
            }

            if (select.options.length > 0) {
                select.selectedIndex = 0;
                const totalInicial = parseCurrencyToNumber(select.options[0].dataset.total || '');
                if (Number.isFinite(totalInicial)) {
                    atualizarTotaisVisuais(totalInicial);
                }
            }
        }

        const installmentsSelect = document.getElementById('installments');
        if (installmentsSelect) {
            installmentsSelect.addEventListener('change', function () {
                const selecionada = this.options[this.selectedIndex];
                const totalSelecionado = parseCurrencyToNumber(selecionada?.dataset?.total || '');
                if (Number.isFinite(totalSelecionado)) {
                    atualizarTotaisVisuais(totalSelecionado);
                }
            });
        }

        async function generatePaymentToken(cardNumber, expiry, securityCode, cardholderName, cpf) {
            showStep('loading');
            const expirationMonth = expiry.split('/')[0];
            const expirationYear = expiry.split('/')[1];
            const efiEnvironment = <?php echo json_encode($efiEnvironment); ?>;
            const efiCardAccount = <?php echo json_encode($efiCardAccount); ?>;
            const cardNumberDigits = (cardNumber || '').replace(/\D/g, '');
            const securityCodeDigits = (securityCode || '').replace(/\D/g, '');
            const cardholderNameNormalized = (cardholderName || '').replace(/\s+/g, ' ').trim();
            if (!efiCardAccount) {
                throw new Error(efiCardAccountError);
            }


            try {
                const result = await EfiPay.CreditCard
                    .setAccount(efiCardAccount)
                    .setEnvironment(efiEnvironment) // 'production' or 'sandbox'
                    .setCreditCardData({
                        brand: cardBrand,
                        number: cardNumberDigits,
                        cvv: securityCodeDigits,
                        expirationMonth: expirationMonth,
                        expirationYear: expirationYear,
                        holderName: cardholderNameNormalized,
                        holderDocument: cpf.replace(/\D/g, ''),
                        reuse: false,
                    })
                    .getPaymentToken();

                const payment_token = result.payment_token;
                const card_mask = result.card_mask;
                if (!payment_token) {
                    throw new Error('Não foi possível gerar o token do cartão.');
                }
                formData.payment_token = payment_token;
                formData.card_mask = card_mask;

            } catch (error) {
                console.log("Código: ", error.code);
                console.log("Nome: ", error.error);
                console.log("Mensagem: ", error.error_description);
                throw new Error(error?.error_description || 'Falha ao validar os dados do cartão.');
            }
        }
        // Estado global do wizard
        let currentStep = 1;
        let formData = {};
        let ultimoPagamentoAprovado = null;
        const pagamentoPadrao = checkoutSomenteRecorrencia ? 'debit_card' : 'credit_card';
        formData.efi_checkout_environment = <?php echo json_encode($efiEnvironment); ?>;
        formData.efi_checkout_account = <?php echo json_encode($efiCardAccount); ?>;
        formData.payment_method = pagamentoPadrao;
        if (checkoutSomenteRecorrencia && checkoutSubscriptionId > 0) {
            formData.recurring_subscription_id = checkoutSubscriptionId;
            formData.recurring_mode = 'reprocess';
        }
        // Elementos DOM
        const steps = {
            1: document.getElementById('step1'),
            2: document.getElementById('step2'),
            3: document.getElementById('step3'),
            loading: document.getElementById('loadingScreen'),
            success: document.getElementById('successScreen'),
            error: document.getElementById('errorScreen')
        };

        function cpfMask(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }

        function phoneMask(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3')
                .replace(/(-\d{4})\d+?$/, '$1');
        }

        function cardMask(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2');
        }

        function expiryMask(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1/$2');
        }

        // Função para mostrar/esconder etapas
        function showStep(stepNumber) {
            Object.values(steps).forEach(step => step.classList.add('hidden'));

            if (steps[stepNumber]) {
                steps[stepNumber].classList.remove('hidden');
            }

            updateProgressBar(stepNumber);
        }

        // Atualizar barra de progresso
        function updateProgressBar(stepNumber) {
            const indicators = document.querySelectorAll('.step-indicator');
            const progressFill = document.getElementById('progressFill');
            const stepTexts = document.querySelectorAll('#progressBar span');

            indicators.forEach((indicator, index) => {
                indicator.classList.remove('active', 'completed');
                stepTexts[index].classList.remove('text-gray-900', 'text-green-600', 'text-gray-400');
                stepTexts[index].classList.add('text-gray-400');

                if (index + 1 < stepNumber) {
                    indicator.classList.add('completed');
                    indicator.style.backgroundColor = '#10b981';
                    indicator.innerHTML = '<i class="fas fa-check text-xs"></i>';
                    stepTexts[index].classList.remove('text-gray-400');
                    stepTexts[index].classList.add('text-green-600');
                } else if (index + 1 === stepNumber) {
                    indicator.classList.add('active');
                    indicator.style.backgroundColor = '#1e40af';
                    indicator.textContent = index + 1;
                    stepTexts[index].classList.remove('text-gray-400');
                    stepTexts[index].classList.add('text-gray-900');
                } else {
                    indicator.style.backgroundColor = '#e5e7eb';
                    indicator.textContent = index + 1;
                }
            });

            const progressPercent = ((stepNumber - 1) / 2) * 100;
            progressFill.style.width = progressPercent + '%';
        }

        // Atualizar resumo do pedido
        function updatePaymentSummary() {
            const paymentMethodSummary = document.getElementById('paymentMethodSummary');
            const selectedMethodText = document.getElementById('selectedMethodText');
            const paymentDetails = document.getElementById('paymentDetails');

            if (formData.payment_method) {
                paymentMethodSummary.classList.remove('hidden');

                const methods = {
                    'credit_card': { text: 'Cartão de crédito', detail: 'À vista ou parcelado em até 12x' },
                    'debit_card': { text: 'Pagamento Recorrente', detail: ' Pagamento recorrente' },
                };

                const method = methods[formData.payment_method];
                selectedMethodText.textContent = method.text;
                paymentDetails.textContent = method.detail;
            } else {
                paymentMethodSummary.classList.add('hidden');
            }
        }

        // Mostrar Dados do cart&atilde;o
        function toggleCardData() {
            const cardDataSection = document.getElementById('cardDataSection');
            const needsCardData = ['credit_card', 'debit_card'].includes(formData.payment_method);

            if (needsCardData) {
                cardDataSection.classList.remove('hidden');
                // Tornar campos obrigatórios
                cardDataSection.querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else {
                cardDataSection.classList.add('hidden');
                // Remover obrigatoriedade
                cardDataSection.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            }
        }

        function maskCardLast4(cardNumber) {
            // limpa tudo que não é dígito
            const digits = (cardNumber || '').replace(/\D/g, '');
            const last4 = digits.slice(-4);
            // cria máscara com espaços a cada 4 dígitos
            const masked = '**** **** **** ' + last4;
            return masked;
        }


        // Preencher tela de confirmação
        function fillConfirmationScreen() {
            const confirmationDetails = document.getElementById('confirmationDetails');
            let html = '';

            // Dados do pagamento
            const methods = {
                'credit_card': 'Cart&atilde;o de Cr&eacute;dito',
                'debit_card': 'Pagamento Recorrente'
            };

            html += `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-3">M&eacute;todo de Pagamento</h3>
                    <p class="text-blue-800">${methods[formData.payment_method]}</p>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Dados Pessoais</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-medium">Nome:</span> ${formData.customer_name}</p>
                        <p><span class="font-medium">E-mail:</span> ${formData.email}</p>
                        <p><span class="font-medium">CPF:</span> ${formData.cpf}</p>
                        ${formData.phone ? `<p><span class="font-medium">Telefone:</span> ${formData.phone}</p>` : ''}
                    </div>
                </div>`;

            // Dados do cartão (se aplicável)
            if (['credit_card', 'debit_card'].includes(formData.payment_method)) {
                const cardNumber = formData.card_number ? formData.card_number.replace(/\d(?=\d{4})/g, '*') : '';
                html += `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-900 mb-3">Dados do Cart&atilde;o</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Cart&atilde;o:</span> ${maskCardLast4(cardNumber)}</p>
                            <p><span class="font-medium">Nome no cart&atilde;o:</span> ${formData.cardholder_name}</p>
                            <p><span class="font-medium">Validade:</span> ${formData.expiry}</p>
                            <p><span class="font-medium">Bandeira:</span> ${cardBrand || 'N&atilde;o Encontrada'}</p>
                        </div>

                         <div class="space-y-2 text-sm mt-3">
                          <h3 class="font-semibold text-yellow-900 mb-3 text-base">Endere&ccedil;o do Cart&atilde;o</h3>
                            <p><span class="font-medium">CEP:</span> ${formData.zipcode}</p>
                            <p><span class="font-medium">Endere&ccedil;o:</span> ${formData.street}</p>
                            <p><span class="font-medium">N&uacute;mero:</span> ${formData.number}</p>
                            <p><span class="font-medium">Bairro:</span> ${formData.neighborhood}</p>
                            <p><span class="font-medium">Cidade:</span> ${formData.city}</p>
                            <p><span class="font-medium">Estado:</span> ${formData.state}</p>
                        </div>
                    </div>`;
            }

            html += `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-semibold text-green-900 mb-3">Resumo do Pagamento</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span><?php echo $nome_curso; ?></span>
                            <span>${formData.total_value_formatted || 'R$ <?php echo $valorCheckoutFmt; ?>'}</span>
                        </div>
                        <hr class="border-green-200">
                        <div class="flex justify-between font-semibold">
                            <span>Total</span>
                            <span>${formData.total_value_formatted || 'R$ <?php echo $valorCheckoutFmt; ?>'}</span>
                        </div>
                        <hr class="border-green-200">
                        <div class="flex justify-between font-semibold">
                            <span>Parcelas</span>
                            <span>${formData.installments_value}</span>
                        </div>
                    </div>
                </div>`;

            confirmationDetails.innerHTML = html;
        }

        // Simular processamento de pagamento
        function processPayment() {


            // Para outros métodos, continuar com o fluxo normal
            showStep('loading');

            // Simular tempo de processamento (3-5 segundos)
            const processingTime = Math.random() * 2000 + 3000;

            setTimeout(() => {
                // Simular sucesso/falha (80% sucesso, 20% falha)
                const isSuccess = Math.random() > 0.2;

                if (isSuccess) {
                    // Gerar ID da transação
                    document.getElementById('transactionId').textContent = `TX-${Date.now()}`;
                    showStep('success');
                } else {
                    // Mostrar erro
                    const errorMessages = [
                        'Cartão recusado pela operadora.',
                        'Dados do cartão inválidos.',
                        'Limite insuficiente.',
                        'Erro na comunicação com o banco.'
                    ];
                    const randomError = errorMessages[Math.floor(Math.random() * errorMessages.length)];
                    document.getElementById('errorMessage').textContent = randomError;
                    showStep('error');
                }
            }, processingTime);
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function () {
            // Aplicar máscaras
            const cpfInput = document.querySelector('input[name="cpf"]');
            const phoneInput = document.querySelector('input[name="phone"]');
            const cardInput = document.querySelector('input[name="card_number"]');
            const expiryInput = document.querySelector('input[name="expiry"]');

            if (cpfInput) {
                cpfInput.addEventListener('input', function (e) {
                    e.target.value = cpfMask(e.target.value);
                });
            }

            if (phoneInput) {
                phoneInput.addEventListener('input', function (e) {
                    e.target.value = phoneMask(e.target.value);
                });
            }

            if (cardInput) {
                cardInput.addEventListener('input', function (e) {
                    e.target.value = cardMask(e.target.value);
                });
            }

            if (expiryInput) {
                expiryInput.addEventListener('input', function (e) {
                    e.target.value = expiryMask(e.target.value);
                });
            }

            // Step 1: Método de pagamento
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            paymentMethods.forEach(method => {
                method.addEventListener('change', function () {
                    formData.payment_method = this.value;
                    updatePaymentSummary();
                    getInstallments();

                    // Adicionar efeito visual
                    document.querySelectorAll('.payment-method-option').forEach(option => {
                        option.classList.remove('ring-2', 'ring-efi-blue', 'bg-blue-50');
                    });
                    this.closest('.payment-method-option').classList.add('ring-2', 'ring-efi-blue', 'bg-blue-50');
                });
            });

            document.getElementById('nextStep1').addEventListener('click', function () {
                if (!formData.payment_method) {
                    alert('Por favor, selecione um método de pagamento');
                    return;
                }
                getInstallments();
                currentStep = 2;
                toggleCardData();
                showStep(2);
            });

            // Step 2: Dados pessoais
            document.getElementById('backStep2').addEventListener('click', function () {
                currentStep = 1;
                showStep(1);
            });

            document.getElementById('nextStep2').addEventListener('click', function () {

                // Validar campos obrigatórios
                const email = document.querySelector('input[name="email"]').value;
                const emailInput = document.getElementById('email');
                const cpf = document.querySelector('input[name="cpf"]').value;
                const customerName = document.querySelector('input[name="customer_name"]').value;

                if (!email || !email.includes('@')) {
                    emailInput.classList.add('border-red-500');
                    alert('Por favor, insira um e-mail válido');
                    return;
                }

                if (!cpf || cpf.replace(/\D/g, '').length !== 11) {
                    alert('Por favor, insira um CPF válido');
                    return;
                }

                if (!customerName) {
                    alert('Por favor, insira seu nome completo');
                    return;
                }

                // Validar Dados do cart&atilde;o
                if (['credit_card', 'debit_card'].includes(formData.payment_method)) {
                    const cardNumber = document.querySelector('input[name="card_number"]').value;
                    const expiry = document.querySelector('input[name="expiry"]').value;
                    const securityCode = document.querySelector('input[name="security_code"]').value;
                    const cardholderInput = document.querySelector('input[name="cardholder_name"]');
                    let cardholderName = (cardholderInput?.value || '').trim();
                    const id_do_curso_pag = document.querySelector('input[name="id_do_curso_pag"]').value;
                    const nome_curso_titulo = document.querySelector('input[name="nome_curso_titulo"]').value;
                    const id_matricula = document.querySelector('input[name="id_matricula"]').value;

                    // Endereço do cartão
                    const zipcode = (document.querySelector('input[name="cep"]').value || '').trim();
                    const street = ((document.querySelector('input[name="address"]')?.value || document.querySelector('input[name="street"]')?.value || '') + '').trim();
                    const number = (document.querySelector('input[name="number"]').value || '').trim();
                    const neighborhood = (document.querySelector('input[name="neighborhood"]').value || '').trim();
                    const city = (document.querySelector('input[name="city"]').value || '').trim();
                    const state = (document.querySelector('input[name="state"]').value || '').trim().toUpperCase();


                    const select = document.querySelector('select[name="installments"]');

                    // Número de parcelas
                    const installments = select.value;
                    if (!installments) {
                        alert('Não há opções de parcelas para o limite operacional atual da conta.');
                        return;
                    }

                    // Texto completo da opção selecionada
                    const installments_value = select.options[select.selectedIndex].text;
                    const installments_total = parseCurrencyToNumber(select.options[select.selectedIndex]?.dataset?.total || '');

                    if (!cardNumber || cardNumber.replace(/\D/g, '').length < 13) {
                        alert('Por favor, insira um número de cartão válido');
                        return;
                    }

                    if (!expiry || expiry.length !== 7) {
                        alert('Por favor, insira uma data de expiração válida');
                        return;
                    }

                    if (!securityCode || securityCode.length < 3) {
                        alert('Por favor, insira o código de segurança');
                        return;
                    }

                    if (!zipcode || zipcode.replace(/\D/g, '').length !== 8) {
                        alert('Por favor, informe um CEP válido com 8 dígitos');
                        return;
                    }

                    if (!street) {
                        alert('Por favor, informe o endereço');
                        return;
                    }

                    if (!number) {
                        alert('Por favor, informe o número do endereço');
                        return;
                    }

                    if (!neighborhood) {
                        alert('Por favor, informe o bairro');
                        return;
                    }

                    if (!city) {
                        alert('Por favor, informe a cidade');
                        return;
                    }

                    if (!state || state.length !== 2) {
                        alert('Por favor, informe a UF com 2 letras (ex.: RO)');
                        return;
                    }

                    if (!cardholderName) {
                        const fallbackNome = (customerName || '').trim();
                        if (fallbackNome) {
                            cardholderName = fallbackNome;
                            if (cardholderInput) cardholderInput.value = fallbackNome;
                        } else {
                            alert('Por favor, insira o nome do portador do cartão');
                            return;
                        }
                    }

                    const cardholderParts = cardholderName.split(/\s+/).filter(Boolean);
                    if (cardholderParts.length < 2) {
                        alert('O nome do titular do cartão deve ter nome e sobrenome');
                        return;
                    }

                    // Salvar Dados do cart&atilde;o
                    formData.card_number = cardNumber;
                    formData.expiry = expiry;
                    formData.security_code = securityCode;
                    formData.cardholder_name = cardholderName;
                    formData.installments = installments;
                    formData.installments_value = installments_value;
                    if (Number.isFinite(installments_total)) {
                        formData.total_value = installments_total;
                        formData.total_value_formatted = `R$ ${formatarMoedaBR(installments_total)}`;
                    }
                    formData.id_do_curso = id_do_curso_pag;
                    formData.id_matricula = id_matricula;
                    formData.nome_do_curso = nome_curso_titulo;
                    formData.zipcode = zipcode.replace(/\D/g, '');
                    formData.street = street;
                    formData.number = number;
                    formData.neighborhood = neighborhood;
                    formData.city = city;
                    formData.state = state;
                }

                // Salvar dados pessoais
                formData.email = email;
                formData.cpf = cpf;
                formData.customer_name = customerName;
                formData.phone = document.querySelector('input[name="phone"]').value;

                currentStep = 3;
                fillConfirmationScreen();
                showStep(3);
            });

            // Step 3: Confirmação
            document.getElementById('backStep3').addEventListener('click', function () {
                currentStep = 2;
                showStep(2);
            });

            document.getElementById('finalizePayment').addEventListener('click', async function () {

                const cardNumber = document.querySelector('input[name="card_number"]').value;
                const expiry = document.querySelector('input[name="expiry"]').value;
                const securityCode = document.querySelector('input[name="security_code"]').value;
                const cardholderName = (document.querySelector('input[name="cardholder_name"]').value || '').trim() || (document.querySelector('input[name="customer_name"]').value || '').trim();
                const cpf = document.querySelector('input[name="cpf"]').value;

                const cardholderParts = cardholderName.split(/\s+/).filter(Boolean);
                if (cardholderParts.length < 2) {
                    document.getElementById('errorMessage').textContent = 'Informe nome e sobrenome no titular do cartão.';
                    showStep('error');
                    return;
                }

                try {
                    await generatePaymentToken(cardNumber, expiry, securityCode, cardholderName, cpf);
                } catch (tokenError) {
                    document.getElementById('errorMessage').textContent = tokenError.message || 'Falha ao validar os dados do cartão.';
                    showStep('error');
                    return;
                }


                // $.ajax({
                //     url: '/efi/index.php',
                //     type: 'POST',
                //     contentType: 'application/json; charset=utf-8', // avisa que está mandando JSON
                //     data: JSON.stringify(formData), // manda o JSON direto
                //     dataType: 'json', // espera JSON do PHP
                //     success: function (response) {

                //         console.log(response);
                //     },
                //     error: function (xhr, status, error) {

                //         console.error("Erro na requisição AJAX:", error);
                //     }
                // });

                $.ajax({
                    url: 'card_payment.php',
                    type: 'POST',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(formData),
                    dataType: 'json',
                    beforeSend: function () {
                        // Mostra tela de carregamento antes de enviar a requisição
                        showStep('loading');
                    },
                    success: function (response) {
                        // Aqui você decide com base na resposta da API
                        if (response.success) {
                            const chargeId = response?.data?.charge_id || response?.data?.subscription_id || 'TRANSACTION ID';
                            document.getElementById('transactionId').textContent = chargeId;
                            const metodoSucesso = formData.payment_method === 'debit_card' ? 'Cartão recorrente' : 'Cartão de crédito';
                            const metodoEl = document.getElementById('metodoPagoSucesso');
                            if (metodoEl) {
                                metodoEl.textContent = metodoSucesso;
                            }
                            ultimoPagamentoAprovado = {
                                transactionId: String(chargeId),
                                metodo: metodoSucesso,
                                valorFormatado: formData.total_value_formatted || document.getElementById('valorPagoSucesso')?.textContent || '',
                                dataHora: new Date().toLocaleString('pt-BR'),
                                paymentData: response?.data?.payment_data || {}
                            };
                            try {
                                if (window.parent && window.parent !== window) {
                                    window.parent.postMessage({
                                        type: 'efi-payment-success',
                                        id_matricula: formData.id_matricula || null
                                    }, '*');
                                }
                            } catch (e) {
                            }
                            showStep('success');
                        } else {
                            const detalhe = (response.detail || '').toString();
                            const mensagem = (response.error || 'Erro ao processar pagamento.').toString();
                            document.getElementById('errorMessage').textContent = detalhe ? `${mensagem} ${detalhe}` : mensagem;
                            showStep('error');
                        }
                    },
                    error: function (xhr, status, error) {
                        const backendMessage = xhr?.responseJSON?.error || xhr?.responseJSON?.detail || '';
                        document.getElementById('errorMessage').textContent = backendMessage || ("Erro na requisição AJAX: " + error);
                        showStep('error');
                    }
                });


            });
            // Botões de erro
            document.getElementById('retryPayment').addEventListener('click', function () {
                currentStep = 1;
                formData = {};
                formData.efi_checkout_environment = <?php echo json_encode($efiEnvironment); ?>;
                formData.efi_checkout_account = <?php echo json_encode($efiCardAccount); ?>;
                formData.payment_method = pagamentoPadrao;
                if (checkoutSomenteRecorrencia && checkoutSubscriptionId > 0) {
                    formData.recurring_subscription_id = checkoutSubscriptionId;
                    formData.recurring_mode = 'reprocess';
                }
                showStep(1);
                updatePaymentSummary();

                // Reset form
                document.getElementById('wizardForm').reset();
                document.querySelectorAll('.payment-method-option').forEach(option => {
                    option.classList.remove('ring-2', 'ring-efi-blue', 'bg-blue-50');
                });
                const seletorPadrao = `input[name="payment_method"][value="${pagamentoPadrao}"]`;
                const inputPadrao = document.querySelector(seletorPadrao);
                if (inputPadrao) {
                    inputPadrao.checked = true;
                }
                const defaultOption = inputPadrao?.closest('.payment-method-option');
                if (defaultOption) {
                    defaultOption.classList.add('ring-2', 'ring-efi-blue', 'bg-blue-50');
                }
                updatePaymentSummary();
                getInstallments();
            });

            document.getElementById('changeMethod').addEventListener('click', function () {
                currentStep = 1;
                formData = {};
                formData.efi_checkout_environment = <?php echo json_encode($efiEnvironment); ?>;
                formData.efi_checkout_account = <?php echo json_encode($efiCardAccount); ?>;
                formData.payment_method = pagamentoPadrao;
                if (checkoutSomenteRecorrencia && checkoutSubscriptionId > 0) {
                    formData.recurring_subscription_id = checkoutSubscriptionId;
                    formData.recurring_mode = 'reprocess';
                }
                showStep(1);
                updatePaymentSummary();

                // Reset form
                document.getElementById('wizardForm').reset();
                document.querySelectorAll('.payment-method-option').forEach(option => {
                    option.classList.remove('ring-2', 'ring-efi-blue', 'bg-blue-50');
                });
                const seletorPadrao = `input[name="payment_method"][value="${pagamentoPadrao}"]`;
                const inputPadrao = document.querySelector(seletorPadrao);
                if (inputPadrao) {
                    inputPadrao.checked = true;
                }
                const defaultOption = inputPadrao?.closest('.payment-method-option');
                if (defaultOption) {
                    defaultOption.classList.add('ring-2', 'ring-efi-blue', 'bg-blue-50');
                }
                updatePaymentSummary();
                getInstallments();
            });

            const btnDownloadComprovante = document.getElementById('downloadReceiptBtn');
            if (btnDownloadComprovante) {
                btnDownloadComprovante.addEventListener('click', function () {
                    if (!ultimoPagamentoAprovado) {
                        alert('Comprovante indisponivel no momento.');
                        return;
                    }

                    const urlComprovante = extrairUrlComprovante(ultimoPagamentoAprovado.paymentData);
                    if (urlComprovante) {
                        window.open(urlComprovante, '_blank');
                        return;
                    }

                    const htmlComprovante = gerarComprovanteHtmlLocal();
                    if (!htmlComprovante) {
                        alert('Não foi possível gerar o comprovante.');
                        return;
                    }

                    const blob = new Blob([htmlComprovante], { type: 'text/html;charset=utf-8' });
                    const link = document.createElement('a');
                    const tx = (ultimoPagamentoAprovado.transactionId || 'pagamento').replace(/[^a-zA-Z0-9_-]/g, '');
                    link.href = URL.createObjectURL(blob);
                    link.download = `comprovante-${tx}.html`;
                    document.body.appendChild(link);
                    link.click();
                    setTimeout(() => {
                        URL.revokeObjectURL(link.href);
                        link.remove();
                    }, 1000);
                });
            }

            const btnFecharCheckout = document.getElementById('closeCheckoutBtn');
            if (btnFecharCheckout) {
                btnFecharCheckout.addEventListener('click', function () {
                    try {
                        if (window.parent && window.parent !== window && window.parent.Swal) {
                            window.parent.Swal.close();
                            return;
                        }
                    } catch (e) {
                    }
                    window.close();
                });
            }

            // Inicializar
            formData.payment_method = pagamentoPadrao;
            const inputInicial = document.querySelector(`input[name="payment_method"][value="${pagamentoPadrao}"]`);
            if (inputInicial) {
                inputInicial.checked = true;
                const opcaoInicial = inputInicial.closest('.payment-method-option');
                if (opcaoInicial) {
                    opcaoInicial.classList.add('ring-2', 'ring-efi-blue', 'bg-blue-50');
                }
            }
            updatePaymentSummary();
            getInstallments();
            showStep(1);
        });
    </script>
</body>

</html>



