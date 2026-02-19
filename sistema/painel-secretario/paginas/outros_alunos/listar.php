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
</small>
HTML;
?>

<script type="text/javascript">
	$(document).ready(function () {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
		});
		$('#tabela_filter label input').focus();
	});
</script>
