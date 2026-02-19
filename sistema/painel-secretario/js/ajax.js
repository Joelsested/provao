$(document).ready(function() {
    listar();
} );

function getCsrfToken() {
    if (window.CSRF_TOKEN) {
        return window.CSRF_TOKEN;
    }
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute) {
        return meta.getAttribute('content') || '';
    }
    return '';
}

function handleAjaxError(xhr, messageSelector) {
    var status = xhr ? xhr.status : 0;
    var responseText = (xhr && xhr.responseText) ? xhr.responseText : '';
    if (status === 401 || status === 403) {
        if (responseText && responseText.toLowerCase().indexOf('csrf') !== -1) {
            window.location.reload();
            return;
        }
    }
    if (messageSelector) {
        $(messageSelector).addClass('text-danger');
        $(messageSelector).text(responseText || 'Erro na requisicao.');
    }
}

function ensureAlunoFields(formData) {
    if (typeof pag === 'undefined') {
        return;
    }
    if (pag !== 'alunos' && pag !== 'atendimentos_alunos' && pag !== 'atendimentos_novo') {
        return;
    }
    var fieldIds = [
        'id', 'nome', 'cpf', 'email', 'telefone', 'rg', 'orgao_expedidor',
        'expedicao', 'nascimento', 'cep', 'sexo', 'endereco', 'numero',
        'bairro', 'cidade', 'estado', 'mae', 'pai', 'naturalidade', 'responsavel_id'
    ];
    fieldIds.forEach(function (id) {
        var el = document.getElementById(id);
        if (!el || !el.name) {
            return;
        }
        if (!formData.has || !formData.has(el.name)) {
            formData.append(el.name, el.value || '');
        }
    });
}

function listar(){
    var data = $('#form').serialize();
    var csrfToken = getCsrfToken();
    if (csrfToken) {
        data = data ? (data + '&csrf_token=' + encodeURIComponent(csrfToken)) : ('csrf_token=' + encodeURIComponent(csrfToken));
    }
    $.ajax({
        url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        data: data,
        dataType: "html",

        success:function(result){
            $("#listar").html(result);
            $('#mensagem-excluir').text('');
        },
        error: function (xhr) {
            handleAjaxError(xhr, '#mensagem-excluir');
        }
    });
}

function inserir(){
    $('#mensagem').text('');
    $('#tituloModal').text('Inserir Registro');
    $('#modalForm').modal('show');
    limparCampos();
}



$("#form").submit(function (event) {	
	event.preventDefault();
	var formData = new FormData(this);
    var csrfToken = getCsrfToken();
    if (csrfToken && (!formData.has || !formData.has('csrf_token'))) {
        formData.append('csrf_token', csrfToken);
    }
    ensureAlunoFields(formData);

	$.ajax({
		url: 'paginas/' + pag + "/inserir.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {                    
                    $('#btn-fechar').click();
                    listar();
                } else {
                	$('#mensagem').addClass('text-danger')
                    $('#mensagem').text(mensagem)
                }

            },
            error: function (xhr) {
                handleAjaxError(xhr, '#mensagem');
            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});





function excluir(id){
    var idNum = parseInt(id, 10);
    if (!idNum) {
        idNum = id;
    }
    $.ajax({
        url: 'paginas/' + pag + "/excluir.php?id=" + encodeURIComponent(idNum),
        method: 'POST',
        data: { id: idNum, id_matricula: idNum, csrf_token: (window.CSRF_TOKEN || '') },
        dataType: "text",

        success: function (mensagem) {
            var texto = (mensagem || '').toLowerCase();
            if (texto.indexOf('sucesso') !== -1) {
                listar();
            } else {
                    $('#mensagem-excluir').addClass('text-danger')
                    $('#mensagem-excluir').text(mensagem)
                }
        },
        error: function (xhr) {
            handleAjaxError(xhr, '#mensagem-excluir');
        },

    });
}




function ativar(id, acao){
    $.ajax({
        url: 'paginas/' + pag + "/mudar-status.php",
        method: 'POST',
        data: {id, acao, csrf_token: getCsrfToken()},
        dataType: "text",

        success: function (mensagem) {
            if (mensagem.trim() == "Alterado com Sucesso") {
                 listar();
            }else{
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem) 
            }               
        },
        error: function (xhr) {
            handleAjaxError(xhr, '#mensagem-excluir');
        },

    });
}
