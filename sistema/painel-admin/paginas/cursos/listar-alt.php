<?php 
require_once("../../../conexao.php");
$tabela = 'alternativas';

$id_pergunta = $_POST['id'];


echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela where pergunta = :pergunta ORDER BY id asc");
$query->execute([':pergunta' => $id_pergunta]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
echo <<<HTML
	<small><table class="table table-hover">
	<thead> 
	<tr> 	
	<th class="">Pergunta</th> 	
	<th class="">Correta</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$resposta = $res[$i]['resposta'];	
	$correta = $res[$i]['correta'];	

	if($correta == 'Sim'){
		$icone_correta = 'fa-check';
		$cor_correta = 'verde';
	}else{
		$icone_correta = 'fa-times';
		$cor_correta = 'text-danger';
	}

	$numeracao = $i + 1;

	if($numeracao == 1){
		$numeracao = 'A';
	}else if($numeracao == 2){
		$numeracao = 'B';
	}else if($numeracao == 3){
		$numeracao = 'C';
	}else if($numeracao == 4){
		$numeracao = 'D';
	}else if($numeracao == 5){
		$numeracao = 'E';
	}else if($numeracao == 6){
		$numeracao = 'F';
	}else if($numeracao == 7){
		$numeracao = 'G';
	}
	
	
echo <<<HTML
<tr> 				
		<td class="">{$numeracao} - {$resposta}</td>	
		<td class=""><i class="fa {$icone_correta} {$cor_correta}"></i></td>	

		<td>
		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluirAlt('{$id}', '{$id_pergunta}')"><span class="text-danger">Sim</span></a></p>
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
	echo 'Não possui nenhuma alternativa cadastrada!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

	$(document).ready( function () {
		
	} );
	
	

function excluirAlt(id, pergunta){
    $.ajax({
        url: 'paginas/' + pag + "/excluir-alt.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
                listarAlt(pergunta);             
            } else {
                    $('#mensagem-excluir-quest').addClass('text-danger')
                    $('#mensagem-excluir-quest').text(mensagem)
                }

        },      

    });
}





</script>
