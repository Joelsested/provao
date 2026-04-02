<?php



ob_start();



require_once('../conexao.php');

require_once('verificar.php');

$pag = 'arquivos';



if (@$_SESSION['nivel'] != 'Aluno') {

 echo "<script>window.location='../index.php'</script>";

 exit();

}





@session_start();





// Se for uma requisiÃ§Ã£o POST para deletar arquivo

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {

 $id = intval($_POST["id"]);



 $stmt = $pdo->prepare("SELECT arquivo FROM arquivos_alunos WHERE id = :id");

 $stmt->execute(["id" => $id]);

 $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);



 if ($arquivo) {

  $caminhoArquivo = "img/arquivos/" . $arquivo["arquivo"];



  // Excluir o registro no banco de dados

  $stmt = $pdo->prepare("DELETE FROM arquivos_alunos WHERE id = :id");

  if ($stmt->execute(["id" => $id])) {

   // Excluir o arquivo fÃ­sico, se existir

   if (file_exists($caminhoArquivo)) {

    unlink($caminhoArquivo);

   }



   // Garante que nada foi enviado antes do JSON

   ob_end_clean();

   echo json_encode(["success" => true]);

   exit();

  }

 }



 // Resposta de erro, se algo falhar

 ob_end_clean();

 echo json_encode(["success" => false, "message" => "Erro ao excluir arquivo."]);

 exit();

}



$id_do_aluno = @$_SESSION['id'];



$consulta_usuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$consulta_usuario->execute([':id' => $id_do_aluno]);

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

try {
 $stmtColuna = $pdo->query("SHOW COLUMNS FROM documentos_emitidos LIKE 'visivel_aluno'");
 if (!$stmtColuna || !$stmtColuna->fetch(PDO::FETCH_ASSOC)) {
  $pdo->exec("ALTER TABLE documentos_emitidos ADD COLUMN visivel_aluno TINYINT(1) NOT NULL DEFAULT 1");
 }
} catch (Throwable $e) {
 // Nao interrompe a tela
}

