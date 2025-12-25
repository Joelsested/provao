<?php 
require_once("../../../conexao.php");
$tabela = 'sessao';

$id_curso = $_POST['id_curso'];


echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela where curso = :curso ORDER BY id asc");
$query->execute([':curso' => $id_curso]);
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
	$nome = $res[$i]['nome'];	
	
	
echo <<<HTML
<tr> 				
		<td class="">{$nome}</td>	

		<td>
		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluirSessao('{$id}')"><span class="text-danger">Sim</span></a></p>
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
	echo 'Não possui nenhuma sessão cadastrada!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

	$(document).ready( function () {
		
	} );
	
	

function excluirSessao(id){
    $.ajax({
        url: 'paginas/' + pag + "/excluir-sessao.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
                listarSessao();                
            } else {
                    $('#mensagem-excluir-sessao').addClass('text-danger')
                    $('#mensagem-excluir-sessao').text(mensagem)
                }

        },      

    });
}





</script>
