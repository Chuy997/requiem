<?php
$exchangeRate = ExchangeRate::getRateForPreviousMonth();
$rateForDisplay = number_format(1 / $exchangeRate, 6);
$totalGlobalUsd = array_sum(array_column($itemsPreview, 'total_usd'));
$totalGlobalMxn = array_sum(array_column($itemsPreview, 'total_mxn'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista Previa - Validación de NREs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2>✅ Vista Previa - Validación antes de enviar</h2>
    <div class="alert alert-info">
        <strong>Tipo de cambio (USD → MXN):</strong> 1 USD = <?= number_format($exchangeRate, 4) ?> MXN<br>
        <small>(Valor de SAFE: 1 MXN = <?= $rateForDisplay ?> USD)</small>
    </div>

    <h4>Detalles del lote</h4>
    <table class="table table-bordered table-sm">
        <thead class="table-dark">
            <tr>
                <th>NRE No.</th>
                <th>Owner</th>
                <th>Request date</th>
                <th>Item Description</th>
                <th>Code Item</th>
                <th>Application Reason / Area</th>
                <th>Operation</th>
                <th>Customizer</th>
                <th>Brand</th>
                <th>Model</th>
                <th>New or Replace</th>
                <th>Qty</th>
                <th>Unit Price (USD)</th>
                <th>Unit Price (MXN)</th>
                <th>Total (USD)</th>
                <th>Total (MXN)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itemsPreview as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['nre_number']) ?></td>
                <td><?= htmlspecialchars($requester->getFullName()) ?></td>
                <td><?= date('d/m/Y') ?></td>
                <td><?= htmlspecialchars($item['item_description']) ?></td>
                <td><?= htmlspecialchars($item['item_code']) ?></td>
                <td><?= htmlspecialchars($reason) ?></td>
                <td><?= htmlspecialchars($item['operation']) ?></td>
                <td><?= htmlspecialchars($item['customizer']) ?></td>
                <td><?= htmlspecialchars($item['brand']) ?></td>
                <td><?= htmlspecialchars($item['model']) ?></td>
                <td><?= htmlspecialchars($item['new_or_replace']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$ <?= number_format($item['unit_price_usd'], 2) ?></td>
                <td>$ <?= number_format($item['unit_price_mxn'], 2) ?></td>
                <td>$ <?= number_format($item['total_usd'], 2) ?></td>
                <td>$ <?= number_format($item['total_mxn'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="14" class="text-end">TOTAL GENERAL:</th>
                <th>$ <?= number_format($totalGlobalUsd, 2) ?> USD</th>
                <th>$ <?= number_format($totalGlobalMxn, 2) ?> MXN</th>
            </tr>
        </tfoot>
    </table>

    <h5>Cotizaciones adjuntas</h5>
    <ul>
        <?php foreach ($savedQuotationPaths as $path): ?>
            <li><?= basename($path) ?></li>
        <?php endforeach; ?>
    </ul>

    <form method="POST" action="/requiem/public/index.php?action=send_batch_nre">
        <input type="hidden" name="items_json" value="<?= htmlspecialchars(json_encode($itemsPreview)) ?>">
        <input type="hidden" name="quotations_json" value="<?= htmlspecialchars(json_encode($savedQuotationPaths)) ?>">
        <input type="hidden" name="project_code" value="<?= htmlspecialchars($project_code) ?>">
        <input type="hidden" name="department" value="<?= htmlspecialchars($department) ?>">
        <input type="hidden" name="reason" value="<?= htmlspecialchars($reason) ?>">
        <input type="hidden" name="needed_date" value="<?= htmlspecialchars($needed_date) ?>">

        <div class="mt-3">
            <button type="submit" class="btn btn-success">✅ Confirmar y Enviar Correo a Jesús Muro</button>
            <a href="/requiem/public/" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>