$consulta_documentos_emitidos = $pdo->prepare("
 SELECT id, tipo, categoria, versao, arquivo_relativo, criado_em
 FROM documentos_emitidos
 WHERE aluno_id = :aluno
   AND COALESCE(visivel_aluno, 1) = 1
   AND tipo IN ('certificado', 'historico')
 ORDER BY id DESC
");
$consulta_documentos_emitidos->execute([':aluno' => $id_pessoa]);
$documentos_emitidos_aluno = $consulta_documentos_emitidos->fetchAll(PDO::FETCH_ASSOC);







?>









<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px" id="listar">



 <div>

  <h1>Meus Documentos</h1>

 </div>



 <br>

 <a href="" class="btn btn-primary btn-flat btn-pri" data-toggle="modal" data-target="#modalArquivos"><i class="fa fa-file"></i>Carregar Documento</a>

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

     <td>
      <?php
      $dataRegistro = $registro['data'] ?? '';
      if ($dataRegistro !== '') {
       $dt = DateTime::createFromFormat('Y-m-d', $dataRegistro);
       if ($dt instanceof DateTime) {
        $dataRegistro = $dt->format('d/m/Y');
       }
      }
      echo $dataRegistro;
      ?>
     </td>



     <td>

      <big>

       <a href="#" onclick="mostrarArquivo('<?php echo $registro['arquivo']; ?>', '<?php echo htmlspecialchars($registro['descricao'], ENT_QUOTES, 'UTF-8'); ?>')" title="Visualizar">

        <i class="fa fa-eye text-secondary"></i>

       </a>

      </big>



      <big>

       <?php if ($registro['bloqueado'] == 0): ?>

        <a href="#" onclick="apagarArquivo('<?php echo $registro['id']; ?>')" title="Apagar">

         <i class="fa fa-trash-o text-danger"></i>

        </a>

       <?php endif; ?>

      </big>

     </td>



    </tr>

   <?php endforeach; ?>





  </tbody>



 </table>







</div>

<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:20px">
 <div>
  <h3>Certificados e Historicos Emitidos</h3>
 </div>
 <br>
 <table class="table table-hover" id="tabela_documentos_emitidos">
  <thead>
   <tr>
    <th>#</th>
    <th>Tipo</th>
    <th>Categoria</th>
    <th>Versao</th>
    <th>Data</th>
    <th>Acoes</th>
   </tr>
  </thead>
  <tbody>
   <?php foreach ($documentos_emitidos_aluno as $docEmitido): ?>
    <?php
    $arquivoRel = ltrim((string)($docEmitido['arquivo_relativo'] ?? ''), '/');
    $arquivoUrl = 'paginas/baixar_documento_emitido.php?id=' . (int)($docEmitido['id'] ?? 0) . '&view=1';
    ?>
    <tr>
     <td><?php echo (int)($docEmitido['id'] ?? 0); ?></td>
     <td><?php echo htmlspecialchars((string)($docEmitido['tipo'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
     <td><?php echo htmlspecialchars((string)($docEmitido['categoria'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
     <td><?php echo htmlspecialchars((string)($docEmitido['versao'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
     <td><?php echo !empty($docEmitido['criado_em']) ? date('d/m/Y H:i', strtotime($docEmitido['criado_em'])) : '-'; ?></td>
     <td>
      <big>
       <a href="<?php echo htmlspecialchars($arquivoUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Visualizar">
        <i class="fa fa-eye text-secondary"></i>
       </a>
      </big>
      &nbsp;
      <big>
       <a href="paginas/baixar_documento_emitido.php?id=<?php echo (int)($docEmitido['id'] ?? 0); ?>" title="Baixar">
        <i class="fa fa-download text-primary"></i>
       </a>
      </big>
     </td>
    </tr>
   <?php endforeach; ?>
  </tbody>
 </table>
</div>



<script type="text/javascript">

 var pag = "<?= $pag ?>"

</script>

<?php $ajax_js_ver = @filemtime(__DIR__ . '/../js/ajax.js'); ?>
<script src="js/ajax.js?v=<?php echo $ajax_js_ver; ?>"></script>





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

  $('#tabela_documentos_emitidos').DataTable({
   "ordering": false,
   "stateSave": true,
  });

  $('#tabela_filter label input').focus();

 });

</script>





<script type="text/javascript">

 function mostrarArquivo(arquivo, descricao) {



  const caminhoArquivo = "img/arquivos/" + arquivo;

  const extensao = arquivo.split('.').pop().toLowerCase(); // ObtÃ©m a extensÃ£o do arquivo



  if (extensao === 'pdf') {

   // Exibir PDF em um iframe

   Swal.fire({

    title: 'Visualizar Arquivo',

    html: `<iframe src="${caminhoArquivo}" width="100%" style="border: none; height: 78vh; min-height: 520px;"></iframe>`,

    width: '90%',

    showCloseButton: true,

    showConfirmButton: false

   });

  } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extensao)) {

   // Exibir imagem diretamente

   Swal.fire({

    title: 'Visualizar Arquivo',

    html: `<div style="height: 78vh; min-height: 520px; display: flex; align-items: center; justify-content: center;"><img src="${caminhoArquivo}" alt="${descricao || 'Imagem do Arquivo'}" style="max-width: 100%; max-height: 74vh; object-fit: contain;"></div>`,

    showCloseButton: true,

    showConfirmButton: false,

   });

  } else {

   Swal.fire({

    title: 'Erro',

    text: 'Formato de arquivo nÃ£o suportado!',

    icon: 'error',

    confirmButtonText: 'OK'

   });

  }

 }







 function apagarArquivo(idArquivo) {

  Swal.fire({

   title: "Deseja apagar o arquivo?",

   text: "Esta aÃ§Ã£o nÃ£o poderÃ¡ ser desfeita!",

   icon: "warning",

   showCancelButton: true,

   confirmButtonColor: "#3085d6",

   cancelButtonColor: "#d33",

   confirmButtonText: "Sim, apagar!"

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

       title: "Apagado!",

       text: "O arquivo foi excluÃ­do.",

       icon: "success"

      }).then(() => {

       window.location.reload();

      });

     })



   }

  });

 }

</script>
