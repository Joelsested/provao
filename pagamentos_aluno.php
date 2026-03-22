<?php
require_once('../conexao.php');
require_once('verificar.php');
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

$id_do_aluno = $id_aluno;
$telefone_aluno = '';
$temRecorrenciaEfi = false;
try {
    $temAssinaturas = $pdo->query("SHOW TABLES LIKE 'efi_assinaturas_cartao'")->fetch(PDO::FETCH_NUM);
    $temParcelas = $pdo->query("SHOW TABLES LIKE 'efi_assinaturas_cartao_parcelas'")->fetch(PDO::FETCH_NUM);
    $temRecorrenciaEfi = (bool) $temAssinaturas && (bool) $temParcelas;
} catch (Throwable $e) {
    $temRecorrenciaEfi = false;
}

if ($temRecorrenciaEfi) {
    $selectRecorrenciaCartao = "
        COALESCE(ea.quantidade_parcelas, 1) AS quantidade_parcelas,
        (
            SELECT COUNT(*)
            FROM efi_assinaturas_cartao_parcelas ep
            WHERE ep.id_matricula = matriculas.id
              AND ep.status = 'PAGA'
        ) AS parcelas_pagas,
        (
            SELECT COUNT(*)
            FROM efi_assinaturas_cartao_parcelas ep
            WHERE ep.id_matricula = matriculas.id
              AND ep.status IN ('PENDENTE', 'ATRASADA')
        ) AS parcelas_pendentes
    ";
    $joinRecorrenciaCartao = "LEFT JOIN efi_assinaturas_cartao ea ON ea.id_matricula = matriculas.id";
} else {
    $selectRecorrenciaCartao = "1 AS quantidade_parcelas, 0 AS parcelas_pagas, 0 AS parcelas_pendentes";
    $joinRecorrenciaCartao = "";
}
try {
 $stmtTelefone = $pdo->prepare("SELECT a.telefone FROM usuarios u JOIN alunos a ON a.id = u.id_pessoa WHERE u.id = :id LIMIT 1");
 $stmtTelefone->execute([':id' => $id_do_aluno]);
 $telefone_aluno = $stmtTelefone->fetchColumn() ?: '';
} catch (Exception $e) {
 $telefone_aluno = '';
}

if (is_string($telefone_aluno)) {
 $telefone_aluno = preg_replace('/\\D/', '', $telefone_aluno);
}

if ($telefone_aluno !== '' && strlen($telefone_aluno) <= 11 && substr($telefone_aluno, 0, 2) !== '55') {
 $telefone_aluno = '55' . $telefone_aluno;
}




$stmt = $pdo->prepare("
    SELECT 
        parcelas_geradas_por_boleto.*,
        JSON_UNQUOTE(JSON_EXTRACT(parcelas_geradas_por_boleto.payload, '$.item_nome')) AS curso
    FROM parcelas_geradas_por_boleto
    JOIN matriculas ON matriculas.id = parcelas_geradas_por_boleto.id_matricula
    WHERE matriculas.aluno = :id_aluno
");
$stmt->execute(['id_aluno' => $id_do_aluno]);
$resposta_consulta_boleto_parcelado = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Consultas separadas por forma de pagamento
$formas_pagamento = ['BOLETO', 'BOLETO_PARCELADO', 'CARTAO_DE_CREDITO', 'CARTAO_RECORRENTE'];
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
        SELECT 
            m.id, 
            m.id_curso, 
            m.data, 
            m.forma_pgto, 
            m.valor, 
            m.status, 
            m.id_asaas,
            m.pacote,
            CASE 
                WHEN m.pacote = 'Sim' THEN p.nome 
                ELSE c.nome 
            END as nome_curso,
            pb.id as pagamento_boleto_id,
            pb.charge_id as boleto_charge_id,
            pb.url_boleto,
            pb.linha_digitavel,
            pb.status as status_boleto
        FROM matriculas m
        LEFT JOIN cursos c ON m.id_curso = c.id
        LEFT JOIN pacotes p ON p.id = m.id_curso
        LEFT JOIN pagamentos_boleto pb ON m.id = pb.id_matricula
        WHERE m.aluno = :aluno AND m.forma_pgto = :forma
        ORDER BY m.id DESC
    ");
    $query->execute(['aluno' => $id_aluno, 'forma' => $forma]);
    $pagamentos[$forma] = $query->fetchAll(PDO::FETCH_ASSOC);
}

