<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'secretarios';

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}
?>


<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus"
                                                                                      aria-hidden="true"></i> Novo
    Secretario
</button>


<div class="bs-example widget-shadow" style="padding:15px" id="listar">

</div>


<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal"></h4>
                <button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="margin-top: -20px">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="form">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Nome</label>
                                <input type="text" class="form-control" name="nome" id="nome" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Telefone</label>
                                <input type="text" class="form-control" name="telefone" id="telefone">
                            </div>
                        </div>


                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CPF</label>
                                <input type="text" class="form-control" name="cpf" id="cpf">
                            </div>
                        </div>


                    </div>


                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                        </div>


                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Endereço</label>
                                <input type="text" class="form-control" name="endereco" id="endereco"
                                       placeholder="Rua X Número 20 Bairro X">
                            </div>
                        </div>


                    </div>


                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cidade</label>
                                <input type="text" class="form-control" name="cidade" id="cidade">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <input type="text" class="form-control" name="estado" id="estado">
                            </div>
                        </div>


                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Sexo</label>
                                <input type="text" class="form-control" name="sexo" id="sexo">
                            </div>
                        </div>


                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Indentificador Banco</label>
                            <input type="text" class="form-control" name="wallet_id" id="wallet_id" required>
                        </div>
                    </div>


                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Foto</label>
                                <input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div id="divImg">
                                <img src="img/perfil/sem-perfil.jpg" width="100px" id="target">
                            </div>
                        </div>

                    </div>


                    <br>
                    <input type="hidden" name="id" id="id">
                    <small>
                        <div id="mensagem" align="center" class="mt-3"></div>
                    </small>

                </div>


                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>


            </form>

        </div>
    </div>
</div>


<!-- Modal Arquivos -->
<div class="modal fade" id="modalArquivos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal">Gestão de Arquivos - <span id="nome_arquivo"> </span></h4>
                <button id="btn-fechar-arquivos" type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="margin-top: -20px">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-arquivos" method="post">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Arquivo</label>
                                <input class="form-control" type="file" name="arquivo_conta"
                                       onChange="carregarImgArquivos();" id="arquivo_conta">
                            </div>
                        </div>
                        <div class="col-md-4" style="margin-top:-10px">
                            <div id="divImgArquivos">
                                <img src="images/arquivos/sem-foto.png" width="60px" id="target-arquivos">
                            </div>
                        </div>


                    </div>

                    <div class="row" style="margin-top:-40px">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="nome_arq" id="nome_arq"
                                   placeholder="Nome do Arquivo * " required>
                        </div>

                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Inserir</button>
                        </div>
                    </div>

                    <hr>

                    <small>
                        <div id="listar_arquivos"></div>
                    </small>

                    <br>
                    <small>
                        <div align="center" id="mensagem_arquivo"></div>
                    </small>

                    <input type="hidden" class="form-control" name="id_arquivo" id="id_arquivo">


                </div>
            </form>
        </div>
    </div>
</div>


<!-- ModalMostrar -->
<div class="modal fade" id="modalMostrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal"><span id="nome_mostrar"> </span></h4>
                <button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="margin-top: -20px">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">


                <div class="row" style="border-bottom: 1px solid #cac7c7;">
                    <div class="col-md-6">
                        <span><b>CPF: </b></span>
                        <span id="cpf_mostrar"></span>
                    </div>
                    <div class="col-md-6">
                        <span><b>Telefone: </b></span>
                        <span id="telefone_mostrar"></span>
                    </div>
                </div>


                <div class="row" style="border-bottom: 1px solid #cac7c7;">
                    <div class="col-md-8">
                        <span><b>Email: </b></span>
                        <span id="email_mostrar"></span>
                    </div>

                    <div class="col-md-4">
                        <span><b>Senha: </b></span>
                        <span id="senha_mostrar"></span>
                    </div>

                </div>


                <div class="row" style="border-bottom: 1px solid #cac7c7;">
                    <div class="col-md-12">
                        <span><b>Endereço: </b></span>
                        <span id="endereco_mostrar"></span>
                    </div>
                </div>


                <div class="row" style="border-bottom: 1px solid #cac7c7;">
                    <div class="col-md-6">
                        <span><b>Cidade: </b></span>
                        <span id="cidade_mostrar"></span>
                    </div>
                    <div class="col-md-6">
                        <span><b>Estado: </b></span>
                        <span id="estado_mostrar"></span>
                    </div>
                </div>


                <div class="row" style="border-bottom: 1px solid #cac7c7;">
                    <div class="col-md-6">
                        <span><b>Sexo: </b></span>
                        <span id="sexo_mostrar"></span>
                    </div>
                    <div class="col-md-6">
                        <span><b>Data Cadastro: </b></span>
                        <span id="data_mostrar"></span>
                    </div>
                </div>

                <div class="row" style="border-bottom: 1px solid #cac7c7;">
                    <div class="col-md-6">
                        <span><b>Cartões: </b></span>
                        <span id="cartao_mostrar"></span>
                    </div>
                    <div class="col-md-6">
                        <span><b>Ativo: </b></span>
                        <span id="ativo_mostrar"></span>
                    </div>
                </div>
                 <div class="row" style="border-bottom: 1px solid #cac7c7;">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Indentificador EFI:</label>
                        <input type="text" class="form-control" name="wallet_id" id="wallet_id" required>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-12" align="center">
                        <img width="200px" id="target_mostrar">
                    </div>
                </div>


            </div>


        </div>
    </div>
</div>


<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>


<script type="text/javascript">
    function carregarImg() {
        var target = document.getElementById('target');
        var file = document.querySelector("#foto").files[0];

        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>


<script type="text/javascript">
    function listarArquivos() {
        var id = $('#id_arquivo').val();

        $.ajax({
            url: 'paginas/' + pag + "/listar_arquivos.php",
            method: 'POST',
            data: {id},
            dataType: "html",

            success: function (result) {
                $("#listar_arquivos").html(result);

            }
        });

    }
</script>

