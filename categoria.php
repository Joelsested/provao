<?php require_once("cabecalho.php"); 
$url = $_GET['url']; 
$query = $pdo->prepare("SELECT * FROM categorias where nome_url = :url"); 
$query->execute([':url' => $url]);
$res = $query->fetchAll(PDO::FETCH_ASSOC); 
$id_cat = $res[0]['id']; 
$nome_cat = $res[0]['nome'];

$query = $pdo->prepare("SELECT * FROM cursos where categoria = :categoria ORDER BY id desc"); 
$query->execute([':categoria' => $id_cat]);
$res = $query->fetchAll(PDO::FETCH_ASSOC); 
$total_reg = @count($res); 
if($total_reg > 0){ ?>  

<br> 
<hr>
<div class="section-heading text-center">
    <div class="col-md-12 col-xs-12">
        <h3><small>Cursos da Categoria <span><?php echo $nome_cat ?></span></small></h3>
    </div>
</div>
<hr>

<div class="container-fluid">
    <div class="row2">
        <?php for($i=0; $i < $total_reg; $i++){
            foreach ($res[$i] as $key => $value){}
            $id = $res[$i]['id'];
            $nome = $res[$i]['nome'];
            $desc_rapida = $res[$i]['desc_rapida'];
            $valor = $res[$i]['valor'];
            $foto = $res[$i]['imagem'];
            $promocao = $res[$i]['promocao'];
            $url = $res[$i]['nome_url'];
            
            $valorF = number_format($valor, 2, ',', '.');
            $promocaoF = number_format($promocao, 2, ',', '.');
            
            $query2 = $pdo->prepare("SELECT * FROM aulas where curso = :curso and num_aula = 1 order by id asc");
            $query2->execute([':curso' => $id]);
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $total_reg2 = @count($res2);
            if($total_reg2 > 0){
                $primeira_aula = $res2[0]['link'];
            } else {
                $primeira_aula = "";
            }
        ?>
        
        <div class="col-md-2 col-sm-6 col-xs-6">
            <div class="product-card">
                <div class="product-tumb">
                    <a href="curso-de-<?php echo $url ?>"><img src="sistema/painel-admin/img/cursos/<?php echo $foto ?>" alt="" width="100%"></a>
                </div>
                <div class="product-details">
                    <h4><a href="curso-de-<?php echo $url ?>"><?php echo $nome ?></a></h4>
                    <p><?php echo $desc_rapida ?></p>
                    
                    <?php if($promocao > 0){ ?>
                        <div class="product-bottom-details">
                            <div class="product-price-menor"><small><?php echo $valorF ?></small> R$ <?php echo $promocaoF ?></div>
                        </div>
                    <?php } else { ?>
                        <div class="product-bottom-details">
                            <div class="product-price-menor">R$ <?php echo $valorF ?></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <?php } ?>
    </div>
</div>

<br><hr><br><br>

<?php } else {
    echo '<br><br><p align="center">Nenhum curso cadastrado para essa categoria!</p>';
} ?>

<?php require_once("rodape.php"); ?>