$pagamentos_cartao = array_merge($pagamentos['CARTAO_DE_CREDITO'] ?? [], $pagamentos['CARTAO_RECORRENTE'] ?? []);

$consulta_matricula = $pdo->prepare("SELECT id, forma_pgto, pacote, id_curso FROM matriculas WHERE aluno = :aluno");
$consulta_matricula->execute(['aluno' => $id_do_aluno]);
$resposta_consulta = $consulta_matricula->fetchAll(PDO::FETCH_ASSOC);

$transactions = [];

foreach ($resposta_consulta as $matricula) {
    $id = $matricula['id'];
    $forma_pgto = $matricula['forma_pgto'];
    $pacote = $matricula['pacote'];
    $id_curso = $matricula['id_curso'];

    $nome_curso_pacote = '';
    if ($pacote === 'Sim') {
        $consulta_nome = $pdo->prepare("SELECT nome FROM pacotes WHERE id = :id");
        $consulta_nome->execute(['id' => $id_curso]);
        $resultado_nome = $consulta_nome->fetch(PDO::FETCH_ASSOC);
        $nome_curso_pacote = $resultado_nome ? $resultado_nome['nome'] : '';
    } elseif ($pacote === 'Nao' || $pacote === 'Não' || $pacote === 'Não') {
        $consulta_nome = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id");
        $consulta_nome->execute(['id' => $id_curso]);
        $resultado_nome = $consulta_nome->fetch(PDO::FETCH_ASSOC);
        $nome_curso_pacote = $resultado_nome ? $resultado_nome['nome'] : '';
    }

    if (!empty($nome_curso_pacote)) {
        $nome_curso_pacote = json_decode('"' . $nome_curso_pacote . '"');
    }

    if ($forma_pgto === 'BOLETO' || $forma_pgto === 'BOLETO_PARCELADO') {
        $tabela_pagamentos = 'pagamentos_boleto';
    } else {
        continue;
    }

    $consulta_pagamentos = $pdo->prepare("SELECT * FROM {$tabela_pagamentos} WHERE id_matricula = :id_matricula");
    $consulta_pagamentos->execute(['id_matricula' => $id]);
    $resposta_pagamentos = $consulta_pagamentos->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($resposta_pagamentos)) {
        $resposta_pagamentos[0]['nome_curso_pacote'] = $nome_curso_pacote;
        array_push($transactions, $resposta_pagamentos[0]);
    }
}

$boleto_transactions = [];

foreach ($transactions as $registro) {
    $eh_boleto = isset($registro['nosso_numero']);

    if ($eh_boleto) {
        $boleto_transactions[] = $registro;
    }
}

$queryAcrescimoCartao = $pdo->query("SELECT acrescimo_cartao_credito FROM config");
$resAcrescimoCartao = $queryAcrescimoCartao->fetchColumn();

