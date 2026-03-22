<?php 
require_once("../conexao.php");
require_once(__DIR__ . "/../../config/upload.php");

$tabela = 'arquivos_alunos';

function ini_size_to_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    switch ($unit) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
    }

    return (int) $number;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($_POST) &&
    empty($_FILES) &&
    !empty($_SERVER['CONTENT_LENGTH'])
) {
    $maxPostBytes = ini_size_to_bytes((string) ini_get('post_max_size'));
    if ($maxPostBytes > 0) {
        echo 'Arquivo maior que o permitido pelo servidor. Tamanho maximo permitido: ' . upload_format_size($maxPostBytes) . '.';
    } else {
        echo 'Arquivo maior que o permitido pelo servidor.';
    }
    exit();
}

$id_aluno = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$descricao = isset($_POST['descricao']) ? trim((string) $_POST['descricao']) : '';

if ($id_aluno <= 0) {
    echo 'Aluno nao identificado. Atualize a pagina e tente novamente.';
    exit();
}

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
