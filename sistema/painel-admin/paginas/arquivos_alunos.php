<?php

ob_start();



require_once('../conexao.php');

require_once('verificar.php');

$pag = 'arquivos_alunos';



if ($_SESSION['nivel'] != 'Administrador' && $_SESSION['nivel'] != 'Secretario' && $_SESSION['nivel'] != 'Vendedor' && $_SESSION['nivel'] != 'Tutor') {

 echo "<script>window.location='../index.php'</script>";

 exit();

}





@session_start();



// Se for uma requisição POST para bloquear/desbloquear arquivo

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {

 $id = intval($_POST["id"]);



 $stmt = $pdo->prepare("SELECT arquivo, bloqueado FROM arquivos_alunos WHERE id = :id");

 $stmt->execute(["id" => $id]);

 $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);



 if ($arquivo) {

     $novoStatus = $arquivo["bloqueado"] ? 0 : 1;



     $stmt = $pdo->prepare("UPDATE arquivos_alunos SET bloqueado = :bloqueado WHERE id = :id");

     if ($stmt->execute(["bloqueado" => $novoStatus, "id" => $id])) {

         ob_end_clean();

         echo json_encode(["success" => true, "bloqueado" => $novoStatus]);

         exit();

     }

 }



 // Resposta de erro, se algo falhar

 ob_end_clean();

 echo json_encode(["success" => false, "message" => "Erro ao atualizar status do arquivo."]);

 exit();

}









$email_aluno = @$_GET['usuario'];



$consulta_usuario = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$consulta_usuario->execute([':usuario' => $email_aluno]);
$resposta_consulta_usuario = $consulta_usuario->fetchAll(PDO::FETCH_ASSOC);







$id_pessoa = $resposta_consulta_usuario[0]['id_pessoa'];



$consulta_arquivos = $pdo->prepare("SELECT * FROM arquivos_alunos WHERE aluno = :aluno ORDER BY id DESC");
$consulta_arquivos->execute([':aluno' => $id_pessoa]);
$resposta_consulta = $consulta_arquivos->fetchAll(PDO::FETCH_ASSOC);

