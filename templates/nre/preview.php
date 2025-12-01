<?php
$pageTitle = 'Vista Previa - NRE';
include __DIR__ . '/../components/header.php';
?>
<style>
    .table th, .table td { white-space: nowrap; }
</style>

<div class="row">
    <div class="col-12">
    <h2 class="mb-4">Vista Previa de Solicitud de Compra</h2>

    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>NRE</th>
                    <th>Item</th>
                    <th>Código</th>
                    <th>Application reason / Area</th>
                    <th>Operation</th>
                    <th>Customizer</th>
                    <th>Qty</th>
                    <th>USD Unit</th>
                    <th>USD Total</th>
                    <th>MXN Unit</th>
                    <th>MXN Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once __DIR__ . '/../../src/models/ExchangeRate.php';
                $exchangeRateModel = new ExchangeRate();
                $rate = $exchangeRateModel->getRateForPeriod($exchangeRateModel->getLastMonthPeriod());
                $iva = 0.16;

                $grandTotalUsd = 0;
                $grandTotalMxn = 0;

                $nreNumbers = $_SESSION['nre_nre_numbers'] ?? [];
                ?>
                <?php foreach ($_SESSION['nre_items'] as $index => $item):
                    $qty = (int) ($item['quantity'] ?? 1);
                    $price = (float) $item['price_amount'];
                    $currency = $item['price_currency'] ?? 'USD';

                    if ($currency === 'USD') {
                        $unitUsd = $price;
                        $unitMxn = round($price * $rate, 2);
                    } else {
                        $unitMxn = $price;
                        $unitUsd = round($price / $rate, 2);
                    }

                    $totalUsd = $qty * $unitUsd;
                    $totalMxn = $qty * $unitMxn;
                    $grandTotalUsd += $totalUsd;
                    $grandTotalMxn += $totalMxn;

                    // ✅ Corrección: obtener NRE por índice
                    $nreNumber = $nreNumbers[$index] ?? '—';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($nreNumber) ?></td>
                        <td><?= htmlspecialchars($item['item_description']) ?></td>
                        <td><?= htmlspecialchars($item['item_code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['reason'] ?? 'All areas') ?></td>
                        <td><?= htmlspecialchars($item['operation'] ?? 'All areas') ?></td>
                        <td><?= htmlspecialchars($item['customizer'] ?? '') ?></td>
                        <td><?= $qty ?></td>
                        <td>$<?= number_format($unitUsd, 2) ?></td>
                        <td>$<?= number_format($totalUsd, 2) ?></td>
                        <td>$<?= number_format($unitMxn, 2) ?></td>
                        <td>$<?= number_format($totalMxn, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="7">SUBTOTAL</th>
                    <th>$<?= number_format($grandTotalUsd, 2) ?></th>
                    <th></th>
                    <th>$<?= number_format($grandTotalMxn, 2) ?></th>
                    <th></th>
                </tr>
                <tr>
                    <th colspan="7">IVA (16%)</th>
                    <th>$<?= number_format($grandTotalUsd * $iva, 2) ?></th>
                    <th></th>
                    <th>$<?= number_format($grandTotalMxn * $iva, 2) ?></th>
                    <th></th>
                </tr>
                <tr>
                    <th colspan="7">TOTAL + IVA</th>
                    <th>$<?= number_format($grandTotalUsd * (1 + $iva), 2) ?></th>
                    <th></th>
                    <th>$<?= number_format($grandTotalMxn * (1 + $iva), 2) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="text-muted">
        <em>Tipo de cambio: 1 USD = <?= number_format($rate, 4) ?> MXN (<?= date('F Y', strtotime('-1 month')) ?>)</em>
    </p>

    <form method="POST" action="/requiem/public/index.php?action=confirm" enctype="multipart/form-data">
        <button type="submit" class="btn btn-success me-2">✅ Confirmar y Enviar</button>
        <a href="/requiem/public/" class="btn btn-secondary">✏️ Editar</a>
    </form>
    </div>
</div>
<?php include __DIR__ . '/../components/footer.php'; ?>