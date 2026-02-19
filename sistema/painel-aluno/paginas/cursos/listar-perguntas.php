<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas';
@session_start();
$id_aluno = $_SESSION['id'];

$id_do_curso_pag = $_POST['id'];

$query_m = $pdo->prepare("SELECT * FROM {$tabela} WHERE curso = :curso ORDER BY id desc");
$query_m->execute(['curso' => $id_do_curso_pag]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
for($i_m=0; $i_m < @count($res_m); $i_m++){
	foreach ($res_m[$i_m] as $key => $value){}
$id = $res_m[$i_m]['id'];
$pergunta = $res_m[$i_m]['pergunta'];
$aula = $res_m[$i_m]['aula'];
$data = $res_m[$i_m]['data'];
$dataF = implode('/', array_reverse(explode('-', $data)));
$aluno = $res_m[$i_m]['aluno'];

$query = $pdo->prepare("SELECT * FROM respostas WHERE pergunta = :pergunta ORDER BY id desc");
$query->execute(['pergunta' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$respostas = @count($res);

if($aluno == $id_aluno){
	$mostrar_excluir = '';
}else{
	$mostrar_excluir = 'ocultar';
}

$query = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$query->execute(['id' => $aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$nome = $res[0]['nome'];
$img_aluno = $res[0]['foto'];

echo <<<HTML
<div class="mt-3">
<small>
<span> <img style="border-radius: 100%;" class="rounded-circle z-depth-0" src="img/perfil/{$img_aluno}" width="25" height="25">  
<span class="text-muted"><b>{$nome}</b> </span>
<span class="text-muted" style="margin-left:10px">{$dataF}</span> 
<span class="text-muted" style="margin-left:10px">{$respostas} Respostas</span> </span>

	<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a class="{$mostrar_excluir}" href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Excluir Pergunta"><big><i class="fa fa-trash-o text-danger "></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluirPergunta('{$id}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>

<br>
<span > <a class="link-aula" href="#" onclick="modalResposta('{$id}', '{$pergunta}')" title="Abrir Pergunta">Aula {$aula} - {$pergunta}</a> </span></small>
</div>
<hr>
HTML;


}
?>		
