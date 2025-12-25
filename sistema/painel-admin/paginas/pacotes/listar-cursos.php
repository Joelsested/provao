<?php 
require_once("../../../conexao.php");
$tabela = 'cursos_pacotes';

$id_pacote = $_POST['id_pacote'];


echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela where id_pacote = :id_pacote ORDER BY id asc");
$query->execute([':id_pacote' => $id_pacote]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
echo <<<HTML
	<small><table class="table table-hover" id="tabela2">
	<thead> 
	<tr> 			
	<th class="">Nome</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$id_curso = $res[$i]['id_curso'];

	$query2 = $pdo->prepare("SELECT * FROM cursos where id = :id");
	$query2->execute([':id' => $id_curso]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_curso = $res2[0]['nome'];	
	
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
		<p>Confirmar Exclusão? <a href="#" onclick="excluirCurso('{$id}')"><span class="text-danger">Sim</span></a></p>
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

}else{
	echo 'Não possui nenhum curso no pacote!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

	$(document).ready( function () {
		$('#total_cursos').text('<?=$total_reg?>');
	} );
	
	

function excluirCurso(id){
    $.ajax({
        url: 'paginas/' + pag + "/excluir-cursos.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
                listarCursos();                
            } else {
                    $('#mensagem-excluir-curso').addClass('text-danger')
                    $('#mensagem-excluir-curso').text(mensagem)
                }

        },      

    });
}





</script>

