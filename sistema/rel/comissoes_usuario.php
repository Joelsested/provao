<?php
include('../conexao.php');
@session_start();

$usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
if ($usuario_id === null || $usuario_id === false) {
	$usuario_id = filter_var($_GET['usuario_id'] ?? null, FILTER_VALIDATE_INT);
}
$dataInicial = trim($_GET['dataInicial'] ?? '');
$dataFinal = trim($_GET['dataFinal'] ?? '');
$pago = trim($_GET['pago'] ?? '');

if (!$usuario_id) {
	echo 'Funcionario nao encontrado.';
	exit();
}

$nivel_session = $_SESSION['nivel'] ?? '';
$id_session = $_SESSION['id'] ?? null;
$is_admin = in_array($nivel_session, ['Administrador', 'Tesoureiro', 'Secretario'], true);
if (!$is_admin && (int) $id_session !== (int) $usuario_id) {
	echo 'Nao autorizado.';
	exit();
}

$stmtUsuario = $pdo->prepare("SELECT id, nome, nivel, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
$stmtUsuario->execute([':id' => $usuario_id]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
if (!$usuario) {
	echo 'Funcionario nao encontrado.';
	exit();
}

$nivel = $usuario['nivel'] ?? '';
$id_pessoa = $usuario['id_pessoa'] ?? null;

$comissao_responsavel = 0;
if ($nivel == 'Vendedor' && $id_pessoa) {
	$stmt = $pdo->prepare("SELECT comissao FROM vendedores WHERE id = :id LIMIT 1");
	$stmt->execute([':id' => $id_pessoa]);
	$comissao_responsavel = $stmt->fetchColumn() ?: 0;
} elseif ($nivel == 'Tutor' && $id_pessoa) {
	$stmt = $pdo->prepare("SELECT comissao FROM tutores WHERE id = :id LIMIT 1");
	$stmt->execute([':id' => $id_pessoa]);
	$comissao_responsavel = $stmt->fetchColumn() ?: 0;
}

$stmtConfig = $pdo->query("SELECT comissao_tutor FROM config LIMIT 1");
$comissao_tutor_atendimento = $stmtConfig ? ($stmtConfig->fetchColumn() ?: 0) : 0;

$mostrarRecebidas = strtolower($pago) === 'sim';
$mostrarPendentes = !$mostrarRecebidas;

function listarMatriculasPorResponsaveis(PDO $pdo, array $responsavelIds, string $dataInicial, string $dataFinal): array
{
	if (empty($responsavelIds)) {
		return [];
	}
	$placeholders = implode(',', array_fill(0, count($responsavelIds), '?'));
	$sql = "SELECT m.*, u.nome AS aluno_nome
			FROM matriculas m
			JOIN usuarios u ON u.id = m.aluno
			JOIN alunos a ON a.id = u.id_pessoa
			WHERE a.usuario IN ($placeholders)
			AND (m.pacote = 'Sim' OR m.id_pacote IS NULL OR m.id_pacote = 0)";
	$params = $responsavelIds;
	if ($dataInicial !== '' && $dataFinal !== '') {
		$sql .= " AND m.data >= ? AND m.data <= ?";
		$params[] = $dataInicial;
		$params[] = $dataFinal;
	}
	$sql .= " ORDER BY m.id DESC";
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$cacheCursos = [];
$cachePacotes = [];
$stmtCurso = $pdo->prepare("SELECT nome FROM cursos WHERE id = ? LIMIT 1");
$stmtPacote = $pdo->prepare("SELECT nome FROM pacotes WHERE id = ? LIMIT 1");
$obterNomeCurso = function ($cursoId, $pacote) use (&$cacheCursos, &$cachePacotes, $stmtCurso, $stmtPacote) {
	$cursoId = (int) $cursoId;
	$isPacote = ($pacote === 'Sim');
	if ($isPacote) {
		if (isset($cachePacotes[$cursoId])) {
			return $cachePacotes[$cursoId];
		}
		$stmtPacote->execute([$cursoId]);
		$nome = $stmtPacote->fetchColumn() ?: '';
		$cachePacotes[$cursoId] = $nome;
		return $nome;
	}
	if (isset($cacheCursos[$cursoId])) {
		return $cacheCursos[$cursoId];
	}
	$stmtCurso->execute([$cursoId]);
	$nome = $stmtCurso->fetchColumn() ?: '';
	$cacheCursos[$cursoId] = $nome;
	return $nome;
};

$comissoes = [];
$total_comissao = 0;

$matriculasResponsavel = [];
if (in_array($nivel, ['Vendedor', 'Tutor'], true)) {
	$matriculasResponsavel = listarMatriculasPorResponsaveis($pdo, [$usuario_id], $dataInicial, $dataFinal);
}

foreach ($matriculasResponsavel as $row) {
	$statusMatricula = $row['status'] ?? '';
	$recebido = ($statusMatricula === 'Matriculado');
	if ($recebido && !$mostrarRecebidas) {
		continue;
	}
	if (!$recebido && !$mostrarPendentes) {
		continue;
	}
	$valor_base = $row['subtotal'] ?? $row['valor'] ?? 0;
	$valor_base = (float) str_replace(',', '.', $valor_base);
	$valor_comissao = ($valor_base * $comissao_responsavel) / 100;
	$comissoes[] = [
		'aluno' => $row['aluno_nome'] ?? '',
		'curso' => $obterNomeCurso($row['id_curso'] ?? 0, $row['pacote'] ?? ''),
		'valor' => $valor_comissao,
		'status' => $recebido ? 'Recebido' : 'A receber',
		'data' => $recebido ? ($row['data'] ?? '') : ''
	];
	$total_comissao += $valor_comissao;
}

if ($nivel == 'Tutor' && $id_pessoa) {
	$stmtVendedores = $pdo->prepare("SELECT u.id AS usuario_id
		FROM vendedores v
		JOIN usuarios u ON u.id_pessoa = v.id AND u.nivel = 'Vendedor'
		WHERE v.professor = 1 AND v.tutor_id = :tutor_id");
	$stmtVendedores->execute([':tutor_id' => $id_pessoa]);
	$vendedoresUsuarios = $stmtVendedores->fetchAll(PDO::FETCH_COLUMN, 0);

	$matriculasAtendimento = listarMatriculasPorResponsaveis($pdo, $vendedoresUsuarios, $dataInicial, $dataFinal);

	foreach ($matriculasAtendimento as $row) {
		$statusMatricula = $row['status'] ?? '';
		$recebido = ($statusMatricula === 'Matriculado');
		if ($recebido && !$mostrarRecebidas) {
			continue;
		}
		if (!$recebido && !$mostrarPendentes) {
			continue;
		}
		$valor_base = $row['subtotal'] ?? $row['valor'] ?? 0;
		$valor_base = (float) str_replace(',', '.', $valor_base);
		$valor_comissao = ($valor_base * $comissao_tutor_atendimento) / 100;
		$comissoes[] = [
			'aluno' => $row['aluno_nome'] ?? '',
			'curso' => $obterNomeCurso($row['id_curso'] ?? 0, $row['pacote'] ?? ''),
			'valor' => $valor_comissao,
			'status' => $recebido ? 'Recebido' : 'A receber',
			'data' => $recebido ? ($row['data'] ?? '') : ''
		];
		$total_comissao += $valor_comissao;
	}
}

$dataInicialF = $dataInicial ? implode('/', array_reverse(explode('-', $dataInicial))) : '';
$dataFinalF = $dataFinal ? implode('/', array_reverse(explode('-', $dataFinal))) : '';
$texto_apuracao = ($dataInicial && $dataFinal && $dataInicial != $dataFinal)
	? "Apuracao de {$dataInicialF} ate {$dataFinalF}"
	: ($dataInicial ? "Apurado em {$dataInicialF}" : 'Apurado em todo o periodo');

$acao_rel = $mostrarRecebidas ? 'Recebidas' : 'Pendentes';

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
$data_hoje = strftime('%A, %d de %B de %Y', strtotime('today'));
?>

<!DOCTYPE html>
<html>
<head>
	<title>Relatorio de Comissoes</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">
	<style>
		@page { margin: 0px; }
		body { margin-top: 0px; font-family: Arial, Helvetica, sans-serif; }
		.cabecalho { padding: 10px; margin-bottom: 20px; width: 100%; border-bottom: solid 1px #0340a3; }
		.titulo_img { position: absolute; margin-top: 10px; margin-left: 10px; }
		.data_img { position: absolute; margin-top: 40px; margin-left: 10px; border-bottom: 1px solid #000; font-size: 10px; }
		.imagem { width: 200px; position: absolute; right: 20px; top: 10px; }
		.verde { color: green; }
		.vermelho { color: #dc3545; }
		table { font-size: 12px; }
	</style>
</head>
<body>

<div class="titulo_img"><u>Relatorio de Comissoes <?php echo $acao_rel ?> - <?php echo $usuario['nome'] ?> (<?php echo $usuario['nivel'] ?>)</u></div>
<div class="data_img"><?php echo mb_strtoupper($data_hoje) ?></div>
<img class="imagem" src="<?php echo $url_sistema ?>/sistema/img/logo_rel.jpg" width="200px" height="47">

<br><br><br>
<div class="cabecalho"></div>

<div class="mx-2" style="padding-top:10px;">
	<small><small><small><u><?php echo $texto_apuracao ?></u></small></small></small>
	<br><br>

	<table class="table table-bordered">
		<thead>
		<tr>
			<th>Aluno</th>
			<th>Curso</th>
			<th>Valor Comissao</th>
			<th>Status</th>
			<th>Data Recebimento</th>
		</tr>
		</thead>
		<tbody>
		<?php if (count($comissoes) > 0) : ?>
			<?php foreach ($comissoes as $item) : ?>
				<?php
				$valorF = number_format($item['valor'], 2, ',', '.');
				$data = $item['data'] ? implode('/', array_reverse(explode('-', $item['data']))) : '-';
				$statusClass = ($item['status'] === 'Recebido') ? 'verde' : 'vermelho';
				?>
				<tr>
					<td><?php echo $item['aluno'] ?></td>
					<td><?php echo $item['curso'] ?></td>
					<td>R$ <?php echo $valorF ?></td>
					<td class="<?php echo $statusClass ?>"><?php echo $item['status'] ?></td>
					<td><?php echo $data ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="5">Nenhuma comissao encontrada.</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php
	$totalF = number_format($total_comissao, 2, ',', '.');
	$totalLabel = $mostrarRecebidas ? 'Total Recebido' : 'Total a Receber';
	?>
	<div style="text-align:right;"><strong><?php echo $totalLabel ?>:</strong> R$ <?php echo $totalF ?></div>
</div>

</body>
</html>