$consulta_matriculas_cartao_p = $pdo->prepare("
    SELECT 
        matriculas.*, 
        pacotes.nome AS nome_curso,
        usuarios.nome AS nome_professor,
        {$selectRecorrenciaCartao}
    FROM matriculas 
    JOIN pacotes ON pacotes.id = matriculas.id_curso 
    JOIN usuarios ON usuarios.id = matriculas.professor
    {$joinRecorrenciaCartao}
    WHERE matriculas.aluno = :aluno
    AND matriculas.forma_pgto IN ('CARTAO_DE_CREDITO', 'CARTAO_RECORRENTE')
    AND matriculas.pacote = 'Sim'
");
$consulta_matriculas_cartao_p->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas_cartao_p = $consulta_matriculas_cartao_p->fetchAll(PDO::FETCH_ASSOC);

$consulta_matriculas_cartao_c = $pdo->prepare("
    SELECT 
        matriculas.*, 
        cursos.nome AS nome_curso,
        usuarios.nome AS nome_professor,
        {$selectRecorrenciaCartao}
    FROM matriculas 
    JOIN cursos ON cursos.id = matriculas.id_curso 
    JOIN usuarios ON usuarios.id = matriculas.professor
    {$joinRecorrenciaCartao}
    WHERE matriculas.aluno = :aluno
    AND matriculas.forma_pgto IN ('CARTAO_DE_CREDITO', 'CARTAO_RECORRENTE')
    AND (matriculas.pacote = 'Nao' OR matriculas.pacote = 'Não' OR matriculas.pacote IS NULL)
");
$consulta_matriculas_cartao_c->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas_cartao_c = $consulta_matriculas_cartao_c->fetchAll(PDO::FETCH_ASSOC);

$cartao_transactions = array_merge($resposta_consulta_matriculas_cartao_c, $resposta_consulta_matriculas_cartao_p);






// Consultar nome do aluno
$queryAluno = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :aluno");
$queryAluno->execute(['aluno' => $id_aluno]);
$resAluno = $queryAluno->fetch(PDO::FETCH_ASSOC);

$nome_aluno = $resAluno['nome'] ?? 'Desconhecido';



$config = [
    'client_id' => $options['clientId'],
    'client_secret' => $options['clientSecret'],
    'certificate_path' => $options['certificate'],
    'sandbox' => $options['sandbox'] // true para teste, false para produção
];

$boletoPaymentApi = new EFIBoletoPayment(
    $config['client_id'],
    $config['client_secret'],
    $config['sandbox']
);

$status_cache = [];
function buscarStatusBoleto($boletoPaymentApi, $chargeId, &$status_cache)
{
    if (!$chargeId) {
        return '';
    }
    if (isset($status_cache[$chargeId])) {
        return $status_cache[$chargeId];
    }
    try {
        $consultar = $boletoPaymentApi->consultarCobranca($chargeId);
        $status = $consultar['data']['status'] ?? '';
    } catch (Exception $e) {
        $status = '';
    }
    $status_cache[$chargeId] = $status;
    return $status;
}

function resumirStatusBoleto($statusApi, $situacao = null)
{
    if (!empty($situacao) && (int) $situacao === 1) {
        return 'Pago';
    }
    $statusApi = strtolower((string) $statusApi);
    if ($statusApi === 'paid') {
        return 'Pago';
    }
    if ($statusApi === 'expired') {
        return 'Vencido';
    }
    if ($statusApi !== '') {
        return 'Pendente';
    }
    return 'Nao Gerado';
}




?>

<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        vertical-align: middle;
    }

    th {
        background-color: #f4f4f4;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
</style>

<div class="bs-example widget-shadow" style="padding:15px" id="listar">
 <h3>ALUNO: <b><?php echo htmlspecialchars($nome_aluno); ?></b></h3>


 <!-- Tabela BOLETO PARCELADO -->
 <br>
 <div style="display:flex; justify-content:space-between; align-items:center;">
  <h4 style="margin:0;">BOLETOS PARCELADOS</h4>
  <button type="button" class="btn btn-default" onclick="history.back()">Voltar</button>
 </div>
 <br>

 <table>
  <thead>
   <tr>
    <th>#</th>
    <th>Curso</th>
    <th>N? da parcela</th>
    <th>Valor</th>
    <th>Situação</th>
    <th>Comissões</th>
    <th>Código de Pagamento</th>
    <th>Gerar boleto</th>
    <th>Enviar</th>
   </tr>
  </thead>
  <tbody>
   <?php foreach ($resposta_consulta_boleto_parcelado as $registro): ?>
    <?php
        $cursoNome = json_decode('"' . preg_replace('/u([0-9a-fA-F]{4})/', '\u$1', $registro['curso']) . '"');
        $cursoNomeSafe = htmlspecialchars($cursoNome ?? '', ENT_QUOTES, 'UTF-8');
        $valorParcelaFmt = number_format($registro['valor_parcela'], 2, ',', '.');
        $chargeId = $registro['charge_id'] ?? '';
        $statusApi = '';
        if (!empty($chargeId)) {
            $statusApi = buscarStatusBoleto($boletoPaymentApi, $chargeId, $status_cache);
        }
        $statusResumo = resumirStatusBoleto($statusApi, $registro['situacao'] ?? null);
        $vencido = ($statusResumo === 'Vencido');
    ?>
    <tr>
     <td><?php echo $registro['id']; ?></td>
     <td><?php echo $cursoNomeSafe; ?></td>
     <td><?php echo htmlspecialchars($registro['ordem_parcela']); ?></td>
     <td><?php echo 'R$ ' . $valorParcelaFmt; ?></td>
     <td><?php echo htmlspecialchars($statusResumo); ?></td>
     <td>
      <?php if (!empty($chargeId)): ?>
       <button type="button" class="btn btn-info btn-xs" onclick="verComissõesBoleto('<?php echo htmlspecialchars($chargeId, ENT_QUOTES); ?>')">
        <i class="fa fa-percent" aria-hidden="true"></i>
        Ver Comissões
       </button>
      <?php else: ?>
       <span class="text-muted">-</span>
      <?php endif; ?>
     </td>
     <td style="max-width: 100px; overflow: hidden;">
      <?php if (!empty($registro['transaction_receipt_url'])): ?>
       <div class="btn btn-primary" onclick="copiarCodigoPagamento('<?php echo htmlspecialchars($registro['transaction_receipt_url'], ENT_QUOTES); ?>')">
        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
        Copiar Código
       </div>
      <?php endif; ?>
     </td>
     <td>
      <?php if (empty($registro['id_asaas'])): ?>
       <form method="post" action="paginas/gerar_boleto.php" target="_blank">
        <input type="hidden" name="valor_parcela" value="<?php echo $registro['valor_parcela']; ?>" />
        <input type="hidden" name="id_parcela" value="<?php echo $registro['id']; ?>" />
        <input type="hidden" name="id_boleto_parcelado" value="<?php echo $registro['id_boleto_parcelado']; ?>" />
        <input type="hidden" name="ordem_parcela" value="<?php echo $registro['ordem_parcela']; ?>" />
        <input type="hidden" name="id_aluno" value="<?php echo $id_do_aluno; ?>" />
        <input type="hidden" name="id_matricula" value="<?php echo $registro['id_matricula']; ?>" />
        <input type="hidden" name="payload" value="<?php echo htmlspecialchars($registro['payload'], ENT_QUOTES, 'UTF-8'); ?>" />
        <button type="submit" name="action" value="visualizar">
         <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
         Gerar Boleto
        </button>
       </form>
      <?php else: ?>
       <button type="button" onclick="openBoleto('<?php echo htmlspecialchars($registro['id_asaas'], ENT_QUOTES); ?>')">
        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
        Visualizar Boleto
       </button>
      <?php endif; ?>
      <?php if ($statusResumo !== 'Pago' && !empty($registro['charge_id'])): ?>
       <input type="date" class="form-control" style="display:inline-block; width:auto;"
        id="vencimento-parcela-<?php echo (int) $registro['id']; ?>"
        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
        title="Nova data de vencimento">
       <button type="button"
        onclick="solicitarNovoVencimento('parcela', <?php echo (int) $registro['id']; ?>)"
        title="Atualizar vencimento">
        Atualizar
       </button>
      <?php endif; ?>
     </td>
     <td>
      <?php if (!empty($registro['id_asaas'])): ?>
       <button type="button" class="btn btn-success" onclick="enviarWhatsAppBoleto('<?php echo htmlspecialchars($registro['id_asaas'], ENT_QUOTES); ?>', '<?php echo $cursoNomeSafe; ?>', '<?php echo (int) $registro['ordem_parcela']; ?>', '<?php echo $valorParcelaFmt; ?>')">
        <i class="fa fa-whatsapp" aria-hidden="true"></i>
        Enviar
       </button>
      <?php else: ?>
       <span class="text-muted">-</span>
      <?php endif; ?>
     </td>
    </tr>
   <?php endforeach; ?>
 </tbody>
 </table>

 <br>
 <h4>CARTÃO RECORRENTE EFY</h4>
 <br>
 <table>
  <thead>
   <tr>
    <th>ID Matrícula</th>
    <th>Curso / Pacote</th>
    <th>Forma</th>
    <th>Parcelas</th>
    <th>Valor Total</th>
    <th>Situação</th>
   </tr>
  </thead>
  <tbody>
   <?php if (!empty($cartao_transactions)): ?>
    <?php foreach ($cartao_transactions as $registro): ?>
     <?php
        $ehRecorrente = (($registro['forma_pgto'] ?? '') === 'CARTAO_RECORRENTE');
        if ($ehRecorrente) {
            $parcelasTotal = max(1, (int) ($registro['quantidade_parcelas'] ?? 1));
            if (!empty($registro['qtd_parcelas_cartao'])) {
                $parcelasTotal = max(1, (int) $registro['qtd_parcelas_cartao']);
            } elseif (!empty($registro['qtd_parcelas'])) {
                $parcelasTotal = max(1, (int) $registro['qtd_parcelas']);
            } elseif (!empty($registro['parcelas'])) {
                $parcelasTotal = max(1, (int) $registro['parcelas']);
            }
        } else {
            $parcelasTotal = 1;
            if (!empty($registro['qtd_parcelas_cartao'])) {
                $parcelasTotal = max(1, (int) $registro['qtd_parcelas_cartao']);
            } elseif (!empty($registro['qtd_parcelas'])) {
                $parcelasTotal = max(1, (int) $registro['qtd_parcelas']);
            } elseif (!empty($registro['parcelas'])) {
                $parcelasTotal = max(1, (int) $registro['parcelas']);
            } elseif (!empty($registro['quantidade_parcelas'])) {
                $parcelasTotal = max(1, (int) $registro['quantidade_parcelas']);
            }
        }
        $parcelasPagas = (int) ($registro['parcelas_pagas'] ?? 0);
        $parcelasPendentes = (int) ($registro['parcelas_pendentes'] ?? 0);
        $statusLinha = strtoupper(trim((string) ($registro['status'] ?? '')));
        if ($statusLinha === '') {
            $statusLinha = 'PENDENTE';
        }
        $formaLinha = ($registro['forma_pgto'] ?? '') === 'CARTAO_RECORRENTE' ? 'Cartão recorrente' : 'Cartão de crédito';
        $valorTotalLinha = (float) ($registro['valor_total_cartao'] ?? 0);
        if ($valorTotalLinha <= 0) {
            $valorTotalLinha = (float) ($registro['subtotal'] ?? 0);
            if ($valorTotalLinha <= 0) {
                $valorTotalLinha = (float) ($registro['valor'] ?? 0);
            }
        }
     ?>
     <tr>
      <td><?php echo (int) ($registro['id'] ?? 0); ?></td>
      <td><?php echo htmlspecialchars((string) json_decode('"' . ($registro['nome_curso'] ?? '') . '"'), ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?php echo $formaLinha; ?></td>
      <td>
       <?php
            echo $parcelasPagas . '/' . $parcelasTotal . ' pagas';
            if ($parcelasPendentes > 0) {
                echo ' (' . $parcelasPendentes . ' pendentes)';
            }
       ?>
      </td>
      <td><?php echo 'R$ ' . number_format($valorTotalLinha, 2, ',', '.'); ?></td>
      <td><?php echo htmlspecialchars($statusLinha, ENT_QUOTES, 'UTF-8'); ?></td>
     </tr>
    <?php endforeach; ?>
   <?php else: ?>
    <tr>
     <td colspan="6" style="text-align:center;">Nenhum pagamento de cartão encontrado.</td>
    </tr>
   <?php endif; ?>
  </tbody>
 </table>


</div>

<script>
    var telefoneAluno = "<?php echo htmlspecialchars($telefone_aluno, ENT_QUOTES, 'UTF-8'); ?>";

    function enviarWhatsAppBoleto(url, curso, parcela, valor) {
        if (!telefoneAluno) {
            alert('Telefone do aluno não encontrado.');
            return;
        }
        var texto = 'Segue o boleto';
        if (curso) {
            texto += ' do curso/pacote ' + curso;
        }
        if (parcela) {
            texto += ' - parcela ' + parcela;
        }
        if (valor) {
            texto += ' - valor R$ ' + valor;
        }
        texto += ': ' + url;
        var link = 'https://wa.me/' + telefoneAluno + '?text=' + encodeURIComponent(texto);
        window.open(link, '_blank');
    }

    function copiarCodigoPagamento(valor) {
        navigator.clipboard.writeText(valor).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copiado!',
                text: 'O código foi copiado para sua área de transferência.'
            });
        }).catch(err => {
            console.error('Erro ao copiar: ', err);
        });
    }

    function openBoleto(boleto) {
        Swal.fire({
            title: 'Visualizar Boleto',
            html: `
                <div>
                    <iframe src="${boleto}" width="100%" height="400px" style="border: none; background: #fff;"></iframe>
                    <div style="margin-top: 12px;">
                        <button type="button" class="btn btn-default" onclick="Swal.close()">Voltar</button>
                    </div>
                </div>
            `,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false,
            showCancelButton: false,
            footer: ''
        });
    }

    function verDetalhesBoleto(boleto) {
        openBoleto(boleto);
    }

    function formatarMoedaBR(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function verComissõesBoleto(chargeId) {
        if (!chargeId) {
            Swal.fire({ icon: 'warning', title: 'Charge ID inválido' });
            return;
        }

        Swal.fire({
            title: 'Carregando comissões...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch('paginas/boletos/detalhar_comissoes.php?charge_id=' + encodeURIComponent(chargeId), {
            credentials: 'same-origin'
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.ok) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Falha ao consultar',
                        text: data.msg || 'Não foi possível obter as comissões.'
                    });
                    return;
                }

                const repasses = Array.isArray(data.repasses) ? data.repasses : [];
                if (repasses.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sem repasses',
                        html: `<div>Charge <b>${data.charge_id}</b> sem divisão de comissões.</div>`
                    });
                    return;
                }

                let linhas = '';
                repasses.forEach((r) => {
                    const nome = (r.usuario_nome || '').trim() || 'Não identificado';
                    const nivel = (r.usuario_nivel || '').trim() || '-';
                    const carteira = r.payee_code || '-';
                    const perc = Number(r.percent || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const valor = formatarMoedaBR(r.valor_aprox || 0);
                    linhas += `
                        <tr>
                            <td>${nome}</td>
                            <td>${nivel}</td>
                            <td>${perc}%</td>
                            <td>R$ ${valor}</td>
                            <td><small>${carteira}</small></td>
                        </tr>`;
                });

                const ambiente = data.sandbox ? 'Sandbox' : 'Produção';
                const total = formatarMoedaBR(data.total_reais || 0);
                const soma = Number(data.soma_percent || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                Swal.fire({
                    title: 'Comissões da cobrança',
                    width: '92%',
                    html: `
                        <div style="text-align:left; margin-bottom:10px;">
                            <div><b>Charge:</b> ${data.charge_id}</div>
                            <div><b>Status:</b> ${data.status || '-'}</div>
                            <div><b>Ambiente:</b> ${ambiente}</div>
                            <div><b>Total:</b> R$ ${total}</div>
                            <div><b>Soma dos repasses:</b> ${soma}%</div>
                        </div>
                        <div style="max-height:380px; overflow:auto;">
                            <table class="table table-bordered table-striped" style="width:100%; margin:0;">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>N?vel</th>
                                        <th>Percentual</th>
                                        <th>Valor aprox.</th>
                                        <th>Wallet ID</th>
                                    </tr>
                                </thead>
                                <tbody>${linhas}</tbody>
                            </table>
                        </div>`
                });
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Falha ao consultar',
                    text: 'Erro de rede ao consultar comissões.'
                });
            });
    }
    function visualizarQR(qrcode, texto_copia_cola, valor, data_criacao, status) {
        const dataFormatada = new Date(data_criacao).toLocaleDateString('pt-BR');
        const valorFormatado = parseFloat(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        Swal.fire({
            title: 'Realizar Pagamento',
            html: `
                <div>
                    <div style="margin-bottom:10px;">Valor do pagamento: ${valorFormatado}</div>
                    <img src="${qrcode}" width="250px" style="display:block; margin:0 auto 10px;" />
                    <input type="text" id="codigo-pagamento" value="${texto_copia_cola}" readonly style="border: 1px solid #ddd; width: 100%; padding: 8px;" />
                    <button onclick="copiarCodigoPagamento('${texto_copia_cola.replace("'", "\'")}')" style="margin-top:10px;">Copiar</button>
                    <div style="margin-top:10px;">Data: ${dataFormatada}</div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Fechar'
        });
    }

    function realizarPagamentoCartao(id, id_aluno, nome_curso) {
        Swal.fire({
            title: 'Realizar Pagamento',
            html: `<iframe src="<?php echo $url_sistema ?>efi/credit_card.php?id=${id}&id_aluno=${id_aluno}&nome_curso=${nome_curso}" width="100%" height="600px" style="border: none; background: #fff;"></iframe>`,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false
        });
    }

    function solicitarNovoVencimento(tipo, id) {
        const input = document.getElementById(`vencimento-${tipo}-${id}`);
        const dataEscolhida = input ? (input.value || '').trim() : '';
        if (!dataEscolhida) {
            Swal.fire({
                icon: 'warning',
                title: 'Informe uma data válida'
            });
            return;
        }

        const formData = new FormData();
        formData.append('tipo', tipo);
        formData.append('id', id);
        formData.append('vencimento', dataEscolhida);

        fetch('paginas/boletos/atualizar_vencimento.php', {
            method: 'POST',
            body: formData
        })
            .then((response) => response.text())
            .then((text) => {
                const mensagem = (text || '').trim();
                if (mensagem === 'sucesso') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Vencimento atualizado',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Falha ao atualizar',
                        text: mensagem || 'Não foi possível atualizar o vencimento.'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Falha ao atualizar',
                    text: 'Não foi possível atualizar o vencimento.'
                });
            });
    }
</script>






