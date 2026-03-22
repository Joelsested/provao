
<?php
require_once('verificar.php');
$pag = 'pagar';

if (
	@$_SESSION['nivel'] != 'Administrador' &&
	@$_SESSION['nivel'] != 'Secretario' &&
	@$_SESSION['nivel'] != 'Tesoureiro' &&
	@$_SESSION['nivel'] != 'Assessor'
) {
	if (!headers_sent()) {
		header('Location: ../index.php');
		exit();
	}
	echo "<script>window.location='../index.php'</script>";
	exit();
}

$data_hoje = date('Y-m-d');
$data_ontem = date('Y-m-d', strtotime("-1 days", strtotime($data_hoje)));

$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_mes = $ano_atual . "-" . $mes_atual . "-01";

$total_alunos = 0;
$total_mat_pendentes = 0;
$total_mat_aprovadas = 0;
$total_vendas_dia = 0;
$total_vendas_diaF = 0;
$total_cursos = 0;

$total_itens_preenchidos = 3;
$total_itens_perfil = 10;
$porcentagemPerfil = 0;
$porcentagemCursos = 0;

$query = $pdo->query("SELECT * FROM alunos ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_alunos = @count($res);

$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno");
$query->execute([':aluno' => $id_usuario]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_mat = @count($res);

$query = $pdo->query("SELECT * FROM matriculas where status = 'Aguardando'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_mat_pendentes = @count($res);

$query = $pdo->prepare("SELECT * FROM matriculas where status != 'Aguardando' and data >= :data_mes and data <= curDate()");
$query->execute([':data_mes' => $data_mes]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_mat_aprovadas = @count($res);

$query = $pdo->query("SELECT * FROM matriculas where status != 'Aguardando' and subtotal > 0 and data = curDate() ORDER BY id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0) {
	for ($i = 0; $i < $total_reg; $i++) {
		foreach ($res[$i] as $key => $value) {
		}
		$total_recebido = $res[$i]['total_recebido'];
		$total_vendas_dia += $total_recebido;
	}
}
$total_vendas_diaF = number_format($total_vendas_dia, 2, ',', '.');

$query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_cursos = @count($res);

$dashboard_data_inicio = trim($_GET['data_inicio'] ?? '');
$dashboard_data_fim = trim($_GET['data_fim'] ?? '');
$dashboard_usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
$usuarios_responsaveis = [];

function validarDataDashboard(string $valor): string
{
    $data = DateTime::createFromFormat('Y-m-d', $valor);
    if ($data && $data->format('Y-m-d') === $valor) {
        return $valor;
    }
    return '';
}

if ($dashboard_data_inicio !== '') {
    $dashboard_data_inicio = validarDataDashboard($dashboard_data_inicio);
}
if ($dashboard_data_fim !== '') {
    $dashboard_data_fim = validarDataDashboard($dashboard_data_fim);
}
if ($dashboard_data_inicio !== '' && $dashboard_data_fim !== '' && $dashboard_data_inicio > $dashboard_data_fim) {
    $tmp = $dashboard_data_inicio;
    $dashboard_data_inicio = $dashboard_data_fim;
    $dashboard_data_fim = $tmp;
}

$stmtResponsaveis = $pdo->query("SELECT id, nome, nivel FROM usuarios WHERE id IN (SELECT DISTINCT usuario FROM alunos) ORDER BY nome");
$usuarios_responsaveis = $stmtResponsaveis ? $stmtResponsaveis->fetchAll(PDO::FETCH_ASSOC) : [];


$matriculas_pagas = 0;
$matriculas_pendentes = 0;
$usuarios_matriculas = [];
$labels_usuarios = [];
$pagos_usuarios = [];
$pendentes_usuarios = [];

$dashboard_where = ["(m.pacote = 'Sim' OR m.id_pacote IS NULL OR m.id_pacote = 0)"];
$dashboard_params = [];

if ($dashboard_data_inicio !== '') {
    $dashboard_where[] = "m.data >= :data_inicio";
    $dashboard_params[':data_inicio'] = $dashboard_data_inicio;
}
if ($dashboard_data_fim !== '') {
    $dashboard_where[] = "m.data <= :data_fim";
    $dashboard_params[':data_fim'] = $dashboard_data_fim;
}
if ($dashboard_usuario_id) {
    $dashboard_where[] = "v.id = :usuario_id";
    $dashboard_params[':usuario_id'] = $dashboard_usuario_id;
}

$dashboard_where_sql = implode(' AND ', $dashboard_where);

$stmtPagamentos = $pdo->prepare("SELECT
    SUM(CASE WHEN m.status IN ('Matriculado', 'Finalizado') THEN 1 ELSE 0 END) AS pagos,
    SUM(CASE WHEN m.status = 'Aguardando' THEN 1 ELSE 0 END) AS pendentes
  FROM matriculas m
  LEFT JOIN usuarios u ON u.id = m.aluno
  LEFT JOIN alunos a ON a.id = u.id_pessoa
  LEFT JOIN usuarios v ON v.id = a.usuario
  WHERE {$dashboard_where_sql}");
$stmtPagamentos->execute($dashboard_params);
$resPagamentos = $stmtPagamentos->fetch(PDO::FETCH_ASSOC) ?: [];
$matriculas_pagas = (int) ($resPagamentos['pagos'] ?? 0);
$matriculas_pendentes = (int) ($resPagamentos['pendentes'] ?? 0);

$sqlUsuarios = "SELECT v.id, v.nome,
        SUM(CASE WHEN m.status IN ('Matriculado', 'Finalizado') THEN 1 ELSE 0 END) AS pagos,
        SUM(CASE WHEN m.status = 'Aguardando' THEN 1 ELSE 0 END) AS pendentes,
        COUNT(*) AS total
    FROM matriculas m
    LEFT JOIN usuarios u ON u.id = m.aluno
    LEFT JOIN alunos a ON a.id = u.id_pessoa
    LEFT JOIN usuarios v ON v.id = a.usuario
    WHERE {$dashboard_where_sql} AND v.id IS NOT NULL
    GROUP BY v.id, v.nome
    ORDER BY total DESC
    LIMIT 10";
$stmtUsuarios = $pdo->prepare($sqlUsuarios);
$stmtUsuarios->execute($dashboard_params);
$usuarios_matriculas = $stmtUsuarios ? $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC) : [];
foreach ($usuarios_matriculas as $item) {
    $labels_usuarios[] = $item['nome'];
    $pagos_usuarios[] = (int) $item['pagos'];
    $pendentes_usuarios[] = (int) $item['pendentes'];
}


$dados_meses = '';
//ALIMENTAR DADOS PARA O GRÁFICO
for ($i = 1; $i <= 12; $i++) {
	if ($i < 10) {
		$mes_atual = '0' . $i;
	} else {
		$mes_atual = $i;
	}

	if ($mes_atual == '4' || $mes_atual == '6' || $mes_atual == '9' || $mes_atual == '11') {
		$dia_final_mes = '30';
	} else if ($mes_atual == '2') {
		$dia_final_mes = '28';
	} else {
		$dia_final_mes = '31';
	}

	$data_mes_inicio_grafico = $ano_atual . "-" . $mes_atual . "-01";
	$data_mes_final_grafico = $ano_atual . "-" . $mes_atual . "-" . $dia_final_mes;

	$total_mes = 0;
	$query = $pdo->prepare("SELECT * FROM matriculas where status != 'Aguardando' and subtotal > 0 and data >= :data_inicio and data <= :data_final ORDER BY id asc");
	$query->execute([
		':data_inicio' => $data_mes_inicio_grafico,
		':data_final' => $data_mes_final_grafico,
	]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);
	if ($total_reg > 0) {
		for ($i2 = 0; $i2 < $total_reg; $i2++) {
			foreach ($res[$i2] as $key => $value) {
			}
			$total_mes += $res[$i2]['total_recebido'];
		}
	}

	$dados_meses = $dados_meses . $total_mes . '-';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard</title>
	<style>
		:root {
			--primary: #4361ee;
			--secondary: #3f37c9;
			--success: #4cc9f0;
			--info: #4895ef;
			--warning: #f72585;
			--danger: #e63946;
			--light: #f8f9fa;
			--dark: #212529;
			--gray: #6c757d;
			--card-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
			--gradient-primary: linear-gradient(135deg, #4361ee, #3a0ca3);
			--gradient-success: linear-gradient(135deg, #4cc9f0, #4895ef);
			--gradient-warning: linear-gradient(135deg, #f72585, #b5179e);
			--gradient-danger: linear-gradient(135deg, #e63946, #d90429);
			--gradient-info: linear-gradient(135deg, #4895ef, #4361ee);
		}

		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		}

		body {
			background-color: #f5f7fa;
			color: var(--dark);
			line-height: 1.6;
		}

		.dashboard-container {
			max-width: 1400px;
			margin: 0 auto;
			padding: 20px;
		}

		.dashboard-header {
			margin-bottom: 30px;
			padding-bottom: 20px;
			border-bottom: 1px solid rgba(0, 0, 0, 0.1);
		}

		.dashboard-header h1 {
			font-size: 28px;
			font-weight: 600;
			color: var(--dark);
		}

		.dashboard-header p {
			color: var(--gray);
			font-size: 16px;
		}

		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}

		.stat-card {
			background: white;
			border-radius: 12px;
			padding: 20px;
			box-shadow: var(--card-shadow);
			transition: transform 0.3s ease, box-shadow 0.3s ease;
			position: relative;
			overflow: hidden;
			display: flex;
			flex-direction: column;
			text-decoration: none;
			color: inherit;
		}

		.stat-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
		}

		.stat-card:before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 5px;
			background: var(--gradient-primary);
		}

		.stat-card.students:before {
			background: var(--gradient-primary);
		}

		.stat-card.pending:before {
			background: var(--gradient-warning);
		}

		.stat-card.approved:before {
			background: var(--gradient-success);
		}

		.stat-card.sales:before {
			background: var(--gradient-info);
		}

		.stat-card.courses:before {
			background: var(--gradient-danger);
		}

		.stat-card-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
		}

		.stat-card-icon {
			width: 50px;
			height: 50px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 20px;
			color: white;
		}

		.students .stat-card-icon {
			background: var(--gradient-primary);
		}

		.pending .stat-card-icon {
			background: var(--gradient-warning);
		}

		.approved .stat-card-icon {
			background: var(--gradient-success);
		}

		.sales .stat-card-icon {
			background: var(--gradient-info);
		}

		.courses .stat-card-icon {
			background: var(--gradient-danger);
		}

		.stat-card-value {
			font-size: 34px;
			font-weight: 700;
			margin-bottom: 10px;
			line-height: 1;
		}

		.stat-card-title {
			color: var(--gray);
			font-size: 14px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			font-weight: 600;
		}

		.chart-container {
			background: white;
			border-radius: 12px;
			padding: 25px;
			box-shadow: var(--card-shadow);
			margin-bottom: 30px;
		}

		.chart-header {
			margin-bottom: 20px;
		}

		.chart-header h2 {
			font-size: 20px;
			font-weight: 600;
			color: var(--dark);
			margin-bottom: 5px;
		}

		.chart-header p {
			color: var(--gray);
			font-size: 14px;
		}

		.dashboard-filters {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			align-items: flex-end;
			background: white;
			padding: 16px;
			border-radius: 12px;
			box-shadow: var(--card-shadow);
			margin-bottom: 24px;
		}

		.dashboard-filters .filter-group {
			display: flex;
			flex-direction: column;
			gap: 6px;
			min-width: 180px;
		}

		.dashboard-filters label {
			font-size: 12px;
			color: var(--gray);
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.dashboard-filters input,
		.dashboard-filters select {
			padding: 8px 10px;
			border: 1px solid #d7dbe7;
			border-radius: 8px;
			font-size: 14px;
		}

		.btn-filter {
			background: var(--gradient-primary);
			color: #fff;
			border: none;
			border-radius: 8px;
			padding: 9px 16px;
			font-weight: 600;
			cursor: pointer;
			text-decoration: none;
			display: inline-block;
		}

		.btn-filter.btn-outline {
			background: transparent;
			color: var(--dark);
			border: 1px solid #d7dbe7;
		}

		.charts-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}

		.chart-card {
			background: white;
			border-radius: 12px;
			padding: 20px;
			box-shadow: var(--card-shadow);
			position: relative;
		}

		.chart-canvas {
			width: 100%;
			height: 260px;
		}

		.chart-legend {
			display: flex;
			gap: 12px;
			margin-top: 10px;
			font-size: 13px;
			color: var(--gray);
		}

		.legend-dot {
			display: inline-block;
			width: 10px;
			height: 10px;
			border-radius: 50%;
			margin-right: 6px;
		}

		.ruler-list {
			display: flex;
			flex-direction: column;
			gap: 12px;
			margin-top: 10px;
		}

		.ruler-item {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.ruler-label {
			display: flex;
			justify-content: space-between;
			font-size: 14px;
			color: var(--dark);
			font-weight: 600;
		}

		.ruler-bar {
			display: flex;
			height: 10px;
			border-radius: 999px;
			overflow: hidden;
			background: #eef1f7;
		}

		.ruler-paid {
			background: #2dce89;
			height: 100%;
		}

		.ruler-pending {
			background: #fb6340;
			height: 100%;
		}

		.ruler-meta {
			font-size: 12px;
			color: var(--gray);
		}

		@media (max-width: 768px) {
			.stats-grid {
				grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
			}

			.dashboard-header h1 {
				font-size: 24px;
			}
		}
	</style>
</head>

<body>
	<input type="hidden" id="dados_grafico" value="<?= $dados_meses ?>">

	<div class="dashboard-container">
		<!-- <div class="dashboard-header">
			<h1>Painel de Controle</h1>
			<p>Visão geral do sistema</p>
		</div> -->

		<div class="stats-grid">
			<a href="index.php?pagina=alunos" class="stat-card students">
				<div class="stat-card-header">
					<div class="stat-card-icon">
						<i class="fa fa-graduation-cap"></i>
					</div>
				</div>
				<div class="stat-card-value"><?php echo $total_alunos ?></div>
				<div class="stat-card-title">Total de Alunos</div>
			</a>

			<a href="index.php?pagina=matriculas" class="stat-card pending">
				<div class="stat-card-header">
					<div class="stat-card-icon">
						<i class="fa fa-clock-o"></i>
					</div>
				</div>
				<div class="stat-card-value"><?php echo $total_mat_pendentes ?></div>
				<div class="stat-card-title">Matrículas Pendentes</div>
			</a>

			<a href="index.php?pagina=matriculas_aprovadas" class="stat-card approved">
				<div class="stat-card-header">
					<div class="stat-card-icon">
						<i class="fa fa-check-circle"></i>
					</div>
				</div>
				<div class="stat-card-value"><?php echo $total_mat_aprovadas ?></div>
				<div class="stat-card-title">Mat Aprovadas Mês</div>
			</a>

			<a href="index.php?pagina=vendas" class="stat-card sales">
				<div class="stat-card-header">
					<div class="stat-card-icon">
						<i class="fa fa-shopping-cart"></i>
					</div>
				</div>
				<div class="stat-card-value"><?php echo $total_vendas_diaF ?></div>
				<div class="stat-card-title">Vendas do Dia</div>
			</a>

			<a href="index.php?pagina=cursos" class="stat-card courses">
				<div class="stat-card-header">
					<div class="stat-card-icon">
						<i class="fa fa-book"></i>
					</div>
				</div>
				<div class="stat-card-value"><?php echo $total_cursos ?></div>
				<div class="stat-card-title">Total de Cursos</div>
			</a>
		</div>


		<form class="dashboard-filters" method="get" action="index.php">
			<div class="filter-group">
				<label for="data_inicio">Data inicio</label>
				<input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($dashboard_data_inicio, ENT_QUOTES, 'UTF-8'); ?>">
			</div>
			<div class="filter-group">
				<label for="data_fim">Data fim</label>
				<input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($dashboard_data_fim, ENT_QUOTES, 'UTF-8'); ?>">
			</div>
			<div class="filter-group">
				<label for="usuario_id">Usuario</label>
				<select id="usuario_id" name="usuario_id">
					<option value="">Todos</option>
					<?php foreach ($usuarios_responsaveis as $resp) { ?>
						<option value="<?php echo (int) $resp['id']; ?>" <?php echo ($dashboard_usuario_id && $dashboard_usuario_id == $resp['id']) ? 'selected' : ''; ?>>
							<?php echo htmlspecialchars($resp['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
						</option>
					<?php } ?>
				</select>
			</div>
			<div class="filter-group">
				<button class="btn-filter" type="submit">Filtrar</button>
				<a class="btn-filter btn-outline" href="index.php">Limpar</a>
			</div>
		</form>

		<div class="charts-grid">
			<div class="chart-card">
				<div class="chart-header">
					<h2>Pagamentos de Matriculas</h2>
					<p>Proporcao de matriculas pagas e pendentes</p>
				</div>
				<div class="chart-canvas">
					<canvas id="chart-pizza"></canvas>
				</div>
				<div class="chart-legend">
					<span><span class="legend-dot" style="background:#2dce89;"></span>Pagas: <?php echo $matriculas_pagas; ?></span>
					<span><span class="legend-dot" style="background:#fb6340;"></span>Pendentes: <?php echo $matriculas_pendentes; ?></span>
				</div>
			</div>

			<div class="chart-card">
				<div class="chart-header">
					<h2>Matriculas por Usuario</h2>
					<p>Top usuarios com matriculas pagas e pendentes</p>
				</div>
				<div class="chart-canvas">
					<canvas id="chart-torre"></canvas>
				</div>
			</div>

			<div class="chart-card">
				<div class="chart-header">
					<h2>Regua de Pagamentos</h2>
					<p>Percentual de pagamentos por usuario</p>
				</div>
				<div class="ruler-list">
					<?php if (!empty($usuarios_matriculas)) { ?>
						<?php foreach ($usuarios_matriculas as $item) { 
							$total_user = (int) ($item['total'] ?? 0);
							$pagos_user = (int) ($item['pagos'] ?? 0);
							$pendentes_user = (int) ($item['pendentes'] ?? 0);
							$percent = $total_user > 0 ? round(($pagos_user / $total_user) * 100) : 0;
						?>
						<div class="ruler-item">
							<div class="ruler-label">
								<span><?php echo htmlspecialchars($item['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
								<span><?php echo $total_user; ?></span>
							</div>
							<div class="ruler-bar">
								<span class="ruler-paid" style="width: <?php echo $percent; ?>%;"></span>
								<span class="ruler-pending" style="width: <?php echo 100 - $percent; ?>%;"></span>
							</div>
							<div class="ruler-meta">Pagas: <?php echo $pagos_user; ?> | Pendentes: <?php echo $pendentes_user; ?></div>
						</div>
						<?php } ?>
					<?php } else { ?>
						<div class="ruler-meta">Nenhuma matricula encontrada.</div>
					<?php } ?>
				</div>
			</div>
		</div>

		<div class="chart-container">
			<div class="chart-header">
				<h2>Demonstrativo de Vendas</h2>
				<p>Valores de vendas mensais do ano corrente</p>
			</div>
			<div id="Linegraph" style="width: 100%; height: 400px"></div>
		</div>
	</div>

	<!-- Scripts -->
	<script src="js/amcharts.js"></script>
	<script src="js/serial.js"></script>
	<script src="js/export.min.js"></script>
	<link rel="stylesheet" href="css/export.css" type="text/css" media="all" />
	<script src="js/light.js"></script>
	<script src="js/SimpleChart.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var dados = document.getElementById('dados_grafico').value;
			var saldo_mes = dados.split('-');

			var graphdata1 = {
				linecolor: "#4361ee",
				title: "Vendas",
				values: [
					{ X: "Janeiro", Y: parseFloat(saldo_mes[0]) },
					{ X: "Fevereiro", Y: parseFloat(saldo_mes[1]) },
					{ X: "Março", Y: parseFloat(saldo_mes[2]) },
					{ X: "Abril", Y: parseFloat(saldo_mes[3]) },
					{ X: "Maio", Y: parseFloat(saldo_mes[4]) },
					{ X: "Junho", Y: parseFloat(saldo_mes[5]) },
					{ X: "Julho", Y: parseFloat(saldo_mes[6]) },
					{ X: "Agosto", Y: parseFloat(saldo_mes[7]) },
					{ X: "Setembro", Y: parseFloat(saldo_mes[8]) },
					{ X: "Outubro", Y: parseFloat(saldo_mes[9]) },
					{ X: "Novembro", Y: parseFloat(saldo_mes[10]) },
					{ X: "Dezembro", Y: parseFloat(saldo_mes[11]) },
				]
			};


			var matriculasPagas = <?php echo (int) $matriculas_pagas; ?>;
			var matriculasPendentes = <?php echo (int) $matriculas_pendentes; ?>;
			var labelsUsuarios = <?php echo json_encode($labels_usuarios, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
			var pagosUsuarios = <?php echo json_encode($pagos_usuarios, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
			var pendentesUsuarios = <?php echo json_encode($pendentes_usuarios, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

			var pieCanvas = document.getElementById('chart-pizza');
			if (pieCanvas && (matriculasPagas > 0 || matriculasPendentes > 0)) {
				var pieCtx = pieCanvas.getContext('2d');
				var pieData = [
					{ value: matriculasPagas, color: '#2dce89', highlight: '#2dce89', label: 'Pagas' },
					{ value: matriculasPendentes, color: '#fb6340', highlight: '#fb6340', label: 'Pendentes' }
				];
				new Chart(pieCtx).Pie(pieData, { responsive: true, maintainAspectRatio: false });
			}

			var barCanvas = document.getElementById('chart-torre');
			if (barCanvas && labelsUsuarios.length) {
				var barCtx = barCanvas.getContext('2d');
				var barData = {
					labels: labelsUsuarios,
					datasets: [
						{
							fillColor: 'rgba(45, 206, 137, 0.7)',
							strokeColor: 'rgba(45, 206, 137, 1)',
							highlightFill: 'rgba(45, 206, 137, 0.9)',
							highlightStroke: 'rgba(45, 206, 137, 1)',
							data: pagosUsuarios
						},
						{
							fillColor: 'rgba(251, 99, 64, 0.7)',
							strokeColor: 'rgba(251, 99, 64, 1)',
							highlightFill: 'rgba(251, 99, 64, 0.9)',
							highlightStroke: 'rgba(251, 99, 64, 1)',
							data: pendentesUsuarios
						}
					]
				};
				new Chart(barCtx).Bar(barData, {
					scaleBeginAtZero: true,
					responsive: true,
					maintainAspectRatio: false,
					scaleShowGridLines: true,
					barValueSpacing: 8,
					barDatasetSpacing: 6
				});
			}

			$("#Linegraph").SimpleChart({
				ChartType: "Line",
				toolwidth: "50",
				toolheight: "25",
				axiscolor: "#E6E6E6",
				textcolor: "#6E6E6E",
				showlegends: false,
				data: [graphdata1],
				legendsize: "140",
				legendposition: 'bottom',
				xaxislabel: '',
				title: 'Total R$ Matrículas',
				yaxislabel: ''
			});
		});
	</script>
</body>

</html>

