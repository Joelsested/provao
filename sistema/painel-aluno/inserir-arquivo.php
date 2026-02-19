<?php 
require_once("../conexao.php");
require_once(__DIR__ . "/../../config/upload.php");

$tabela = 'arquivos_alunos';

$id_aluno = $_POST['id'];
$arquivo = @$_POST['arquivo_2'];
$descricao = @$_POST['descricao'];

$destDir = __DIR__ . '/img/arquivos';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'zip', 'rar'];
$allowedMime = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/zip',
    'application/x-zip-compressed',
    'application/x-rar-compressed',
    'application/vnd.rar',
];
$upload = upload_handle($_FILES['arquivo_2'] ?? [], $destDir, $allowedExt, $allowedMime, 10 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', false);
if (!$upload['ok']) {
    echo $upload['error'];
    exit();
}
$imagem = $upload['filename'];

$stmtInsert = $pdo->prepare("INSERT INTO arquivos_alunos SET aluno = :aluno, arquivo = :arquivo, data = curDate(), descricao = :descricao");
$stmtInsert->execute([
    ':aluno' => $id_aluno,
    ':arquivo' => $imagem,
    ':descricao' => $descricao,
]);


echo 'Salvo com Sucesso';

?>
