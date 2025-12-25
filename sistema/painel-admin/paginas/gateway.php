<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'gateway';
@session_start();
$id_usuario = $_SESSION['id'];

if (@$_SESSION['nivel'] != 'Administrador') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}

// Buscar todos os gateways cadastrados
$query = $pdo->query("SELECT * FROM gateways ORDER BY nome ASC");
$gateways = $query->fetchAll(PDO::FETCH_ASSOC);

// Buscar o gateway ativo
$query = $pdo->query("SELECT * FROM gateways WHERE ativo = 'Sim'");
$gateway_ativo = $query->fetch(PDO::FETCH_ASSOC);
?>


<style>
    .gateway_buttons {
        display: flex;
        flex-direction: row;
        width: 100%;
        justify-content: space-between;
    }

    .modal-custom {
        max-width: 80% !important;
        width: 80%;
        height: 70vh;
        margin: auto;
    }

    .modal-custom .modal-content {
        height: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .modal-custom .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h2 class="m-0 font-weight-bold text-primary">Configurações de Gateways de Pagamento</h2>
                    <br>

                    <div class="gateway_buttons">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#modalGateway">
                            <i class="fa fa-plus"></i> Novo Gateway
                        </button>

                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#modalGatewayWebhooks">
                            <i class="fa fa-database" style="margin-right: 4px;"></i> Gateway Webhooks Logs
                        </button>
                    </div>

                </div>
                <br>
                <div class="card-body">
                    <?php if (count($gateways) > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Status</th>
                                        <th>Chave API</th>
                                        <th>Chave Secreta</th>
                                        <th>Webhook URL</th>
                                        <th>Webhook PATH</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gateways as $gateway) { ?>
                                        <tr>
                                            <td><?php echo $gateway['nome']; ?></td>
                                            <td style="width: 50px;">
                                                <?php if ($gateway['ativo'] == 'Sim') { ?>
                                                    <span class="badge badge-success">Ativo</span>
                                                <?php } else { ?>
                                                    <span class="badge badge-secondary">Inativo</span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block">
                                                    <?php echo substr($gateway['chave_api'], 0, 10) . '...'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block">
                                                    <?php echo substr($gateway['chave_secreta'], 0, 10) . '...'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block">
                                                    <?php echo substr($gateway['webhook_url'], 0, 10) . '...'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block">
                                                    <?php echo $gateway['webhook_path']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($gateway['ativo'] != 'Sim') { ?>
                                                        <button type="button" class="btn btn-success btn-sm"
                                                            onclick="ativarGateway(<?php echo $gateway['id']; ?>)"
                                                            title="Ativar Gateway">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                    <?php } ?>
                                                    <button type="button" class="btn btn-primary btn-sm"
                                                        onclick="editarGateway(<?php echo $gateway['id']; ?>, '<?php echo $gateway['nome']; ?>', '<?php echo $gateway['chave_api']; ?>', '<?php echo $gateway['chave_secreta']; ?>', '<?php echo $gateway['webhook_url']; ?>')"
                                                        title="Editar Gateway">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="deletarGateway(<?php echo $gateway['id']; ?>)"
                                                        title="Excluir Gateway">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="alert alert-info">
                            Nenhum gateway de pagamento cadastrado. Clique em "Novo Gateway" para adicionar.
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h2 class="m-0 font-weight-bold text-primary">Gateway Ativo</h2>
                </div>
                <br>
                <div class="card-body">
                    <?php if ($gateway_ativo) { ?>
                        <div class="gateway-active-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4><?php echo $gateway_ativo['nome']; ?></h4>
                                    <p class="text-muted">Este gateway está configurado para processar todos os pagamentos
                                    </p>
                                </div>

                            </div>
                            <hr>
                            <div class="row">


                                <div class="col-md-12">
                                    <div class="gateway-urls">
                                        <p><strong>Webhook URL:</strong> <span
                                                class="text-success"><?php echo $gateway_ativo['webhook_url']; ?></span>
                                        </p>
                                    </div>
                                </div>

                                <br>
                                <br>

                                <div class="col-md-12">

                                    <div class="form-group">
                                        <label>Chave API</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" style="width: 600px;"
                                                value="<?php echo $gateway_ativo['chave_api']; ?>" id="chaveAPI" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="copiarTexto('chaveAPI')">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Chave Secreta</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" style="width: 600px;"
                                                value="<?php echo $gateway_ativo['chave_secreta']; ?>" id="chaveSecreta"
                                                readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="copiarTexto('chaveSecreta')">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="alert alert-warning">
                            Nenhum gateway de pagamento está ativo no momento. Selecione um gateway como ativo para
                            processar pagamentos.
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Gateway -->
<div class="modal fade" id="modalGateway" tabindex="-1" role="dialog" aria-labelledby="modalGatewayLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGatewayLabel">Novo Gateway de Pagamento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-gateway" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="nome">Nome do Gateway*</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="chave_api">Chave API*</label>
                                <input type="text" class="form-control" id="chave_api" name="chave_api" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="chave_secreta">Chave Secreta*</label>
                                <input type="text" class="form-control" id="chave_secreta" name="chave_secreta"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="webhook_url">URL de Webhook</label>
                                <input type="text" class="form-control" id="webhook_url" name="webhook_url"
                                    placeholder="https://seusite.com.br/webhooks/pagamento.php">
                                <small class="form-text text-muted">URL que receberá notificações de pagamento do
                                    gateway</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="webhook_path">Webhook PATH</label>
                                <input type="text" class="form-control" id="webhook_path" name="webhook_path"
                                    placeholder="/path/webhook.php">
                                <small class="form-text text-muted">Path para o arquivo de webhook.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ativar_gateway"
                                    name="ativar_gateway">
                                <label class="custom-control-label" for="ativar_gateway">Definir como Gateway
                                    Ativo</label>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="id-gateway" name="id-gateway">
                    <input type="hidden" name="acao" id="acao" value="inserir">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Gateway Webhooks -->
<div class="modal fade" id="modalGatewayWebhooks" tabindex="-1" role="dialog"
    aria-labelledby="modalGatewayWebhooksLabel" aria-hidden="true">
    <div class="modal-dialog modal-custom" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGatewayWebhooksLabel">Logs de Webhooks</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="table-responsive" style="max-height: calc(70vh - 120px); overflow-y: auto;">
                    <table class="table table-bordered table-hover" id="webhookTable">
                        <thead class="thead-light">
                            <tr>
                                <th>ID do Evento</th>
                                <th>Evento</th>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data de Criação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Os dados devem ser inseridos dinamicamente via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>



<script>
    const exampleWebhookData = [
        {
            id: "evt_05b708f961d739ea7eba7e4db318f621&859347873",
            event: "PAYMENT_CREATED",
            payment: {
                customer: "cus_000111206127",
                value: 5,
                status: "PENDING",
                dateCreated: "2025-02-25",
                description: "Parcela unitária do boleto gerado pelo aluno Gabriel Ralusi"
            },
            dateCreated: "2025-02-25 22:51:39"
        },
        {
            id: "evt_05b708f961d739ea7eba7e4db318f621&859347873",
            event: "PAYMENT_CREATED",
            payment: {
                customer: "cus_000111206127",
                value: 5,
                status: "PENDING",
                dateCreated: "2025-02-25",
                description: "Parcela unitária do boleto gerado pelo aluno Gabriel Ralusi"
            },
            dateCreated: "2025-02-25 22:51:39"
        },
        {
            id: "evt_05b708f961d739ea7eba7e4db318f621&859347873",
            event: "PAYMENT_CREATED",
            payment: {
                customer: "cus_000111206127",
                value: 5,
                status: "PENDING",
                dateCreated: "2025-02-25",
                description: "Parcela unitária do boleto gerado pelo aluno Gabriel Ralusi"
            },
            dateCreated: "2025-02-25 22:51:39"
        },
        {
            id: "evt_05b708f961d739ea7eba7e4db318f621&859347873",
            event: "PAYMENT_CREATED",
            payment: {
                customer: "cus_000111206127",
                value: 5,
                status: "PENDING",
                dateCreated: "2025-02-25",
                description: "Parcela unitária do boleto gerado pelo aluno Gabriel Ralusi"
            },
            dateCreated: "2025-02-25 22:51:39"
        },
        {
            id: "evt_05b708f961d739ea7eba7e4db318f621&859347873",
            event: "PAYMENT_CREATED",
            payment: {
                customer: "cus_000111206127",
                value: 5,
                status: "PENDING",
                dateCreated: "2025-02-25",
                description: "Parcela unitária do boleto gerado pelo aluno Gabriel Ralusi"
            },
            dateCreated: "2025-02-25 22:51:39"
        },
        {
            id: "evt_05b708f961d739ea7eba7e4db318f621&859347873",
            event: "PAYMENT_CREATED",
            payment: {
                customer: "cus_000111206127",
                value: 5,
                status: "PENDING",
                dateCreated: "2025-02-25",
                description: "Parcela unitária do boleto gerado pelo aluno Gabriel Ralusi"
            },
            dateCreated: "2025-02-25 22:51:39"
        },
        {
            id: "evt_05b708f961d739ea7eba7e4db318f621&859347873",
            event: "PAYMENT_CREATED",
            payment: {
                customer: "cus_000111206127",
                value: 5,
                status: "PENDING",
                dateCreated: "2025-02-25",
                description: "Parcela unitária do boleto gerado pelo aluno Gabriel Ralusi"
            },
            dateCreated: "2025-02-25 22:51:39"
        },

    ];

    function renderWebhookTable() {
        const tbody = document.querySelector("#webhookTable tbody");
        tbody.innerHTML = ""; // Limpa antes de renderizar

        exampleWebhookData.forEach(event => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${event.id}</td>
                <td>${event.event}</td>
                <td>${event.payment.customer}</td>
                <td>R$ ${event.payment.value.toFixed(2)}</td>
                <td>${event.payment.status}</td>
                <td>${event.dateCreated}</td>
                <td><button class="btn btn-sm btn-info" onclick="alert('Descrição: ${event.payment.description}')">Ver</button></td>
            `;
            tbody.appendChild(row);
        });
    }

    // Renderiza quando o modal for aberto
    $('#modalGatewayWebhooks').on('shown.bs.modal', renderWebhookTable);
</script>


<script type="text/javascript">
    $(document).ready(function () {
        $('#dataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
            }
        });

        $('#form-gateway').submit(function (e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: "paginas/gateway/salvar.php",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function (result) {
                    if (result.trim() === "Salvo com Sucesso") {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Gateway Salvo com Sucesso!',
                            showConfirmButton: false,
                            timer: 1500
                        });

                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Erro ao Salvar Gateway!',
                            text: result,
                            showConfirmButton: true
                        });
                    }
                }
            });
        });
    });

    function editarGateway(id, nome, chave_api, chave_secreta, webhook_url) {
        $('#id-gateway').val(id);
        $('#nome').val(nome);
        $('#chave_api').val(chave_api);
        $('#chave_secreta').val(chave_secreta);
        $('#webhook_url').val(webhook_url);
        $('#acao').val('editar');
        $('#modalGatewayLabel').html('Editar Gateway de Pagamento');
        $('#modalGateway').modal('show');
    }

    function ativarGateway(id) {
        Swal.fire({
            title: 'Confirma a ativação?',
            text: "Este gateway será definido como o único ativo para processar pagamentos!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, ativar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "paginas/gateway/ativar.php",
                    method: "POST",
                    data: { id: id },
                    success: function (result) {
                        if (result.trim() === "Ativado com Sucesso") {
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Gateway Ativado!',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            setTimeout(function () {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Erro ao Ativar Gateway!',
                                text: result,
                                showConfirmButton: true
                            });
                        }
                    }
                });
            }
        });
    }

    function deletarGateway(id) {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "paginas/gateway/excluir.php",
                    method: "POST",
                    data: { id: id },
                    success: function (result) {
                        if (result.trim() === "Excluído com Sucesso") {
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Gateway Excluído!',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            setTimeout(function () {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Erro ao Excluir Gateway!',
                                text: result,
                                showConfirmButton: true
                            });
                        }
                    }
                });
            }
        });
    }

    function copiarTexto(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        document.execCommand("copy");

        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: 'Texto copiado!',
            showConfirmButton: false,
            timer: 1000
        });
    }
</script>