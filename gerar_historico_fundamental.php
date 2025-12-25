<?php
include('../conexao.php');

function removerAcentos($string)
{
    $mapa = [
        'á' => 'a',
        'à' => 'a',
        'ã' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ó' => 'o',
        'ò' => 'o',
        'õ' => 'o',
        'ô' => 'o',
        'ö' => 'o',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ç' => 'c',
        'Á' => 'A',
        'À' => 'A',
        'Ã' => 'A',
        'Â' => 'A',
        'Ä' => 'A',
        'É' => 'E',
        'È' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
        'Í' => 'I',
        'Ì' => 'I',
        'Î' => 'I',
        'Ï' => 'I',
        'Ó' => 'O',
        'Ò' => 'O',
        'Õ' => 'O',
        'Ô' => 'O',
        'Ö' => 'O',
        'Ú' => 'U',
        'Ù' => 'U',
        'Û' => 'U',
        'Ü' => 'U',
        'Ç' => 'C'
    ];
    return strtr($string, $mapa);
}

function formatarNomeCurso($nome)
{
    $nome = strtolower($nome);
    $nome = removerAcentos($nome);
    $nome = trim($nome);
    $nome = preg_replace('/[^a-z0-9]+/', '_', $nome);
    $nome = trim($nome, '_');
    return $nome;
}


// Verifica se foi passado um ID de aluno
$id_aluno = isset($_GET['id']) ? intval($_GET['id']) : null;
$dados_aluno = null;
$notas_existentes = [];

$r1 = intval($id_aluno);
$query = $pdo->prepare("SELECT * FROM usuarios WHERE id_pessoa = :id_pessoa");
$query->execute([':id_pessoa' => $r1]);
$res1 = $query->fetchAll(PDO::FETCH_ASSOC);

$aluno_error = false;


