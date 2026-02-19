<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'comissoes_usuario';

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Secretario') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}

$usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
$usuario = null;
if ($usuario_id) {
	$stmtUsuario = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE id = :id LIMIT 1");
	$stmtUsuario->execute([':id' => $usuario_id]);
	$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
}

if (!$usuario) {
	echo 'Funcionario nao encontrado.';
	exit();
}

$data_hoje = date('Y-m-d');
$data_ontem = date('Y-m-d', strtotime("-1 days", strtotime($data_hoje)));

$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";

if ($mes_atual == '4' || $mes_atual == '6' || $mes_atual == '9' || $mes_atual == '11') {
	$dia_final_mes = '30';
} else if ($mes_atual == '2') {
	$dia_final_mes = '28';
} else {
	$dia_final_mes = '31';
}

$data_final_mes = $ano_atual . "-" . $mes_atual . "-" . $dia_final_mes;
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";
?>

<div class="bs-example widget-shadow" style="padding:15px;">
	<div style="margin-bottom: 10px;">
		<strong>Funcionario:</strong> <?php echo $usuario['nome'] ?> (<?php echo $usuario['nivel'] ?>)
	</div>

	<div class="row">

	<div class="col-md-5" style="margin-bottom:5px;">

		<div style="float:left; margin-right:10px"><span><small><i title="Data Inicial" class="fa fa-calendar-o"></i></small></span></div>
		<div style="float:left; margin-right:20px">
			<input type="date" class="form-control" name="data-inicial" id="data-inicial-caixa" value="<?php echo $data_inicio_mes ?>" required>
		</div>

		<div style="float:left; margin-right:10px"><span><small><i title="Data Final" class="fa fa-calendar-o"></i></small></span></div>
		<div style="float:left; margin-right:30px">
			<input type="date" class="form-control" name="data-final" id="data-final-caixa" value="<?php echo $data_final_mes ?>" required>
		</div>
	</div>

	<div class="col-md-2" style="margin-top:5px;" align="center">
		<div>
		<small>
			<a title="Conta de Ontem" class="text-muted" href="#" onclick="valorData('<?php echo $data_ontem ?>', '<?php echo $data_ontem ?>')"><span>Ontem</span></a> /
			<a title="Conta de Hoje" class="text-muted" href="#" onclick="valorData('<?php echo $data_hoje ?>', '<?php echo $data_hoje ?>')"><span>Hoje</span></a> /
			<a title="Conta do Mes" class="text-muted" href="#" onclick="valorData('<?php echo $data_inicio_mes ?>', '<?php echo $data_final_mes ?>')"><span>Mes</span></a>
		</small>
		</div>
	</div>

	<div class="col-md-2" style="margin-top:5px;" align="center">
		<div>
		<div class="form-group">
			<select class="form-control-sm sel2" name="pago" id="pago" required style="width:100%;">
				<option value="Nao">Pendentes</option>
				<option value="Sim">Recebidas</option>
			</select>
		</div>
		</div>
	</div>

	<div class="col-md-3" style="margin-top:5px;" align="right">
		<form method="post" action="../rel/comissoes_usuario_class.php" target="_blank" id="form-relatorio-comissoes">
			<input type="hidden" name="dataInicial" id="rel-data-inicial">
			<input type="hidden" name="dataFinal" id="rel-data-final">
			<input type="hidden" name="pago" id="rel-pago">
			<input type="hidden" name="usuario_id" id="rel-usuario-id" value="<?php echo (int) $usuario_id ?>">
			<button type="button" class="btn btn-primary btn-sm" onclick="imprimirRelatorioComissoes()">Imprimir PDF</button>
		</form>
	</div>

	</div>

	<input type="hidden" id="usuario-id" value="<?php echo (int) $usuario_id ?>">

	<hr>

	<div id="listar"></div>
</div>

<script type="text/javascript">var pag = "<?= $pag ?>"</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
	function valorData(dataInicio, dataFinal) {
		$('#data-inicial-caixa').val(dataInicio);
		$('#data-final-caixa').val(dataFinal);
		listar();
	}
</script>

<script type="text/javascript">
	function imprimirRelatorioComissoes() {
		$('#rel-data-inicial').val($('#data-inicial-caixa').val());
		$('#rel-data-final').val($('#data-final-caixa').val());
		$('#rel-pago').val($('#pago').val());
		document.getElementById('form-relatorio-comissoes').submit();
	}
</script>

<script type="text/javascript">
	function listar() {
		var dataInicial = $('#data-inicial-caixa').val();
		var dataFinal = $('#data-final-caixa').val();
		var pago = $('#pago').val();
		var usuario_id = $('#usuario-id').val();

		$.ajax({
			url: 'paginas/' + pag + "/listar.php",
			method: 'POST',
			data: {dataInicial, dataFinal, pago, usuario_id},
			dataType: "html",
			success: function(result) {
				$("#listar").html(result);
			}
		});
	}
</script>

<script type="text/javascript">
	$('#data-inicial-caixa').change(function() {
		listar();
	});

	$('#data-final-caixa').change(function() {
		listar();
	});

	$('#pago').change(function() {
		listar();
	});
</script>
