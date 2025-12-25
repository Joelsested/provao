<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas_quest';

$id_curso = $_POST['id'];


echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela where curso = :curso ORDER BY id asc");
$query->execute([':curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
echo <<<HTML
	<small><table class="table table-hover">
	<thead> 
	<tr> 	
	<th class="">Pergunta</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$pergunta = $res[$i]['pergunta'];	
	
	
echo <<<HTML
<tr> 				
		<td class=""><a href="#" onclick="alternativas('{$id}', '{$id_curso}', '{$pergunta}')">{$pergunta}</a></td>	

		<td>
		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluirQuest('{$id}', '{$id_curso}')"><span class="text-danger">Sim</span></a></p>
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
<small><div align="center" id="mensagem-excluir-sessao"></div></small>
</table>	
</small>
HTML;

}else{
	echo 'Não possui nenhuma pergunta cadastrada!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

	$(document).ready( function () {
		
	} );
	
	

function excluirQuest(id, curso){
    $.ajax({
        url: 'paginas/' + pag + "/excluir-quest.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
                listarPerguntasQuest(curso);             
            } else {
                    $('#mensagem-excluir-quest').addClass('text-danger')
                    $('#mensagem-excluir-quest').text(mensagem)
                }

        },      

    });
}



function alternativas(id, curso, pergunta){
	$('#id-alt').val(id);
   $('#id-curso-alt').val(curso);
		$('#nome_curso_alt').text(pergunta);	
		$('#alternativa').val('');		
		$('#modalAlt').modal('show');
		listarAlt(id);
}



</script>
