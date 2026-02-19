<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../helpers.php");
@session_start();

if (empty($_SESSION['nivel']) || !in_array($_SESSION['nivel'], ['Administrador', 'Secretario'], true)) {
	http_response_code(403);
	echo 'Sem permissao.';
	exit();
}

$alunoId = filter_input(INPUT_POST, 'aluno_id', FILTER_VALIDATE_INT);
if (!$alunoId) {
	echo 'Aluno invalido.';
	exit();
}

ensureHistoricoAtendentesTable($pdo);

$stmt = $pdo->prepare("SELECT h.id, h.data, h.motivo, h.origem,
	ua.nome AS anterior_nome,
	un.nome AS novo_nome,
	uadm.nome AS admin_nome
	FROM historico_atendentes h
	LEFT JOIN usuarios ua ON ua.id = h.usuario_anterior
	LEFT JOIN usuarios un ON un.id = h.usuario_novo
	LEFT JOIN usuarios uadm ON uadm.id = h.admin_id
	WHERE h.aluno_id = :aluno_id
	ORDER BY h.data DESC");
$stmt->execute([':aluno_id' => $alunoId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
	echo '<div class="text-muted">Nenhum historico encontrado.</div>';
	exit();
}

echo '<table class="table table-hover">';
echo '<thead><tr>
	<th>Data</th>
	<th>Anterior</th>
	<th>Novo</th>
	<th>Motivo</th>
	<th>Origem</th>
	<th>Admin</th>
</tr></thead><tbody>';

foreach ($rows as $row) {
	$data = $row['data'] ? date('d/m/Y H:i', strtotime($row['data'])) : '';
	$anterior = htmlspecialchars($row['anterior_nome'] ?? '-', ENT_QUOTES, 'UTF-8');
	$novo = htmlspecialchars($row['novo_nome'] ?? '-', ENT_QUOTES, 'UTF-8');
	$motivo = htmlspecialchars($row['motivo'] ?? '-', ENT_QUOTES, 'UTF-8');
	$origem = htmlspecialchars($row['origem'] ?? '-', ENT_QUOTES, 'UTF-8');
	$admin = htmlspecialchars($row['admin_nome'] ?? '-', ENT_QUOTES, 'UTF-8');
	echo "<tr>
		<td>{$data}</td>
		<td>{$anterior}</td>
		<td>{$novo}</td>
		<td>{$motivo}</td>
		<td>{$origem}</td>
		<td>{$admin}</td>
	</tr>";
}

echo '</tbody></table>';
