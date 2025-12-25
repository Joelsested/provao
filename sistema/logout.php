<?php 
@session_start();
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
