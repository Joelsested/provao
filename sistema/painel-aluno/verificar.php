<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/csrf.php';
csrf_start();
if(@$_SESSION['nivel'] != 'Aluno'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}	
 ?>
