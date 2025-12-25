<?php 
require_once("../../../conexao.php");
$tabela = 'sessao';

$id_curso = $_POST['curso'];


echo '<select class="form-control sel2" name="sessao_aula" id="sessao_aula"  style="width:100%;">';							

$query = $pdo->prepare("SELECT * FROM sessao where curso = :curso order by id asc");
$query->execute([':curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){

	for($i=0; $i < @count($res); $i++){
		foreach ($res[$i] as $key => $value){}
		$id = $res[$i]['id'];
		$nome = $res[$i]['nome'];	
				
		echo "<option value='$id'>$nome</option>";

	 }

}else{
	echo '<option value="0">Nenhuma Sessão Criada</option>';
}

echo '</select>';

?>


<script type="text/javascript">
	$("#sessao_aula").change(function () {	
		listarAulas();
	});
</script>
