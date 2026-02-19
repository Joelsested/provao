<?php
// Ponte para a rota ?pagina=config sem 404:
// abre o modal de configuracoes quando a pagina terminar de carregar.
?>
<script>
window.addEventListener('load', function () {
  if (window.jQuery && $('#modalConfig').length) {
    $('#modalConfig').modal('show');
  }
});
</script>
