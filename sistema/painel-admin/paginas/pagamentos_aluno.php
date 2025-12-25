<?php
require_once('../conexao.php');
require_once('verificar.php');
require_once __DIR__ . '/../../../efi/pix.php';
require_once __DIR__ . '/../../../efi/boleto.php';
$options = require_once __DIR__ . '/../../../efi/options.php';
$pag = 'pagamentos_aluno';

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

$dados_aluno = $pdo->prepare("SELECT email FROM alunos WHERE id = :id");
$dados_aluno->execute([':id' => $aluno]);
$resposta_aluno = $dados_aluno->fetchAll(PDO::FETCH_ASSOC);
$email_aluno = $resposta_aluno[0]['email'];

$dados_usuario_aluno = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
$dados_usuario_aluno->execute([':usuario' => $email_aluno]);
$resposta_usuario_aluno = $dados_usuario_aluno->fetchAll(PDO::FETCH_ASSOC);
$id_aluno = $resposta_usuario_aluno[0]['id'];




$consulta_parcelas = $pdo->prepare("SELECT parcelas_geradas_por_boleto.*, cursos.nome as curso FROM parcelas_geradas_por_boleto JOIN boletos_parcelados ON boletos_parcelados.id = parcelas_geradas_por_boleto.id_boleto_parcelado JOIN matriculas ON matriculas.id = boletos_parcelados.id_matricula JOIN cursos ON cursos.id = matriculas.id_curso WHERE matriculas.aluno = :aluno");
$consulta_parcelas->execute([':aluno' => $id_aluno]);
$resposta_consulta = $consulta_parcelas->fetchAll(PDO::FETCH_ASSOC);



// Consultas separadas por forma de pagamento
$formas_pagamento = ['BOLETO', 'PIX', 'MP'];
$pagamentos = [];

// foreach ($formas_pagamento as $forma) {
//  $query = $pdo->prepare("
//         SELECT m.id, m.id_curso, m.data, m.forma_pgto, m.valor, m.status, m.id_asaas, 
//                c.nome as nome_curso
//         FROM matriculas m
//         JOIN cursos c ON m.id_curso = c.id
//         WHERE m.aluno = :aluno AND m.forma_pgto = :forma
//         ORDER BY m.id DESC
//     ");
//  $query->execute(['aluno' => $id_aluno, 'forma' => $forma]);
//  $pagamentos[$forma] = $query->fetchAll(PDO::FETCH_ASSOC);
// }

