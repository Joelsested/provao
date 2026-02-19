<?php 
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/csrf.php';
csrf_start();
@session_destroy();
echo "<script>
try {
  localStorage.removeItem('active_user_id');
  localStorage.removeItem('active_user_level');
  localStorage.removeItem('active_user_at');
  localStorage.removeItem('id_usu');
} catch (err) {}
window.location='index.php';
</script>";
 ?>
