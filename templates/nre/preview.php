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

    <?php if (!empty($_SESSION['nre_is_special_req'])): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Atención:</strong> Esta solicitud está marcada como <b>Requerimiento Especial</b> y permitirá exceder el límite de presupuesto mensual.
        </div>
    <?php endif; ?>

    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>NRE No.</th>
                    <th>Owner</th>
                    <th>Request date</th>
                    <th>Item</th>
                    <th>Code item</th>
                    <th>Application reason / Area</th>
                    <th>Operation</th>
                    <th>Customizer</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>New or replace</th>
                    <th>Qty required</th>
                    <th>Quotation Unit Price (MXN)</th>
                    <th>Total amount (MXN)</th>
                    <th>MX total + IVA</th>
                    <th>Amount (USD)</th>
                    <th>Total (USD)</th>
                    <th>Total + IVA USD</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once __DIR__ . '/../../src/models/ExchangeRate.php';
                require_once __DIR__ . '/../../src/models/User.php';

                $exchangeRateModel = new ExchangeRate();
                // Get current month rate as in controller
                $today = new DateTime();
                $currentPeriod = $exchangeRateModel->getCurrentMonthPeriod();
                $rate = $exchangeRateModel->getRateForPeriod($currentPeriod);
                
                // Fallback if current month rate is not set (should be handled by controller logic but good for safety)
                if ($rate === null) {
                    $rate = $exchangeRateModel->getRateForPeriod($exchangeRateModel->getLastMonthPeriod());
                }

                $iva = 0.16;

                $grandTotalUsd = 0;
                $grandTotalMxn = 0;

                $nreNumbers = $_SESSION['nre_nre_numbers'] ?? [];
                // Get current user name for "Owner" column
                $currentUser = new User($_SESSION['user_id']);
                $ownerName = $currentUser->getFullName();
                $requestDate = date('m/d/Y');
                ?>
                <?php foreach ($_SESSION['nre_items'] as $index => $item):
                    $qty = (int) ($item['quantity'] ?? 1);
                    $price = (float) $item['price_amount'];
                    $currency = $item['price_currency'] ?? 'USD';

                    if ($currency === 'USD') {
                        $unitUsd = $price;
                        $unitMxn = $price * $rate;
                    } else {
                        $unitMxn = $price;
                        $unitUsd = $price / $rate;
                    }

                    $totalMxn = round($qty * $unitMxn, 2);
                    $totalUsd = round($qty * $unitUsd, 2);
                    $totalMxnIva = round($totalMxn * (1 + $iva), 2);
                    $totalUsdIva = round($totalUsd * (1 + $iva), 2);

                    $grandTotalUsd += $totalUsd;
                    $grandTotalMxn += $totalMxn;

                    $nreNumber = $nreNumbers[$index] ?? '—';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($nreNumber) ?></td>
                        <td><?= htmlspecialchars($ownerName) ?></td>
                        <td><?= $requestDate ?></td>
                        <td><?= htmlspecialchars($item['item_description']) ?></td>
                        <td><?= htmlspecialchars($item['item_code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['reason'] ?? 'All areas') ?></td>
                        <td><?= htmlspecialchars($item['operation'] ?? 'All areas') ?></td>
                        <td><?= htmlspecialchars($item['customizer'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['brand'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['model'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['new_or_replace'] ?? 'New') ?></td>
                        <td><?= $qty ?></td>
                        <td>$<?= number_format($unitMxn, 2) ?></td>
                        <td>$<?= number_format($totalMxn, 2) ?></td>
                        <td>$<?= number_format($totalMxnIva, 2) ?></td>
                        <td>$<?= number_format($unitUsd, 2) ?></td>
                        <td>$<?= number_format($totalUsd, 2) ?></td>
                        <td>$<?= number_format($totalUsdIva, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="13" class="text-end">SUBTOTAL</th>
                    <th>$<?= number_format($grandTotalMxn, 2) ?></th>
                    <th></th>
                    <th></th>
                    <th>$<?= number_format($grandTotalUsd, 2) ?></th>
                    <th></th>
                </tr>
                <tr>
                    <th colspan="13" class="text-end">IVA (16%)</th>
                    <th>$<?= number_format($grandTotalMxn * $iva, 2) ?></th>
                    <th></th>
                    <th></th>
                    <th>$<?= number_format($grandTotalUsd * $iva, 2) ?></th>
                    <th></th>
                </tr>
                <tr>
                    <th colspan="13" class="text-end">TOTAL + IVA</th>
                    <th>$<?= number_format($grandTotalMxn * (1 + $iva), 2) ?></th>
                    <th></th>
                    <th></th>
                    <th>$<?= number_format($grandTotalUsd * (1 + $iva), 2) ?></th>
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
        <a href="/requiem/public/index.php?action=edit_from_preview" class="btn btn-secondary">✏️ Editar</a>
    </form>
    </div>
</div>
<?php include __DIR__ . '/../components/footer.php'; ?>