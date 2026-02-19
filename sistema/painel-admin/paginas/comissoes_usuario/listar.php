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
	echo 'Selecione um usuario para visualizar as comissoes.';
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
$id_pessoa = (int) ($user['id_pessoa'] ?? 0);

function tabelaTemColuna(PDO $pdo, string $tabela, string $coluna): bool
{
	$stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
	$stmt->execute([':coluna' => $coluna]);
	return (bool) $stmt->fetchColumn();
}

function obterComissoesTutor(PDO $pdo, int $tutorId, float $padraoOutros): array
{
	$comissoes = [
		'meus' => 0.0,
		'outros' => $padraoOutros,
	];

	$temMeus = tabelaTemColuna($pdo, 'tutores', 'comissao_meus_alunos');
	$temOutros = tabelaTemColuna($pdo, 'tutores', 'comissao_outros_alunos');

	if (!$temMeus && !$temOutros) {
		$stmt = $pdo->prepare("SELECT comissao FROM tutores WHERE id = :id LIMIT 1");
		$stmt->execute([':id' => $tutorId]);
		$valor = $stmt->fetchColumn();
		$comissoes['meus'] = $valor !== false ? (float) $valor : 0.0;
		return $comissoes;
	}

	$campos = ["comissao"];
	if ($temMeus) {
		$campos[] = "comissao_meus_alunos";
	}
	if ($temOutros) {
		$campos[] = "comissao_outros_alunos";
	}

	$stmt = $pdo->prepare("SELECT " . implode(', ', $campos) . " FROM tutores WHERE id = :id LIMIT 1");
	$stmt->execute([':id' => $tutorId]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

	$comissaoBase = isset($row['comissao']) ? (float) $row['comissao'] : 0.0;
	$comissoes['meus'] = ($temMeus && isset($row['comissao_meus_alunos']) && $row['comissao_meus_alunos'] !== null)
		? (float) $row['comissao_meus_alunos']
		: $comissaoBase;
	$comissoes['outros'] = ($temOutros && isset($row['comissao_outros_alunos']) && $row['comissao_outros_alunos'] !== null)
		? (float) $row['comissao_outros_alunos']
		: $padraoOutros;

	return $comissoes;
}

$comissao_responsavel = 0;
$stmtConfig = $pdo->query("SELECT comissao_tutor FROM config LIMIT 1");
$comissao_tutor_atendimento = $stmtConfig ? (float) ($stmtConfig->fetchColumn() ?: 0) : 0;

if ($nivel == 'Vendedor' && $id_pessoa) {
	$stmt = $pdo->prepare("SELECT comissao FROM vendedores WHERE id = :id LIMIT 1");
	$stmt->execute([':id' => $id_pessoa]);
	$comissao_responsavel = $stmt->fetchColumn() ?: 0;
} elseif ($nivel == 'Tutor' && $id_pessoa) {
	$comissoesTutor = obterComissoesTutor($pdo, $id_pessoa, $comissao_tutor_atendimento);
	$comissao_responsavel = $comissoesTutor['meus'];
	$comissao_tutor_atendimento = $comissoesTutor['outros'];
} elseif ($nivel == 'Parceiro' && $id_pessoa) {
	$stmt = $pdo->prepare("SELECT comissao FROM parceiros WHERE id = :id LIMIT 1");
	$stmt->execute([':id' => $id_pessoa]);
	$comissao_responsavel = $stmt->fetchColumn() ?: 0;
}

function listarMatriculasPorResponsaveis(PDO $pdo, array $responsavelIds, string $dataInicial, string $dataFinal): array
{
	if (empty($responsavelIds)) {
		return [];
	}
	$placeholders = implode(',', array_fill(0, count($responsavelIds), '?'));
	$usaResponsavelId = tabelaTemColuna($pdo, 'alunos', 'responsavel_id');
	$filtroResponsavel = $usaResponsavelId ? "COALESCE(NULLIF(a.responsavel_id, 0), a.usuario)" : "a.usuario";
	$sql = "SELECT m.*, u.nome AS aluno_nome
			FROM matriculas m
			JOIN usuarios u ON u.id = m.aluno
			JOIN alunos a ON a.id = u.id_pessoa
			WHERE {$filtroResponsavel} IN ($placeholders)
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
$obterNomeCurso = function ($cursoId, $pacote, $pacoteId = null) use (&$cacheCursos, &$cachePacotes, $stmtCurso, $stmtPacote) {
	$cursoId = (int) $cursoId;
	$pacoteId = (int) ($pacoteId ?? 0);
	$isPacote = ($pacote === 'Sim') || $pacoteId > 0;
	if ($isPacote) {
		$pacoteLookup = $pacoteId > 0 ? $pacoteId : $cursoId;
		if (isset($cachePacotes[$pacoteLookup])) {
			return $cachePacotes[$pacoteLookup];
		}
		$stmtPacote->execute([$pacoteLookup]);
		$nome = $stmtPacote->fetchColumn() ?: '';
		$cachePacotes[$pacoteLookup] = $nome;
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
if (in_array($nivel, ['Vendedor', 'Tutor', 'Parceiro'], true)) {
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
		'curso' => $obterNomeCurso($row['id_curso'] ?? 0, $row['pacote'] ?? '', $row['id_pacote'] ?? null),
		'valor' => $valor_comissao,
		'status' => $recebido ? 'Recebido' : 'A receber',
		'data' => $recebido ? ($row['data'] ?? '') : '',
		'data_matricula' => $row['data'] ?? ''
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
	$stmtParceiros = $pdo->prepare("SELECT u.id AS usuario_id
		FROM parceiros p
		JOIN usuarios u ON u.id_pessoa = p.id AND u.nivel = 'Parceiro'
		WHERE p.professor = 1 AND p.tutor_id = :tutor_id");
	$stmtParceiros->execute([':tutor_id' => $id_pessoa]);
	$parceirosUsuarios = $stmtParceiros->fetchAll(PDO::FETCH_COLUMN, 0);
	$vendedoresUsuarios = array_merge($vendedoresUsuarios, $parceirosUsuarios);

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
			'curso' => $obterNomeCurso($row['id_curso'] ?? 0, $row['pacote'] ?? '', $row['id_pacote'] ?? null),
			'valor' => $valor_comissao,
			'status' => $recebido ? 'Recebido' : 'A receber',
			'data' => $recebido ? ($row['data'] ?? '') : '',
			'data_matricula' => $row['data'] ?? ''
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
	echo "<div style=\"margin-bottom: 10px;\"><strong>Total de registros encontrados: " . count($comissoes) . "</strong></div>";
	echo "<table class=\"table table-hover\" id=\"tabela\">";
	echo "<thead><tr>";
	echo "<th>Aluno</th>";
	echo "<th class=\"esc\">Curso/Pacote</th>";
	echo "<th class=\"esc\">Valor Comissao</th>";
	echo "<th class=\"esc\">Status</th>";
	echo "<th class=\"esc\">Data Recebimento</th>";
	echo "</tr></thead><tbody>";

	foreach ($comissoes as $item) {
		$valorF = number_format($item['valor'], 2, ',', '.');
		$statusClass = ($item['status'] === 'Recebido') ? 'verde' : 'text-danger';
		$data = $item['data'] ? implode('/', array_reverse(explode('-', $item['data']))) : '-';
		$dataMatricula = $item['data_matricula'] ? implode('/', array_reverse(explode('-', $item['data_matricula']))) : '-';
		$alunoLinha = $item['aluno'] . "<br><small>Matricula: {$dataMatricula}</small>";
		echo "<tr>";
		echo "<td>{$alunoLinha}</td>";
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