foreach ($formas_pagamento as $forma) {
    $query = $pdo->prepare("
        SELECT m.id, m.id_curso, m.data, m.forma_pgto, m.valor, m.status, m.id_asaas, 
               c.nome as nome_curso,
               pb.url_boleto
        FROM matriculas m
        JOIN cursos c ON m.id_curso = c.id
        LEFT JOIN pagamentos_boleto pb ON m.id = pb.id_matricula
        WHERE m.aluno = :aluno AND m.forma_pgto = :forma
        ORDER BY m.id DESC
    ");
    $query->execute(['aluno' => $id_aluno, 'forma' => $forma]);
    $pagamentos[$forma] = $query->fetchAll(PDO::FETCH_ASSOC);
}






// Consultar nome do aluno
$queryAluno = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :aluno");
$queryAluno->execute(['aluno' => $id_aluno]);
$resAluno = $queryAluno->fetch(PDO::FETCH_ASSOC);

$nome_aluno = $resAluno['nome'] ?? 'Desconhecido';



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




$consultarCobranca = $pixPayment->consultarCobranca('922675015');

$data = $consultarCobranca['data']['items'][0];

$value = $data['value'];

$repasses = $data['marketplace']['repasses'];

// foreach ($repasses as $repasse) {
//     echo "Repasses:\n";
//     echo $repasse['percentage'];
//     echo "\n";
// }

echo '<pre>';
echo json_encode($repasses, JSON_PRETTY_PRINT);
echo '</pre>';
return;
?>

<div class="bs-example widget-shadow" style="padding:15px" id="listar">
 <h3>ALUNO: <b><?php echo htmlspecialchars($nome_aluno); ?></b></h3>


 <!-- Tabela BOLETO PARCELADO -->
 <br>
 <h4>BOLETOS PARCELADOS</h4>
 <br>
 <table class="table table-hover" id="tabela_boleto">
  <thead>
   <tr>
    <th>#</th>
    <th>Compra</th>
    <th>N° da parcela</th>
    <th>Valor</th>
    <th>Situação</th>
    <th>Gerar boleto</th>
   </tr>
  </thead>

  <tbody>

   <?php foreach ($resposta_consulta as $registro): ?>
    <!-- <form method="post" action="<?php echo is_null($registro['id_asaas']) ? 'paginas/gerar_boleto.php' : 'https://sandbox.asaas.com/i/' . substr(htmlspecialchars($registro['id_asaas']), 4); ?>" <?php echo is_null($registro['id_asaas']) ? '' : 'target="_blank"'; ?>> -->
    <form method="post" action="<?php echo is_null($registro['id_asaas']) ? 'paginas/gerar_boleto.php' : 'https://asaas.com/i/' . substr(htmlspecialchars($registro['id_asaas']), 4); ?>" <?php echo is_null($registro['id_asaas']) ? '' : 'target="_blank"'; ?>>
     <tr>
      <td><?php echo $registro['id']; ?></td>
      <td><?php echo $registro['curso']; ?></td>
      <td><?php echo htmlspecialchars($registro['ordem_parcela']); ?></td>
      <td><?php echo 'R$ ' . number_format($registro['valor_parcela'], 2, ',', '.'); ?></td>
      <td><?php echo htmlspecialchars($registro['situacao'] ? 'Pago' : 'Não pago'); ?></td>
      <td>
       <?php if (!$registro['situacao']) : ?>
        <input type="hidden" name="valor_parcela" value="<?php echo $registro['valor_parcela']; ?>" />
        <input type="hidden" name="id_parcela" value="<?php echo $registro['id']; ?>" />
        <button type="submit" name="action" value="visualizar">
         <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
         <?php echo is_null($registro['id_asaas']) ? 'Gerar Boleto' : 'Pagar Boleto'; ?>
        </button>
       <?php elseif ($registro['situacao'] == 1) : ?>
        <!-- Novo botão para situação "Pago" (situacao = 1) -->
        <a href="<?php echo $registro['transaction_receipt_url']; ?>" target="_blank">
         <button type="button" style="color: #000;">
          <i class="fa fa-external-link" aria-hidden="true"></i>
          Ver Detalhes
         </button>
        </a>
       <?php endif; ?>
      </td>
     </tr>
    </form>
   <?php endforeach; ?>

  </tbody>
 </table>

 <?php if ($resposta_consulta === []) {
  echo "<center><span>Nenhum boleto encontrado.</span></center>";
 }  ?>
 <br>

 <!-- Tabela BOLETO A VISTA -->
 <br>
 <div>
  <div style="width: 100%; height: 1px; background-color: gray; margin-bottom: 10px" />

 </div>
 <div>
  <h4>BOLETO Á VISTA</h4>
 </div>
 <br>
 <table class="table table-hover" id="tabela_boleto">
  <thead>
   <tr>
    <th>ID</th>
    <th>Curso</th>
    <th>Data</th>
    <th>Pagamento</th>
    <th>Valor</th>
    <th>Status</th>
    <th>Ações</th>
   </tr>
  </thead>
  <tbody>
   <?php if (empty($pagamentos['BOLETO'])) : ?>
    <tr>
     <td colspan="7" class="text-center">Nenhum pagamento por BOLETO encontrado.</td>
    </tr>
   <?php else : ?>
    <?php foreach ($pagamentos['BOLETO'] as $pagamento) : ?>
     <tr>
      <td><?php echo $pagamento['id']; ?></td>
      <td><?php echo htmlspecialchars($pagamento['nome_curso']); ?></td>
      <td><?php echo (new DateTime($pagamento['data']))->format('d/m/Y'); ?></td>
      <td><?php echo htmlspecialchars($pagamento['forma_pgto']); ?></td>
      <td><?php echo 'R$ ' . number_format($pagamento['valor'], 2, ',', '.'); ?></td>
      <td><?php echo htmlspecialchars($pagamento['status']); ?></td>
      <td>
       <button onclick="verDetalhesBoleto('<?php echo htmlspecialchars($pagamento['url_boleto'], ENT_QUOTES); ?>');" value="visualizar">
        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
        Ver Detalhes
       </button>
      </td>
     </tr>
    <?php endforeach; ?>
   <?php endif; ?>
  </tbody>

 </table>
 <div>
  <div style="width: 100%; height: 1px; background-color: gray; margin-bottom: 10px" />

 </div>

 <!-- Tabela PIX -->
 <br>
 <h4>PIX</h4>
 <br>
 <table class="table table-hover" id="tabela_pix">
  <thead>
   <tr>
    <th>ID</th>
    <th>Curso</th>
    <th>Data</th>
    <th>Pagamento</th>
    <th>Valor</th>
    <th>Status</th>
    <th>Ações</th>
   </tr>
  </thead>
  <tbody>
   <?php if (empty($pagamentos['PIX'])) : ?>
    <tr>
     <td colspan="7" class="text-center">Nenhum pagamento por PIX encontrado.</td>
    </tr>
   <?php else : ?>
    <?php foreach ($pagamentos['PIX'] as $pagamento) : ?>
    <form method="post" action="<?php echo is_null($pagamento['id_asaas']) ? 'paginas/gerar_boleto.php' : 'https://asaas.com/i/' . substr(htmlspecialchars($pagamento['id_asaas']), 4); ?>" <?php echo is_null($pagamento['id_asaas']) ? '' : 'target="_blank"'; ?>>
     <tr>
      <td><?php echo $pagamento['id']; ?></td>
      <td><?php echo htmlspecialchars($pagamento['nome_curso']); ?></td>
      <td><?php echo (new DateTime($pagamento['data']))->format('d/m/Y'); ?></td>
      <td><?php echo htmlspecialchars($pagamento['forma_pgto']); ?></td>
      <td><?php echo 'R$ ' . number_format($pagamento['valor'], 2, ',', '.'); ?></td>
      <td><?php echo htmlspecialchars($pagamento['status']); ?></td>
      <td>
       <button type="submit" name="action" value="visualizar">
        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
        Ver Detalhes
       </button>
      </td>
     </tr>
     </form>
    <?php endforeach; ?>
   <?php endif; ?>
  </tbody>
 </table>

 <div>
  <div style="width: 100%; height: 1px; background-color: gray; margin-bottom: 10px" />

 </div>
 <!-- Tabela MP -->
 <br>
 <h4>CARTÃO DE CRÉDITO</h4>
 <br>
 <table class="table table-hover" id="tabela_mp">
  <thead>
   <tr>
    <th>ID</th>
    <th>Curso</th>
    <th>Data</th>
    <th>Pagamento</th>
    <th>Valor</th>
    <th>Status</th>
    <th>Ações</th>
   </tr>
  </thead>
  <tbody>
   <?php if (empty($pagamentos['MP'])) : ?>
    <tr>
     <td colspan="7" class="text-center">Nenhum pagamento por MP encontrado.</td>
    </tr>
   <?php else : ?>
    <?php foreach ($pagamentos['MP'] as $pagamento) : ?>
     <tr>
      <td><?php echo $pagamento['id']; ?></td>
      <td><?php echo htmlspecialchars($pagamento['nome_curso']); ?></td>
      <td><?php echo (new DateTime($pagamento['data']))->format('d/m/Y'); ?></td>
      <td><?php echo htmlspecialchars($pagamento['forma_pgto']); ?></td>
      <td><?php echo 'R$ ' . number_format($pagamento['valor'], 2, ',', '.'); ?></td>
      <td><?php echo htmlspecialchars($pagamento['status']); ?></td>
      <td>
       <button type="submit" name="action" value="visualizar">
        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
        Ver Detalhes
       </button>
      </td>
     </tr>
    <?php endforeach; ?>
   <?php endif; ?>
  </tbody>
 </table>

</div>

<script>
    function  verDetalhesBoleto(boleto) {
        Swal.fire({
            title: 'Visualizar Boleto',
            html: `<iframe src="${boleto}" width="100%" height="400px" style="border: none;"></iframe>`,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false
        });
    }
</script>
