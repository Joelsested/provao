<?php

require_once('../vendor/autoload.php');
require_once("../sistema/conexao.php");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

@session_start();

$id_aluno = $_GET['id_aluno'];
$id = $_GET['id'];
$nome_curso = $_GET['nome_curso'];


//BUSCA VALOR ACRESCIMO CARTAO
$queryAcrescimoCartao = $pdo->query("SELECT acrescimo_cartao_credito FROM config");
$resAcrescimoCartao = $queryAcrescimoCartao->fetchColumn();



//BUSCA DADOS DA MATRICULA
$query = $pdo->prepare("SELECT * FROM matriculas where id = :id and aluno = :aluno");
$query->execute([':id' => $id, ':aluno' => $id_aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$response = $res[0];


// aplica desconto caso exista
$valor = (float)$response['valor'];
$desconto = !empty($response['valor_cupom']) ? (float)$response['valor_cupom'] : 0;
$response['valor'] = $valor - $desconto;




$valor = (int) $response['valor']; 
$percentual = isset($resAcrescimoCartao) ? (float) $resAcrescimoCartao : 0; 



if ($percentual > 0) {
    
    $valor += ceil($valor * ($percentual / 100));

}

$response['valor'] = $valor;




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
                        <span class="font-medium text-green-600">R$ <?php echo $response['valor']; ?></span>
                    </div>

                    <!-- Método de Pagamento Selecionado -->
                    <div id="paymentMethodSummary" class="hidden">
                        <hr class="my-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Método:</span>
                            <span id="selectedMethodText" class="font-medium text-blue-600"></span>
                        </div>
                        <div id="paymentDetails" class="text-sm text-gray-500 mt-1"></div>
                    </div>

                    <hr class="my-3">
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Total:</span>
                        <span class="text-green-600">R$ <?php echo $response['valor']; ?></span>
                    </div>
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
                                class="payment-method-option flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="payment_method" value="credit_card"
                                    class="w-4 h-4 text-efi-blue" checked>
                                <div class="ml-3 flex items-center flex-1">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">Cartão de crédito</div>
                                        <div class="text-sm text-gray-500">Em até 12x • Aprovação em segundos</div>
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
                                    class="w-4 h-4 text-efi-blue">
                                <div class="ml-3 flex items-center flex-1">
                                    <div
                                        class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">Pagamento Recorrente</div>
                                        <div class="text-sm text-gray-500">Pagamento recorrente • Aprovação imediata
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

                        <!-- Dados do Cartão (aparece apenas se necessário) -->
                        <div id="cardDataSection" class="space-y-4 mt-6 hidden">
                            <h3 class="text-lg font-medium text-gray-900 border-t pt-4">Dados do cartão</h3>

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
                                        title="Código de 3 ou 4 dígitos no verso do cartão"></i>
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
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Numero *</label>
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

                    <!-- PIX QR Code Screen -->
                    <div id="pixScreen" class="step-content hidden">
                        <div class="text-center py-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-2">Pagamento via PIX</h2>
                            <p class="text-gray-600 mb-6">Escaneie o QR Code com seu app do banco</p>

                            <!-- QR Code Container -->
                            <div class="flex flex-col items-center mb-6">
                                <div class="bg-white p-6 rounded-lg shadow-lg border-2 border-gray-200 mb-4">
                                    <!-- <div id="qrcode" class="flex items-center justify-center w-64 h-64 bg-gray-50 rounded"></div> -->
                                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRJgiYusr3xvjwvq0Me--mCxGUfnIdvdWa_0g&s"
                                        alt="">
                                </div>

                                <!-- PIX Key/Code -->
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 max-w-md w-full">
                                    <p class="text-sm text-gray-600 mb-2">Chave PIX (Copia e Cola)</p>
                                    <div class="flex items-center space-x-2">
                                        <input type="text" id="pixKey" readonly
                                            class="flex-1 px-3 py-2 text-xs font-mono bg-white border border-gray-300 rounded focus:ring-2 focus:ring-efi-blue"
                                            value="00020126580014BR.GOV.BCB.PIX0136123e4567-e12b-12d1-a456-426614174000520400005303986540515990630472804">
                                        <button type="button" id="copyPixKey"
                                            class="px-3 py-2 bg-efi-blue text-white text-xs rounded hover:bg-efi-light-blue transition-colors"
                                            title="Copiar">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Timer and Status -->
                            <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-6 max-w-md mx-auto">
                                <div class="flex items-center justify-center mb-2">
                                    <div class="w-3 h-3 bg-teal-500 rounded-full animate-pulse mr-2"></div>
                                    <span class="text-sm font-medium text-teal-800">Aguardando pagamento...</span>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-teal-600 mb-2">Este PIX expira em:</p>
                                    <div class="text-lg font-bold text-teal-800" id="pixTimer">15:00</div>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="text-left max-w-md mx-auto mb-6">
                                <h3 class="font-semibold text-gray-800 mb-3 text-center">Como pagar:</h3>
                                <ol class="space-y-2 text-sm text-gray-600">
                                    <li class="flex items-start">
                                        <span
                                            class="bg-efi-blue text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</span>
                                        Abra o app do seu banco
                                    </li>
                                    <li class="flex items-start">
                                        <span
                                            class="bg-efi-blue text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</span>
                                        Escolha a opção PIX
                                    </li>
                                    <li class="flex items-start">
                                        <span
                                            class="bg-efi-blue text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</span>
                                        Escaneie o QR Code ou cole a chave
                                    </li>
                                    <li class="flex items-start">
                                        <span
                                            class="bg-efi-blue text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">4</span>
                                        Confirme o pagamento
                                    </li>
                                </ol>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                <button type="button" id="checkPixPayment"
                                    class="bg-teal-600 hover:bg-teal-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                                    <i class="fas fa-sync mr-2"></i>
                                    Verificar Pagamento
                                </button>
                                <button type="button" id="cancelPix"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancelar
                                </button>
                            </div>
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
                                        <span class="text-gray-600">ID da transação:</span>
                                        <span class="font-mono text-green-700" id="transactionId">TX-123456789</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Valor pago:</span>
                                        <span class="font-semibold text-green-700">R$ <?php echo $response['valor']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <button type="button"
                                class="bg-efi-blue hover:bg-efi-light-blue text-white font-medium py-3 px-8 rounded-lg transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Baixar Comprovante
                            </button>
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
                                    <p id="errorMessage">Verifique os dados do cartão ou tente outro método de
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
                // CEP completo → busca
                buscarCep(value.replace('-', ''));
            } else {
                // CEP incompleto → limpa e esconde
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

        // Função para buscar parcelas
        async function getInstallments() {
            const total = <?php echo json_encode($response['valor']); ?>;
            const valorCentavos = Math.round(total * 100);

            if (!cardBrand) return; // só continua se tiver bandeira

            try {
                const installmentsResponse = await EfiPay.CreditCard
                    .setAccount("09c6ec939c0ad967bf568d6f145a733d")
                    .setEnvironment("production")
                    .setBrand(cardBrand)
                    .setTotal(valorCentavos)
                    .getInstallments();

                const select = document.getElementById('installments');
                select.innerHTML = '';

                installmentsResponse.installments.forEach(installment => {
                    const valorReais = (installment.value / 100).toFixed(2).replace('.', ',');
                    const option = document.createElement('option');
                    option.value = installment.installment;
                    option.text = `${installment.installment}x de R$ ${valorReais} ${installment.has_interest ? '(com juros)' : '(sem juros)'}`;
                    select.appendChild(option);
                });

            } catch (error) {
                console.error("Erro ao obter parcelas:", error);
            }
        }

        async function generatePaymentToken(cardNumber, expiry, securityCode, cardholderName, cpf) {
            showStep('loading');
            const expirationMonth = expiry.split('/')[0];
            const expirationYear = expiry.split('/')[1];

            try {
                const result = await EfiPay.CreditCard
                    .setAccount("09c6ec939c0ad967bf568d6f145a733d")
                    .setEnvironment("production") // 'production' or 'sandbox'
                    .setCreditCardData({
                        brand: cardBrand,
                        number: cardNumber,
                        cvv: securityCode,
                        expirationMonth: expirationMonth,
                        expirationYear: expirationYear,
                        holderName: cardholderName,
                        holderDocument: cpf.replace(/\D/g, ''),
                        reuse: false,
                    })
                    .getPaymentToken();

                const payment_token = result.payment_token;
                const card_mask = result.card_mask;
                formData.payment_token = payment_token;
                formData.card_mask = card_mask;

            } catch (error) {
                console.log("Código: ", error.code);
                console.log("Nome: ", error.error);
                console.log("Mensagem: ", error.error_description);
            }
        }
        // Estado global do wizard
        let currentStep = 1;
        let formData = {};
        let pixTimer = null;
        let pixTimeRemaining = 15 * 60; // 15 minutos em segundos
        let pixCheckInterval = null;

        // Elementos DOM
        const steps = {
            1: document.getElementById('step1'),
            2: document.getElementById('step2'),
            3: document.getElementById('step3'),
            pix: document.getElementById('pixScreen'),
            loading: document.getElementById('loadingScreen'),
            success: document.getElementById('successScreen'),
            error: document.getElementById('errorScreen')
        };

        // Função para gerar QR Code PIX (simulado)
        function generatePixQRCode() {
            const qrCodeElement = document.getElementById('qrcode');

            // Limpar QR code anterior
            qrCodeElement.innerHTML = '';

            // Criar um QR code simples usando CSS (simulação)
            const qrSize = 256;
            const pixelSize = 8;
            const qrGrid = qrSize / pixelSize;

            qrCodeElement.style.width = qrSize + 'px';
            qrCodeElement.style.height = qrSize + 'px';
            qrCodeElement.style.position = 'relative';
            qrCodeElement.style.backgroundColor = 'white';

            // Gerar padrão de QR code simples (simulado)
            for (let i = 0; i < qrGrid; i++) {
                for (let j = 0; j < qrGrid; j++) {
                    // Algoritmo simples para criar padrão QR-like
                    const shouldFill = (i + j) % 3 === 0 ||
                        (i % 4 === 0 && j % 4 === 0) ||
                        (i < 7 && j < 7) ||
                        (i < 7 && j > qrGrid - 8) ||
                        (i > qrGrid - 8 && j < 7) ||
                        (Math.abs(i - qrGrid / 2) < 2 && Math.abs(j - qrGrid / 2) < 2);

                    if (shouldFill) {
                        const pixel = document.createElement('div');
                        pixel.style.position = 'absolute';
                        pixel.style.left = (j * pixelSize) + 'px';
                        pixel.style.top = (i * pixelSize) + 'px';
                        pixel.style.width = pixelSize + 'px';
                        pixel.style.height = pixelSize + 'px';
                        pixel.style.backgroundColor = 'black';
                        qrCodeElement.appendChild(pixel);
                    }
                }
            }
        }

        // Timer do PIX
        function startPixTimer() {
            pixTimeRemaining = 15 * 60; // Reset para 15 minutos
            const timerElement = document.getElementById('pixTimer');

            pixTimer = setInterval(() => {
                const minutes = Math.floor(pixTimeRemaining / 60);
                const seconds = pixTimeRemaining % 60;
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                pixTimeRemaining--;

                if (pixTimeRemaining < 0) {
                    clearInterval(pixTimer);
                    clearInterval(pixCheckInterval);

                    // PIX expirado
                    const errorMessages = document.getElementById('errorMessage');
                    errorMessages.textContent = 'PIX expirado. Por favor, gere um novo PIX ou escolha outro método de pagamento.';
                    showStep('error');
                }

                // Mudar cor quando restam menos de 5 minutos
                if (pixTimeRemaining < 5 * 60) {
                    timerElement.classList.add('text-red-600');
                    timerElement.classList.remove('text-teal-800');
                }
            }, 1000);
        }

        // Verificar status do pagamento PIX
        function checkPixPayment() {
            // Simular verificação do pagamento PIX
            // Na implementação real, você faria uma chamada para a API da EFI

            // Simular 30% de chance de pagamento confirmado a cada verificação
            const isPaid = Math.random() > 0.7;

            if (isPaid) {
                // Pagamento confirmado
                clearInterval(pixTimer);
                clearInterval(pixCheckInterval);

                // Mostrar loading brevemente e depois sucesso
                showStep('loading');
                setTimeout(() => {
                    document.getElementById('transactionId').textContent = `PIX-${Date.now()}`;
                    showStep('success');
                }, 2000);
            } else {
                // Criar efeito visual de verificação
                const checkButton = document.getElementById('checkPixPayment');
                const originalText = checkButton.innerHTML;

                checkButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verificando...';
                checkButton.disabled = true;

                setTimeout(() => {
                    checkButton.innerHTML = originalText;
                    checkButton.disabled = false;
                }, 2000);
            }
        }

        // Auto-verificação do PIX a cada 10 segundos
        function startAutoPixCheck() {
            pixCheckInterval = setInterval(() => {
                // Verificação automática silenciosa
                const isPaid = Math.random() > 0.85; // 15% de chance por verificação automática

                if (isPaid) {
                    clearInterval(pixTimer);
                    clearInterval(pixCheckInterval);

                    // Mostrar loading e depois sucesso
                    showStep('loading');
                    setTimeout(() => {
                        document.getElementById('transactionId').textContent = `PIX-${Date.now()}`;
                        showStep('success');
                    }, 2000);
                }
            }, 10000); // Verificar a cada 10 segundos
        }

        // Copiar chave PIX
        function copyPixKey() {
            const pixKeyInput = document.getElementById('pixKey');
            pixKeyInput.select();
            pixKeyInput.setSelectionRange(0, 99999);

            navigator.clipboard.writeText(pixKeyInput.value).then(() => {
                const copyButton = document.getElementById('copyPixKey');
                const originalIcon = copyButton.innerHTML;

                copyButton.innerHTML = '<i class="fas fa-check"></i>';
                copyButton.classList.remove('bg-efi-blue', 'hover:bg-efi-light-blue');
                copyButton.classList.add('bg-green-600', 'hover:bg-green-700');

                setTimeout(() => {
                    copyButton.innerHTML = originalIcon;
                    copyButton.classList.add('bg-efi-blue', 'hover:bg-efi-light-blue');
                    copyButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                }, 2000);
            });
        }

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

        // Função para mostrar/esconder steps
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
                    'credit_card': { text: 'Cartão de Crédito', detail: 'À vista ou parcelado em até 12x' },
                    'debit_card': { text: 'Pagamento Recorrente', detail: ' Pagamento recorrente' },
                };

                const method = methods[formData.payment_method];
                selectedMethodText.textContent = method.text;
                paymentDetails.textContent = method.detail;
            } else {
                paymentMethodSummary.classList.add('hidden');
            }
        }

        // Mostrar dados do cartão se necessário
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
                'pix': 'PIX',
                'credit_card': 'Cartão de Crédito',
                'debit_card': 'Pagamento Recorrente',
                'boleto': 'Boleto Bancário'
            };

            html += `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-3">Método de Pagamento</h3>
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
                        <h3 class="font-semibold text-yellow-900 mb-3">Dados do Cartão</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Cartão:</span> ${maskCardLast4(cardNumber)}</p>
                            <p><span class="font-medium">Nome no cartão:</span> ${formData.cardholder_name}</p>
                            <p><span class="font-medium">Validade:</span> ${formData.expiry}</p>
                            <p><span class="font-medium">Bandeira:</span> ${cardBrand || 'Não Encontrada'}</p>
                        </div>

                         <div class="space-y-2 text-sm mt-3">
                          <h3 class="font-semibold text-yellow-900 mb-3 text-base">Endereço do Cartão</h3>
                            <p><span class="font-medium">CEP:</span> ${formData.zipcode}</p>
                            <p><span class="font-medium">Endereço:</span> ${formData.street}</p>
                            <p><span class="font-medium">Número:</span> ${formData.number}</p>
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
                            <span>R$ <?php echo $response['valor']; ?></span>
                        </div>
                        <hr class="border-green-200">
                        <div class="flex justify-between font-semibold">
                            <span>Total</span>
                            <span>R$ <?php echo $response['valor']; ?></span>
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
                // getInstallments();
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

                // Validar dados do cartão se necessário
                if (['credit_card', 'debit_card'].includes(formData.payment_method)) {
                    const cardNumber = document.querySelector('input[name="card_number"]').value;
                    const expiry = document.querySelector('input[name="expiry"]').value;
                    const securityCode = document.querySelector('input[name="security_code"]').value;
                    const cardholderName = document.querySelector('input[name="cardholder_name"]').value;
                    const id_do_curso_pag = document.querySelector('input[name="id_do_curso_pag"]').value;
                    const nome_curso_titulo = document.querySelector('input[name="nome_curso_titulo"]').value;

                    // Endereço do cartão
                    const zipcode = document.querySelector('input[name="cep"]').value;
                    const street = document.querySelector('input[name="address"]').value;
                    const number = document.querySelector('input[name="number"]').value;
                    const neighborhood = document.querySelector('input[name="neighborhood"]').value;
                    const city = document.querySelector('input[name="city"]').value;
                    const state = document.querySelector('input[name="state"]').value;


                    const select = document.querySelector('select[name="installments"]');

                    // Número de parcelas
                    const installments = select.value;

                    // Texto completo da opção selecionada
                    const installments_value = select.options[select.selectedIndex].text;

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

                    if (!cardholderName) {
                        alert('Por favor, insira o nome do portador do cartão');
                        return;
                    }

                    // Salvar dados do cartão
                    formData.card_number = cardNumber;
                    formData.expiry = expiry;
                    formData.security_code = securityCode;
                    formData.cardholder_name = cardholderName;
                    formData.installments = installments;
                    formData.installments_value = installments_value;
                    formData.id_do_curso = id_do_curso_pag;
                    formData.nome_do_curso = nome_curso_titulo;
                    formData.zipcode = zipcode;
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
                const cardholderName = document.querySelector('input[name="cardholder_name"]').value;
                const cpf = document.querySelector('input[name="cpf"]').value;

                await generatePaymentToken(cardNumber, expiry, securityCode, cardholderName, cpf);


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
                    url: '/efi/card_payment.php',
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
                            document.getElementById('transactionId').textContent = 'TRANSACTION ID';
                            showStep('success');
                        } else {
                            document.getElementById('errorMessage').textContent = 'Erro ao processar pagamento.';
                            showStep('error');
                        }
                    },
                    error: function (xhr, status, error) {
                        document.getElementById('errorMessage').textContent = "Erro na requisição AJAX: " + error;
                        showStep('error');
                    }
                });


            });

            // PIX Event Listeners
            document.getElementById('copyPixKey').addEventListener('click', copyPixKey);

            document.getElementById('checkPixPayment').addEventListener('click', checkPixPayment);

            document.getElementById('cancelPix').addEventListener('click', function () {
                clearInterval(pixTimer);
                clearInterval(pixCheckInterval);
                currentStep = 3;
                showStep(3);
            });

            // Botões de erro
            document.getElementById('retryPayment').addEventListener('click', function () {
                // Limpar timers se existirem
                clearInterval(pixTimer);
                clearInterval(pixCheckInterval);

                currentStep = 1;
                formData = {};
                showStep(1);
                updatePaymentSummary();

                // Reset form
                document.getElementById('wizardForm').reset();
                document.querySelectorAll('.payment-method-option').forEach(option => {
                    option.classList.remove('ring-2', 'ring-efi-blue', 'bg-blue-50');
                });
                document.querySelector('input[name="payment_method"][value="credit_card"]').checked = true;
            });

            document.getElementById('changeMethod').addEventListener('click', function () {
                // Limpar timers se existirem
                clearInterval(pixTimer);
                clearInterval(pixCheckInterval);

                currentStep = 1;
                formData = {};
                showStep(1);
                updatePaymentSummary();

                // Reset form
                document.getElementById('wizardForm').reset();
                document.querySelectorAll('.payment-method-option').forEach(option => {
                    option.classList.remove('ring-2', 'ring-efi-blue', 'bg-blue-50');
                });
                document.querySelector('input[name="payment_method"][value="credit_card"]').checked = true;
            });

            // Inicializar
            formData.payment_method = 'credit_card';
            updatePaymentSummary();
            showStep(1);
        });
    </script>
</body>

</html>
