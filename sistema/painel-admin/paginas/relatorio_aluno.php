<?php

require_once('../conexao.php');

require_once('verificar.php');

require_once __DIR__ . '/../../../efi/pix.php';
require_once __DIR__ . '/../../../efi/boleto.php';

$options = require_once __DIR__ . '/../../../efi/options.php';

$pag = 'relatorio_aluno';

@session_start();

$id_user = @$_SESSION['id'];



if (!in_array(@$_SESSION['nivel'], ['Administrador', 'Secretario', 'Tesoureiro', 'Tutor', 'Parceiro', 'Professor', 'Vendedor'])) {

  echo "<script>window.location='../index.php'</script>";

  exit();

}



// Verificar se o parâmetro "aluno" foi passado corretamente

if (!isset($_GET['aluno']) || empty($_GET['aluno'])) {

  echo "<script>window.location='index.php'</script>";

  exit();

}



$aluno = $_GET['aluno'];



// Obter dados do aluno

$dados_aluno = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
$dados_aluno->execute([':id' => $aluno]);

$resposta_aluno = $dados_aluno->fetchAll(PDO::FETCH_ASSOC);

$email_aluno = $resposta_aluno[0]['email'];

$telefone_aluno = $resposta_aluno[0]['telefone'] ?? 'Não informado';

$dados_usuario_aluno = $pdo->prepare("SELECT id, nome, cpf, foto FROM usuarios WHERE usuario = :usuario");
$dados_usuario_aluno->execute([':usuario' => $email_aluno]);

$resposta_usuario_aluno = $dados_usuario_aluno->fetchAll(PDO::FETCH_ASSOC);

$id_aluno = $resposta_usuario_aluno[0]['id'];

$nome_aluno = $resposta_usuario_aluno[0]['nome'] ?? 'Desconhecido';

$cpf_aluno = $resposta_usuario_aluno[0]['cpf'] ?? 'Não informado';

$foto = $resposta_usuario_aluno[0]['foto'];







// Data para filtro

$dataInicial = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : date('Y-m-d', strtotime('-1 year'));

$dataFinal = isset($_GET['data_final']) ? $_GET['data_final'] : date('Y-m-d');

$statusFiltro = isset($_GET['status']) ? $_GET['status'] : 'todos';



// // Consulta todas as matriculas

// $query_matriculas = $pdo->prepare("

//     SELECT m.*, c.nome as nome_curso, c.valor as valor_curso, c.status as status_curso

//     FROM matriculas m

//     JOIN cursos c ON m.id_curso = c.id

//     WHERE m.aluno = :aluno

//     ORDER BY m.data DESC

// ");

// $query_matriculas->execute(['aluno' => $id_aluno]);

// $matriculas = $query_matriculas->fetchAll(PDO::FETCH_ASSOC);



// Consulta todas as matriculas com verificação de campos existentes

