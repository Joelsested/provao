<?php 
require_once("../../../conexao.php");
$tabela = 'aulas';

$id_curso = $_POST['id_curso'];
$sessao_sel = @$_POST['sessao_sel'];

echo <<<HTML
HTML;
$query_m = $pdo->prepare("SELECT * FROM sessao where curso = :curso ORDER BY id asc");
$query_m->execute([':curso' => $id_curso]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$total_reg_m = @count($res_m);
$ultima_aula = 1;
if($total_reg_m > 0){
	for($i_m=0; $i_m < $total_reg_m; $i_m++){
	foreach ($res_m[$i_m] as $key => $value){}
	$sessao = $res_m[$i_m]['id'];
	$nome_sessao = $res_m[$i_m]['nome'];


	//pegar o id da primeira sessao
	if($i_m == 0){
		$primeira_sessao = $res_m[$i_m]['id'];
	}

	echo '<small><b>' .$nome_sessao. '</small></b>';
	echo '<hr>';


	$query = $pdo->prepare("SELECT * FROM $tabela where curso = :curso and sessao = :sessao ORDER BY num_aula desc");
	$query->execute([
		':curso' => $id_curso,
		':sessao' => $sessao,
	]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){

if($sessao_sel == "undefined" || $sessao_sel == 0){
	$sessao_sel = $primeira_sessao;
}
$query2 = $pdo->prepare("SELECT * FROM $tabela where curso = :curso and sessao = :sessao ORDER BY num_aula desc");
$query2->execute([
	':curso' => $id_curso,
	':sessao' => $sessao_sel,
]);
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$ultima_aula = $res2[0]['num_aula'] + 1;
}else{
	$ultima_aula = 1;
}


echo <<<HTML
	<small>
	<small><table class="table table-hover" id="tabela2">
	<thead> 
	<tr> 
	<th>Aula</th>
	<th class="">Nome</th> 
	<th class="">arquivo</th>	
	<th class="esc">Link</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$nome = $res[$i]['nome'];	
	$num_aula = $res[$i]['num_aula'];	
	$link = $res[$i]['link'];
	$sessao = $res[$i]['sessao'];	
	$arquivo = $res[$i]['apostila'];
	
	$linkF = mb_strimwidth($link, 0, 15, "...");

	
	$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
if($ext == 'pdf' || $ext == 'PDF'){
	$tumb_arquivo = 'pdf.png';
}else if($ext == 'rar' || $ext == 'zip' || $ext == 'RAR' || $ext == 'ZIP'){
	$tumb_arquivo = 'rar.png';
}else if($ext == 'doc' || $ext == 'docx' || $ext == 'DOC' || $ext == 'DOCX'){
	$tumb_arquivo = 'word.png';
}else if($ext == 'xlsx' || $ext == 'xlsm' || $ext == 'xls'){
	$tumb_arquivo = 'excel.png';
}else if($ext == 'xml'){
	$tumb_arquivo = 'xml.png';
}else{
	$tumb_arquivo = $arquivo;
}


	
echo <<<HTML
<tr> 
		<td>		
		{$num_aula}	
		</td> 		
		<td class="">{$nome}</td>
			<td class="esc"><a href="img/arquivos/{$arquivo}" target="_blank"><img src="img/arquivos/{$tumb_arquivo}" width="25px"></a></td>
		<td class="esc"><a title="{$link}" href="{$link}" target="_blank">{$linkF}</a></td>		
				
		<td>
		<big><a href="#" onclick="editarAula('{$id}', '{$num_aula}', '{$nome}', '{$link}', '{$sessao}', '{$arquivo}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluirAula('{$id}')"><span class="text-danger">Sim</span></a></p>
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
<small><div align="center" id="mensagem-excluir-aulas"></div></small>
</table>	
</small>
HTML;

}else{
	echo '<small>Não possui nenhuma aula cadastrada!</small>';
}
echo <<<HTML
</small>
HTML;

echo '<br>';

}
	
}else{

	$query = $pdo->prepare("SELECT * FROM $tabela where curso = :curso ORDER BY num_aula desc");
	$query->execute([':curso' => $id_curso]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
$ultima_aula = 1;
if($total_reg > 0){
$ultima_aula = $res[0]['num_aula'] + 1;

echo <<<HTML
	<small><table class="table table-hover" id="tabela2">
	<thead> 
	<tr> 
	<th>Aula</th>
	<th class="">Nome</th> 	
	<th class="esc">Link</th>
	<th class="esc">Arquivo</th> 	 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$nome = $res[$i]['nome'];	
	$num_aula = $res[$i]['num_aula'];	
	$link = $res[$i]['link'];
	$sessao = $res[$i]['sessao'];	
	$arquivo = $res[$i]['apostila'];
	
	$linkF = mb_strimwidth($link, 0, 15, "...");

	$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
if($ext == 'pdf' || $ext == 'PDF'){
	$tumb_arquivo = 'pdf.png';
}else if($ext == 'rar' || $ext == 'zip' || $ext == 'RAR' || $ext == 'ZIP'){
	$tumb_arquivo = 'rar.png';
}else if($ext == 'doc' || $ext == 'docx' || $ext == 'DOC' || $ext == 'DOCX'){
	$tumb_arquivo = 'word.png';
}else if($ext == 'xlsx' || $ext == 'xlsm' || $ext == 'xls'){
	$tumb_arquivo = 'excel.png';
}else if($ext == 'xml'){
	$tumb_arquivo = 'xml.png';
}else{
	$tumb_arquivo = $arquivo;
}

	
	
echo <<<HTML
<tr> 
		<td>		
		{$num_aula}	
		</td> 		
		<td class="">{$nome}</td>
		<td class="esc"><a title="{$link}" href="{$link}" target="_blank">{$linkF}</a></td>		
		<td class="esc"><a href="img/arquivos/{$arquivo}" target="_blank"><img src="img/arquivos/{$tumb_arquivo}" width="25px"></a></td>
				
		<td>
		<big><a href="#" onclick="editarAula('{$id}', '{$num_aula}', '{$nome}', '{$link}', '{$sessao}', '{$arquivo}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>



		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluirAula('{$id}')"><span class="text-danger">Sim</span></a></p>
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
<small><div align="center" id="mensagem-excluir-aulas"></div></small>
</table>	
</small>
HTML;

}else{
	echo '<small>Não possui nenhuma aula cadastrada!<small>';
}
echo <<<HTML
</small>
HTML;

}


//totalizar aulas do curso
$query = $pdo->prepare("SELECT * FROM $tabela where curso = :curso");
$query->execute([':curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_aulas = @count($res);

?>


<script type="text/javascript">

	$(document).ready( function () {
		
	} );
	
	function editarAula(id, aula, nome, link, sessao, arquivo){



		$('#id-da-aula').val(id);
		$('#link_aula').val(link);
		$('#nome_aula').val(nome);
		$('#num_aula').val(aula);
		$('#sessao_aula').val(sessao);	
		$('#arquivo_2').val('');
		$('#target_2').attr('src','img/arquivos/' + arquivo);
			

			resultado = arquivo.split(".", 2);

		
			if(resultado[1] === 'pdf'){
				$('#target_2').attr('src', "img/arquivos/pdf.PNG");
				
			}else{
				$('#target_2').attr('src', "img/arquivos/sem-arquivo.PNG");
			}	
	}


	

	function limparCamposAulas(){
		$('#id-da-aula').val('');
		$('#link_aula').val('');
		$('#nome_aula').val('');
		$('#arquivo_2').val('');
		$('#target_2').val('');
		$('#num_aula').val('<?=$ultima_aula?>');
		$('#aulas_aula').text('<?=$total_aulas?>');
		$('#target-video').attr('src', '');
		
	}




function excluirAula(id){
    $.ajax({
        url: 'paginas/' + pag + "/excluir-aulas.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
                listarAulas();                
            } else {
                    $('#mensagem-excluir-aulas').addClass('text-danger')
                    $('#mensagem-excluir-aulas').text(mensagem)
                }

        },      

    });
}





</script>
