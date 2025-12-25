<?php 
@session_start();
if(@$_SESSION['nivel'] != 'Administrador' 
	and @$_SESSION['nivel'] != 'Professor' 
	and @$_SESSION['nivel'] != 'Secretario'  
	and @$_SESSION['nivel'] != 'Tesoureiro'
	and @$_SESSION['nivel'] != 'Tutor' 
	and @$_SESSION['nivel'] != 'Parceiro'
	and @$_SESSION['nivel'] != 'Assessor'
    and @$_SESSION['nivel'] != 'Vendedor'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}	
 ?>
