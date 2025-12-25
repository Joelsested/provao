<?php 
$id = $_GET['id'];
include('../conexao.php');

$query = $pdo->prepare("SELECT * from usuarios where id_pessoa = :id_pessoa order by id desc");
$query->execute([':id_pessoa' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$nome = $res[0]['nome'];
$id_aluno = $res[0]['id'];    

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Porto_Velho');
$data_hoje = utf8_encode(strftime('%A, %d de %B de %Y', strtotime('today')));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Relatório de Avaliações</title>    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">
    <style>
        @page {
            margin: 0px;
        }

        body {
            margin-top: 0px;
            font-family: Times, "Times New Roman", Georgia, serif;
        }        

        .footer {
            margin-top: 20px;
            width: 100%;
            background-color: #ebebeb;
            padding: 5px;
            position: absolute;
            bottom: 0;
        }

        .cabecalho {    
            padding: 10px;
            margin-bottom: 30px;
            width: 100%;
            font-family: Times, "Times New Roman", Georgia, serif;
        }

        .titulo_cab {
            color: #0340a3;
            font-size: 17px;
        }

        .titulo {
            margin: 0;
            font-size: 28px;
            font-family: Arial, Helvetica, sans-serif;
            color: #6e6d6d;
        }

        .subtitulo {
            margin: 0;
            font-size: 12px;
            font-family: Arial, Helvetica, sans-serif;
            color: #6e6d6d;
        }

        hr {
            margin: 8px;
            padding: 0px;
        }

        .area-cab {
            display: block;
            width: 100%;
            height: 10px;
        }

        .coluna {
            margin: 0px;
            float: left;
            height: auto;
            padding-right: 5px;
        }

        .area-tab {
            display: block;
            width: 100%;
            height: auto;
            clear: both;
            margin-bottom: 5px;
        }

        .imagem {
            width: 200px;
            position: absolute;
            right: 20px;
            top: 10px;
        }

        .titulo_img {
            position: absolute;
            margin-top: 10px;
            margin-left: 10px;
        }

        .data_img {
            position: absolute;
            margin-top: 40px;
            margin-left: 10px;
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .endereco {
            position: absolute;
            margin-top: 50px;
            margin-left: 10px;
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .verde {
            color: green;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #e3e3e3;
        }

        th, td {
            padding: 5px;
            text-align: left;
            font-size: 12px;
        }

        th {
            background-color: #f5f5f5;
        }
        
        .resposta-item {
            display: inline-block;
            margin-right: 8px;
            white-space: nowrap;
        }
    </style>
</head>
<body>    
    <div class="titulo_cab titulo_img"><u>Relatório de Avaliações <?php echo $nome ?></u></div>    
    <div class="data_img"><?php echo mb_strtoupper($data_hoje) ?></div>
    <img class="imagem" src="<?php echo $url_sistema ?>/sistema/img/logo_rel.jpg" width="200px" height="47">
    
    <br><br><br>
    <div class="cabecalho" style="border-bottom: solid 1px #0340a3"></div>

    <div class="mx-2" style="padding-top:10px">
        <br>
        <?php 
        $query = $pdo->query("SELECT M.*, C.nome as nome_curso 
                             FROM matriculas M 
                             INNER JOIN cursos C ON M.id_curso = C.id 
                             WHERE M.aluno = '$id_aluno' 
                             AND M.pacote != 'Sim'
                             ORDER BY C.nome ASC");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        $total_reg = count($res);
        
        if($total_reg > 0) {
        ?>
            <table>
                <thead>
                    <tr>
                        <th width="10%">NOTA</th>
                        <th width="30%">CURSO</th>
                        <th width="60%">PERGUNTAS / RESPOSTAS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach($res as $matricula) {
                        $nota = $matricula['nota'] !== '' ? $matricula['nota'] : 'sem Nota';
                        $curso = $matricula['id_curso'];
                        $nome_curso = $matricula['nome_curso'];
                    ?>
                    <tr>
                        <td><?php echo $nota; ?></td>
                        <td><?php echo $nome_curso; ?></td>
                        <td>
                            <?php 
                            $query9 = $pdo->query("SELECT * FROM perguntas_respostas 
                                                  WHERE id_curso = '$curso' AND id_aluno = '$id_aluno' 
                                                  ORDER BY numeracao ASC, id ASC");
                            $res9 = $query9->fetchAll(PDO::FETCH_ASSOC);
                            
                            if(count($res9) > 0) {
                                // Array para armazenar combinações únicas de numeração/letra
                                $respostas_unicas = [];
                                
                                foreach($res9 as $pergunta_resposta) {
                                    $numeracao = $pergunta_resposta['numeracao'];
                                    $letra = $pergunta_resposta['letra'];
                                    
                                    // Cria um identificador único para cada combinação
                                    $identificador = $numeracao . '/' . $letra;
                                    
                                    // Verifica se essa combinação já foi exibida
                                    if(!in_array($identificador, $respostas_unicas)) {
                                        // Adiciona ao array de combinações já exibidas
                                        $respostas_unicas[] = $identificador;
                                        
                                        // Exibe a resposta
                                        echo "<span class='resposta-item'>{$identificador}</span>";
                                    }
                                }
                            } else {
                                echo "<span>-</span>";
                            }
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else {
            echo '<div style="margin:8px"><small><small>Sem Registros no banco de dados!</small></small></div>';
        } ?>

        <div class="cabecalho mt-3" style="border-bottom: solid 1px #0340a3"></div>

        <div class="col-md-12 p-2">
            <div class="" align="right"></div>
        </div>
        <div class="cabecalho" style="border-bottom: solid 1px #0340a3"></div>

        <div class="footer" align="center">
            <span style="font-size:10px"><?php echo $nome_sistema ?> Whatsapp: <?php echo $tel_sistema ?></span> 
        </div>
    </div>
</body>
</html>
