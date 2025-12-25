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





// Se for uma requisição POST para deletar arquivo

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

   // Excluir o arquivo físico, se existir

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

     <td><?php echo $registro['data']; ?></td>



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

  $('#tabela_filter label input').focus();

 });

</script>





<script type="text/javascript">

 function mostrarArquivo(arquivo, descricao) {



  const caminhoArquivo = "img/arquivos/" + arquivo;

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







 function apagarArquivo(idArquivo) {

  Swal.fire({

   title: "Deseja apagar o arquivo?",

   text: "Esta ação não poderá ser desfeita!",

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

       text: "O arquivo foi excluído.",

       icon: "success"

      }).then(() => {

       window.location.reload();

      });

     })



   }

  });

 }

</script>
