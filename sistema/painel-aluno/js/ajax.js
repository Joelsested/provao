$(document).ready(function() {
    listar();
} );

function listar(){
    $.ajax({
        url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        data: $('#form').serialize(),
        dataType: "html",

        success:function(result){
            $("#listar").html(result);
            $('#mensagem-excluir').text('');
        }
    });
}

function inserir(){
    $('#mensagem').text('');
    $('#tituloModal').text('Inserir Registro');
    $('#modalForm').modal('show');
    limparCampos();
}



$("#form").submit(function () {	
	event.preventDefault();
	var formData = new FormData(this);

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

            cache: false,
            contentType: false,
            processData: false,
            
        });

});





function excluir(id){
    var idMat = '';
    if (id && typeof id === 'object') {
        idMat = (id.dataset && id.dataset.id) ? id.dataset.id : '';
        if (!idMat && window.jQuery) {
            idMat = $(id).data('id') || '';
        }
    }
    if (!idMat) {
        idMat = String(id || '').trim();
    }
    if (!idMat || idMat.toLowerCase() === 'undefined' || idMat.toLowerCase() === 'null') {
        $('#mensagem-excluir').addClass('text-danger')
        $('#mensagem-excluir').text('Matricula invalida.')
        return;
    }
    idMat = idMat.replace(/[^0-9]/g, '');
    if (!idMat) {
        $('#mensagem-excluir').addClass('text-danger')
        $('#mensagem-excluir').text('Matricula invalida.')
        return;
    }
    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: { id: idMat, id_matricula: idMat, csrf_token: (window.CSRF_TOKEN || '') },
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

    });
}




function ativar(id, acao){
    $.ajax({
        url: 'paginas/' + pag + "/mudar-status.php",
        method: 'POST',
        data: {id, acao},
        dataType: "text",

        success: function (mensagem) {
            if (mensagem.trim() == "Alterado com Sucesso") {
                 listar();
            }else{
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem) 
            }               
        },

    });
}
