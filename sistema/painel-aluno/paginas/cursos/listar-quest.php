<?php
require_once("../../../conexao.php");

$id_curso = $_POST['curso'];
@session_start();
$id_aluno = $_SESSION['id'];

$queryCurso = $pdo->prepare("SELECT * FROM cursos WHERE id = :id ORDER BY id asc");
$queryCurso->execute(['id' => $id_curso]);
$resCurso = $queryCurso->fetchAll(PDO::FETCH_ASSOC);

$query = $pdo->prepare("SELECT * FROM perguntas_quest WHERE curso = :curso ORDER BY id asc");
$query->execute(['curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$total_reg = @count($res);

// Array para armazenar as perguntas e alternativas
$questions_data = array();


$queryColorsConfig = $pdo->query("SELECT * FROM cores_sistema WHERE nome_classe = 'menu_lateral'");
$resQueryColorsConfig = $queryColorsConfig->fetchAll(PDO::FETCH_ASSOC);

$bg_m = $resQueryColorsConfig[0]['valor_cor'];

// echo '<pre>';
// echo json_encode($bg_m, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;



if ($total_reg > 0) {
	for ($i = 0; $i < $total_reg; $i++) {
		$id = $res[$i]['id'];
		$pergunta = $res[$i]['pergunta'];

		// Buscar alternativas para esta pergunta
		$query2 = $pdo->prepare("SELECT * FROM alternativas WHERE pergunta = :pergunta ORDER BY id asc");
		$query2->execute(['pergunta' => $id]);
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$total_reg2 = @count($res2);

		$alternatives = array();

		if ($total_reg2 > 0) {
			for ($i2 = 0; $i2 < $total_reg2; $i2++) {
				$id_alt = $res2[$i2]['id'];
				$resposta = $res2[$i2]['resposta'];
				$correta = $res2[$i2]['correta'];

				$alternatives[] = array(
					'id' => $id_alt,
					'text' => $resposta,
					'correct' => $correta
				);
			}
		}

		$questions_data[] = array(
			'id' => $id,
			'text' => $pergunta,
			'options' => $alternatives
		);
	}
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
	<meta charset="UTF-8">
	<title>Questionário - <?php echo $resCurso[0]['nome']; ?></title>
	<!-- <script src="https://cdn.tailwindcss.com"></script> -->
	<script src="/script/questionario.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

		body {
			font-family: 'Inter', sans-serif;
		}

		.gradient-bg {
			background: <?php echo $bg_m ?>;
		}

		.glass-effect {
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(20px);
			border: 1px solid rgba(255, 255, 255, 0.2);
		}

		.progress-bar {
			transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
		}

		.option-card {
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			cursor: pointer;
		}

		.option-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
		}

		.option-card.selected {
			background: <?php echo $bg_m ?>;
			color: white;
			transform: translateY(-2px);
			box-shadow: 0 10px 25px rgba(5, 86, 148, 0.4);
		}

		.option-card.selected .checkErrorQuest {
			color: white;
			/* ou direto: color: white; */
		}

		.question-enter {
			animation: slideInRight 0.5s ease-out;
		}

		@keyframes slideInRight {
			from {
				opacity: 0;
				transform: translateX(30px);
			}

			to {
				opacity: 1;
				transform: translateX(0);
			}
		}

		.pulse-ring {
			animation: pulse-ring 1.5s ease-in-out infinite;
		}

		@keyframes pulse-ring {
			0% {
				transform: scale(0.33);
			}

			40%,
			50% {
				opacity: 1;
			}

			100% {
				opacity: 0;
				transform: scale(1.2);
			}
		}

		@keyframes shake {

			0%,
			100% {
				transform: translateX(0);
			}

			10%,
			30%,
			50%,
			70%,
			90% {
				transform: translateX(-5px);
			}

			20%,
			40%,
			60%,
			80% {
				transform: translateX(5px);
			}
		}

		.summary-item {
			animation: fadeInUp 0.5s ease-out forwards;
			opacity: 0;
			transform: translateY(20px);
		}

		@keyframes fadeInUp {
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		#corpoPergunta {
			max-height: 60vh;
			overflow-y: auto;
		}

		@media (max-height: 500px) {
			#corpoPergunta {
				max-height: 40vh;
			}
		}
	</style>
</head>

<body class="gradient-bg rounded-5xl">

	<div class="glass-effect rounded-5xl">
		<!-- Header com progresso -->
		<div class="bg-gradient-to-r from-[<?php echo $bg_m ?>] to-[<?php echo $bg_m ?>] p-6 text-white">
			<div class="flex justify-between items-center">
				<div class="flex items-center gap-2">
					<h2 class="text-lg font-bold mb-1"><?php echo $resCurso[0]['nome']; ?></h2>

				</div>
				<div class="bg-white bg-opacity-20 rounded-full px-2 py-1">
					<span class="font-semibold" id="questionCounter">Questão <span id="questionNumber">1</span> de
						<?php echo $total_reg; ?></span>
				</div>
				<div class="flex items-center gap-2">

					<button onclick="closeModal()" class="text-white hover:text-red-300 transition-colors">
						<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
								d="M6 18L18 6M6 6l12 12">
							</path>
						</svg>
					</button>
				</div>
			</div>

			<!-- Indicador de questão e progresso -->


			<!-- Barra de progresso -->
			<div class="w-full bg-white bg-opacity-20 rounded-full h-3 overflow-hidden mt-2">
				<div id="progressBar"
					class="progress-bar h-full bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full"
					style="width: 0%"></div>
			</div>
		</div>

		<!-- Corpo da pergunta -->
		<div class="flex-1 overflow-y-auto p-6" id="corpoPergunta">
			<div id="questionArea" class="space-y-0"></div>
		</div>

		<!-- Botões -->
		<div class="flex-shrink-0 flex justify-between items-center px-6 py-3 bg-gray-50 border-t">
			<button onclick="prevStep()" id="prevBtn"
				class="hidden flex items-center gap-2 bg-gray-200 hover:bg-gray-300 px-6 py-3 rounded-xl font-medium transition-all duration-200 hover:scale-105">
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
					</path>
				</svg>
				Voltar
			</button>

			<div class="flex-1 items-center justify-center w-full ml-12">
				<span class="text-red-500 text-sm" id="checkError">

				</span>
			</div>

			<button onclick="nextStep()" id="nextBtn"
				class="flex items-center gap-3 bg-gradient-to-r from-[<?php echo $bg_m ?>]/80 to-[<?php echo $bg_m ?>]/80 hover:from-[<?php echo $bg_m ?>] hover:to-[<?php echo $bg_m ?>] text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
				<span>Próxima</span>
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
				</svg>
			</button>
		</div>
	</div>

	<script>
		// Dados das perguntas vindos do PHP
		const questions = <?php echo json_encode($questions_data); ?>;

		let currentStep = 0;
		const answers = []; // Armazenar IDs das respostas selecionadas
		const selectedAnswers = []; // Armazenar dados completos das respostas

		function openModal() {
			if (questions.length === 0) {
				alert('Nenhuma pergunta encontrada no banco de dados.');
				return;
			}


			renderQuestion();
			updateProgress();
		}

		function closeModal() {
			currentStep = 0;
			answers.length = 0;
			selectedAnswers.length = 0;
			$('#modalQuest').modal('hide');
			window.location.reload();
		}

		function updateProgress() {
			const progress = questions.length > 0 ? ((currentStep + 1) / questions.length) * 100 : 0;
			const progressBar = document.getElementById('progressBar');
			const progressText = document.getElementById('progressText');
			const questionCounter = document.getElementById('questionCounter');
			const questionNumber = document.getElementById('questionNumber');

			progressBar.style.width = progress + '%';
			// progressText.textContent = Math.round(progress) + '% concluído';
			// questionCounter.textContent = `Questão ${currentStep + 1} de ${questions.length}`;
			questionNumber.textContent = currentStep + 1;
			console.log(questionNumber.textContent)
		}

		function renderQuestion() {
			if (questions.length === 0) return;

			const question = questions[currentStep];
			const questionArea = document.getElementById('questionArea');

			questionArea.innerHTML = `
				<div class="question-enter">
					<div class="mb-4">
						<div class="flex flex-row items-center gap-2">
							<div>
							<div class="w-8 h-8 lg:w-10 lg:h-10  bg-gradient-to-r from-[<?php echo $bg_m ?>] to-[<?php echo $bg_m ?>] rounded-full flex items-center justify-center text-white font-bold">
								${currentStep + 1}
							</div>
							</div>
							<div class="">
								<h3 class="text-[16px] lg:text-sm font-bold text-gray-800">${question.text}</h3>
							</div>
						</div>
					</div>
					
					<div class="space-y-2">
						${question.options.map((opt, i) => {
				const letter = String.fromCharCode(65 + i); // A, B, C, D, etc.
				return `
					<div class="option-card group bg-white border-2 border-gray-200 rounded-xl  p-2 lg:p-6 hover:border-indigo-300" onclick="selectOption(${i})">
						<label class="flex items-start gap-4 cursor-pointer">
							<div class="flex-shrink-0 mt-1">
								<input type="radio" name="answer" value="${opt.id}" class="w-5 h-5 text-white hidden">
								<div class="radio-custom w-5 h-5 border-2 border-white rounded-full flex items-center justify-center">
									<div class="w-2.5 h-2.5 bg-white rounded-full hidden selected-indicator"></div>
								</div>
							</div>
							<span class="checkErrorQuest text-gray-700  leading-relaxed font-medium text-sm">
								<strong>${letter})</strong> ${opt.text}
							</span>
						</label>
					</div>
				`;
			}).join('')}
					</div>
				</div>
			`;

			// Restaurar seleção anterior se existir
			if (answers[currentStep] !== undefined) {
				const optionIndex = question.options.findIndex(opt => opt.id == answers[currentStep]);
				if (optionIndex !== -1) {
					selectOption(optionIndex);
				}
			}

			// Atualizar botões
			const prevBtn = document.getElementById('prevBtn');
			const nextBtn = document.getElementById('nextBtn');

			if (currentStep > 0) {
				prevBtn.classList.remove('hidden');
				prevBtn.classList.add('flex');
			} else {
				prevBtn.classList.add('hidden');
				prevBtn.classList.remove('flex');
			}

			if (currentStep === questions.length - 1) {
				nextBtn.innerHTML = `
					<span>Finalizar</span>
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
					</svg>
				`;
			} else {
				nextBtn.innerHTML = `
					<span>Próxima</span>
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
					</svg>
				`;
			}

			updateProgress();
		}

		function selectOption(index) {
			// Remove seleção anterior
			document.querySelectorAll('.option-card').forEach(card => {
				card.classList.remove('selected');
				const indicator = card.querySelector('.selected-indicator');
				const radio = card.querySelector('.radio-custom');
				indicator.classList.add('hidden');
				radio.style.borderColor = '#d1d5db';
			});

			// Adiciona nova seleção
			const selectedCard = document.querySelectorAll('.option-card')[index];
			const selectedRadio = document.querySelectorAll('input[name="answer"]')[index];
			const indicator = selectedCard.querySelector('.selected-indicator');
			const radio = selectedCard.querySelector('.radio-custom');

			selectedCard.classList.add('selected');
			selectedRadio.checked = true;
			indicator.classList.remove('hidden');
			radio.style.borderColor = '#FFF';
		}

		function nextStep() {
			const selected = document.querySelector('input[name="answer"]:checked');
			const checkError = document.getElementById('checkError');
			const checkErrorQuest = document.querySelectorAll('.checkErrorQuest');
			if (!selected) {
				// Animação de shake para chamar atenção
				const questionArea = document.getElementById('questionArea');
				questionArea.style.animation = 'shake 0.5s ease-in-out';
				setTimeout(() => {
					questionArea.style.animation = '';
				}, 500);


				checkError.textContent = '';

				Swal.fire({
					title: 'Ops!',
					text: 'Selecione uma resposta para continuar!',
					icon: 'error',
					// background: '<?php echo $bg_m ?>'
				});

				// checkErrorQuest.forEach(item => {
				// 	item.classList.remove('text-gray-700');
				// 	item.classList.add('text-red-500');
				// });

				return;
			}

			// Armazenar resposta com dados completos
			const question = questions[currentStep];
			const selectedOption = question.options.find(opt => opt.id == selected.value);
			checkError.textContent = '';
			// checkErrorQuest.forEach(item => {
			// 	item.classList.remove('text-red-500');
			// 	item.classList.add('text-gray-700');
			// });
			answers[currentStep] = selected.value;
			selectedAnswers[currentStep] = {
				questionId: question.id,
				questionText: question.text,
				answerId: selectedOption.id,
				answerText: selectedOption.text,
				isCorrect: selectedOption.correct
			};

			if (currentStep < questions.length - 1) {
				currentStep++;
				renderQuestion();
			} else {
				showSummary();
			}
		}

		function prevStep() {
			if (currentStep > 0) {
				currentStep--;
				renderQuestion();
			}
		}

		function showSummary() {
			const questionArea = document.getElementById('questionArea');

			let summaryHTML = `
				<div class="text-center py-8">
					<div class="mb-8">
						<div class="w-24 h-24 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-6">
							<svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
							</svg>
						</div>
						<h3 class="text-3xl font-bold text-gray-800 mb-4">Resumo das Respostas</h3>
						<p class="text-lg text-gray-600 mb-8">Revise suas respostas antes de enviar o questionário</p>
					</div>
					
					<div class="max-w-4xl mx-auto space-y-6 text-left">
			`;

			selectedAnswers.forEach((answer, index) => {
				const letter = String.fromCharCode(65 + questions[index].options.findIndex(opt => opt.id == answer.answerId));
				summaryHTML += `
					<div class="summary-item bg-white rounded-xl p-6 border-l-4 border-indigo-500 shadow-sm" style="animation-delay: ${index * 0.1}s">
						<div class="flex items-start gap-4">
							<div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-sm flex-shrink-0 mt-1">
								${index + 1}
							</div>
							<div class="flex-1">
								<h4 class="font-semibold text-gray-800 mb-3">${answer.questionText}</h4>
								<div class="bg-gradient-to-r from-[<?php echo $bg_m ?>] to-[<?php echo $bg_m ?>] rounded-lg p-4">
									<div class="flex items-center gap-2">
										<span class="font-medium text-white">${letter})</span>
										<span class="text-white">${answer.answerText}</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				`;
			});

			summaryHTML += `
					</div>
					
					<div class="mt-12 bg-gradient-to-r from-[<?php echo $bg_m ?>] to-[<?php echo $bg_m ?>] rounded-xl p-6 max-w-md mx-auto">
						<p class="text-white font-semibold">Total de questões respondidas:</p>
						<p class="text-3xl font-bold text-white">${questions.length}</p>
					</div>
				</div>
			`;

			questionArea.innerHTML = summaryHTML;

			// Atualizar botões
			const prevBtn = document.getElementById('prevBtn');
			const nextBtn = document.getElementById('nextBtn');

			prevBtn.classList.remove('hidden');
			prevBtn.classList.add('flex');
			prevBtn.innerHTML = `
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
				</svg>
				Revisar Respostas
			`;
			prevBtn.onclick = () => {
				currentStep = questions.length - 1;
				renderQuestion();
				prevBtn.onclick = prevStep;
			};

			nextBtn.innerHTML = `
				<span>Enviar Questionário</span>
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
				</svg>
			`;
			nextBtn.onclick = enviarQuestionario;

			// Atualizar progresso para 100%
			const progressBar = document.getElementById('progressBar');
			const progressText = document.getElementById('progressText');
			const questionCounter = document.getElementById('questionCounter');

			progressBar.style.width = '100%';
			progressText.textContent = '100% concluído';
			questionCounter.textContent = `Resumo Final`;
		}

		function enviarQuestionario() {
			// Desabilitar botão durante o processamento
			const nextBtn = document.getElementById('nextBtn');
			nextBtn.disabled = true;
			nextBtn.innerHTML = `
				<span>Processando...</span>
				<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
				</svg>
			`;

			// Preparar dados para envio
			const formData = new FormData();
			formData.append('id_curso', <?php echo $id_curso; ?>);
			formData.append('id_aluno', <?php echo $id_aluno; ?>);
			const idMatricula = document.getElementById('id_mat_quest') ? document.getElementById('id_mat_quest').value : '';
			formData.append('id_matricula', idMatricula);
			formData.append('respostas', JSON.stringify(selectedAnswers));

			// Enviar questionário
			fetch('paginas/cursos/processar-questionario.php', {
				method: 'POST',
				body: formData
			})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						mostrarResultado(data);
					} else {
						alert('Erro: ' + data.message);
						// Reabilitar botão em caso de erro
						nextBtn.disabled = false;
						nextBtn.innerHTML = `
						<span>Enviar Questionário</span>
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
						</svg>
					`;
					}
				})
				.catch(error => {
					console.error('Erro na requisição:', error);
					alert('Erro ao processar questionário. Tente novamente.');
					// Reabilitar botão em caso de erro
					nextBtn.disabled = false;
					nextBtn.innerHTML = `
					<span>Enviar Questionário</span>
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
					</svg>
				`;
				});
			document.getElementById('modalQuest');
			modalQuest.scrollTo({
				top: 0,
				behavior: 'smooth'
			});
		}

		function mostrarResultado(data) {
			const questionArea = document.getElementById('questionArea');
			const resultado = data.resultado;
			const detalhes = data.detalhes;

			// Cores baseadas no resultado
			const corPrimaria = resultado.aprovado ? 'from-green-400 to-emerald-500' : 'from-red-400 to-pink-500';
			const corSecundaria = resultado.aprovado ? 'from-green-50 to-emerald-50' : 'from-red-50 to-pink-50';
			const corTexto = resultado.aprovado ? 'text-green-800' : 'text-red-800';
			const iconeResultado = resultado.aprovado ?
				'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
				'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';

			questionArea.innerHTML = `
				<div class="text-center py-8">
					<!-- Resultado Principal -->
					<div class="mb-8">
						<div class="w-32 h-32 bg-gradient-to-r ${corPrimaria} rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
							<svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								${iconeResultado}
							</svg>
						</div>
						<h2 class="text-4xl font-bold ${corTexto} mb-2">${resultado.status}!</h2>
						<p class="text-2xl font-semibold text-gray-700 mb-2">Nota: ${resultado.nota_formatada}</p>
						<p class="text-lg text-gray-600">Você acertou ${resultado.acertos} de ${resultado.total_questoes} questões (${resultado.percentual_acerto}%)</p>
					</div>

					<!-- Estatísticas -->
					<div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-8">
						<div class="bg-gradient-to-r ${corSecundaria} rounded-xl p-6">
							<div class="text-3xl font-bold ${corTexto}">${resultado.acertos}</div>
							<div class="text-sm font-medium text-gray-600">Questões Corretas</div>
						</div>
						<div class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-xl p-6">
							<div class="text-3xl font-bold text-gray-700">${resultado.total_questoes - resultado.acertos}</div>
							<div class="text-sm font-medium text-gray-600">Questões Erradas</div>
						</div>
						<div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6">
							<div class="text-3xl font-bold text-blue-700">${resultado.media_necessaria}%</div>
							<div class="text-sm font-medium text-gray-600">Média Necessária</div>
						</div>
					</div>

					<!-- Detalhes das Respostas -->
					<div class="max-w-4xl mx-auto mb-8">
						<h3 class="text-xl font-bold text-gray-800 mb-6">Detalhamento das Respostas</h3>
						<div class="space-y-4">
							${detalhes.map((detalhe, index) => `
								<div class="bg-white rounded-xl p-4 shadow-sm border-l-4 ${detalhe.correta ? 'border-green-500' : 'border-red-500'}">
									<div class="flex items-start gap-4">
										<div class="w-8 h-8 ${detalhe.correta ? 'bg-green-100' : 'bg-red-100'} rounded-full flex items-center justify-center flex-shrink-0">
											<svg class="w-4 h-4 ${detalhe.correta ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												${detalhe.correta ?
					'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' :
					'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'
				}
											</svg>
										</div>
										<div class="flex-1 text-left">
											<h4 class="font-medium text-gray-800 mb-2">${detalhe.questao}. ${detalhe.pergunta}</h4>
											<div class="text-sm">
											
											</div>
										</div>
									</div>
								</div>
							`).join('')}
						</div>
					</div>
				</div>
			`;

			// Atualizar header
			const questionCounter = document.getElementById('questionCounter');
			questionCounter.textContent = `Resultado Final`;

			// Atualizar botões
			const prevBtn = document.getElementById('prevBtn');
			const nextBtn = document.getElementById('nextBtn');

			prevBtn.classList.add('hidden');
			nextBtn.innerHTML = `
				<span>Fechar</span>
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			`;
			nextBtn.onclick = closeModal;
			nextBtn.disabled = false;
		}

		// Verificar se há perguntas ao carregar a página
		if (questions.length === 0) {
			alert('Nenhuma pergunta disponível para este curso.');
		} else {
			// Inicializar questionário
			openModal();
		}
	</script>

</body>

</html>
