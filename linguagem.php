<?php 
require_once("cabecalho.php");
$url = $_GET['url'];
$query = $pdo->prepare("SELECT * FROM linguagens where nome_url = :url");
$query->execute([':url' => $url]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_cat = $res[0]['id'];
$nome_cat = $res[0]['nome'];

$query = $pdo->prepare("SELECT * FROM pacotes where linguagem = :linguagem ORDER BY id desc");
$query->execute([':linguagem' => $id_cat]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
   ?>

<br>
<hr>
    <div class="section-heading text-center">
                <div class="col-md-12 col-xs-12">
                <h3><small>Pacotes da Linguagem <span><?php echo $nome_cat ?></span></small></h3>
             
            </div>
        </div>

        <hr>

    
                

      <div class="row" style="margin-left:10px; margin-right:10px; margin-top:-50px;">

                 <?php 
                    for($i=0; $i < $total_reg; $i++){
    foreach ($res[$i] as $key => $value){}
    $id = $res[$i]['id'];
                $nome = $res[$i]['nome'];
                $desc_rapida = $res[$i]['desc_rapida'];
                  $desc_rapida = mb_strimwidth($desc_rapida, 0, 20, "...");      
                  $nome = mb_strimwidth($nome, 0, 20, "...");       
                $valor = $res[$i]['valor'];     
                $foto = $res[$i]['imagem']; 
                $promocao = $res[$i]['promocao'];
                $url = $res[$i]['nome_url'];
                $primeira_aula = $res[$i]['video'];

    $valorF = number_format($valor, 2, ',', '.');    
                $promocaoF = number_format($promocao, 2, ',', '.'); 

                   

                  ?>

                <div class="col-md-2 col-sm-6 col-xs-6">    
                <div class="product-card">                  
                    <div class="product-tumb">
                        <a href="cursos-do-<?php echo $url ?>"><img src="sistema/painel-admin/img/pacotes/<?php echo $foto ?>" alt="" width="100%"></a>
                    </div>
                    <div class="product-details">                       
                        <h4><a href="cursos-do-<?php echo $url ?>"><?php echo $nome ?></a></h4>
                        <p><?php echo $desc_rapida ?></p>
                       
                         <?php if($promocao > 0){ ?>
                                <div class="product-bottom-details">
                                    <div class="product-price-menor"><small><?php echo $valorF ?></small>R$ <?php echo $promocaoF ?></div>
                                </div>
                            <?php }else{?>
                                <div class="product-bottom-details">
                                    <div class="product-price-menor">R$ <?php echo $valorF ?></div>
                                </div>
                            <?php } ?>
                       
                    </div>
                </div>
               </div>

           <?php } ?>

               

               </div>

          

        <?php }else{
        	echo '<br><br><p align="center">Nenhum pacote cadastrado para essa linguagem!</p>';
        } ?>



 <br><hr>

<br><br>

<?php 
require_once("rodape.php");
?>
