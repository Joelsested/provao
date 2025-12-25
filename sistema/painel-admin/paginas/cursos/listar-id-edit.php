
<?php 
require_once("../../../conexao.php");
$idei = @$_POST['idzinho'];
                            $id_al = @$_GET['id'];
                             $query = $pdo->prepare("SELECT * FROM arquivos_cursos where curso = :curso order by id desc");
                             $query->execute([':curso' => $idei]);
                             $res = $query->fetchAll(PDO::FETCH_ASSOC);

                for ($i=0; $i < count($res); $i++) { 
                  foreach ($res[$i] as $key => $value) {
                  }

                  $arquivo = $res[$i]['arquivo'];
                  $id_arq = $res[$i]['id'];
                  $descricao = $res[$i]['descricao'];
                  $data = $res[$i]['data'];
                  $data = implode('/', array_reverse(explode('-', $data)));

                  $ext = pathinfo($arquivo, PATHINFO_EXTENSION);   
        if($ext == 'pdf'){ 
            $tumb_arquivo = 'pdf.png';
        }else if($ext == 'rar' || $ext == 'zip'){
            $tumb_arquivo = 'rar.png';
        }else{
            $tumb_arquivo = $arquivo;
        }


                             ?>
                             
                             <a href="img/arquivos/<?php echo $arquivo ?>" target="_blank" title="Abrir Arquivo">
                             <span class="mr-2"> <?php echo $descricao ?>  </span>
                             <span class="mr-2"> Data: <?php echo $data ?>  </span></a>

                            <li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir_arq(<?php echo $id_arq ?>)"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>
                             <hr>

                         <?php } ?>

