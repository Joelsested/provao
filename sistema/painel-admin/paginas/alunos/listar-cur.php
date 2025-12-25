<?php 
require_once("../../../conexao.php");
$tabela = 'arquivos_alunos';

$id = $_POST['id'];

$query_m = $pdo->prepare("SELECT * FROM usuarios where id_pessoa = :id_pessoa ORDER BY id asc");
$query_m->execute([':id_pessoa' => $id]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$id_aluno = $res_m[0]['id_pessoa'];


echo <<<HTML
HTML;
$query_m = $pdo->prepare("SELECT * FROM $tabela where aluno = :aluno ORDER BY id asc");
$query_m->execute([':aluno' => $id_aluno]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$total_reg_m = @count($res_m);
$ultima_aula = 1;
if($total_reg_m > 0){
	for($i_m=0; $i_m < $total_reg_m; $i_m++){
	foreach ($res_m[$i_m] as $key => $value){}
	
	$arquivo = @$res_m[$i_m]['arquivo'];
	$descricao = @$res_m[$i_m]['descricao'];	

	





echo <<<HTML

	<small><table class="table table-hover" id="tabela2">
	<thead> 
	<tr> 
	<th style="width:70%">Descriçao</th>
	
	

	<th style="width:30%">acoes</th>

	</tr> 
	</thead> 
	<tbody>
HTML;


echo <<<HTML
<tr> 
				
		<td class="">{$descricao}</td>
		
			
				
		<td>
		

		<big><a class="" href="$url_sistema/sistema/painel-aluno/img/arquivos/$arquivo" target="_blank"  title="Arquivos do Aluno">
	    	<small><span class="fa fa-file-pdf-o text-danger" ></span></small></i></a></big>




		

		</td>
</tr>

HTML;



echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir-aulas"></div></small>
</table>	
</small>
HTML;

}}else{
	echo '<small>Não possui nenhum Arquivo Salvo!</small>';
}
echo <<<HTML


HTML;

echo '<br>';



?>

