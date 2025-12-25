<?php
require_once('../conexao.php');

// Consulta para obter os níveis dos usuários
$tabela_para_niveis = 'usuarios';
$consulta_niveis = $pdo->prepare("SELECT nivel FROM $tabela_para_niveis ORDER BY id DESC");
$consulta_niveis->execute();
$resposta_consulta_niveis = $consulta_niveis->fetchAll(PDO::FETCH_COLUMN, 0);

$niveis = array_unique($resposta_consulta_niveis);

// Verifica se 'Aluno' está presente no array $niveis
if (($key = array_search('Aluno', $niveis)) !== false) {
    unset($niveis[$key]);
}

$niveis = array_values($niveis);

// Consulta para obter as comissões
$tabela_para_comissoes = 'comissoes';
$consulta_comissoes = $pdo->prepare("SELECT * FROM $tabela_para_comissoes ORDER BY id DESC");
$consulta_comissoes->execute();
$comissoes = $consulta_comissoes->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="bs-example widget-shadow" style="padding:15px;">
    <div class="modal-header">
        <h4 class="modal-title" id="tituloModal">
            Adicionar comissão
        </h4>
    </div>
    <form method="POST" id="form" action="paginas/asaas_comissoes/inserir.php">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nivel">Nível</label>
                        <select class="form-control" name="nivel" id="nivel" required>
                            <?php
                            foreach ($niveis as $nivel) {
                                echo '<option value="' . htmlspecialchars($nivel) . '">' . htmlspecialchars($nivel) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="porcentagem">Porcentagem (%)</label>
                        <input type="number" class="form-control" name="porcentagem" id="porcentagem" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nivel">Recebe pagamentos fixos por todas as vendas?</label>
                        <select class="form-control" name="recebeSempre" id="recebeSempre" required>
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                </div>
            </div>

            <br>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</div>

<div class="bs-example widget-shadow" style="padding:15px;">
    <div id="listar">
        <form method="POST" id="form_alterar_registros" action="paginas/asaas_comissoes/alterar_registros.php">
            <table class="table table-hover" id="tabela">
                <thead>
                <tr>
                    <th class="esc">Nível</th>
                    <th class="esc">Pagamento fixo</th>
                    <th class="esc">Porcentagem</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <input name="acao" id="acao" value="editar" type="hidden"/>
                <input type="hidden" id="id_exclusao" name="id_exclusao" value="">
                <?php foreach ($comissoes as $comissao) : ?>
                    <tr id="<?= $comissao['id'] ?>">

                        <input type="hidden" name="registros[<?= $comissao['id'] ?>][id]"
                               value="<?= $comissao['id'] ?>">
                        <input type="hidden" name="registros[<?= $comissao['id'] ?>][recebeSempre]"
                               value="<?= $comissao['recebeSempre'] ?>">


                        <td class="esc"><?= $comissao['nivel'] ?></td>
                        <td class="esc"><?= $comissao['recebeSempre'] ? 'Sim' : 'Não' ?></td>
                        <td class="esc"><input class="form-control" type="text" placeholder="%"
                                               value="<?= $comissao['porcentagem'] ?>"
                                               name="registros[<?= $comissao['id'] ?>][porcentagem]"></td>
                        <td>
                            <button type="submit" class="btn btn-danger" name="excluir<?= $comissao['id'] ?>"
                                    onclick="definirTipoAcao('<?= $comissao['id'] ?>')">Excluir</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </form>

    </div>
</div>
<script>
    function definirTipoAcao(id) {
        document.getElementById('acao').value = 'excluir';
        document.getElementById('id_exclusao').value = id;
    }
</script>
