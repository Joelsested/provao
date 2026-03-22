<?php 

require_once("../../../conexao.php");
$tabela = 'arquivos_cursos';

$id = $_POST['id'];


echo <<<HTML
HTML;
$query_m = $pdo->prepare("SELECT * FROM {$tabela} WHERE curso = :curso ORDER BY id asc");
$query_m->execute(['curso' => $id]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$total_reg_m = @count($res_m);
$ultima_aula = 1;
if($total_reg_m > 0){
	for($i_m=0; $i_m < $total_reg_m; $i_m++){
	foreach ($res_m[$i_m] as $key => $value){}
	
	$arquivo = @$res_m[$i_m]['arquivo'];
	$descricao = @$res_m[$i_m]['descricao'];	
	$arquivo_url = rawurlencode((string) $arquivo);
	$urlAbrir = $url_sistema . '/sistema/painel-aluno/paginas/cursos/abrir-apostila.php?arquivo=' . $arquivo_url . '&download=0';
	$urlBaixar = $url_sistema . '/sistema/painel-aluno/paginas/cursos/abrir-apostila.php?arquivo=' . $arquivo_url . '&download=1';
	$urlAbrirJs = json_encode($urlAbrir, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$urlBaixarJs = json_encode($urlBaixar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$descricaoJs = json_encode((string) $descricao, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	





echo <<<HTML

	<small><table class="table table-hover" id="tabela2">
	<thead> 
	<tr> 
	<th style="width:70%">Descriçao</th>
	
	

	<th style="width:30%">Clique no ícone e baixe o arquivo.</th>

	</tr> 
	</thead> 
	<tbody>
HTML;


echo <<<HTML
<tr> 
				
		<td class="">{$descricao}</td>
		
			
				
		<td>
		

	<button type="button" class="btn btn-xs btn-primary" onclick='return abrirArquivoNoApp({$urlAbrirJs}, {$descricaoJs});' title="Abrir aqui no app"><i class="fa fa-eye" style="display: inline-block;"></i> Abrir</button>
	&nbsp;
	<button type="button" class="btn btn-xs btn-success" onclick='return baixarArquivoNoApp({$urlBaixarJs});' title="Baixar Gabarito"><i class="fa fa-download" style="display: inline-block;"></i> Baixar</button>



		

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

