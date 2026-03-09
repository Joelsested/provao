<?php
require_once("../../../conexao.php");

@session_start();

if (@$_SESSION['nivel'] != 'Secretario') {
	echo 'Nao autorizado';
	exit();
}

$id_user = $_SESSION['id'];

$stmtSecretario = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id AND nivel = 'Secretario' LIMIT 1");
$stmtSecretario->execute([':id' => $id_user]);
$secretario_id = (int) ($stmtSecretario->fetchColumn() ?: 0);

if (!$secretario_id) {
	echo 'Secretario nao encontrado.';
	exit();
}

$vendedor_ids = [];
$parceiro_ids = [];

$stmtVendedores = $pdo->prepare("SELECT id FROM vendedores WHERE professor = 1 AND secretario_id = :secretario_id");
$stmtVendedores->execute([':secretario_id' => $secretario_id]);
$vendedor_ids = $stmtVendedores->fetchAll(PDO::FETCH_COLUMN, 0);

$stmtParceiros = $pdo->prepare("SELECT id FROM parceiros WHERE professor = 1 AND secretario_id = :secretario_id");
$stmtParceiros->execute([':secretario_id' => $secretario_id]);
$parceiro_ids = $stmtParceiros->fetchAll(PDO::FETCH_COLUMN, 0);

$usuario_ids = [];
if (!empty($vendedor_ids)) {
	$placeholders = implode(',', array_fill(0, count($vendedor_ids), '?'));
	$stmtUsuarios = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa IN ($placeholders) AND nivel = 'Vendedor' ORDER BY id desc");
	$stmtUsuarios->execute($vendedor_ids);
	$usuario_ids = array_merge($usuario_ids, $stmtUsuarios->fetchAll(PDO::FETCH_COLUMN, 0));
}
if (!empty($parceiro_ids)) {
	$placeholders = implode(',', array_fill(0, count($parceiro_ids), '?'));
	$stmtUsuarios = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa IN ($placeholders) AND nivel = 'Parceiro' ORDER BY id desc");
	$stmtUsuarios->execute($parceiro_ids);
	$usuario_ids = array_merge($usuario_ids, $stmtUsuarios->fetchAll(PDO::FETCH_COLUMN, 0));
}

$usuario_ids = array_values(array_unique(array_filter($usuario_ids)));

if (empty($usuario_ids)) {
	echo 'Nao possui nenhum registro cadastrado!';
	exit();
}

$placeholders_alunos = implode(',', array_fill(0, count($usuario_ids), '?'));
$stmtAlunos = $pdo->prepare("SELECT * FROM alunos WHERE usuario IN ($placeholders_alunos) ORDER BY id desc");
$stmtAlunos->execute($usuario_ids);
$res = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);

$total_reg = @count($res);
if ($total_reg <= 0) {
	echo 'Nao possui nenhum registro cadastrado!';
	exit();
}

echo <<<HTML
<small>
<table class="table table-hover" id="tabela">
<thead>
<tr>
<th>Nome</th>
<th class="esc">Telefone</th>
<th class="esc">Email</th>
<th class="esc">Cadastro</th>
</tr>
</thead>
<tbody>
HTML;

for ($i = 0; $i < $total_reg; $i++) {
	foreach ($res[$i] as $key => $value) {
	}
	$nome = $res[$i]['nome'];
	$telefone = $res[$i]['telefone'];
	$email = $res[$i]['email'];
	$foto = $res[$i]['foto'];
	$data = $res[$i]['data'];

	$dataF = implode('/', array_reverse(explode('-', $data)));
	$icone_whatsapp = $telefone ? 'fa-whatsapp' : '';

	echo <<<HTML
<tr>
	<td>
	<img src="../painel-aluno/img/perfil/{$foto}" width="27px" class="mr-2">
	{$nome}
	</td>
	<td class="esc">
	{$telefone}
	<a target="_blank" href="https://api.whatsapp.com/send?1=pt_BR&phone=55{$telefone}" title="Chamar no Whatsapp"><i class="fa {$icone_whatsapp} verde"></i></a>
	</td>
	<td class="esc">{$email}</td>
	<td class="esc">{$dataF}</td>
</tr>
HTML;
}

echo <<<HTML
</tbody>
</table>
<div id="rodape_registros_outros_alunos" style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:nowrap; overflow-x:auto;">
	<div id="resumo_registros_outros_alunos" style="color:#666; white-space:nowrap;"></div>
	<div id="paginacao_registros_outros_alunos" style="white-space:nowrap;"></div>
</div>
</small>
HTML;
?>

<script type="text/javascript">
	$(document).ready(function () {
		const dtApi = $('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
		});

		function sincronizarPaginacaoRodape() {
			const $destino = $('#paginacao_registros_outros_alunos');
			if (!$destino.length) {
				return;
			}
			const $paginacao = $('#tabela_paginate');
			if ($paginacao.length) {
				$paginacao.css({ float: 'none', textAlign: 'right', margin: 0 });
				$destino.empty().append($paginacao);
				return;
			}
			$destino.empty();
		}

		function atualizarResumoRegistros() {
			const $resumo = $('#resumo_registros_outros_alunos');
			if (!$resumo.length || !dtApi) {
				return;
			}
			const info = dtApi.page.info();
			const totalFiltrado = info ? info.recordsDisplay : 0;
			const totalGeral = info ? info.recordsTotal : 0;

			if (!totalFiltrado) {
				$resumo.text('Nenhum aluno encontrado.');
				return;
			}

			const inicio = (info.start || 0) + 1;
			const fim = info.end || totalFiltrado;
			$resumo.text('Mostrando ' + inicio + ' até ' + fim + ' de ' + totalFiltrado + ' alunos' + (totalFiltrado !== totalGeral ? ' (total: ' + totalGeral + ')' : '') + '.');
		}

		$('#tabela_info').hide();
		sincronizarPaginacaoRodape();
		atualizarResumoRegistros();

		$('#tabela').on('draw.dt', function () {
			$('#tabela_info').hide();
			sincronizarPaginacaoRodape();
			atualizarResumoRegistros();
		});

		$('#tabela_filter label input').focus();
	});
</script>
