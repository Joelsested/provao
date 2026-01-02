<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'alunos';

@session_start();

$id_user = @$_SESSION['id'];
$nivel_session = $_SESSION['nivel'] ?? '';
$responsavelAutoLevels = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro'];
$precisaEscolherResponsavel = !in_array($nivel_session, $responsavelAutoLevels, true);
$responsaveis = [];
if ($precisaEscolherResponsavel) {
	$placeholders = implode(',', array_fill(0, count($responsavelAutoLevels), '?'));
	$stmtResponsaveis = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE nivel IN ($placeholders) AND ativo = 'Sim' ORDER BY nome");
	$stmtResponsaveis->execute($responsavelAutoLevels);
	$responsaveis = $stmtResponsaveis->fetchAll(PDO::FETCH_ASSOC);
}

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Tutor' and @$_SESSION['nivel'] != 'Parceiro' and @$_SESSION['nivel'] != 'Professor' and @$_SESSION['nivel'] != 'Vendedor') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}


?>

<style>
    .invalid-feedback {
    color: red !important;
    
}
</style>

<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Aluno</button>


<div class="bs-example widget-shadow" style="padding:15px" id="listar">

</div>





<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"></h4>
				<button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" id="form">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Nome do Aluno*</label>
					          <input type="text" class="form-control" name="nome" id="nome" required>

							</div>
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label>Cpf*</label>
							<input type="text" class="form-control" name="cpf" id="cpf"required>

							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								<label>Email*</label>
								<input type="email" class="form-control" name="email" id="email" required>
                               
                               </div>
						    </div>

								<div class="col-md-2">
							<div class="form-group">
								<label> Telefone:</label>
								<input type="text" class="form-control" name="telefone" id="telefone" required>

							

							</div>
						</div>

					</div>

					<?php if ($precisaEscolherResponsavel) { ?>
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Responsavel*</label>
									<select class="form-control" name="responsavel_id" id="responsavel_id" required>
										<option value="">Selecione</option>
										<?php foreach ($responsaveis as $responsavel) : ?>
											<option value="<?php echo (int) $responsavel['id']; ?>" <?php echo ((int) $responsavel['id'] === (int) $id_user) ? 'selected' : ''; ?>>
												<?php echo htmlspecialchars($responsavel['nome']); ?> (<?php echo htmlspecialchars($responsavel['nivel']); ?>)
											</option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</div>
					<?php } ?>

					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label>Documento:<small><small>( RG, CTPS, etc)</small></small></label>
								<input type="text" class="form-control" name="rg" id="rg">

							</div>
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label>Orgão Expedidor:</label>
								<input type="text" class="form-control" name="orgao_expedidor" id="orgao_expedidor">

							</div>
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label>Data de Expedicao:</label>
								<input type="text" class="form-control" name="expedicao" id="expedicao">

							</div>
						</div>

					
						<div class="col-md-2">
							<div class="form-group">
								<label>Data de Nascimento:</label>
								<input type="text" class="form-control" name="nascimento" id="nascimento" required>

							</div>
						</div>
                            
                            
                            <div class="col-md-2">
							<div class="form-group">
								<label>Cep:</label>
								<input type="text" class="form-control" name="cep" id="cep">

							</div>
					     </div>

							
												
								<div class="col-md-2">
							<div class="form-group">
								<label>Sexo:</label>
								<input type="text" class="form-control" name="sexo" id="sexo">
							</div>
						</div>
					</div>


					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label>Endereço:</label>
								<input type="text" class="form-control" name="endereco" id="endereco">

							</div>
						</div>

						
						<div class="col-md-2">
							<div class="form-group">
								<label>Numero:</label>
								<input type="text" class="form-control" name="numero" id="numero">

							</div>
						</div>

						
						<div class="col-md-2">
							<div class="form-group">
								<label>Bairro:</label>
								<input type="text" class="form-control" name="bairro" id="bairro">

							</div>
						</div>

						

						<div class="col-md-2">
							<div class="form-group">
								<label>Cidade:</label>
								<input type="text" class="form-control" name="cidade" id="cidade">

							</div>
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label>Estado:</label>
							<input type="text" class="form-control" name="estado" id="estado">

							</div>
						   </div>
					      </div>

					   <div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Nome da Mãe:</label>
								<input type="text" class="form-control" name="mae" id="mae">
							</div>
						   </div>

				

					
						<div class="col-md-4">
							<div class="form-group">
								<label>Nome do Pai:</label>
								<input type="text" class="form-control" name="pai" id="pai">

								</div>
						       </div>
                            
                            <div class="col-md-3">
							<div class="form-group">
								<label>Naturalidade:</label>
								<input type="text" class="form-control" name="naturalidade" id="naturalidade">

							</div>
						   </div>
                         </div>

                          
			
					     <div class="row">
					    <div class="col-md-4">
							<div class="form-group">
								<label>Foto do Aluno:</label>
								<input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
							</div>
						</div>
						<div class="col-md-2">
							<div id="divImg">
								<img src="img/perfil/sem-perfil.jpg" width="100px" id="target">
							</div>
						</div>

				

			
					<input type="hidden" name="id" id="id">
					<small>
						<div id="mensagem" align="center" class="mt-3"></div>
					</small>

				</div>


				<div class="modal-footer">
					<button id="saveAluno" type="submit" disabled class="btn btn-primary">Salvar</button>
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
				<button id="btn-fechar-arquivos" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form id="form-arquivos" method="post">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-8">
							<div class="form-group">
								<label>Arquivo</label>
								<input class="form-control" type="file" name="arquivo_conta" onChange="carregarImgArquivos();" id="arquivo_conta">
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
							<input type="text" class="form-control" name="nome_arq" id="nome_arq" placeholder="Nome do Arquivo * " required>
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
<div class="modal fade" id="modalMostrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"><span id="nome_mostrar"> </span></h4>
				<button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">



				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-3">
						<span><b>CPF: </b></span>
						<span id="cpf_mostrar"></span>
					</div>
					<div class="col-md-5">
						<span><b>Email: </b></span>
						<span id="email_mostrar"></span>
					</div>
					<div class="col-md-4">
						<span><b>RG </b></span>
						<span id="rg_mostrar"></span>

					</div>
				</div>
				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-3">
						<span><b>Expedicao: </b></span>
						<span id="expedicao_mostrar"></span>
					</div>


					<div class="col-md-4">
						<span><b>Telefone: </b></span>
						<span id="telefone_mostrar"></span>
					</div>


					<div class="col-md-3">
						<span><b>Cep: </b></span>
						<span id="cep_mostrar"></span>
					</div>
				</div>


				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-12">
						<span><b>Endereço: </b></span>
						<span id="endereco_mostrar"></span>
					</div>

				</div>


				<div class="row" style="border-bottom: 1px solid #cac7c7;">


					<div class="col-md-5">
						<span><b>Cidade: </b></span>
						<span id="cidade_mostrar"></span>
					</div>
					<div class="col-md-2">
						<span><b>Estado: </b></span>
						<span id="estado_mostrar"></span>
					</div>


					<div class="col-md-2">
						<span><b>sexo: </b></span>
						<span id="sexo_mostrar"></span>
					</div>
				</div>

				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-3">
						<span><b>Nascimento: </b></span>
						<span id="nascimento_mostrar"></span>
					</div>
					<div class="col-md-5">
						<span><b>Mae: </b></span>
						<span id="mae_mostrar"></span>
					</div>
				</div>
				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-6">
						<span><b>Pai: </b></span>
						<span id="pai_mostrar"></span>
					</div>
					<div class="col-md-4">
						<span><b>Naturalidade: </b></span>
						<span id="naturalidade_mostrar"></span>
					</div>
				</div>

				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-4">
						<span><b>Data Cadastro: </b></span>
						<span id="data_mostrar"></span>
					</div>



					<div class="col-md-3">
						<span><b>Ativo: </b></span>
						<span id="ativo_mostrar"></span>
					</div>

					<div class="col-md-3">
						<span><b>Senha: </b></span>
						<span id="senha_mostrar"></span>
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