if (!$res1) {
    $aluno_error = true;
} else {
    $nome = $res1[0]['nome'];
    $aluno_id = $res1[0]['id_pessoa'];
    $usuario_id = $res1[0]['id'];

    $query2 = $pdo->prepare("SELECT M.*, C.nome as nome_curso 
                             FROM matriculas M 
                             INNER JOIN cursos C ON M.id_curso = C.id 
                             WHERE M.aluno = :aluno
                             AND M.pacote != 'Sim'
                             AND C.categoria = 7
                             ORDER BY C.nome ASC");
    $query2->execute([':aluno' => $usuario_id]);
    $res2 = $query2->fetchAll(mode: PDO::FETCH_ASSOC);
    $total_reg = count($res2);



    foreach ($res2 as &$linha) {
        $linha['nome_curso_original'] = $linha['nome_curso'];
        $linha['nome_curso'] = formatarNomeCurso($linha['nome_curso']);
    }

    $queryAluno = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
    $queryAluno->execute([':id' => $aluno_id]);
    $resAluno = $queryAluno->fetchAll(PDO::FETCH_ASSOC);
}


if (!$aluno_error) {
    include('../conexao.php');

    $dadosAluno = $resAluno[0];



    if (count($res) > 0) {
        $dados_aluno = $res[0];

        // Organizar notas por matéria
        foreach ($res2 as $matricula) {
            $curso_id = $matricula['id_curso'];
            $nome_curso = $matricula['nome_curso'];
            $nota = $matricula['nota'];
            $nome_curso_original = $matricula['nome_curso_original'];


            // Buscar respostas do aluno para o curso
            $query_respostas = $pdo->prepare("
            SELECT *
            FROM perguntas_respostas
            WHERE id_curso = :id_curso
              AND id_aluno = :id_aluno
            ORDER BY numeracao ASC, id ASC");

            $query_respostas->bindValue(":id_curso", $curso_id, PDO::PARAM_INT);
            $query_respostas->bindValue(":id_aluno", $usuario_id, PDO::PARAM_INT);
            $query_respostas->execute();

            $respostas = $query_respostas->fetchAll(PDO::FETCH_ASSOC);



            // Armazenar nota (assumindo que é uma nota geral - você pode adaptar conforme sua estrutura)
            $notas_existentes[$nome_curso] = [
                'nota' => $nota,
                'respostas' => $respostas,
                'nome_curso_original' => $nome_curso_original
            ];
        }
    }


}




?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Histórico Escolar</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

    <style>
        .container {
            /* max-width: 800px; */
            /* margin: 0 auto; */
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #1e7e34;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .swal-content__input {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }

        .grade-input {
            width: 60px;
            text-align: center;
        }

        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .subjects-table th,
        .subjects-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .subjects-table th {
            background-color: #f2f2f2;
        }

        .aluno-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .nota-existente {
            background-color: #e8f5e8;
        }

        .wide-swal {
            width: 80% !important;
            max-width: 900px !important;
        }

        .swal-modal {
            width: 80% !important;
            max-width: 900px !important;
        }

        /* Student Info Card */
        .student-card {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-gradient);
        }

        .student-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            font-weight: 700;
            box-shadow: var(--shadow-md);
        }

        .student-basic {
            flex: 1;
        }

        .student-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .student-id {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .student-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 0.5rem;
        }

        .detail-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-start;
            /* ou space-between se quiser espaçamento igual */
            background: linear-gradient(135deg, #f0f6fc 0%, #e1ecf7 100%);
            padding: 1.5rem;
            border-radius: var(--border-radius-sm);
            border-left: 4px solid var(--primary-color);
        }

        .detail-group h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }


        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* padding: 0.5rem 0; */
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 14px;
            margin-right: 1rem;
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-primary);
            text-align: right;
            /* max-width: 60%; */
            font-size: 14px;

        }

        .courses-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .courses-tag {

            background: linear-gradient(135deg, #055694 0%, #0a7bc4 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0.25rem;
        }

        /* History Section */
        .history-section {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .history-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .history-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .history-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
    </style>
</head>

<body>
    <div class="">

        <div style="display: flex; justify-content: space-between; padding: 20px;">
            <h1>Histórico Fundamental</h1>
            <button class="btn" onclick="abrirFormularioCompleto()">
                📝 Gerar Histórico Escolar
            </button>

        </div>

        <?php if ($dados_aluno): ?>
            <div class="aluno-info">

                <div class="detail-group">
                    <h2><?php echo htmlspecialchars($dadosAluno['nome']); ?></h2>
                </div>

                <div class="student-details">
                    <!-- Informações Pessoais -->
                    <div class="detail-group">
                        <h4>👤 Informações Pessoais</h4>
                        <div class="detail-item">
                            <span class="detail-label">CPF</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['cpf']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">RG</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['rg']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nascimento</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['nascimento']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Sexo</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['sexo']); ?></span>
                        </div>

                    </div>

                    <!-- Contato -->
                    <div class="detail-group">
                        <h4>📞 Contato</h4>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Telefone</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['telefone']); ?></span>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="detail-group">
                        <h4>🏠 Endereço</h4>
                        <div class="detail-item">
                            <span class="detail-label">Logradouro</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['endereco']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Bairro</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['bairro']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Cidade</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['cidade']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">CEP</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['cep']); ?></span>
                        </div>
                    </div>

                    <!-- Filiação -->
                    <div class="detail-group">
                        <h4>👨‍👩‍👧‍👦 Filiação</h4>
                        <div class="detail-item">
                            <span class="detail-label">Mãe</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['mae']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pai</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dadosAluno['pai']); ?></span>
                        </div>
                    </div>


                </div>
                <!-- Cursos -->
                <div style="margin-top: 1rem;">
                    <div style="display: flex; margin-top: 20px;">
                        <h3>📚 Cursos Matriculados</h3>
                    </div>
                    <div class="courses-list" style="margin-top: 1rem;">
                        <span class="courses-tag">Arte - Fundamental</span>
                        <span class="courses-tag">Ciências - Fundamental</span>
                        <span class="courses-tag">Educação Física - Fundamental</span>
                        <span class="courses-tag">Educação Religiosa - Fundamental</span>
                        <span class="courses-tag">Geografia - Fundamental</span>
                        <span class="courses-tag">História - Fundamental</span>
                        <span class="courses-tag">Língua Inglesa - Fundamental</span>
                        <span class="courses-tag">Língua Portuguesa - Fundamental</span>
                        <span class="courses-tag">Matemática Fundamental</span>
                    </div>
                </div>


                <div>
                    <div style="display: flex; margin-top: 40px;">
                        <h3>🎓 Historicos Gerados</h3>
                    </div>
                    <?php
                    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                    $dir = __DIR__ . "/../../../historicos/{$id}/fundamental/";

                    if (is_dir($dir)) {
                        $arquivos = glob($dir . "*.html");

                        if (!empty($arquivos)) {
                            echo '<table class="table table-hover">';
                            echo '<thead class="bg-gray-50"><tr>';
                            echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arquivo</th>';
                            echo '<th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>';
                            echo '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

                            foreach ($arquivos as $arquivo) {
                                $nomeArquivo = basename($arquivo);
                                $caminho = "../../../historicos/{$id}/fundamental/" . $nomeArquivo;

                                echo '<tr>';
                                echo "<td class='px-6 py-4 whitespace-nowrap'>{$nomeArquivo}</td>";
                                echo "<td class='px-6 py-4 text-center'>
                    <button onclick=\"abrirHistorico('{$caminho}')\" class='text-blue-500 hover:text-blue-700'>
                        🔍
                    </button>
                  </td>";
                                echo '</tr>';
                            }

                            echo '</tbody></table>';
                        } else {
                            echo "<p class='text-gray-500' style='margin-top: 20px;'>Nenhum arquivo  encontrado.</p>";
                        }
                    } else {
                        echo "<p class='text-red-500' style='margin-top: 20px; margin-left: 20px;'>Nenhum histórico encontrado.</p>";
                    }
                    ?>
                </div>
            </div>


        </div>

    <?php else: ?>
        <center>
            <h2>Ops! Algo deu errado.</h2>
            <p>Não foi possível carregar os dados do aluno.</p>
            <button class="btn" onclick="BuscarAlunoNovamente()">
                📋 Buscar novamente
            </button>
        </center>
    <?php endif; ?>


    <!-- <button class="btn btn-success" onclick="visualizarExemplo()" style="margin-left: 20px;">
            👁️ Visualizar Exemplo
        </button> -->



    <!-- Inclua SweetAlert2 no topo da página -->



    <script>
        function abrirHistorico(caminho) {
            fetch(caminho)
                .then(response => response.text())
                .then(html => {
                    Swal.fire({
                        title: 'Visualização do Histórico',
                        html: '<iframe src="' + caminho + '" width="100%" height="600px" style="border: none;"></iframe>',
                        width: '60%',

                        showCloseButton: true,
                        showConfirmButton: false,
                        scrollbarPadding: false,

                    });
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Não foi possível carregar o arquivo.'
                    });
                });
        }
    </script>


    <script>
        // Dados das matérias por área
        const materias = {
            'linguagens': [
                'Língua portuguesa',
                'Arte',
                'Língua inglesa',
                'Língua espanhola',
                'Educação física'
            ],
            'matematica': ['Matemática'],
            'ciencias': ['Química', 'Física', 'Biologia'],
            'humanas': ['História', 'Geografia'],
            'diversificada': [
                'Sociologia',
                'Filosofia',
                'História do Estado de Rondônia',
                'Geografia do Estado de Rondônia'
            ]
        };

        // Dados do aluno carregados do PHP
        let dadosAluno = <?php echo json_encode([

            'nome' => $dadosAluno['nome'] ?? '',
            'email' => $dadosAluno['email'] ?? '',
            'telefone' => $dadosAluno['telefone'] ?? '',
            'cpf' => $dadosAluno['cpf'] ?? '',
            'id_aluno' => $dadosAluno['id'] ?? null,
            'pai' => $dadosAluno['pai'] ?? 'N/A',
            'mae' => $dadosAluno['mae'] ?? 'N/A',
            'rg' => $dadosAluno['rg'] ?? 'N/A',
            'naturalidade' => $dadosAluno['naturalidade'] ?? 'N/A',
            'dataNasc' => $dadosAluno['nascimento'] ?? 'N/A',
            'sexo' => $dadosAluno['sexo'] ?? 'N/A',
            'categoria' => 'FUNDAMENTAL'
        ]); ?>;

        // Notas existentes do banco
        const notasExistentes = <?php echo json_encode($notas_existentes); ?>;


        const mapeamentoCursos = {

            // arte_medio, biologia_medio, educacao_fisica_medio, filosofia_medio, fisica_medio, geografia_medio, geografia_de_rondonia_medio, historia_medio, historia_de_rondonia_medio, lingua_espanhola_medio, lingua_inglesa_medio, lingua_portuguesa_medio, matematica_medio, quimica_medio, sociologia_medio
            // Cursos Fundamentais
            'lingua_portuguesa_medio': 'Língua portuguesa',
            'matematica_medio': 'Matemática',
            'arte_medio': 'Arte',
            'arte_fundamental': 'Arte',
            'lingua_inglesa_fundamental': 'Língua inglesa',
            'lingua_inglesa_medio': 'Língua inglesa',
            'educacao_fisica_medio': 'Educação física',
            'biologia_medio': 'Biologia',
            'geografia_medio': 'Geografia',
            "geografia_de_rondonia_medio": 'Geografia',
            'historia_medio': 'História',
            'filosofia_medio': 'Filosofia',
            'historia_de_rondonia_medio': 'História do Estado de Rondônia',
            'geografia_de_rondonia_medio': 'Geografia do Estado de Rondônia',
            'lingua_espanhola_medio': 'Língua espanhola',
            'quimica_medio': 'Química',
            'fisica_medio': 'Física',
            'sociologia_medio': 'Sociologia',
            'biologia_medio': 'Biologia',
            'educacao_fisica_medio': 'Educação física',



        };

        function abrirFormularioCompleto() {
            if (!dadosAluno.nome) {
                abrirFormularioAluno();
                return;
            }
            abrirFormularioNotas();
            ativarCheckboxes();
        }

        function abrirFormularioAluno() {
            swal({
                title: "Dados Pessoais do Aluno",
                text: "Insira os dados do aluno",
                buttons: {
                    cancel: "Cancelar",
                    confirm: {
                        text: "Próximo",
                        value: true
                    }
                }
            }).then((result) => {
                if (result) {
                    coletarDadosPessoais();
                }
            });
        }

        function coletarDadosPessoais() {
            dadosAluno = {
                ...dadosAluno,
                nome: document.getElementById('nome').value,
                sexo: document.getElementById('sexo').value,
                dataNasc: document.getElementById('dataNasc').value,
                naturalidade: document.getElementById('naturalidade').value,
                cpf: document.getElementById('cpf').value,
                rg: document.getElementById('rg').value,
                pai: document.getElementById('pai').value,
                mae: document.getElementById('mae').value
            };

            if (!dadosAluno.nome || !dadosAluno.sexo || !dadosAluno.dataNasc) {
                swal("Erro!", "Por favor, preencha todos os campos obrigatórios (Nome, Sexo, Data de Nascimento).", "error");
                return;
            }

            abrirFormularioNotas();
        }

        function obterNotaExistente(materia, serie) {
            // console.log(`Buscando nota para: ${materia} - Série: ${serie}`);

            // Buscar nota existente no banco através do mapeamento
            for (let nomeCurso in notasExistentes) {
                let materiaCorrespondente = mapeamentoCursos[nomeCurso];

                // console.log(`Curso: ${nomeCurso} -> Matéria: ${materiaCorrespondente}`);

                if (materiaCorrespondente === materia) {
                    const nota = notasExistentes[nomeCurso].nota;
                    // console.log(`Nota encontrada: ${nota} para ${materia}`);

                    // Por enquanto, aplicar a mesma nota para todas as séries
                    // Você pode adaptar esta lógica se tiver notas por série específica
                    return nota || '';
                }
            }

            // console.log(`Nenhuma nota encontrada para: ${materia}`);
            return '';
        }

        function abrirFormularioNotas() {
            let htmlNotas = '<div style="max-height: 400px; overflow-y: auto;">';

            // Debug: Mostrar dados disponíveis
            console.log('Notas existentes:', notasExistentes);
            console.log('Mapeamento de cursos:', mapeamentoCursos);
            const hoje = new Date();
            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0'); // mês começa em 0
            const ano = hoje.getFullYear();
            const dataFormatada = `${ano}-${mes}-${dia}`; // formato yyyy-mm-dd

            // Linguagens
            htmlNotas += '<h3>LINGUAGENS E TECNOLOGIAS</h3>';
            htmlNotas += '<table class="subjects-table">';
            htmlNotas += '<tr><th>Matéria</th><th>1ª Série</th><th>2ª Série</th><th>3ª Série</th><th style="width: 120px !important;">Data</th></tr>';
            materias.linguagens.forEach(materia => {
                const nota1 = obterNotaExistente(materia, '1');
                const nota2 = obterNotaExistente(materia, '2');
                const nota3 = obterNotaExistente(materia, '3');
                const classExistente = temNotaExistente(materia) ? 'nota-existente' : '';

                // Para cursos que existem no banco, preencher os 3 campos com a mesma nota
                const valorCampo1 = nota1 || (temNotaExistente(materia) ? obterNotaExistente(materia, '1') : '');
                const valorCampo2 = nota2 || (temNotaExistente(materia) ? obterNotaExistente(materia, '2') : '');
                const valorCampo3 = nota3 || (temNotaExistente(materia) ? obterNotaExistente(materia, '3') : '');


                htmlNotas += `<tr class="${classExistente}">
            <td style="display: flex; align-items: center; justify-content: space-between;">
            ${materia}
            <div>
            <label for="asterisco">*</label>
            <input type="checkbox" name="asterisco" id="${materia.replace(/\s+/g, '_')}_asterisco" placeholder="Asterisco">
            </div>
          

            </td>
            <td> 
            <input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_1" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo1}">
            </td>
            </td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_2" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo2}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_3" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo3}"></td>
           
           <td>
            ${!valorCampo1
                        ? `<input type="date" id="${materia.replace(/\s+/g, '_')}_date" name="${materia.replace(/\s+/g, '_')}_date">`
                        : `<input type="date" id="${materia.replace(/\s+/g, '_')}_date"  name="${materia.replace(/\s+/g, '_')}_date" value="${dataFormatada}">`
                    }
             </td>
           
            </tr>`;
            });
            htmlNotas += '</table>';

            // Matemática
            htmlNotas += '<h3>MATEMÁTICA</h3>';
            htmlNotas += '<table class="subjects-table">';
            htmlNotas += '<tr><th>Matéria</th><th>1ª Série</th><th>2ª Série</th><th>3ª Série</th><th style="width: 120px !important;">Data</th></tr>';
            materias.matematica.forEach(materia => {
                const classExistente = temNotaExistente(materia) ? 'nota-existente' : '';
                const valorCampo = temNotaExistente(materia) ? obterNotaExistente(materia, '1') : '';

                htmlNotas += `<tr class="${classExistente}">
             <td style="display: flex; align-items: center; justify-content: space-between;">
            ${materia}
            <div>
            <label for="asterisco">*</label>
            <input type="checkbox" name="asterisco" id="${materia.replace(/\s+/g, '_')}_asterisco" placeholder="Asterisco">
            </div>
            </td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_1" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_2" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_3" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td>
            ${!valorCampo
                        ? `<input type="date" id="${materia.replace(/\s+/g, '_')}_date" name="${materia.replace(/\s+/g, '_')}_date">`
                        : `<input type="date" id="${materia.replace(/\s+/g, '_')}_date"  name="${materia.replace(/\s+/g, '_')}_date" value="${dataFormatada}">`
                    }
             </td>
            </tr>`;
            });
            htmlNotas += '</table>';

            // Ciências da Natureza
            htmlNotas += '<h3>CIÊNCIAS DA NATUREZA</h3>';
            htmlNotas += '<table class="subjects-table">';
            htmlNotas += '<tr><th>Matéria</th><th>1ª Série</th><th>2ª Série</th><th>3ª Série</th><th style="width: 120px !important;">Data</th></tr>';
            materias.ciencias.forEach(materia => {
                const classExistente = temNotaExistente(materia) ? 'nota-existente' : '';
                const valorCampo = temNotaExistente(materia) ? obterNotaExistente(materia, '1') : '';

                htmlNotas += `<tr class="${classExistente}">
            <td style="display: flex; align-items: center; justify-content: space-between;">
            ${materia}
            <div>
            <label for="asterisco">*</label>
            <input type="checkbox" name="asterisco" id="${materia.replace(/\s+/g, '_')}_asterisco" placeholder="Asterisco">
            </div>
            </td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_1" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_2" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_3" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td>
            ${!valorCampo
                        ? `<input type="date" id="${materia.replace(/\s+/g, '_')}_date" name="${materia.replace(/\s+/g, '_')}_date">`
                        : `<input type="date" id="${materia.replace(/\s+/g, '_')}_date"  name="${materia.replace(/\s+/g, '_')}_date" value="${dataFormatada}">`
                    }
             </td>
            </tr>`;
            });
            htmlNotas += '</table>';

            // Ciências Humanas
            htmlNotas += '<h3>CIÊNCIAS HUMANAS</h3>';
            htmlNotas += '<table class="subjects-table">';
            htmlNotas += '<tr><th>Matéria</th><th>1ª Série</th><th>2ª Série</th><th>3ª Série</th><th style="width: 120px !important;">Data</th></tr>';
            materias.humanas.forEach(materia => {
                const classExistente = temNotaExistente(materia) ? 'nota-existente' : '';
                const valorCampo = temNotaExistente(materia) ? obterNotaExistente(materia, '1') : '';

                htmlNotas += `<tr class="${classExistente}">
            <td style="display: flex; align-items: center; justify-content: space-between;">
            ${materia}
            <div>
            <label for="asterisco">*</label>
            <input type="checkbox" name="asterisco" id="${materia.replace(/\s+/g, '_')}_asterisco" placeholder="Asterisco">
            </div>
            </td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_1" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_2" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_3" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td>
            ${!valorCampo
                        ? `<input type="date" id="${materia.replace(/\s+/g, '_')}_date" name="${materia.replace(/\s+/g, '_')}_date">`
                        : `<input type="date" id="${materia.replace(/\s+/g, '_')}_date"  name="${materia.replace(/\s+/g, '_')}_date" value="${dataFormatada}">`
                    }
             </td>
            </tr>`;
            });
            htmlNotas += '</table>';

            // Parte Diversificada
            htmlNotas += '<h3>PARTE DIVERSIFICADA</h3>';
            htmlNotas += '<table class="subjects-table">';
            htmlNotas += '<tr><th>Matéria</th><th>1ª Série</th><th>2ª Série</th><th>3ª Série</th><th style="width: 120px !important;">Data</th></tr>';
            materias.diversificada.forEach(materia => {
                const classExistente = temNotaExistente(materia) ? 'nota-existente' : '';
                const valorCampo = temNotaExistente(materia) ? obterNotaExistente(materia, '1') : '';

                htmlNotas += `<tr class="${classExistente}">
            <td style="display: flex; align-items: center; justify-content: space-between;">
            ${materia}
            <div>
            <label for="asterisco">*</label>
            <input type="checkbox" name="asterisco" id="${materia.replace(/\s+/g, '_')}_asterisco" placeholder="Asterisco">
            </div>
            </td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_1" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_2" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td><input type="number" class="grade-input" id="${materia.replace(/\s+/g, '_')}_3" min="0" max="100" step="0.1" placeholder="0.0" value="${valorCampo}"></td>
            <td>
            ${!valorCampo
                        ? `<input type="date" id="${materia.replace(/\s+/g, '_')}_date" name="${materia.replace(/\s+/g, '_')}_date">`
                        : `<input type="date" id="${materia.replace(/\s+/g, '_')}_date"  name="${materia.replace(/\s+/g, '_')}_date" value="${dataFormatada}">`
                    }
             </td>
            </tr>`;
            });
            htmlNotas += '</table>';

            // Adicionar informações sobre o preenchimento
            htmlNotas += `
        <div style="margin-top: 15px; padding: 10px; background-color: #f0f8ff; border-radius: 5px;">
            <p style="margin: 5px 0;"><small>💡 <strong>Legenda:</strong></small></p>
            <p style="margin: 5px 0;"><small>🟢 <strong>Verde:</strong> Campos preenchidos automaticamente com notas do sistema</small></p>
            <p style="margin: 5px 0;"><small>⚪ <strong>Branco:</strong> Campos para preenchimento manual</small></p>
            <p style="margin: 5px 0;"><small>📝 <strong>Nota:</strong> Você pode editar qualquer valor, mesmo os preenchidos automaticamente</small></p>
        </div>
    `;

            htmlNotas += '</div>';



            swal({
                title: "Notas do Histórico Escolar",
                content: {
                    element: "div",
                    attributes: {
                        innerHTML: htmlNotas
                    }
                },
                buttons: {
                    cancel: "Voltar",
                    confirm: {
                        text: "Gerar Histórico",
                        value: true
                    }
                },
                className: "wide-swal"
            }).then((result) => {
                if (result) {
                    coletarNotas();
                }
            });
        }

        function temNotaExistente(materia) {
            for (let nomeCurso in notasExistentes) {
                let materiaCorrespondente = mapeamentoCursos[nomeCurso];
                if (materiaCorrespondente === materia) {
                    return true;
                }
            }
            return false;
        }

        function coletarNotas() {
            const todasMaterias = [
                ...materias.linguagens,
                ...materias.matematica,
                ...materias.ciencias,
                ...materias.humanas,
                ...materias.diversificada
            ];

            const notasColetadas = {};

            todasMaterias.forEach(materia => {
                const materiaId = materia.replace(/\s+/g, '_');

                const addNota = (serie) => {
                    const input = document.getElementById(`${materiaId}_${serie}`);
                    const checkbox = document.getElementById(`${materiaId}_asterisco`);

                    let nota = input.value.trim() || '0.0';
                    if (checkbox && checkbox.checked) {
                        nota = '*' + nota;
                    }
                    return nota;
                };

                // Coletar o valor da data
                const dataInput = document.getElementById(`${materiaId}_date`);
                let data = dataInput ? dataInput.value : '';

                // Adicionar asterisco na data se o checkbox estiver marcado
                const checkbox = document.getElementById(`${materiaId}_asterisco`);
                if (checkbox && checkbox.checked && data) {
                    data = '*' + data;
                }

                notasColetadas[materia] = {
                    serie1: addNota(1),
                    serie2: addNota(2),
                    serie3: addNota(3),
                    data: data // adiciona o valor do input de data com asterisco se marcado
                };
            });

            // Passa para o próximo passo
            abrirFormularioAdicional(notasColetadas);
        }



        function abrirFormularioAdicional(notas) {
            console.log(notas);
            swal({
                title: "Informações Adicionais da Escola",
                content: {
                    element: "div",
                    attributes: {
                        innerHTML: `
                            <div class="form-group">
                                <label>Nome da Instituição de Ensino:</label>
                                <input type="text" id="escola" class="swal-content__input" placeholder="Ex: Escola Estadual Prof. João Silva" value="Centro de Estudos Supletivos">
                            </div>
                            <div class="form-group">
                                <label>Município/UF:</label>
                                <input type="text" id="municipio" class="swal-content__input" placeholder="Ex: Machadinho D'Oeste - RO" value="Machadinho D'Oeste - RO">
                            </div>
                            <div class="form-group">
                                <label>Ano de Conclusão:</label>
                                <input type="number" id="anoConclusao" class="swal-content__input" placeholder="Ex: 2024" min="1990" max="2030">
                            </div>
                            <div class="form-group">
                                <label>Carga Horária Total:</label>
                                <input type="number" id="cargaHoraria" class="swal-content__input" placeholder="Ex: 2400" value="2400">
                            </div>

                            <div class="form-group">
                                <label>Observações:</label>
                                <textarea id="observacoes" rows="4" class="swal-content__input" placeholder="Ex: Observações"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Situação:</label>
                                <select id="situacao" class="swal-content__input">
                                    <option value="APROVADO">APROVADO</option>
                                    <option value="REPROVADO">REPROVADO</option>
                                    <option value="TRANSFERIDO">TRANSFERIDO</option>
                                    <option value="CURSANDO">CURSANDO</option>
                                </select>
                            </div>
                        `
                    }
                },
                buttons: {
                    cancel: "Voltar",
                    confirm: {
                        text: "Gerar PDF",
                        value: true
                    }
                }
            }).then((result) => {
                if (result) {
                    const dadosAdicionais = {
                        escola: document.getElementById('escola').value,
                        municipio: document.getElementById('municipio').value,
                        anoConclusao: document.getElementById('anoConclusao').value,
                        cargaHoraria: document.getElementById('cargaHoraria').value,
                        situacao: document.getElementById('situacao').value,
                        observacoes: document.getElementById('observacoes').value
                    };

                    gerarHistoricoPDF(notas, dadosAdicionais);
                }
            });
        }

        function gerarHistoricoPDF(notas, dadosAdicionais) {
            swal("Processando...", "Gerando histórico escolar, aguarde...", "info", {
                buttons: false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });

            // Preparar dados para envio
            const dadosCompletos = {
                dadosAluno: dadosAluno,
                notas: notas,
                dadosAdicionais: dadosAdicionais,
                acao: 'gerar_pdf',
                retorno: 'json'
            };
            console.log(dadosCompletos);
            // return;

            // Enviar para processamento PHP
            fetch("<?php echo $url_sistema; ?>processar_historico_novo_fundamental.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dadosCompletos)
            })
                .then(response => response.json())
                .then(data => {
                    swal.close();
                    if (data.sucesso) {
                        console.log(data)
                        swal({
                            title: "Sucesso!",
                            text: "Histórico escolar gerado com sucesso!",
                            icon: "success",
                            buttons: {
                                visualizar: "Visualizar HTML",
                                download: "Download HTML",
                                fechar: "Fechar"
                            }
                        }).then((result) => {
                            if (result === 'visualizar') {
                                window.open(data.url_visualizacao, '_blank');
                            } else if (result === 'download') {
                                const link = document.createElement('a');
                                link.href = data.arquivo_html;
                                link.download = `historico_${dadosAluno.nome.replace(/\s+/g, '_')}.html`;
                                link.click();
                            }
                        });
                    } else {
                        swal("Erro!", data.mensagem || "Erro ao gerar o histórico.", "error");
                    }
                })
                .catch(error => {
                    swal.close();
                    console.error('Erro:', error);
                    swal("Erro!", "Erro de comunicação com o servidor.", "error");
                });
        }

        function visualizarExemplo() {
            const dadosExemplo = {
                nome: "Maria Silva Santos",
                sexo: "F",
                dataNasc: "1995-05-15",
                naturalidade: "Machadinho D'Oeste - RO",
                cpf: "123.456.789-00",
                rg: "1234567 SSP/RO",
                pai: "João Santos Silva",
                mae: "Ana Maria Silva"
            };

            const notasExemplo = {};
            const todasMaterias = [...materias.linguagens, ...materias.matematica, ...materias.ciencias, ...materias.humanas, ...materias.diversificada];

            todasMaterias.forEach(materia => {
                notasExemplo[materia] = {
                    serie1: (Math.random() * 3 + 7).toFixed(1), // Notas entre 7.0 e 10.0
                    serie2: (Math.random() * 3 + 7).toFixed(1),
                    serie3: (Math.random() * 3 + 7).toFixed(1)
                };
            });

            const dadosAdicionaisExemplo = {
                escola: "Centro de Estudos Supletivos de Machadinho D'Oeste",
                municipio: "Machadinho D'Oeste - RO",
                anoConclusao: "2024",
                cargaHoraria: "2400",
                situacao: "APROVADO"
            };

            // Simular dados completos
            dadosAluno = dadosExemplo;
            gerarHistoricoPDF(notasExemplo, dadosAdicionaisExemplo);
        }

        // Função para formatar CPF durante a digitação
        document.addEventListener('DOMContentLoaded', function () {
            // Aplicar máscaras quando o documento carregar
            setTimeout(() => {
                const cpfInput = document.getElementById('cpf');
                if (cpfInput) {
                    cpfInput.addEventListener('input', function (e) {
                        let value = e.target.value.replace(/\D/g, '');
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                        e.target.value = value;
                    });
                }
            }, 1000);
        });
    </script>

    <script>
        function ativarCheckboxes() {
            // Seleciona todos os checkboxes do formulário
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="asterisco"]');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    // Obtém o id base da matéria a partir do checkbox
                    const baseId = this.id.replace('_asterisco', '');
                    const inputDate = document.getElementById(`${baseId}_date`);

                    if (this.checked) {
                        // Mostra o campo de data
                        inputDate.style.display = 'table-cell';
                    } else {
                        // Oculta o campo de data
                        inputDate.style.display = 'none';
                        // Limpa o valor do campo de data
                        inputDate.querySelector('input').value = '';
                    }
                });
            });
        }

    </script>


    <script>
        function BuscarAlunoNovamente() {
            fetch("<?php echo $url_sistema; ?>api/usuarios/buscar.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let usuarios = data.resultado;

                        // Cria as opções do select
                        let options = usuarios.map(u =>
                            `<option style="margin-top: 5px;" value="${u.id}">${u.nome} - ${u.email}</option>`
                        ).join('');

                        Swal.fire({
                            title: 'Selecione um usuário',
                            width: '80%', // ajusta a largura
                            html: `
    <div style="display: flex; flex-direction: column; gap: 10px; width: 95%;">
      <input id="buscaUsuario" class="swal2-input" placeholder="Buscar usuário...">
      <select id="selectUsuario" class="swal2-select" size="18" 
              style="width:100%; max-height:550px;  overflow-y:auto; white-space:normal;">
        ${options}
      </select>
    </div>
  `,
                            showCancelButton: true,
                            confirmButtonText: 'Confirmar',
                            cancelButtonText: 'Cancelar',
                            didOpen: () => {
                                const inputBusca = document.getElementById('buscaUsuario');
                                const select = document.getElementById('selectUsuario');

                                // Filtro de busca
                                inputBusca.addEventListener('input', () => {
                                    const termo = inputBusca.value.toLowerCase();
                                    select.querySelectorAll('option').forEach(opt => {
                                        if (opt.textContent.toLowerCase().includes(termo)) {
                                            opt.style.display = '';
                                        } else {
                                            opt.style.display = 'none';
                                        }
                                    });
                                });
                            },
                            preConfirm: () => {
                                const select = document.getElementById('selectUsuario');
                                const idSelecionado = select.value;
                                if (!idSelecionado) {
                                    Swal.showValidationMessage('Selecione um usuário');
                                    return false;
                                }
                                return idSelecionado;
                            }
                        }).then(result => {
                            if (result.isConfirmed) {
                                const idUsuario = result.value;
                                // Aqui você decide o que fazer, por exemplo:
                                window.location.href = "<?php echo $url_sistema; ?>gerador_historico.php?id=" + idUsuario;
                            }
                        });

                    } else {
                        Swal.fire("Erro!", data.mensagem || "Erro ao gerar o histórico.", "error");
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire("Erro!", "Erro de comunicação com o servidor.", "error");
                });
        }
    </script>


</body>

</html>
