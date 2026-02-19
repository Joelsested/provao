<?php 
@session_start();
if(@$_SESSION['nivel'] != 'Aluno'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}	
 ?>
