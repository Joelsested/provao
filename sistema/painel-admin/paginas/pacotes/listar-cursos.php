<?php
require_once("../../../conexao.php");
$tabela = 'cursos_pacotes';

$id_pacote = isset($_POST['id_pacote']) ? (int) $_POST['id_pacote'] : 0;

echo <<<HTML
<small>
HTML;

if ($id_pacote <= 0) {
	echo 'Nao possui nenhum curso no pacote!';
	echo '</small>';
	exit();
}

$query = $pdo->prepare("SELECT * FROM $tabela WHERE id_pacote = :id_pacote ORDER BY id ASC");
$query->execute([':id_pacote' => $id_pacote]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0) {
echo <<<HTML
	<small><table class="table table-hover" id="tabela2">
	<thead>
	<tr>
	<th class="">Nome</th>
	<th>Acoes</th>
	</tr>
	</thead>
	<tbody>
HTML;

for ($i = 0; $i < $total_reg; $i++) {
	$id = $res[$i]['id'];
	$id_curso = (int) $res[$i]['id_curso'];

	$query2 = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id LIMIT 1");
	$query2->execute([':id' => $id_curso]);
	$res2 = $query2->fetch(PDO::FETCH_ASSOC);
	if (!$res2) {
		continue;
	}
	$nome_curso = $res2['nome'];
	$numero_curso = $i + 1;

echo <<<HTML
<tr>
	<td class="">{$numero_curso} - {$nome_curso}</td>
	<td>
	<li class="dropdown head-dpdn2" style="display: inline-block;">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>
	<ul class="dropdown-menu" style="margin-left:-230px;">
	<li>
	<div class="notification_desc2">
	<p>Confirmar Exclusao? <a href="#" onclick="excluirCurso('{$id}')"><span class="text-danger">Sim</span></a></p>
	</div>
	</li>
	</ul>
	</li>
	</td>
</tr>
HTML;

}

echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir-curso"></div></small>
</table>
</small>
HTML;

} else {
	echo 'Nao possui nenhum curso no pacote!';
}
echo <<<HTML
</small>
HTML;

?>

<script type="text/javascript">
	$(document).ready(function () {
		$('#total_cursos').text('<?=$total_reg?>');
	});

	function excluirCurso(id) {
		var csrf_token = (typeof getCsrfToken === 'function') ? getCsrfToken() : '';
		$.ajax({
			url: 'paginas/' + pag + "/excluir-cursos.php",
			method: 'POST',
			data: {id, csrf_token},
			dataType: "text",
			success: function (mensagem) {
				$('#mensagem-excluir-curso').text('');
				$('#mensagem-excluir-curso').removeClass('text-danger');
				if ((mensagem || '').toLowerCase().indexOf('sucesso') !== -1) {
					listarCursos();
				} else {
					$('#mensagem-excluir-curso').addClass('text-danger');
					$('#mensagem-excluir-curso').text(mensagem);
				}
			}
		});
	}
</script>
