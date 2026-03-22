<?php
require_once('../conexao.php');
require_once('verificar.php');

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Secretario') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}

function tabelaExiste(PDO $pdo, string $tabela): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tabela");
    $stmt->execute([':tabela' => $tabela]);
    return (bool) $stmt->fetchColumn();
}

function colunaExiste(PDO $pdo, string $tabela, string $coluna): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
    $stmt->execute([':coluna' => $coluna]);
    return (bool) $stmt->fetchColumn();
}

if (!tabelaExiste($pdo, 'comissoes')) {
    $pdo->exec("CREATE TABLE comissoes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nivel VARCHAR(60) NOT NULL,
        porcentagem DECIMAL(10,2) NOT NULL DEFAULT 0,
        recebeSempre TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if (!colunaExiste($pdo, 'comissoes', 'recebeSempre')) {
    $pdo->exec("ALTER TABLE comissoes ADD COLUMN recebeSempre TINYINT(1) NOT NULL DEFAULT 0");
}

if (!colunaExiste($pdo, 'comissoes', 'created_at')) {
    $pdo->exec("ALTER TABLE comissoes ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

$consultaNiveis = $pdo->query("SELECT DISTINCT nivel FROM usuarios WHERE nivel <> 'Aluno' ORDER BY nivel ASC");
$niveisUsuarios = $consultaNiveis->fetchAll(PDO::FETCH_COLUMN) ?: [];

$consultaNiveisComissao = $pdo->query("SELECT DISTINCT nivel FROM comissoes ORDER BY nivel ASC");
$niveisComissoes = $consultaNiveisComissao->fetchAll(PDO::FETCH_COLUMN) ?: [];

$niveis = array_values(array_unique(array_merge($niveisUsuarios, $niveisComissoes)));

$consultaComissoes = $pdo->query("SELECT id, nivel, porcentagem, recebeSempre FROM comissoes ORDER BY nivel ASC, id DESC");
$comissoes = $consultaComissoes->fetchAll(PDO::FETCH_ASSOC) ?: [];

$status = $_GET['status'] ?? '';
$mensagem = '';
$classeMensagem = 'alert-success';

if ($status === 'ok') {
    $mensagem = 'Operação realizada com sucesso.';
}
if ($status === 'duplicado') {
    $mensagem = 'Já existe uma configuração para esse nível.';
    $classeMensagem = 'alert-warning';
}
if ($status === 'invalido') {
    $mensagem = 'Dados inválidos. Confira os campos e tente novamente.';
    $classeMensagem = 'alert-danger';
}
?>

<div class="bs-example widget-shadow" style="padding:15px;">
    <div class="modal-header">
        <h4 class="modal-title">Adicionar comissão</h4>
    </div>

    <?php if ($mensagem !== '') { ?>
        <div class="alert <?php echo $classeMensagem ?>" style="margin-top: 15px; margin-bottom: 0;">
            <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php } ?>

    <form method="POST" action="paginas/comissoes_fixas/inserir.php" style="margin-top: 15px;">
        <div class="row">
            <div class="col-md-5">
                <div class="form-group">
                    <label for="nivel">Nível</label>
                    <select class="form-control" name="nivel" id="nivel" required>
                        <option value="">Selecione</option>
                        <?php foreach ($niveis as $nivel) { ?>
                            <option value="<?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8') ?>">
                                <?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="porcentagem">Porcentagem (%)</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="porcentagem" id="porcentagem" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="recebeSempre">Recebe pagamento fixo em todas as vendas?</label>
                    <select class="form-control" name="recebeSempre" id="recebeSempre" required>
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</div>

<div class="bs-example widget-shadow" style="padding:15px; margin-top: 15px;">
    <form method="POST" action="paginas/comissoes_fixas/alterar_registros.php" id="form-alterar-registros">
        <input type="hidden" name="acao" id="acao-comissao" value="editar">
        <input type="hidden" name="id_exclusao" id="id-exclusao-comissao" value="0">

        <table class="table table-hover" id="tabela-comissoes">
            <thead>
                <tr>
                    <th>Nível</th>
                    <th class="esc">Pagamento Fixo</th>
                    <th class="esc">Porcentagem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comissoes as $comissao) {
                    $id = (int) ($comissao['id'] ?? 0);
                    $nivel = (string) ($comissao['nivel'] ?? '');
                    $porcentagem = (string) ($comissao['porcentagem'] ?? '0');
                    $recebeSempre = (int) ($comissao['recebeSempre'] ?? 0);
                ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8') ?>
                            <input type="hidden" name="registros[<?php echo $id ?>][id]" value="<?php echo $id ?>">
                        </td>
                        <td class="esc" style="width: 200px;">
                            <select class="form-control" name="registros[<?php echo $id ?>][recebeSempre]">
                                <option value="1" <?php echo $recebeSempre === 1 ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?php echo $recebeSempre === 0 ? 'selected' : '' ?>>Não</option>
                            </select>
                        </td>
                        <td class="esc" style="width: 220px;">
                            <input class="form-control" type="number" step="0.01" min="0" name="registros[<?php echo $id ?>][porcentagem]" value="<?php echo htmlspecialchars($porcentagem, ENT_QUOTES, 'UTF-8') ?>">
                        </td>
                        <td>
                            <button type="submit" class="btn btn-danger" onclick="return excluirComissao(<?php echo $id ?>);">Excluir</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary" onclick="return salvarComissoes();">Salvar alterações</button>
    </form>
</div>

<script>
function excluirComissao(id) {
    if (!confirm('Confirma a exclusão dessa configuração?')) {
        return false;
    }
    document.getElementById('acao-comissao').value = 'excluir';
    document.getElementById('id-exclusao-comissao').value = String(id);
    return true;
}

function salvarComissoes() {
    document.getElementById('acao-comissao').value = 'editar';
    document.getElementById('id-exclusao-comissao').value = '0';
    return true;
}

if (window.jQuery && jQuery.fn && jQuery.fn.DataTable && document.getElementById('tabela-comissoes')) {
    jQuery(function () {
        jQuery('#tabela-comissoes').DataTable({
            ordering: false,
            stateSave: true
        });
    });
}
</script>
