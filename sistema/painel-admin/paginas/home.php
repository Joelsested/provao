
<?php
require_once('verificar.php');
$pag = 'pagar';

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro') {
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
