<?php 

require_once("../../../conexao.php");
$tabela = 'arquivos_cursos';

$id = $_POST['id'];
echo '$id';
exit();

echo <<<HTML
HTML;
$query_m = $pdo->prepare("SELECT * FROM $tabela where curso = :curso ORDER BY id asc");
$query_m->execute([':curso' => $id]);
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
		

		<big><a class="" href="$url_sistema/sistema/painel-aluno/img/arquivos/$arquivo" target="_blank" ><i class="fa  fa-file-pdf-o" style="display: inline-block;" title="Arquivos do Curso"></i></a></big>



		

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