$query_matriculas = $pdo->prepare("

    SELECT 

        m.*, 

        CASE 

            WHEN m.pacote = 'Sim' THEN p.nome 

            ELSE c.nome 

        END as nome_curso,

        CASE 

            WHEN m.pacote = 'Sim' THEN p.valor 

            ELSE c.valor 

        END as valor_curso,

        CASE 

            WHEN m.pacote = 'Sim' THEN 'Ativo' -- Valor padrão ou constante para pacotes

            ELSE c.status 

        END as status_curso

    FROM 

        matriculas m

    LEFT JOIN 

        cursos c ON m.id_curso = c.id AND m.pacote = 'Não'

    LEFT JOIN 

        pacotes p ON m.id_curso = p.id AND m.pacote = 'Sim'

    WHERE 

        m.aluno = :aluno

    ORDER BY 

        m.data DESC

");



$query_matriculas->execute(['aluno' => $id_aluno]);

$matriculas = $query_matriculas->fetchAll(PDO::FETCH_ASSOC);



// Consulta matriculas de cursos (onde pacote = 'Não' ou null)

$query_cursos = $pdo->prepare("

    SELECT 

        m.*,

        c.nome as nome_curso,

        c.valor as valor_curso,

        c.status as status_curso

    FROM 

        matriculas m

    JOIN 

        cursos c ON m.id_curso = c.id

    WHERE 

        m.aluno = :aluno

        AND (m.pacote = 'Não' OR m.pacote IS NULL)

    ORDER BY 

        m.data DESC

");

$query_cursos->execute(['aluno' => $id_aluno]);

$matriculas_cursos = $query_cursos->fetchAll(PDO::FETCH_ASSOC);



// Consulta matriculas de pacotes (onde pacote = 'Sim')

$query_pacotes = $pdo->prepare("

    SELECT 

        m.*,

        p.nome as nome_curso,

        p.valor as valor_curso,

        'Ativo' as status_curso

    FROM 

        matriculas m

    JOIN 

        pacotes p ON m.id_curso = p.id

    WHERE 

        m.aluno = :aluno

        AND m.pacote = 'Sim'

    ORDER BY 

        m.data DESC

");

$query_pacotes->execute(['aluno' => $id_aluno]);

$matriculas_pacotes = $query_pacotes->fetchAll(PDO::FETCH_ASSOC);









// Consultar parcelas

$consulta_parcelas = $pdo->query("

  SELECT 

    pbp.*, 

    CASE 

        WHEN m.pacote = 'Sim' THEN p.nome 

        ELSE c.nome 

    END as curso,

    bp.id_matricula,
    m.data as data_matricula,
    m.status as status_matricula

FROM 

    parcelas_geradas_por_boleto pbp

    JOIN boletos_parcelados bp ON bp.id = pbp.id_boleto_parcelado

    JOIN matriculas m ON m.id = bp.id_matricula

    LEFT JOIN cursos c ON c.id = m.id_curso AND m.pacote != 'Sim'

    LEFT JOIN pacotes p ON p.id = m.id_curso AND m.pacote = 'Sim'

WHERE 

    m.aluno = '$id_aluno'

    

");

$resposta_parcelas = $consulta_parcelas->fetchAll(PDO::FETCH_ASSOC);



// echo '<pre>';

// echo json_encode($resposta_parcelas, JSON_PRETTY_PRINT);

// echo '</pre>';

// return;




$hoje = date('Y-m-d');




// Configurações da EFI
$config = [
    'client_id' => $options['clientId'],
    'client_secret' => $options['clientSecret'],
    'certificate_path' => $options['certificate'], // Apenas para PIX
    'chave_pix' => $options['pixKey'] ?? '', // Sua chave PIX
    'sandbox' => $options['sandbox'] // true para teste, false para produção
];

$pixPayment = new EFIBoletoPayment(
    $config['client_id'],
    $config['client_secret'],
    $config['sandbox']
);

$boletos_efi = ['data' => []];

// $consultarCobranca = $pixPayment->consultarCobranca('884351428');




// function getChargeStatus($chargeId){
 
//     $charge = $pixPayment->consultarCobranca($chargeId);
//     return $charge['data']['status'];
// }

function getChargeStatus($pixPayment, $chargeId) {
    $consultarCobranca = $pixPayment->consultarCobranca($chargeId);
    return $consultarCobranca['data']['status'] ?? 'indefinido';
}


$matriculas_parceladas = [];
foreach ($resposta_parcelas as $parcela) {
  $matId = $parcela['id_matricula'] ?? null;
  if (!empty($matId)) {
    $matriculas_parceladas[(int) $matId] = true;
  }
}

$total_cursos = 0.0;
$total_pago = 0.0;
$total_pendente = 0.0;
$total_vencido = 0.0;

foreach ($matriculas as $mat) {
  $dataMatricula = strtotime($mat['data'] ?? '');
  $dataInicialFiltro = strtotime($dataInicial);
  $dataFinalFiltro = strtotime($dataFinal);

  if ($dataMatricula && ($dataMatricula < $dataInicialFiltro || $dataMatricula > $dataFinalFiltro)) {
    continue;
  }

  $statusMatricula = strtolower(trim($mat['status'] ?? ''));
  if (in_array($statusMatricula, ['matriculado', 'concluido', 'finalizado'], true)) {
    $statusResumo = 'pago';
  } elseif ($statusMatricula === 'vencido') {
    $statusResumo = 'vencido';
  } else {
    $statusResumo = 'pendente';
  }

  if ($statusFiltro !== 'todos' && $statusResumo !== strtolower($statusFiltro)) {
    continue;
  }

  $valorBase = (float) ($mat['subtotal'] ?? 0);
  if ($valorBase <= 0) {
    $valorBase = (float) ($mat['valor'] ?? 0);
  }

  $total_cursos += $valorBase;

  if (empty($matriculas_parceladas[$mat['id']])) {
    if ($statusResumo === 'pago') {
      $total_pago += $valorBase;
    } elseif ($statusResumo === 'vencido') {
      $total_vencido += $valorBase;
    } else {
      $total_pendente += $valorBase;
    }
  }
}

foreach ($resposta_parcelas as $i => $parcela) {
  $statusApi = '';
  if (!empty($parcela['charge_id'])) {
    $statusApi = getChargeStatus($pixPayment, $parcela['charge_id']);
  }

  $statusResumo = 'pendente';
  if (!empty($parcela['situacao']) && (int) $parcela['situacao'] === 1) {
    $statusResumo = 'pago';
  } elseif ($statusApi === 'paid') {
    $statusResumo = 'pago';
  } elseif (in_array($statusApi, ['pending', 'unpaid'], true)) {
    $statusResumo = 'pendente';
  } elseif ($statusApi === 'expired') {
    $statusResumo = 'vencido';
  }

  $resposta_parcelas[$i]['status_resumo'] = $statusResumo;

  $dataMatricula = !empty($parcela['data_matricula']) ? strtotime($parcela['data_matricula']) : null;
  if ($dataMatricula) {
    $dataInicialFiltro = strtotime($dataInicial);
    $dataFinalFiltro = strtotime($dataFinal);
    if ($dataMatricula < $dataInicialFiltro || $dataMatricula > $dataFinalFiltro) {
      continue;
    }
  }

  if ($statusFiltro !== 'todos' && $statusResumo !== strtolower($statusFiltro)) {
    continue;
  }

  $valorParcela = (float) ($parcela['valor_parcela'] ?? 0);
  if ($statusResumo === 'pago') {
    $total_pago += $valorParcela;
  } elseif ($statusResumo === 'vencido') {
    $total_vencido += $valorParcela;
  } else {
    $total_pendente += $valorParcela;
  }
}

$comprovantes = [];
foreach ($resposta_parcelas as $parcela) {
  $urlComprovante = trim($parcela['url_boleto'] ?? '');
  if ($urlComprovante === '') {
    $urlComprovante = trim($parcela['id_asaas'] ?? '');
  }
  if ($urlComprovante === '') {
    $urlComprovante = trim($parcela['transaction_receipt_url'] ?? '');
  }
  if ($urlComprovante === '') {
    continue;
  }
  $tipoComprovante = preg_match('~^https?://~i', $urlComprovante) ? 'url' : 'pix';
  if (($parcela['status_resumo'] ?? '') !== 'pago') {
    continue;
  }

  $dataMatricula = !empty($parcela['data_matricula']) ? strtotime($parcela['data_matricula']) : null;
  if ($dataMatricula) {
    $dataInicialFiltro = strtotime($dataInicial);
    $dataFinalFiltro = strtotime($dataFinal);
    if ($dataMatricula < $dataInicialFiltro || $dataMatricula > $dataFinalFiltro) {
      continue;
    }
  }

  $dataPagamentoRaw = $parcela['data_pagamento'] ?? '';
  $dataPagamento = '-';
  if (!empty($dataPagamentoRaw)) {
    $dataPagamento = date('d/m/Y H:i', strtotime($dataPagamentoRaw));
  }

  $comprovantes[] = [
    'id' => $parcela['id'],
    'pagador' => $nome_aluno,
    'valor' => $parcela['valor_parcela'] ?? 0,
    'status' => $parcela['status_resumo'] ?? 'pendente',
    'data_pagamento' => $dataPagamento,
    'url' => $urlComprovante,
    'tipo' => $tipoComprovante,
  ];
}


?>



<!DOCTYPE html>

<html lang="pt-br">



<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Relatório Financeiro - <?php echo htmlspecialchars($nome_aluno); ?></title>



  <!-- CSS -->



</head>



<body>

  <div id="relatorioPDF" class="container-fluid py-4">

    <style>

      :root {

        --primary-color: #4361ee;

        --secondary-color: #3f37c9;

        --success-color: #4cc9f0;

        --info-color: #4895ef;

        --warning-color: #f72585;

        --danger-color: #e63946;

        --light-color: #f8f9fa;

        --dark-color: #212529;

      }



      .card {

        border-radius: 15px;

        box-shadow: 0 6px 20px rgba(56, 125, 255, 0.17);

        margin-bottom: 20px;

        transition: all 0.3s ease;

        border: none;

      }



      .card:hover {

        transform: translateY(-5px);

        box-shadow: 0 10px 25px rgba(56, 125, 255, 0.25);

      }



      .card-header {

        border-radius: 15px 15px 0 0 !important;

        font-weight: 600;

        padding: 15px 20px;

      }



      .card-body {

        padding: 20px;

      }



      .stats-card {

        text-align: center;

        padding: 20px 15px;

        border-radius: 12px;

        color: white;

      }



      .stats-card .value {

        font-size: 28px;

        font-weight: 700;

        margin-bottom: 5px;

      }



      .stats-card .label {

        font-size: 14px;

        opacity: 0.9;

      }



      .bg-gradient-primary {

        background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);

      }
      
      .bg-gradient-sucesso {

        background: linear-gradient(to bottom, #33cc33 0%, #009900 100%);

      }
      
      .bg-gradient-vencido {
    background: linear-gradient(to bottom, #ff6666 0%, #ff0000 100%);
        }



      .bg-gradient-success {

        background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);

      }



      .bg-gradient-warning {

        background: linear-gradient(135deg, #f72585 0%, #b5179e 100%);

      }



      .bg-gradient-danger {

        background: linear-gradient(135deg, #e63946 0%, #d90429 100%);

      }



      .badge {

        padding: 6px 10px;

        border-radius: 20px;

        font-weight: 500;

        font-size: 12px;

      }



      .badge-success {

        background-color: #03a11d;

        color: #fff;

      }



      .badge-warning {

        background-color: #f72585;

        color: #fff;

      }



      .badge-danger {

        background-color: #e63946;

        color: #fff;

      }



      .badge-info {

        background-color: #4895ef;

        color: #fff;

      }



      .badge-secondary {

        background-color: #3f37c9;

        color: #fff;

      }



      .table {

        border-collapse: separate;

        border-spacing: 0;

        width: 100%;

        border-radius: 10px;

        overflow: hidden;

      }



      .table thead th {

        background-color: #4361ee;

        color: white;

        padding: 12px 15px;

        border: none;

      }



      .table tbody tr:nth-child(even) {

        background-color: rgba(67, 97, 238, 0.05);

      }



      .table tbody td {

        padding: 12px 15px;

        border: none;

        border-bottom: 1px solid #e9ecef;

      }



      .btn-custom {

        padding: 10px 20px;

        border-radius: 50px;

        font-weight: 600;

        letter-spacing: 0.5px;

        transition: all 0.3s;

        border: none;

      }



      .btn-primary {

        background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);

        color: white;

      }



      .btn-primary:hover {

        background: linear-gradient(135deg, #3f37c9 0%, #3a0ca3 100%);

        transform: translateY(-2px);

        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);

      }



      .btn-success {

        background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);

        color: white;

      }



      .btn-success:hover {

        background: linear-gradient(135deg, #4895ef 0%, #3a86ff 100%);

        transform: translateY(-2px);

        box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);

      }



      .form-control {

        border-radius: 50px;

        padding: 10px 20px;

        border: 1px solid #ced4da;

      }



      .form-control:focus {

        border-color: #4361ee;

        box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);

      }



      .profile-card {

        background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);

        color: white;

        padding: 25px 20px;

        border-radius: 15px;

        display: flex;

        align-items: center;

        margin-bottom: 25px;

      }



      .profile-image {

        width: 80px;

        height: 80px;

        background-color: #fff;

        border-radius: 50%;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 30px;

        color: #4361ee;

        margin-right: 20px;

        font-weight: bold;

      }



      .profile-details h2 {

        margin: 0 0 5px 0;

        font-size: 1.8em;

      }



      .profile-details p {

        margin: 0;

        opacity: 0.9;

        font-size: 0.9em;

      }



      .progress {

        height: 10px;

        border-radius: 5px;

        margin-bottom: 5px;

      }



      .progress-bar {

        border-radius: 5px;

      }



      .timeline {

        position: relative;

        padding: 20px 0;

      }



      .timeline-item {

        position: relative;

        padding-left: 40px;

        margin-bottom: 20px;

      }



      .timeline-item:before {

        content: "";

        position: absolute;

        left: 10px;

        top: 0;

        height: 100%;

        width: 2px;

        background-color: #4361ee;

      }



      .timeline-item:last-child:before {

        height: 50%;

      }



      .timeline-item .dot {

        position: absolute;

        left: 0;

        top: 0;

        width: 20px;

        height: 20px;

        border-radius: 50%;

        background-color: #4361ee;

        border: 4px solid #fff;

        box-shadow: 0 0 0 2px #4361ee;

      }



      .timeline-item .date {

        font-size: 12px;

        color: #6c757d;

        margin-bottom: 5px;

      }



      .timeline-item .content {

        padding: 15px;

        background-color: #fff;

        border-radius: 10px;

        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

      }



      .tab-custom {

        display: flex;

        overflow-x: auto;

        margin-bottom: 20px;

        border-bottom: 1px solid #e9ecef;

        padding-bottom: 10px;

      }



      .tab-btn {

        padding: 10px 20px;

        background: none;

        border: none;

        border-radius: 50px;

        cursor: pointer;

        margin-right: 10px;

        white-space: nowrap;

        font-weight: 500;

        transition: all 0.3s;

      }



      .tab-btn.active {

        background-color: #4361ee;

        color: white;

      }



      .tab-btn:not(.active):hover {

        background-color: #e9ecef;

      }



      .tab-content>div:not(.active-tab) {

        display: none;

      }



      .loader {

        border: 4px solid #f3f3f3;

        border-top: 4px solid #4361ee;

        border-radius: 50%;

        width: 30px;

        height: 30px;

        animation: spin 1s linear infinite;

        margin: 20px auto;

      }



      @keyframes spin {

        0% {

          transform: rotate(0deg);

        }



        100% {

          transform: rotate(360deg);

        }

      }



      @media print {

        .no-print {

          display: none !important;

        }



        body {

          background-color: white !important;

        }

      }



      /* Animações */

      @keyframes fadeIn {

        from {

          opacity: 0;

          transform: translateY(20px);

        }



        to {

          opacity: 1;

          transform: translateY(0);

        }

      }



      .animated {

        animation: fadeIn 0.5s ease forwards;

      }



      .delay-1 {

        animation-delay: 0.1s;

      }



      .delay-2 {

        animation-delay: 0.2s;

      }



      .delay-3 {

        animation-delay: 0.3s;

      }



      .delay-4 {

        animation-delay: 0.4s;

      }

    </style>



    <!-- <div style="display: none;"> -->

    <div>

      <!-- Cabeçalho do perfil -->

      <div class="profile-card animated">

        <img class="profile-image" src="../painel-aluno/img/perfil/<?php echo htmlspecialchars($foto); ?>" width="27px"

          class="mr-2">



        <div class="profile-details">

          <h2><?php echo htmlspecialchars($nome_aluno); ?></h2>

          <p><i class="fa fa-id-card"></i> <?php echo htmlspecialchars($cpf_aluno); ?></p>

          <p><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($email_aluno); ?></p>

          <p><i class="fa fa-phone"></i> <?php echo htmlspecialchars($telefone_aluno); ?></p>

        </div>

      </div>



      <!-- Filtros -->

      <div class="card no-print animated delay-1">

        <div class="card-header bg-gradient-primary text-white">

          <i class="fa fa-filter"></i> Filtros

        </div>

        <div class="card-body">

          <form method="GET" action="" class="row">

            <input type="hidden" name="aluno" value="<?php echo $aluno; ?>">



            <div class="col-md-3 mb-3">

              <label for="data_inicial" class="form-label">Data Inicial</label>

              <input type="date" class="form-control" id="data_inicial" name="data_inicial"

                value="<?php echo $dataInicial; ?>">

            </div>



            <div class="col-md-3 mb-3">

              <label for="data_final" class="form-label">Data Final</label>

              <input type="date" class="form-control" id="data_final" name="data_final"

                value="<?php echo $dataFinal; ?>">

            </div>



            <div class="col-md-3 mb-3">

              <label for="status" class="form-label">Status</label>

              <select class="form-control" id="status" name="status">

                <option value="todos" <?php if ($statusFiltro == 'todos')

                  echo 'selected'; ?>>Todos</option>

                <option value="pago" <?php if ($statusFiltro == 'pago')

                  echo 'selected'; ?>>Pagos</option>

                <option value="pendente" <?php if ($statusFiltro == 'pendente')

                  echo 'selected'; ?>>Pendentes

                </option>

                <option value="vencido" <?php if ($statusFiltro == 'vencido')

                  echo 'selected'; ?>>Vencidos

                </option>

              </select>

            </div>



            <div class="col-md-3 mb-3 d-flex align-items-end">

              <div class="btn btn-primary btn-custom">

                <i class="fa fa-search"></i> Filtrar

              </div>

            </div>

          </form>

        </div>

      </div>



      <!-- Estatísticas -->

      <div class="row mt-4 animated delay-2">

        <div class="col-md-3 mb-4">

          <div class="card stats-card bg-gradient-primary">

            <div class="value">R$ <?php echo number_format($total_cursos, 2, ',', '.'); ?></div>

            <div class="label">Total dos Cursos</div>

          </div>

        </div>



        <div class="col-md-3 mb-4">

          <div class="card stats-card bg-gradient-success">

            <div class="value">R$ <?php echo number_format($total_pendente, 2, ',', '.'); ?></div>

            <div class="label">Total Pendente</div>

          </div>

        </div>
        
        <div class="col-md-3 mb-4">

          <div class="card stats-card bg-gradient-sucesso">

            <div class="value">R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></div>

            <div class="label">Total Pago</div>

          </div>

        </div>



        <div class="col-md-3 mb-4">

          <div class="card stats-card bg-gradient-vencido">

            <div class="value">R$ <?php echo number_format($total_vencido, 2, ',', '.'); ?></div>

            <div class="label">Total Vencido</div>

          </div>

        </div>



       
      </div>



      <!-- Gráfico de progresso -->

      <div class="card animated delay-3">

        <div class="card-header bg-gradient-primary text-white">

          <i class="fa fa-chart-line"></i> Resumo Financeiro

        </div>

        <div class="card-body">

          <div class="row">

            <div class="col-md-6">

              <h5>Total de Matrículas: <?php echo count($matriculas); ?></h5>

              <div class="progress mb-3">

                <div class="progress-bar bg-success" role="progressbar"

                  style="width: <?php echo ($total_pago / ($total_pago + $total_pendente + 0.0001)) * 100; ?>%"

                  aria-valuenow="<?php echo ($total_pago / ($total_pago + $total_pendente + 0.0001)) * 100; ?>"

                  aria-valuemin="0" aria-valuemax="100"></div>

              </div>

            </div>

          </div>

        </div>



        <!-- Tab Histórico -->

        <div style="display: none;" id="historico" class="animated delay-4">

          <div class="card">

            <div class="card-header bg-gradient-primary text-white">

              <i class="fa fa-history"></i> Histórico de Pagamentos

            </div>

            <div class="card-body">

              <div class="timeline">

                <?php

                // Ordenar matrículas e parcelas por data para criar um histórico cronológico

                $historico = [];



                // Adicionar matrículas ao histórico

                foreach ($matriculas as $mat) {

                  $historico[] = [

                    'data' => $mat['data'],

                    'tipo' => 'matricula',

                    'descricao' => 'Matrícula no curso ' . $mat['nome_curso'],

                    'valor' => $mat['valor'],

                    'status' => $mat['status'],

                    'forma_pgto' => $mat['forma_pgto']

                  ];

                }



                // // Adicionar parcelas ao histórico

                // foreach ($resposta_parcelas as $parcela) {

                //     // Identificar o curso da parcela

                //     $curso_parcela = $parcela['curso'];

                

                //     $historico[] = [

                //         'data' => $parcela['data_vencimento'],

                //         'tipo' => 'parcela',

                //         'descricao' => 'Parcela ' . $parcela['numero_parcela'] . '/' . $parcela['total_parcelas'] . ' do curso ' . $curso_parcela,

                //         'valor' => $parcela['valor'],

                //         'status' => $parcela['status'],

                //         'forma_pgto' => 'Boleto'

                //     ];

                // }

                

                foreach ($resposta_parcelas as $parcela) {

                  if (

                    isset(

                    $parcela['data_vencimento'],

                    $parcela['numero_parcela'],

                    $parcela['total_parcelas'],

                    $parcela['valor'],

                    $parcela['status'],

                    $parcela['curso']

                  )

                  ) {

                    $curso_parcela = $parcela['curso'];



                    $historico[] = [

                      'data' => $parcela['data_vencimento'],

                      'tipo' => 'parcela',

                      'descricao' => 'Parcela ' . $parcela['numero_parcela'] . '/' . $parcela['total_parcelas'] . ' do curso ' . $curso_parcela,

                      'valor' => $parcela['valor'],

                      'status' => $parcela['status'],

                      'forma_pgto' => 'Boleto'

                    ];

                  } else {

                    // (opcional) Log de depuração para identificar parcelas incompletas

                    error_log('Parcela com dados incompletos: ' . json_encode($parcela));

                  }

                }





                // Ordenar o histórico por data (mais recente primeiro)

                usort($historico, function ($a, $b) {

                  return strtotime($b['data']) - strtotime($a['data']);

                });



                // Filtrar histórico conforme os filtros selecionados

                $historico_filtrado = [];

                foreach ($historico as $item) {

                  // Filtro por status

                  if ($statusFiltro != 'todos' && strtolower($statusFiltro) != strtolower($item['status'])) {

                    continue;

                  }



                  // Filtro por data

                  $dataItem = strtotime($item['data']);

                  $dataInicialFiltro = strtotime($dataInicial);

                  $dataFinalFiltro = strtotime($dataFinal);



                  if ($dataItem < $dataInicialFiltro || $dataItem > $dataFinalFiltro) {

                    continue;

                  }



                  $historico_filtrado[] = $item;

                }



                if (count($historico_filtrado) > 0) {

                  foreach ($historico_filtrado as $item) {

                    ?>

                    <div class="timeline-item">

                      <div class="dot"></div>

                      <div class="date"><?php echo date('d/m/Y', strtotime($item['data'])); ?></div>

                      <div class="content">

                        <h5>

                          <?php echo htmlspecialchars($item['descricao']); ?>

                          <?php if ($item['status'] == 'Pago' || $item['status'] == 'pago') { ?>

                            <span class="badge badge-success">Pago</span>

                          <?php } else if ($item['status'] == 'Pendente' || $item['status'] == 'pendente') {

                            if (strtotime($item['data']) < strtotime('today')) { ?>

                                <span class="badge badge-danger">Vencido</span>

                            <?php } else { ?>

                                <span class="badge badge-warning">Pendente</span>

                            <?php }

                          } else if ($item['status'] == 'Vencido' || $item['status'] == 'vencido') { ?>

                                <span class="badge badge-danger">Vencido</span>

                          <?php } else if ($item['status'] == 'Concluído') { ?>

                                  <span class="badge badge-info">Concluído</span>

                          <?php } else { ?>

                                  <span class="badge badge-secondary"><?php echo $item['status']; ?></span>

                          <?php } ?>

                        </h5>

                        <p>

                          <strong>Valor:</strong> R$

                          <?php echo number_format($item['valor'], 2, ',', '.'); ?>

                          <br>

                          <strong>Forma de Pagamento:</strong> <?php echo $item['forma_pgto']; ?>

                        </p>

                      </div>

                    </div>

                    <?php

                  }

                } else {

                  ?>

                  <div class="text-center">

                    <p>Nenhum histórico encontrado com os filtros selecionados.</p>

                  </div>

                <?php } ?>

              </div>

            </div>

          </div>

        </div>

      </div>



      <!-- Botões de Ação -->

      <div class="row mt-4 no-print animated delay-4">

        <div class="col-md-12 d-flex justify-content-end">

          <button class="btn btn-success btn-custom me-2" id="btn-pdf">

            <i class="fa fa-file-pdf"></i> Gerar PDF

          </button>

          <button class="btn btn-primary btn-custom" id="btn-print">

            <i class="fa fa-print"></i> Imprimir

          </button>

        </div>

      </div>

    </div>











    <div style="margin-bottom: 140px;">

      <!-- Tabs para diferentes seções -->

      <div class="tab-custom no-print animated delay-3">

        <button class="tab-btn active" data-tab="cursos">

          <i class="fa fa-graduation-cap"></i> Cursos

        </button>

        <button class="tab-btn" data-tab="pacotes">

          <i class="fa fa-graduation-cap"></i> Pacotes

        </button>

        <button class="tab-btn" data-tab="parcelas">

          <i class="fa fa-money"></i> Parcelas

        </button>

         <!-- <button class="tab-btn" data-tab="boletos_efi">

          <i class="fa fa-history"></i> Boletos EFI

        </button> -->

        <button class="tab-btn" data-tab="comprovantes">

          <i class="fa fa-calendar"></i> Comprovantes

        </button>

      </div>



      <div class="tab-content">

        <!-- Tab Cursos -->

        <div id="cursos" class="active-tab animated delay-4">

          <div class="card">

            <div class="card-header bg-gradient-primary text-white">

              <i class="fa fa-graduation-cap"></i> Cursos do Aluno

            </div>

            <div class="card-body">

              <div class="table-responsive">

                <table class="table">

                  <thead>

                    <tr>

                      <th width="5%">ID</th>

                      <th width="25%">Curso</th>

                      <th width="15%">Data</th>

                      <th width="15%">Forma Pgto.</th>

                      <th width="15%">Valor</th>

                      <th width="15%">Status</th>

                      <th width="10%">Ações</th>

                    </tr>

                  </thead>

                  <tbody>

                    <?php

                    if (count($matriculas_cursos) > 0) {

                      foreach ($matriculas_cursos as $mat) {

                        // Filtro por status

                        if ($statusFiltro != 'todos') {

                          if (strtolower($statusFiltro) != strtolower($mat['status'])) {

                            continue;

                          }

                        }



                        // Filtro por data

                        $dataMatricula = strtotime($mat['data']);

                        $dataInicialFiltro = strtotime($dataInicial);

                        $dataFinalFiltro = strtotime($dataFinal);



                        if ($dataMatricula < $dataInicialFiltro || $dataMatricula > $dataFinalFiltro) {

                          continue;

                        }

                        ?>

                        <tr>

                          <td><?php echo $mat['id']; ?></td>

                          <td><?php echo htmlspecialchars($mat['nome_curso']); ?></td>

                          <td><?php echo date('d/m/Y', strtotime($mat['data'])); ?></td>

                          <td><?php echo $mat['forma_pgto'] ? $mat['forma_pgto'] : 'Desconhecido'; ?></td>

                          <td>R$ <?php echo number_format($mat['valor'], 2, ',', '.'); ?></td>

                          <td>

                            <?php if ($mat['status'] == 'Finalizado') { ?>

                              <span class="badge badge-success">Finalizado</span>

                            <?php } else if ($mat['status'] == 'Matriculado') { ?>

                                <span class="badge badge-success">Matriculado</span>

                            <?php } else if ($mat['status'] == 'Aguardando') { ?>

                                  <span class="badge badge-danger">Aguardando</span>

                            <?php } else if ($mat['status'] == 'Concluído') { ?>

                                    <span class="badge badge-info">Concluído</span>

                            <?php } else { ?>

                                    <span class="badge badge-secondary"><?php echo $mat['status']; ?></span>

                            <?php } ?>

                          </td>

                          <td>

                            <button onclick='verDetalhes(<?php echo json_encode($mat); ?>)' class="btn btn-sm btn-primary">

                              <i class="fa fa-eye"></i>

                            </button>

                          </td>

                        </tr>

                        <?php

                      }

                    } else {

                      ?>

                      <tr>

                        <td colspan="7" class="text-center">Nenhum curso encontrado</td>

                      </tr>

                    <?php } ?>

                  </tbody>

                </table>

              </div>

            </div>

          </div>

        </div>



        <!-- Tab Pacotes -->

        <div id="pacotes" class="animated delay-4">

          <div class="card">

            <div class="card-header bg-gradient-primary text-white">

              <i class="fa fa-box"></i> Pacotes do Aluno

            </div>

            <div class="card-body">

              <div class="table-responsive">

                <table class="table">

                  <thead>

                    <tr>

                      <th width="5%">ID</th>

                      <th width="25%">Pacote</th>

                      <th width="15%">Data</th>

                      <th width="15%">Forma Pgto.</th>

                      <th width="15%">Valor</th>

                      <th width="15%">Status</th>

                      <th width="10%">Ações</th>

                    </tr>

                  </thead>

                  <tbody>

                    <?php

                    if (count($matriculas_pacotes) > 0) {

                      foreach ($matriculas_pacotes as $mat) {

                        // Filtro por status

                        if ($statusFiltro != 'todos') {

                          if (strtolower($statusFiltro) != strtolower($mat['status'])) {

                            continue;

                          }

                        }



                        // Filtro por data

                        $dataMatricula = strtotime($mat['data']);

                        $dataInicialFiltro = strtotime($dataInicial);

                        $dataFinalFiltro = strtotime($dataFinal);



                        if ($dataMatricula < $dataInicialFiltro || $dataMatricula > $dataFinalFiltro) {

                          continue;

                        }

                        ?>

                        <tr>

                          <td><?php echo $mat['id']; ?></td>

                          <td><?php echo htmlspecialchars($mat['nome_curso']); ?></td>

                          <td><?php echo date('d/m/Y', strtotime($mat['data'])); ?></td>

                          <td><?php echo $mat['forma_pgto'] ? $mat['forma_pgto'] : 'Desconhecido'; ?></td>

                          <td>R$ <?php echo number_format($mat['valor'], 2, ',', '.'); ?></td>

                          <td>

                            <span class="badge badge-success">Ativo</span>

                          </td>

                          <td>

                            <button onclick='verDetalhes(<?php echo json_encode($mat); ?>)' class="btn btn-sm btn-primary">

                              <i class="fa fa-eye"></i>

                            </button>

                          </td>

                        </tr>

                        <?php

                      }

                    } else {

                      ?>

                      <tr>

                        <td colspan="7" class="text-center">Nenhum pacote encontrado</td>

                      </tr>

                    <?php } ?>

                  </tbody>

                </table>

              </div>

            </div>

          </div>

        </div>



        <!-- Tab Parcelas (Mantido como estava) -->

        <div id="parcelas" class="animated delay-4">

          <div class="card">

            <div class="card-header bg-gradient-primary text-white">

              <i class="fa fa-money-bill"></i> Parcelas do Alunos

            </div>

            <div class="card-body">

              <div class="table-responsive">

                <table class="table">

                  <thead>

                    <tr>

                      <th>#</th>

                      <th>Curso</th>

                      <th>Nº Parcela</th>

                      <th>Valor</th>

                      <th>Status</th>

                    </tr>

                  </thead>

                  <tbody>

                    <?php

                    if (count($resposta_parcelas) > 0) {

                      foreach ($resposta_parcelas as $parcela) {
                        
                        // Filtro por status

                        if ($statusFiltro != 'todos') {

                          if (strtolower($statusFiltro) != strtolower($parcela['status_resumo'] ?? '')) {

                            continue;

                          }

                        }



                        // Filtro por data

                        // $dataVencimento = strtotime($parcela['data_vencimento']);

                        // $dataInicialFiltro = strtotime($dataInicial);

                        // $dataFinalFiltro = strtotime($dataFinal);

                    

                        // if ($dataVencimento < $dataInicialFiltro || $dataVencimento > $dataFinalFiltro) {

                        //     continue;

                        // }
                        
                        

                        ?>

                        <tr>

                          <td><?php echo $parcela['id']; ?></td>
                    

                          <td><?php echo htmlspecialchars($parcela['curso']); ?></td>

                          <td><?php echo htmlspecialchars($parcela['ordem_parcela']); ?></td>



                          <td>R$ <?php echo number_format($parcela['valor_parcela'], 2, ',', '.'); ?></td>







<td>
  <?php
  $statusResumo = $parcela['status_resumo'] ?? 'pendente';
  if (empty($parcela['charge_id']) && empty($parcela['id_asaas'])) {
      echo '<span class="badge">Nao Gerado</span>';
  } elseif ($statusResumo === 'pago') {
      echo '<span class="badge badge-success">Pago</span>';
  } elseif ($statusResumo === 'vencido') {
      echo '<span class="badge badge-danger">Vencido</span>';
  } else {
      echo '<span class="badge badge-warning">Pendente</span>';
  }
  ?>
</td>

                        </tr>

                        <?php

                      }

                    } else {

                      ?>

                      <tr>

                        <td colspan="6" class="text-center">Nenhuma parcela encontrada</td>

                      </tr>

                    <?php } ?>

                  </tbody>

                </table>

              </div>

            </div>

          </div>

        </div>

        <!-- Tab Boletos EFI (NOVO) -->
        <!-- <div id="boletos_efi" class="animated delay-4">
          
            <div class="card">

                  <div class="card-header bg-gradient-primary text-white">

                    <i class="fa fa-history"></i> Boletos EFI

                  </div>

                     <div class="card-body">

                      <div class="table-responsive">

                       <table class="table">
  <thead>
    <tr>
      <th>#</th>
      <th>Pagador</th>
      <th>CPF</th>
      <th>Telefone</th>
      <th>Data Criação</th>
      <th>Nº Parcela</th>
      <th>Valor</th>
      <th>Status</th>
      <th>Data Pagamento</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php
      // Ordenar boletos pelo ID
      usort($boletos_efi['data'], fn($a, $b) => $a['id'] <=> $b['id']);
      $parcela = 1;

      foreach($boletos_efi['data'] as $boleto_efi):
          $valor = number_format($boleto_efi['total'] / 100, 2, ',', '.');

          // Status formatado
          if ($boleto_efi['status'] === 'paid') {
              $status = '<span style="background:green; color:white; padding:4px 8px; border-radius:6px;">PAGO</span>';
          } elseif ($boleto_efi['status'] === 'waiting') {
              $status = '<span style="background:orange; color:white; padding:4px 8px; border-radius:6px;">PENDENTE</span>';
          } else {
              $status = '<span style="background:gray; color:white; padding:4px 8px; border-radius:6px;">AGUARDANDO</span>';
          }

          // Data de pagamento se existir
          $dataPagamento = !empty($boleto_efi['payment']['paid_at']) 
              ? date("d/m/Y H:i", strtotime($boleto_efi['payment']['paid_at'])) 
              : '-';

          // Link de pagamento (boleto/pdf)
          $linkPagamento = $boleto_efi['payment']['banking_billet']['link'] ?? null;
    ?>
      <tr>
        <td><?= $boleto_efi['id'] ?></td>
        <td><?= $boleto_efi['customer']['name'] ?></td>
        <td><?= $boleto_efi['customer']['cpf'] ?></td>
        <td><?= $telefone_aluno ?></td>
        <td><?= date("d/m/Y H:i", strtotime($boleto_efi['created_at'])) ?></td>
        <td><?= $parcela++ ?></td>
        <td>R$ <?= $valor ?></td>
        <td><?= $status ?></td>
        <td><?= $dataPagamento ?></td>
        <td>
          <?php if ($linkPagamento): ?>
            <button onclick="abrirBoleto('<?= $linkPagamento ?>')" style="background:#007bff; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;">
              👁️
            </button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>



                      </div>

                    </div>

                  </div>
            
        </div> -->


          <!-- Tab Comprovantes -->
        <div id="comprovantes" class="animated delay-4">
          
            <div class="card">

                  <div class="card-header bg-gradient-primary text-white">

                    <i class="fa fa-calendar"></i> Comprovantes de Pagamentos

                  </div>

                     <div class="card-body">

                      <div class="table-responsive">

                       <table class="table">
  <thead>
    <tr>
      <th>#</th>
      <th>Pagador</th>
      <th>Valor</th>
      <th>Status</th>
      <th>Data Pagamento</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php
      if (!empty($comprovantes)) {
        foreach ($comprovantes as $comp) {
          $valor = number_format((float) ($comp['valor'] ?? 0), 2, ',', '.');
          if (($comp['status'] ?? '') === 'pago') {
            $status = '<span style="background:green; color:white; padding:4px 8px; border-radius:6px;">BOLETO PAGO</span>';
          } elseif (($comp['status'] ?? '') === 'vencido') {
            $status = '<span style="background:red; color:white; padding:4px 8px; border-radius:6px;">BOLETO VENCIDO</span>';
          } else {
            $status = '<span style="background:gray; color:white; padding:4px 8px; border-radius:6px;">AGUARDANDO PAGAMENTO</span>';
          }
          $dataPagamento = $comp['data_pagamento'] ?? '-';
    ?>
      <tr>
        <td><?= htmlspecialchars($comp['id']) ?></td>
        <td><?= htmlspecialchars($comp['pagador']) ?></td>
        <td>R$ <?= $valor ?></td>
        <td><?= $status ?></td>
        <td><?= htmlspecialchars($dataPagamento) ?></td>
        <td>
          <?php if (!empty($comp['url'])): ?>
            <button onclick="abrirBoleto('<?= htmlspecialchars($comp['url'], ENT_QUOTES); ?>')" style="background:#007bff; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;">
              Ver comprovante
            </button>
          <?php endif; ?>
        </td>
      </tr>
    <?php
        }
      } else {
        echo '<tr><td colspan="6" class="text-center">Nenhum comprovante encontrado</td></tr>';
      }
    ?>
  </tbody>
</table>



                      </div>

                    </div>

                  </div>
            
        </div>

        </div>

      

      </div>

    </div>







  </div>







  <!-- Modal de Detalhes -->

  <div class="modal fade" id="detalhesModal" tabindex="-1" aria-labelledby="detalhesModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-lg">

      <div class="modal-content">

        <div class="modal-header bg-gradient-primary text-white">

          <h5 class="modal-title" id="detalhesModalLabel">Detalhes da Matrícula</h5>

          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

        </div>

        <div class="modal-body">

          <div class="loader" id="modal-loader"></div>

          <div id="detalhes-conteudo"></div>

        </div>

        <div class="modal-footer">

          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>

        </div>

      </div>

    </div>

  </div>





  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/3.0.1/jspdf.umd.min.js"

    integrity="sha512-ad3j5/L4h648YM/KObaUfjCsZRBP9sAOmpjaT2BDx6u9aBrKFp7SbeHykruy83rxfmG42+5QqeL/ngcojglbJw=="

    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"

    integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA=="

    crossorigin="anonymous" referrerpolicy="no-referrer"></script>



  <script>

    // Gerenciar Tabs

    document.addEventListener('DOMContentLoaded', function () {

      const tabBtns = document.querySelectorAll('.tab-btn');

      const tabContents = document.querySelectorAll('.tab-content > div');



      tabBtns.forEach(btn => {

        btn.addEventListener('click', function () {

          const tabId = this.getAttribute('data-tab');



          // Remover classe ativa de todas as tabs

          tabBtns.forEach(b => b.classList.remove('active'));

          tabContents.forEach(c => c.classList.remove('active-tab'));



          // Adicionar classe ativa à tab clicada

          this.classList.add('active');

          document.getElementById(tabId).classList.add('active-tab');

        });

      });



      // Modal detalhes



      // Gerar PDF

      // document.getElementById('btn-pdf').addEventListener('click', function () {

      //   window.scrollTo(0, 0);



      //   const { jsPDF } = window.jspdf;

      //   const doc = new jsPDF('p', 'mm', 'a4');



      //   // Adicionar classe para esconder elementos que não devem aparecer no PDF

      //   document.querySelectorAll('.no-print').forEach(el => {

      //     el.style.display = 'none';

      //   });



      //   // Usar HTML2Canvas para renderizar o relatório

      //   html2canvas(document.body, {

      //     scale: 2,

      //     useCORS: true,

      //     logging: false

      //   }).then(canvas => {

      //     const imgData = canvas.toDataURL('image/jpeg', 1.0);

      //     const imgProps = doc.getImageProperties(imgData);

      //     const pdfWidth = doc.internal.pageSize.getWidth();

      //     const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;



      //     doc.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);

      //     doc.save(`Relatorio_${<?php echo json_encode($nome_aluno); ?>}_${new Date().toISOString().slice(0, 10)}.pdf`);



      //     // Restaurar elementos escondidos

      //     document.querySelectorAll('.no-print').forEach(el => {

      //       el.style.display = '';

      //     });

      //   });

      // });





      document.getElementById('btn-pdf').addEventListener('click', function () {

        // Referência ao elemento que contém o conteúdo a ser transformado em PDF

        const relatorioElement = document.getElementById('relatorioPDF');



        if (!relatorioElement) {

          console.error('Elemento com ID "relatorioPDF" não encontrado.');

          return;

        }



        // Resetar scroll para o topo

        window.scrollTo(0, 0);



        const { jsPDF } = window.jspdf;

        const doc = new jsPDF('p', 'mm', 'a4');



        // Adicionar classe para esconder elementos que não devem aparecer no PDF

        document.querySelectorAll('.no-print').forEach(el => {

          el.style.display = 'none';

        });



        // Usar HTML2Canvas para renderizar apenas o conteúdo da div específica

        html2canvas(relatorioElement, {

          scale: 2,

          useCORS: true,

          logging: false

        }).then(canvas => {

          const imgData = canvas.toDataURL('image/jpeg', 1.0);

          const imgProps = doc.getImageProperties(imgData);

          const pdfWidth = doc.internal.pageSize.getWidth();

          const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;



          doc.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);

          doc.save(`Relatorio_${<?php echo json_encode($nome_aluno); ?>}_${new Date().toISOString().slice(0, 10)}.pdf`);



          // Restaurar elementos escondidos

          document.querySelectorAll('.no-print').forEach(el => {

            el.style.display = '';

          });

        });

      });



      // // Imprimir

      // document.getElementById('btn-print').addEventListener('click', function () {

      //   window.print();

      // });



      document.getElementById('btn-print').addEventListener('click', function () {

        // Referência ao elemento que contém o conteúdo a ser impresso

        const relatorioElement = document.getElementById('relatorioPDF');



        if (!relatorioElement) {

          console.error('Elemento com ID "relatorioPDF" não encontrado.');

          return;

        }



        // Cria um iframe temporário para conter apenas o conteúdo que queremos imprimir

        const printIframe = document.createElement('iframe');

        printIframe.style.position = 'absolute';

        printIframe.style.top = '-9999px';

        printIframe.style.left = '-9999px';

        document.body.appendChild(printIframe);



        // Adiciona o conteúdo da div ao iframe

        const iframeDoc = printIframe.contentDocument || printIframe.contentWindow.document;

        iframeDoc.open();



        // Adicionamos um HTML básico, incluindo os estilos da página atual

        iframeDoc.write(`

    <!DOCTYPE html>

    <html>

    <head>

      <title>Impressão de Relatório</title>

      <meta charset="utf-8">

      <style>

        /* Copiar estilos relevantes da página principal */

        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }

        

        /* Você pode adicionar aqui mais estilos específicos para impressão */

        @media print {

          body { padding: 0; }

          .no-print { display: none !important; }

        }

      </style>

    </head>

    <body>

      ${relatorioElement.outerHTML}

    </body>

    </html>

  `);

        iframeDoc.close();



        // Esconder elementos com classe no-print dentro do iframe

        const noPrintElements = iframeDoc.querySelectorAll('.no-print');

        noPrintElements.forEach(el => {

          el.style.display = 'none';

        });



        // Espera um pouco para garantir que o conteúdo seja carregado

        setTimeout(() => {

          // Foca no iframe e imprime seu conteúdo

          printIframe.contentWindow.focus();

          printIframe.contentWindow.print();



          // Remove o iframe após a impressão (ou depois de um tempo)

          setTimeout(() => {

            document.body.removeChild(printIframe);

          }, 1000);

        }, 500);

      });

    });

  </script>





  <style>

    /* Customização do SweetAlert2 */

    .financial-modal .swal2-popup {

      background: linear-gradient(135deg, #1a2035 0%, #121625 100%);

      border-radius: 16px;

      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);

      border: 1px solid rgba(83, 92, 136, 0.3);

      padding: 0;

    }



    .financial-modal .swal2-title {

      color: #fff;

      font-family: 'Poppins', sans-serif;

      font-weight: 600;

      padding: 1.5rem 1.5rem 0.5rem;

      font-size: 1.5rem;

      border-bottom: 1px solid rgba(83, 92, 136, 0.2);

      margin: 0;

    }



    .financial-modal .swal2-html-container {

      padding: 0;

      margin: 0;

    }



    .financial-modal .swal2-actions {

      margin: 0;

      padding: 1rem;

      border-top: 1px solid rgba(83, 92, 136, 0.2);

    }



    .financial-modal .swal2-styled.swal2-confirm {

      background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);

      border-radius: 8px;

      font-weight: 600;

      padding: 0.75rem 2rem;

      box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);

      border: none;

    }



    .financial-modal .swal2-styled.swal2-confirm:hover {

      background: linear-gradient(135deg, #2a6ac4 0%, #00b3ee 100%);

      transform: translateY(-2px);

      transition: all 0.3s ease;

    }



    .financial-modal .swal2-icon {

      margin: 1.5rem auto 0.5rem;

    }



    /* Conteúdo do Modal */

    .matricula-card {

      background-color: transparent;

      color: #fff;

      font-family: 'Poppins', sans-serif;

    }



    .header-info {

      background: linear-gradient(90deg, rgba(88, 103, 221, 0.1) 0%, rgba(0, 210, 255, 0.1) 100%);

      border-radius: 8px;

      padding: 1.25rem;

      margin-bottom: 1.5rem;

      position: relative;

      overflow: hidden;

      display: flex;

      align-items: center;

      justify-content: space-between;

    }



    .header-info::before {

      content: '';

      position: absolute;

      top: 0;

      left: 0;

      width: 4px;

      height: 100%;

      background: linear-gradient(180deg, #586BDD 0%, #00D2FF 100%);

    }



    .status-indicator {

      height: 60px;

      width: 60px;

      border-radius: 50%;

      display: flex;

      align-items: center;

      justify-content: center;

    }



    .bg-pago {

      background: linear-gradient(135deg, rgba(56, 196, 138, 0.2) 0%, rgba(56, 196, 138, 0.05) 100%);

      border: 1px solid rgba(56, 196, 138, 0.3);

      color: #38C48A;

    }



    .bg-pendente {

      background: linear-gradient(135deg, rgba(255, 184, 34, 0.2) 0%, rgba(255, 184, 34, 0.05) 100%);

      border: 1px solid rgba(255, 184, 34, 0.3);

      color: #FFB822;

    }



    .bg-vencido {

      background: linear-gradient(135deg, rgba(244, 81, 108, 0.2) 0%, rgba(244, 81, 108, 0.05) 100%);

      border: 1px solid rgba(244, 81, 108, 0.3);

      color: #F4516C;

    }



    .bg-concluido {

      background: linear-gradient(135deg, rgba(85, 120, 235, 0.2) 0%, rgba(85, 120, 235, 0.05) 100%);

      border: 1px solid rgba(85, 120, 235, 0.3);

      color: #5578EB;

    }



    .status-indicator i {

      font-size: 1.5rem;

    }



    .status-badge {

      display: inline-block;

      padding: 6px 12px;

      border-radius: 30px;

      font-size: 0.8rem;

      font-weight: 600;

      text-transform: uppercase;

      letter-spacing: 0.5px;

    }



    .badge-pago {

      background: linear-gradient(135deg, #38C48A 0%, #28A745 100%);

      color: white;

      box-shadow: 0 2px 8px rgba(56, 196, 138, 0.3);

    }



    .badge-pendente {

      background: linear-gradient(135deg, #FFB822 0%, #F9A825 100%);

      color: white;

      box-shadow: 0 2px 8px rgba(255, 184, 34, 0.3);

    }



    .badge-vencido {

      background: linear-gradient(135deg, #F4516C 0%, #E53935 100%);

      color: white;

      box-shadow: 0 2px 8px rgba(244, 81, 108, 0.3);

    }



    .badge-concluido {

      background: linear-gradient(135deg, #5578EB 0%, #4E73DF 100%);

      color: white;

      box-shadow: 0 2px 8px rgba(85, 120, 235, 0.3);

    }



    .info-grid {

      display: grid;

      grid-template-columns: repeat(2, 1fr);

      gap: 1.5rem;

      padding: 0 1.25rem;

    }



    .info-card {

      background: rgba(83, 92, 136, 0.1);

      border-radius: 8px;

      padding: 1rem;

      position: relative;

      border: 1px solid rgba(83, 92, 136, 0.2);

    }



    .info-card .label {

      font-size: 0.75rem;

      text-transform: uppercase;

      letter-spacing: 1px;

      color: #8a94a6;

      margin-bottom: 0.3rem;

    }



    .info-card .value {

      font-size: 1rem;

      font-weight: 600;

      color: #fff;

    }



    .highlight-box {

      background: linear-gradient(135deg, rgba(88, 103, 221, 0.15) 0%, rgba(0, 210, 255, 0.05) 100%);

      border: 1px solid rgba(83, 92, 136, 0.2);

      border-radius: 10px;

      padding: 1.5rem;

      margin: 2rem 1.25rem 1.5rem;

      position: relative;

    }



    .highlight-box .icon {

      position: absolute;

      right: 1.5rem;

      top: 1.5rem;

      font-size: 2rem;

      color: rgba(0, 210, 255, 0.2);

    }



    .highlight-box .label {

      font-size: 0.85rem;

      color: #8a94a6;

      margin-bottom: 0.5rem;

    }



    .highlight-box .value {

      font-size: 1.75rem;

      font-weight: 700;

      color: #fff;

      background: linear-gradient(90deg, #586BDD 0%, #00D2FF 100%);

      -webkit-background-clip: text;

      -webkit-text-fill-color: transparent;

    }



    .matricula-id {

      font-size: 0.85rem;

      opacity: 0.7;

      margin-top: 0.5rem;

    }



    .curso-nome {

      font-size: 1.5rem;

      font-weight: 600;

      margin-bottom: 0.5rem;

    }



    /* Animações */

    @keyframes fadeInUp {

      from {

        opacity: 0;

        transform: translateY(20px);

      }



      to {

        opacity: 1;

        transform: translateY(0);

      }

    }



    .animate-fadeInUp {

      animation: fadeInUp 0.5s ease forwards;

    }



    .delay-1 {

      animation-delay: 0.1s;

    }



    .delay-2 {

      animation-delay: 0.2s;

    }



    .delay-3 {

      animation-delay: 0.3s;

    }



    .delay-4 {

      animation-delay: 0.4s;

    }

  </style>



  <script>

    function verDetalhes(matricula) {

      // Formatar data e valor para exibição

      const dataFormatada = new Date(matricula.data).toLocaleDateString('pt-BR');

      const valorFormatado = parseFloat(matricula.valor).toLocaleString('pt-BR', {

        minimumFractionDigits: 2,

        maximumFractionDigits: 2

      });



      // Definir classes e ícones de acordo com o status

      let statusClass = '';

      let iconClass = '';

      let badgeClass = '';



      switch (matricula.status) {

        case 'Matriculado':

          statusClass = 'bg-pago';

          iconClass = 'fa-check-circle';

          badgeClass = 'badge-pago';

          break;

        case 'Pendente':

          statusClass = 'bg-pendente';

          iconClass = 'fa-clock';

          badgeClass = 'badge-pendente';

          break;

        case 'Aguardando':

          statusClass = 'bg-vencido';

          iconClass = 'fa-exclamation-circle';

          badgeClass = 'badge-vencido';

          break;

        case 'Concluído':

          statusClass = 'bg-concluido';

          iconClass = 'fa-check-double';

          badgeClass = 'badge-concluido';

          break;

        default:

          statusClass = 'bg-secondary';

          iconClass = 'fa-graduation-cap';

          badgeClass = 'badge-secondary';

      }



      // Montar o HTML para o conteúdo do SweetAlert

      const conteudoHtml = `

    <div class="matricula-card">

      <div class="header-info animate-fadeInUp">

        <div style="display: flex; flex-direction: row; gap: 10px;">

          <div class="curso-nome">${matricula.nome_curso}</div>

          <div class="matricula-id">ID: ${matricula.id}</div>

        </div>

        <div class="status-indicator ${statusClass}">

          <i class="fa ${iconClass}"></i>

        </div>

      </div>

      

      <div class="highlight-box animate-fadeInUp delay-1">

        <i class="fas fa-money-bill-wave icon"></i>

        <div class="label">Valor total</div>

        <div class="value">R$ ${valorFormatado}</div>

        <div class="status-badge ${badgeClass} mt-3">${matricula.status}</div>

      </div>

      

      <div class="info-grid">

        <div class="info-card animate-fadeInUp delay-2">

          <div class="label">Data de inscrição</div>

          <div class="value">${dataFormatada}</div>

        </div>

        

        <div class="info-card animate-fadeInUp delay-3">

          <div class="label">Forma de pagamento</div>

          <div class="value">${matricula.forma_pgto ? matricula.forma_pgto : 'Desconhecido'}</div>

        </div>

      </div>

    </div>

  `;



      // Configurar e exibir o SweetAlert com design personalizado

      Swal.fire({

        title: 'Detalhes da Matrícula',

        html: conteudoHtml,

        showConfirmButton: true,

        confirmButtonText: 'Fechar',

        customClass: {

          popup: 'swal2-popup',

          title: 'swal2-title',

          htmlContainer: 'swal2-html-container',

          actions: 'swal2-actions',

          confirmButton: 'swal2-styled swal2-confirm',

          container: 'financial-modal'

        },

        showClass: {

          popup: 'animate__animated animate__fadeIn'

        },

        hideClass: {

          popup: 'animate__animated animate__fadeOut'

        },

        width: '850px',

        padding: 0,

        // background: 'transparent',

        // backdrop: `rgba(18, 22, 37, 0.8)`,

        allowOutsideClick: true

      });

    }

  </script>


<script>
function abrirBoleto(link) {
  const linkBruto = (link || '').trim();
  if (!linkBruto) {
    Swal.fire({
      icon: 'warning',
      title: 'Comprovante indisponível',
      text: 'Não há link válido para visualizar.'
    });
    return;
  }

  let linkResolvido = linkBruto;
  if (linkResolvido.startsWith('/')) {
    linkResolvido = `${window.location.origin}${linkResolvido}`;
  }

  if (!/^https?:\/\//i.test(linkResolvido)) {
    const linkSeguro = linkResolvido
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');

    Swal.fire({
      title: 'Comprovante PIX',
      html: `
        <p style="margin-bottom:10px;">Copia e cola PIX:</p>
        <textarea id="pix-code" style="width:100%; height:140px; border:1px solid #ddd; border-radius:6px; padding:8px;" readonly>${linkSeguro}</textarea>
        <button id="btn-copiar-pix" style="margin-top:10px; background:#007bff; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer;">
          Copiar
        </button>
      `,
      showCloseButton: true,
      showConfirmButton: false,
      didOpen: () => {
        const btn = document.getElementById('btn-copiar-pix');
        if (btn) {
          btn.addEventListener('click', () => {
            const textarea = document.getElementById('pix-code');
            if (textarea) {
              textarea.select();
              textarea.setSelectionRange(0, 99999);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
              navigator.clipboard.writeText(linkResolvido);
            } else {
              document.execCommand('copy');
            }
            Swal.fire({
              icon: 'success',
              title: 'Copiado!',
              timer: 1200,
              showConfirmButton: false
            });
          });
        }
      }
    });
    return;
  }

  Swal.fire({
    title: 'Visualizar Boleto',
    html: `
      <iframe src="${linkResolvido}" width="80%" height="650px" style="border:none; border-radius:8px;"></iframe>
    `,
    width: '80%',
    heightAuto: false,
    showCloseButton: true,
    showConfirmButton: false
  });
}
</script>


</body>



</html>
