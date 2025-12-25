<?php
require_once('../conexao.php');
require_once('verificar.php');

@session_start();

$id_user = @$_SESSION['id'];
?>

<div class="bs-example widget-shadow" style="padding:15px" id="listar">

    <?php
    $query = $pdo->query("SELECT * FROM cursos ORDER BY id DESC");
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_reg = @count($res);

    if ($total_reg > 0) {
        echo <<<HTML
        <table class="table table-hover" id="tabela">
        <thead> 
        <tr> 
        <th>Nome</th>
        <th>Data</th>
        <th>Editar</th>
        </tr> 
        </thead> 
        <tbody>
        HTML;

        foreach ($res as $row) {
            $id = $row['id'];
            $nome = $row['nome'];
            $foto = $row['imagem'];
            $data_certificado = $row['data_certificado'];

            echo <<<HTML
            <tr> 
                <td>
                    <img src="img/cursos/{$foto}" width="27px" class="mr-2">
                    <a href="#" class="cinza_escuro">{$nome}</a>
                </td> 
                <td>{$data_certificado}</td>
                <td>
                    <big><a href="#" onclick="editar('{$id}', '{$nome}', '{$data_certificado}')" title="Editar Dados">
                        <i class="fa fa-edit text-primary"></i>
                    </a></big>
                </td>			
            </tr>
            HTML;
        }

        echo <<<HTML
        </tbody>
        <small><div align="center" id="mensagem-excluir"></div></small>
        </table>	
        HTML;
    } else {
        echo 'Não possui nenhum registro cadastrado!';
    }
    ?>
</div>

<!-- Modal INSERIR / EDITAR -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal"></h4>
                <button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="margin-top: -20px">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="form-niceEdit">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Data</label>
                                <input type="date" class="form-control" name="data" id="data" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    $('#tabela').DataTable({
        "ordering": false,
        "stateSave": true,
    });
    $('#tabela_filter label input').focus();
});

function editar(id, nome, data_certificado) {
    // Converter DD-MM-YYYY -> YYYY-MM-DD para exibir no input date
    if(data_certificado) {
        let partes = data_certificado.split('-'); // ["02","10","2025"]
        let dataInput = partes[2] + '-' + partes[1] + '-' + partes[0]; // "2025-10-02"
        $('#data').val(dataInput);
    } else {
        // Se não houver data, coloca a data atual
        let hoje = new Date();
        let dia = String(hoje.getDate()).padStart(2, '0');
        let mes = String(hoje.getMonth() + 1).padStart(2, '0');
        let ano = hoje.getFullYear();
        $('#data').val(`${ano}-${mes}-${dia}`);
    }

    $('#id').val(id);
    $('#tituloModal').text('Editar data de: ' + nome);
    $('#modalForm').modal('show');

    // Remove evento anterior para não duplicar
    $('#form-niceEdit').off('submit').on('submit', function (e) {
        e.preventDefault();

        // Pega a data do input (YYYY-MM-DD) e converte para DD-MM-YYYY
        let dataInput = $('#data').val();
        let partes = dataInput.split('-');
        let dataFormatada = partes[2] + '-' + partes[1] + '-' + partes[0];

        // Prepara os dados para enviar via AJAX
        let dados = $(this).serializeArray();
        dados = dados.map(item => item.name === 'data' ? {name: item.name, value: dataFormatada} : item);

        $.ajax({
            url: 'paginas/certificados/editar.php',
            method: 'POST',
            data: $.param(dados),
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Sucesso',
                        text: 'Data atualizada com sucesso!',
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: 'Ops',
                        text: 'Erro ao atualizar data, tente novamente!',
                        icon: 'error'
                    }).then(() => location.reload());
                }
            },
            error: function () {
                alert('Erro na requisição AJAX.');
            }
        });
    });
}
</script>