$pdo->exec("
  CREATE TABLE IF NOT EXISTS documentos_emitidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    categoria VARCHAR(30) NULL,
    versao INT NULL,
    arquivo_relativo VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL,
    criado_por INT NULL,
    ip VARCHAR(45) NULL,
    INDEX idx_aluno_tipo (aluno_id, tipo)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$documentos_emitidos = [];
if ($_SESSION['nivel'] === 'Administrador' || $_SESSION['nivel'] === 'Secretario') {
  $consulta_documentos = $pdo->prepare("
    SELECT d.*, u.nome AS nome_usuario
    FROM documentos_emitidos d
    LEFT JOIN usuarios u ON u.id = d.criado_por
    WHERE d.aluno_id = :aluno
    ORDER BY d.id DESC
  ");
  $consulta_documentos->execute([':aluno' => $id_pessoa]);
  $documentos_emitidos = $consulta_documentos->fetchAll(PDO::FETCH_ASSOC);
}







?>









<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px" id="listar">



 <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">

  <h1 style="margin:0;">Arquivos</h1>

  <a href="javascript:history.back()" class="btn btn-default">
   <i class="fa fa-arrow-left"></i> Voltar
  </a>

 </div>



 <br>

 <br>

 <br>









 <table class="table table-hover" id="tabela2">

  <thead>

   <tr>

    <th>#</th>

    <th>Nome</th>

    <th>Descrição</th>

    <th>Data</th>

    <th>Ações</th>

   </tr>

  </thead>

  <tbody>

   <?php foreach ($resposta_consulta as $registro): ?>

    <tr>

     <td><?php echo $registro['id']; ?></td>

     <td><?php echo $registro['arquivo']; ?></td>

     <td><?php echo $registro['descricao']; ?></td>

     <td><?php echo $registro['data']; ?></td>



     <td>

      <big>

       <a href="#" onclick="mostrarArquivo('<?php echo $registro['arquivo']; ?>', '<?php echo htmlspecialchars($registro['descricao'], ENT_QUOTES, 'UTF-8'); ?>')" title="Visualizar">

        <i class="fa fa-eye text-secondary"></i>

       </a>

      </big>







      <big>

       <a href="#" onclick="apagarArquivo('<?php echo $registro['id']; ?>', '<?php echo htmlspecialchars($registro['bloqueado'], ENT_QUOTES, 'UTF-8'); ?>')"

        title="<?php echo $registro['bloqueado'] ? 'Desbloquear' : 'Bloquear'; ?>">

        <i class="fa fa-lock <?php echo $registro['bloqueado'] ? 'text-danger' : 'text-primary'; ?>"></i>

       </a>

      </big>



    

     </td>



    </tr>

   <?php endforeach; ?>





  </tbody>



 </table>







</div>



<?php if ($_SESSION['nivel'] === 'Administrador' || $_SESSION['nivel'] === 'Secretario') { ?>
<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:20px">

 <div>
  <h1>Historicos e Certificados</h1>
 </div>

 <br>

 <table class="table table-hover" id="tabela_documentos">
  <thead>
   <tr>
    <th>#</th>
    <th>Tipo</th>
    <th>Categoria</th>
    <th>Versão</th>
    <th>Data</th>
    <th>Gerado por</th>
    <th>Ações</th>
   </tr>
  </thead>
  <tbody>
   <?php foreach ($documentos_emitidos as $doc): ?>
    <tr>
     <td><?php echo $doc['id']; ?></td>
     <td><?php echo htmlspecialchars($doc['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
     <td><?php echo htmlspecialchars($doc['categoria'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
     <td><?php echo $doc['versao'] ?? '-'; ?></td>
     <td><?php echo !empty($doc['criado_em']) ? date('d/m/Y H:i', strtotime($doc['criado_em'])) : '-'; ?></td>
     <td><?php echo htmlspecialchars($doc['nome_usuario'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
     <td>
      <?php
      $arquivo_rel = ltrim($doc['arquivo_relativo'], '/');
      $arquivo_url = $url_sistema . $arquivo_rel;
      ?>
      <big>
       <a href="<?php echo $arquivo_url; ?>" target="_blank" title="Visualizar">
        <i class="fa fa-eye text-secondary"></i>
       </a>
      </big>
      &nbsp;
      <big>
       <a href="paginas/baixar_documento_emitido.php?id=<?php echo (int) $doc['id']; ?>" title="Baixar PDF">
        <i class="fa fa-download text-primary"></i>
       </a>
      </big>
      &nbsp;
      <big>
       <a href="#" onclick="apagarDocumentoEmitido('<?php echo htmlspecialchars($arquivo_rel, ENT_QUOTES, 'UTF-8'); ?>')" title="Excluir">
        <i class="fa fa-trash text-danger"></i>
       </a>
      </big>
     </td>
    </tr>
   <?php endforeach; ?>
  </tbody>
 </table>

</div>
<?php } ?>


<script type="text/javascript">

 var pag = "<?= $pag ?>"

</script>

<?php if ($_SESSION['nivel'] === 'Administrador' || $_SESSION['nivel'] === 'Secretario') { ?>
<script type="text/javascript">
 function apagarDocumentoEmitido(caminhoRel) {
  if (!caminhoRel) {
   return;
  }
  Swal.fire({
   title: "Tem certeza?",
   text: "O arquivo será apagado permanentemente!",
   icon: "warning",
   showCancelButton: true,
   confirmButtonColor: "#d33",
   cancelButtonColor: "#3085d6",
   confirmButtonText: "Sim, apagar!"
  }).then((result) => {
   if (result.isConfirmed) {
    fetch("paginas/apagar_historico.php", {
     method: "POST",
     headers: { "Content-Type": "application/x-www-form-urlencoded" },
     body: "arquivo=" + encodeURIComponent(caminhoRel)
    })
     .then(res => res.text())
     .then(msg => {
      Swal.fire("Removido!", msg, "success").then(() => location.reload());
     })
     .catch(() => {
      Swal.fire("Erro!", "Não foi possível apagar o arquivo.", "error");
     });
   }
  });
 }
</script>
<?php } ?>
<script src="js/ajax.js"></script>





<script type="text/javascript">

 $(document).ready(function() {

  $('.sel2').select2({

   dropdownParent: $('#modalForm')

  });

 });

</script>



<script type="text/javascript">

 $(document).ready(function() {

  $('#tabela2').DataTable({

   "ordering": false,

   "stateSave": true,

  });

  $('#tabela_documentos').DataTable({
   "ordering": false,
   "stateSave": true,
  });
  $('#tabela_filter label input').focus();

 });

</script>





<script type="text/javascript">

 function mostrarArquivo(arquivo, descricao) {



  const caminhoArquivo = "/sistema/painel-aluno/img/arquivos/" + arquivo;

  const extensao = arquivo.split('.').pop().toLowerCase(); // Obtém a extensão do arquivo



  if (extensao === 'pdf') {

   // Exibir PDF em um iframe

   Swal.fire({

    title: 'Visualizar Arquivo',

    html: `<iframe src="${caminhoArquivo}" width="100%" height="400px" style="border: none;"></iframe>`,

    width: '80%',

    showCloseButton: true,

    showConfirmButton: false

   });

  } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extensao)) {

   // Exibir imagem diretamente

   Swal.fire({

    // title: descricao,

    text: descricao,

    imageUrl: caminhoArquivo,

    imageAlt: 'Imagem do Arquivo',

    imageWidth: 400,

    // imageHeight: 200,

    showCloseButton: true,

    showConfirmButton: false,

   });

  } else {

   Swal.fire({

    title: 'Erro',

    text: 'Formato de arquivo não suportado!',

    icon: 'error',

    confirmButtonText: 'OK'

   });

  }

 }













 function apagarArquivo(idArquivo, status) {



  const isBlocked = status === '1' ? 'Desbloquear' : 'Bloquear';

  const isBlockedSuccess = status === '1' ? 'Desbloqueado' : 'Bloqueado';



  Swal.fire({

   title: "Atenção",

   text: `${isBlocked} Arquivo`,

   icon: "warning",

   showCancelButton: true,

   confirmButtonColor: "#3085d6",

   cancelButtonColor: "#d33",

   confirmButtonText: `Sim, ${isBlocked}`

  }).then((result) => {

   if (result.isConfirmed) {

    fetch("", {

      method: "POST",

      headers: {

       "Content-Type": "application/x-www-form-urlencoded"

      },

      body: `id=${idArquivo}`

     })

     .then(data => {

      Swal.fire({

       title: "Sucesso!",

       text: `Arquivo ${isBlockedSuccess} com sucesso!`,

       icon: "success"

      }).then(() => {

       window.location.reload();

      });

     })



   }

  });

 }

</script>

