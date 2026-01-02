<?php
require_once("../../../conexao.php");
@session_start();

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Secretario') {
	echo 'Nao autorizado.';
	exit();
}

$usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
$dataInicial = trim($_POST['dataInicial'] ?? '');
$dataFinal = trim($_POST['dataFinal'] ?? '');
$pago = trim($_POST['pago'] ?? '');

if (!$usuario_id) {
	echo 'Funcionario nao encontrado.';
	exit();
}

$mostrarRecebidas = strtolower($pago) === 'sim';
$mostrarPendentes = !$mostrarRecebidas;

$stmtUser = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $usuario_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user) {
	echo 'Funcionario nao encontrado.';
	exit();
}

$nivel = $user['nivel'] ?? '';
$id_pessoa = $user['id_pessoa'] ?? null;

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

$tipoTitulo = $mostrarRecebidas ? 'Comissoes Recebidas' : 'Comissoes Pendentes';
$tipoDescricao = $mostrarRecebidas
	? 'Comissoes de alunos que ja realizaram o pagamento.'
	: 'Comissoes de alunos que ainda nao realizaram o pagamento.';

if (count($comissoes) > 0) {
	echo "<small>";
	echo "<h3>{$tipoTitulo}</h3>";
	echo "<div style=\"margin-top: 10px;\"><small><div align=\"left\">{$tipoDescricao}</div></small></div>";
	echo "<br>";
	echo "<table class=\"table table-hover\" id=\"tabela\">";
	echo "<thead><tr>";
	echo "<th>Aluno</th>";
	echo "<th class=\"esc\">Curso</th>";
	echo "<th class=\"esc\">Valor Comissao</th>";
	echo "<th class=\"esc\">Status</th>";
	echo "<th class=\"esc\">Data Recebimento</th>";
	echo "</tr></thead><tbody>";

	foreach ($comissoes as $item) {
		$valorF = number_format($item['valor'], 2, ',', '.');
		$statusClass = ($item['status'] === 'Recebido') ? 'verde' : 'text-danger';
		$data = $item['data'] ? implode('/', array_reverse(explode('-', $item['data']))) : '-';
		echo "<tr>";
		echo "<td>{$item['aluno']}</td>";
		echo "<td class=\"esc\">{$item['curso']}</td>";
		echo "<td class=\"esc\">R$ {$valorF}</td>";
		echo "<td class=\"esc\"><span class=\"{$statusClass}\">{$item['status']}</span></td>";
		echo "<td class=\"esc\">{$data}</td>";
		echo "</tr>";
	}

	$totalF = number_format($total_comissao, 2, ',', '.');
	$totalLabel = $mostrarRecebidas ? 'Total Recebido' : 'Total a Receber';
	$totalClass = $mostrarRecebidas ? 'verde' : 'text-danger';

	echo "</tbody><small><div align=\"center\" id=\"mensagem-excluir\"></div></small></table>";
	echo "<br>";
	echo "<div align=\"right\">{$totalLabel}: <span class=\"{$totalClass}\">R$ {$totalF}</span></div>";
	echo "</small>";
} else {
	echo $mostrarRecebidas ? 'Nenhuma comissao recebida!' : 'Nenhuma comissao pendente!';
}
?>

<script type="text/javascript">
	$(document).ready(function() {
		if ($('#tabela').length) {
			$('#tabela').DataTable({
				"ordering": false,
				"stateSave": true,
			});
			$('#tabela_filter label input').focus();
		}
	});
</script>
