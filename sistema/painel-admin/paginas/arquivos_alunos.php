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
    ano_certificado VARCHAR(4) NULL,
    data_certificado DATE NULL,
    numero_registro VARCHAR(30) NULL,
    folha_livro VARCHAR(20) NULL,
    numero_livro VARCHAR(20) NULL,
    criado_em DATETIME NOT NULL,
    criado_por INT NULL,
    ip VARCHAR(45) NULL,
    INDEX idx_aluno_tipo (aluno_id, tipo)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

try {
  $colunaVisivelExiste = false;
  $stmtColuna = $pdo->query("SHOW COLUMNS FROM documentos_emitidos LIKE 'visivel_aluno'");
  if ($stmtColuna && $stmtColuna->fetch(PDO::FETCH_ASSOC)) {
    $colunaVisivelExiste = true;
  }
  if (!$colunaVisivelExiste) {
    $pdo->exec("ALTER TABLE documentos_emitidos ADD COLUMN visivel_aluno TINYINT(1) NOT NULL DEFAULT 1");
  }
  $colunasExtras = [
    "ano_certificado" => "ALTER TABLE documentos_emitidos ADD COLUMN ano_certificado VARCHAR(4) NULL",
    "data_certificado" => "ALTER TABLE documentos_emitidos ADD COLUMN data_certificado DATE NULL",
    "numero_registro" => "ALTER TABLE documentos_emitidos ADD COLUMN numero_registro VARCHAR(30) NULL",
    "folha_livro" => "ALTER TABLE documentos_emitidos ADD COLUMN folha_livro VARCHAR(20) NULL",
    "numero_livro" => "ALTER TABLE documentos_emitidos ADD COLUMN numero_livro VARCHAR(20) NULL",
  ];
  foreach ($colunasExtras as $nomeColuna => $sqlAddColuna) {
    $stmtColunaExtra = $pdo->query("SHOW COLUMNS FROM documentos_emitidos LIKE " . $pdo->quote($nomeColuna));
    if (!$stmtColunaExtra || !$stmtColunaExtra->fetch(PDO::FETCH_ASSOC)) {
      $pdo->exec($sqlAddColuna);
    }
  }
} catch (Throwable $e) {
  // Nao interrompe a pagina por falha de estrutura.
}

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
    <th>Visivel Aluno</th>
    <th>Acoes</th>
   </tr>
  </thead>
  <tbody>
   <?php foreach ($documentos_emitidos as $doc): ?>
    <tr>
     <td><?php echo $doc['id']; ?></td>
     <td><?php echo htmlspecialchars($doc['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
     <td><?php echo htmlspecialchars($doc['categoria'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
     <td><?php echo $doc['versao'] ?? '-'; ?></td>
     <td>
      <?php
      $dataExibicaoDoc = '-';
      if (($doc['tipo'] ?? '') === 'certificado' && !empty($doc['data_certificado'])) {
        $dataExibicaoDoc = date('d/m/Y', strtotime($doc['data_certificado']));
      } elseif (!empty($doc['criado_em'])) {
        $dataExibicaoDoc = date('d/m/Y H:i', strtotime($doc['criado_em']));
      }
      echo $dataExibicaoDoc;
      ?>
     </td>
     <td><?php echo htmlspecialchars($doc['nome_usuario'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
     <td class="text-center">
      <?php $visivelAluno = isset($doc['visivel_aluno']) ? (int) $doc['visivel_aluno'] : 1; ?>
      <span class="label <?php echo $visivelAluno === 1 ? 'label-success' : 'label-default'; ?>">
       <?php echo $visivelAluno === 1 ? 'Sim' : 'Nao'; ?>
      </span>
      &nbsp;
      <a href="#" onclick="alternarVisibilidadeDocumento('<?php echo (int) $doc['id']; ?>', '<?php echo $visivelAluno; ?>'); return false;" title="Alternar visibilidade para o aluno">
       <i class="fa fa-exchange text-primary"></i>
      </a>
     </td>
     <td>
      <?php
      $arquivo_url = 'paginas/baixar_documento_emitido.php?id=' . (int) $doc['id'] . '&view=1';
      ?>
      <?php if (($doc['tipo'] ?? '') === 'certificado') { ?>
      <big>
       <a href="#" onclick="abrirModalEdicaoCertificado('<?php echo (int) $doc['id']; ?>'); return false;" title="Editar dados do certificado">
        <i class="fa fa-pencil text-warning"></i>
       </a>
      </big>
      &nbsp;
      <?php } ?>
      <big>
       <a href="<?php echo htmlspecialchars($arquivo_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Visualizar">
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
       <a href="#" onclick="apagarDocumentoEmitido('<?php echo (int) $doc['id']; ?>')" title="Excluir">
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
 const ID_ALUNO_ARQUIVOS = <?php echo (int) $id_pessoa; ?>;

 function apagarDocumentoEmitido(idDocumento) {
  if (!idDocumento) {
   return;
  }
  Swal.fire({
   title: "Tem certeza?",
   text: "O arquivo sera apagado permanentemente!",
   icon: "warning",
   showCancelButton: true,
   confirmButtonColor: "#d33",
   cancelButtonColor: "#3085d6",
   confirmButtonText: "Sim, apagar!"
  }).then((result) => {
   if (result.isConfirmed) {
    fetch("paginas/apagar_documento_emitido.php", {
     method: "POST",
     headers: { "Content-Type": "application/x-www-form-urlencoded" },
     body: "id=" + encodeURIComponent(idDocumento)
    })
     .then(res => res.json())
     .then(resp => {
      if (resp && resp.success) {
       Swal.fire("Removido!", resp.message || "Documento removido com sucesso.", "success").then(() => location.reload());
       return;
      }
      Swal.fire("Erro!", (resp && resp.message) ? resp.message : "Nao foi possivel apagar o documento.", "error");
     })
     .catch(() => {
      Swal.fire("Erro!", "Nao foi possivel apagar o arquivo.", "error");
     });
   }
  });
 }

 function alternarVisibilidadeDocumento(idDocumento, visivelAtual) {
  if (!idDocumento) {
   return;
  }

  fetch("paginas/alternar_visibilidade_documento.php", {
   method: "POST",
   headers: { "Content-Type": "application/x-www-form-urlencoded" },
   body: "id=" + encodeURIComponent(idDocumento) + "&visivel_atual=" + encodeURIComponent(visivelAtual)
  })
   .then(res => res.json())
   .then(resp => {
    if (resp && resp.success) {
     Swal.fire("Atualizado!", resp.message || "Visibilidade atualizada.", "success").then(() => location.reload());
     return;
    }
    Swal.fire("Erro!", (resp && resp.message) ? resp.message : "Nao foi possivel atualizar visibilidade.", "error");
   })
   .catch(() => {
    Swal.fire("Erro!", "Falha ao atualizar visibilidade.", "error");
   });
 }

 function abrirModalEdicaoCertificado(idDocumento) {
  if (!idDocumento) {
   return;
  }

  fetch("paginas/obter_dados_certificado_emitido.php?id=" + encodeURIComponent(idDocumento))
   .then(res => res.json())
   .then(resp => {
    if (!resp || !resp.success || !resp.dados) {
     Swal.fire("Erro!", (resp && resp.message) ? resp.message : "Nao foi possivel carregar os dados do certificado.", "error");
     return;
    }

    const dados = resp.dados;
    Swal.fire({
     title: "Editar Certificado",
     html: `
      <label for="ano_certificado_editar">Insira o ano da conclusao:</label>
      <br>
      <input type="number" id="ano_certificado_editar" class="swal2-input" style="width:34%; max-width:220px;" min="1900" max="2100" step="1" placeholder="Ex: 2025" value="${(dados.ano_certificado || '').toString()}">
      <br>
      <label for="data_certificado_editar">Selecione a data do certificado:</label>
      <input type="date" id="data_certificado_editar" class="swal2-input" style="width:34%; max-width:220px;" value="${(dados.data_certificado || '').toString()}">
      <label for="numero_registro_certificado_editar" style="display:block; width:34%; margin:8px auto 4px auto; text-align:left;">N&ordm; do Registro:</label>
      <input type="text" id="numero_registro_certificado_editar" class="swal2-input" style="width:34%; max-width:220px;" maxlength="30" placeholder="Ex: 125" value="${(dados.numero_registro || '').toString()}">
      <label for="folha_livro_certificado_editar" style="display:block; width:34%; margin:8px auto 4px auto; text-align:left;">Folha (FL):</label>
      <input type="text" id="folha_livro_certificado_editar" class="swal2-input" style="width:34%; max-width:220px;" maxlength="20" placeholder="Ex: 18" value="${(dados.folha_livro || '').toString()}">
      <label for="numero_livro_certificado_editar" style="display:block; width:34%; margin:8px auto 4px auto; text-align:left;">N&ordm; do Livro:</label>
      <input type="text" id="numero_livro_certificado_editar" class="swal2-input" style="width:34%; max-width:220px;" maxlength="20" placeholder="Ex: 03" value="${(dados.numero_livro || '').toString()}">
     `,
     showCancelButton: true,
     confirmButtonText: "Salvar Alteracoes",
     cancelButtonText: "Cancelar",
     preConfirm: () => {
      const ano = (document.getElementById("ano_certificado_editar").value || "").trim();
      const data = (document.getElementById("data_certificado_editar").value || "").trim();
      const numeroRegistro = (document.getElementById("numero_registro_certificado_editar").value || "").trim();
      const folhaLivro = (document.getElementById("folha_livro_certificado_editar").value || "").trim();
      const numeroLivro = (document.getElementById("numero_livro_certificado_editar").value || "").trim();

      if (!ano || ano.length !== 4) {
       Swal.showValidationMessage("Por favor, informe um ano valido com 4 digitos.");
       return false;
      }
      if (!data) {
       Swal.showValidationMessage("Por favor, selecione a data do certificado.");
       return false;
      }
      if (!numeroRegistro) {
       Swal.showValidationMessage("Por favor, informe o numero do registro.");
       return false;
      }
      if (!folhaLivro) {
       Swal.showValidationMessage("Por favor, informe a folha (FL).");
       return false;
      }
      if (!numeroLivro) {
       Swal.showValidationMessage("Por favor, informe o numero do livro.");
       return false;
      }
      return { ano, data, numeroRegistro, folhaLivro, numeroLivro };
     }
    }).then((result) => {
     if (!result.isConfirmed || !result.value) {
      return;
     }
     const payload = result.value;
     fetch("paginas/atualizar_dados_certificado_emitido.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body:
       "id_documento=" + encodeURIComponent(idDocumento) +
       "&aluno_id=" + encodeURIComponent(ID_ALUNO_ARQUIVOS) +
       "&ano_certificado=" + encodeURIComponent(payload.ano) +
       "&data_certificado=" + encodeURIComponent(payload.data) +
       "&numero_registro=" + encodeURIComponent(payload.numeroRegistro) +
       "&folha_livro=" + encodeURIComponent(payload.folhaLivro) +
       "&numero_livro=" + encodeURIComponent(payload.numeroLivro)
     })
      .then(res => res.json())
      .then(respSalvar => {
       if (respSalvar && respSalvar.success) {
        Swal.fire("Salvo!", respSalvar.message || "Dados atualizados com sucesso.", "success").then(() => location.reload());
        return;
       }
       Swal.fire("Erro!", (respSalvar && respSalvar.message) ? respSalvar.message : "Nao foi possivel salvar os dados.", "error");
      })
      .catch(() => {
       Swal.fire("Erro!", "Falha ao salvar os dados do certificado.", "error");
      });
    });
   })
   .catch(() => {
    Swal.fire("Erro!", "Nao foi possivel carregar os dados para edicao.", "error");
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

  if ($('#tabela_documentos').length) {
   $('#tabela_documentos').DataTable({
    "ordering": false,
    "stateSave": true,
   });
  }
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

