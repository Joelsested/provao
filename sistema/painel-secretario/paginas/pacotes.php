<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'pacotes';

if(@$_SESSION['nivel'] != 'Aluno'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}
 ?>


<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px" id="listar">
	
</div>



<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>


<script type="text/javascript">
	$(document).ready(function() {
		$('.sel2').select2({
				dropdownParent: $('#modalForm')
		});
});
</script>


