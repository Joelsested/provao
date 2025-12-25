<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'vendedores';

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}
?>

<style>
    .invalid-feedback {
    color: red !important;
    
}
</style>

<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Vendedor</button>


<div class="bs-example widget-shadow" style="padding:15px" id="listar">

</div>


<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nome</label>
                                <input type="text" class="form-control" name="nome" id="nome" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Telefone</label>
                                <input type="text" class="form-control" name="telefone" id="telefone">
                            </div>
                        </div>


                    </div>


                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CPF</label>
                                <input type="text" class="form-control" name="cpf" id="cpf">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Indentificador Banco</label>
                            <input type="text" class="form-control" name="wallet_id" id="wallet_id" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Comissao</label>
                            <input type="number" class="form-control" name="comissao" id="comissao">
                        </div>
                    </div>
                  

                    <div class="col-md-6 d-flex align-items-center" style="margin-top: 30px;">
                        <div class="form-group">
                            <input type="checkbox" name="professor" id="professor" class="mr-2">
                            <label for="professor">Professor</label>
                        </div>
                    </div>




                    <div class="row">


                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Foto do Usuario</label>
                                <input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
                            </div>
                        </div>
                        <div class="col-md-4">
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
                    <div class="col-md-7">
                        <span><b>Email: </b></span>
                        <span id="email_mostrar"></span>
                    </div>

                    <div class="col-md-5">
                        <span><b>Data Cadastro: </b></span>
                        <span id="data_mostrar"></span>
                    </div>

                </div>

                <div class="col-md-5" style="margin-top: 15px;">
                    <span><b>Comissao </b></span>
                    <span id="comissao_mostrar"></span>%
                </div>

                <div class="col-md-5" style="margin-top: 15px;">
                    <span><b>Professor </b></span>
                    <span id="professor_mostrar"></span>
                </div>


                <div class="col-md-12">
                    <div class="form-group">
                        <label>Indentificador Banco</label>
                        <input type="text" class="form-control" name="walletId" id="walletId" required>
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


<script type="text/javascript">
    var pag = "<?= $pag ?>"
</script>

<script src="js/ajax.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        $('#modalForm').on('shown.bs.modal', function() {
            let isProfessorValue = document.getElementById('professor').textContent;
            if (isProfessorValue === "true") {
                $('#professor').prop('checked', true);
            } else {
                $('#professor').prop('checked', false);
            }
        });
     
    });
</script>

<script>
// --- Função para validar CPF ---
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

    let soma = 0;
    for (let i = 0; i < 9; i++) soma += parseInt(cpf.charAt(i)) * (10 - i);
    let resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.charAt(9))) return false;

    soma = 0;
    for (let i = 0; i < 10; i++) soma += parseInt(cpf.charAt(i)) * (11 - i);
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    return resto === parseInt(cpf.charAt(10));
}

// --- Função para formatar CPF enquanto digita ---
function formatarCPF(input) {
    let valor = input.value.replace(/[^\d]/g, '');
    if (valor.length <= 11) {
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    input.value = valor;
}

// --- Campos obrigatórios do formulário ---
const camposObrigatorios = ['nome', 'cpf', 'email', 'wallet_id', 'comissao'];

// --- Função para verificar se todos os campos estão preenchidos e válidos ---
function verificarCampos() {
    const botao = document.querySelector('#modalForm button[type="submit"]');
    const mensagem = document.getElementById('mensagem');
    let todosPreenchidos = true;

    for (let id of camposObrigatorios) {
        const campo = document.getElementById(id);
        if (!campo.value.trim()) {
            todosPreenchidos = false;
            break;
        }
    }

    const cpf = document.getElementById('cpf').value.trim();
    const email = document.getElementById('email').value.trim();
    const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    const cpfValido = validarCPF(cpf);

    // Habilita o botão somente se tudo estiver válido
    if (todosPreenchidos && cpfValido && emailValido) {
        botao.removeAttribute('disabled');
        mensagem.innerHTML = '';
    } else {
        botao.setAttribute('disabled', true);
    }
}

// --- Eventos para atualizar o botão em tempo real ---
camposObrigatorios.forEach(id => {
    const campo = document.getElementById(id);
    if (campo) {
        campo.addEventListener('input', verificarCampos);
        campo.addEventListener('change', verificarCampos);
        campo.addEventListener('blur', verificarCampos);
    }
});

// --- Máscara e validação de CPF em tempo real ---
const inputCPF = document.getElementById('cpf');
inputCPF.addEventListener('input', function() {
    formatarCPF(this);
    verificarCampos();
});

inputCPF.addEventListener('blur', function() {
    const cpf = this.value;
    if (cpf && !validarCPF(cpf)) {
        this.classList.add('is-invalid');
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
            const erro = document.createElement('div');
            erro.className = 'invalid-feedback';
            erro.textContent = 'CPF inválido!';
            this.parentNode.appendChild(erro);
        }
    } else {
        this.classList.remove('is-invalid');
        const erro = this.parentNode.querySelector('.invalid-feedback');
        if (erro) erro.remove();
    }
    verificarCampos();
});

// --- Desabilita o botão ao abrir o modal ---
$('#modalForm').on('shown.bs.modal', function() {
    const botao = document.querySelector('#modalForm button[type="submit"]');
    botao.setAttribute('disabled', true);
    verificarCampos();
});
</script>



<script type="text/javascript">
    function carregarImg() {
        var target = document.getElementById('target');
        var file = document.querySelector("#foto").files[0];

        var reader = new FileReader();

        reader.onloadend = function() {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>