<script type="text/javascript">
	function listarArquivos() {
		var id = $('#id_arquivo').val();

		$.ajax({
			url: 'paginas/' + pag + "/listar_arquivos.php",
			method: 'POST',
			data: {
				id
			},
			dataType: "html",

			success: function(result) {
				$("#listar_arquivos").html(result);

			}
		});

	}
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

// --- Lista dos campos obrigatórios ---
const camposObrigatorios = [
    'nome', 'cpf', 'email', 'telefone', 'nascimento'
];
if (document.getElementById('responsavel_id')) {
    camposObrigatorios.push('responsavel_id');
}

// --- Função para verificar se todos os campos estão preenchidos e válidos ---
function verificarCampos() {
    const botao = document.getElementById('saveAluno');
    const mensagem = document.getElementById('mensagem');
    let todosPreenchidos = true;

    for (let id of camposObrigatorios) {
        const campo = document.getElementById(id);
        if (!campo.value.trim()) {
            todosPreenchidos = false;
            break;
        }
    }

    // Verifica se CPF e Email são válidos
    const cpf = document.getElementById('cpf').value.trim();
    const email = document.getElementById('email').value.trim();
    const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    const cpfValido = validarCPF(cpf);

    // Se tudo estiver preenchido e válido, habilita o botão
    if (todosPreenchidos && cpfValido && emailValido) {
        botao.removeAttribute('disabled');
        mensagem.innerHTML = '';
    } else {
        botao.setAttribute('disabled', true);
    }
}

// --- Adiciona eventos para atualizar o estado do botão em tempo real ---
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

// --- Também chama a verificação inicial ---
verificarCampos();
</script>



<script>
	document.getElementById('cep').addEventListener('input', function() {
		let cep = this.value.replace(/\D/g, ''); // Remove caracteres não numéricos

	if (cep.length === 8) { // Verifica se o CEP tem 8 dígitos
		fetch(`https://viacep.com.br/ws/${cep}/json/`)
			.then(response => response.json())
			.then(data => {
				if (!data.erro) {
					document.getElementById('endereco').value = `${data.logradouro}, ${data.bairro}`;
					document.getElementById('cidade').value = data.localidade;
					document.getElementById('estado').value = data.uf;
				} else {
					alert("CEP não encontrado!");
				}
			})
			.catch(error => console.error('Erro ao buscar o CEP:', error));
    }
});
</script>
