<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'simulado';

@session_start();

$nivel = $_SESSION['nivel'] ?? '';
if (!in_array($nivel, ['Vendedor', 'Tutor', 'Parceiro', 'Secretario'], true)) {
    echo "<script>window.location='../index.php'</script>";
    exit();
}

$stmt = $pdo->query("SELECT id, nome, valor, promocao FROM pacotes ORDER BY nome");
$pacotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="bs-example widget-shadow" style="padding:15px;">
    <h3 style="margin-top: 0;">Simulado de Pacotes</h3>
    <p class="text-muted" style="margin-bottom: 0;">Simule desconto e parcelamento ate 6x sem juros.</p>
</div>

<div class="bs-example widget-shadow" style="padding:15px;">
    <?php if (count($pacotes) === 0) : ?>
        <div class="alert alert-info" style="margin: 0;">Nenhum pacote encontrado.</div>
    <?php else : ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Pacote</th>
                    <th>Preco</th>
                    <th>Desconto (R$)</th>
                    <th>Desconto (%)</th>
                    <th>Valor final</th>
                    <th>Parcelas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pacotes as $pacote) : ?>
                    <?php
                    $preco_valor = (float) ($pacote['valor'] ?? 0);
                    $preco_promocao = (float) ($pacote['promocao'] ?? 0);
                    $preco_base = $preco_promocao > 0 ? $preco_promocao : $preco_valor;
                    ?>
                    <tr class="simulado-row" data-base="<?php echo number_format($preco_base, 2, '.', ''); ?>">
                        <td><?php echo htmlspecialchars($pacote['nome'] ?? ''); ?></td>
                        <td>R$ <?php echo number_format($preco_base, 2, ',', '.'); ?></td>
                        <td style="max-width: 140px;">
                            <input type="text" class="form-control desconto-valor" placeholder="0,00" inputmode="decimal">
                        </td>
                        <td style="max-width: 120px;">
                            <input type="text" class="form-control desconto-percent" placeholder="0" inputmode="decimal">
                        </td>
                        <td><strong class="valor-final">R$ <?php echo number_format($preco_base, 2, ',', '.'); ?></strong></td>
                        <td style="min-width: 170px;">
                            <select class="form-control parcelas">
                                <option value="1">1x</option>
                                <option value="2">2x</option>
                                <option value="3">3x</option>
                                <option value="4">4x</option>
                                <option value="5">5x</option>
                                <option value="6">6x</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
(function () {
    function toNumber(value) {
        if (typeof value !== 'string') {
            return 0;
        }
        var normalized = value.replace(/\./g, '').replace(',', '.');
        var parsed = parseFloat(normalized);
        return isNaN(parsed) ? 0 : parsed;
    }

    function formatBRL(value) {
        return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function atualizarLinha(row) {
        var base = parseFloat(row.getAttribute('data-base')) || 0;
        var inputValor = row.querySelector('.desconto-valor');
        var inputPercent = row.querySelector('.desconto-percent');
        var finalEl = row.querySelector('.valor-final');
        var parcelas = row.querySelector('.parcelas');

        var descontoValor = toNumber(inputValor.value);
        var descontoPercent = toNumber(inputPercent.value);
        if (descontoPercent < 0) {
            descontoPercent = 0;
        }
        if (descontoPercent > 100) {
            descontoPercent = 100;
        }

        var descontoCalc = (base * (descontoPercent / 100)) + descontoValor;
        var final = base - descontoCalc;
        if (final < 0) {
            final = 0;
        }

        finalEl.textContent = 'R$ ' + formatBRL(final);

        var selected = parseInt(parcelas.value, 10) || 1;
        parcelas.innerHTML = '';
        for (var i = 1; i <= 6; i++) {
            var opt = document.createElement('option');
            opt.value = String(i);
            opt.textContent = i + 'x de R$ ' + formatBRL(final / i);
            if (i === selected) {
                opt.selected = true;
            }
            parcelas.appendChild(opt);
        }
    }

    var rows = document.querySelectorAll('.simulado-row');
    rows.forEach(function (row) {
        var inputValor = row.querySelector('.desconto-valor');
        var inputPercent = row.querySelector('.desconto-percent');
        var parcelas = row.querySelector('.parcelas');
        inputValor.addEventListener('input', function () { atualizarLinha(row); });
        inputPercent.addEventListener('input', function () { atualizarLinha(row); });
        parcelas.addEventListener('change', function () { atualizarLinha(row); });
        atualizarLinha(row);
    });
})();
</script>
