<?php
// Solo accesible vía navegador; asume autenticación implícita en MVP
$items = $_SESSION['nre_form_data']['items'] ?? [];
$hasError = !empty($_SESSION['nre_form_error']);
$errorMsg = $_SESSION['nre_form_error'] ?? '';
unset($_SESSION['nre_form_data'], $_SESSION['nre_form_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo NRE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .item-row { position: relative; }
        .btn-remove-item {
            position: absolute; top: -10px; right: -10px; z-index: 10;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">Crear Solicitud de Compra (NRE)</h2>

    <?php if ($hasError): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <form action="/requiem/public/index.php?action=create" method="POST" enctype="multipart/form-data" id="nreForm">
        <div id="items-container">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $index => $item): ?>
                    <div class="item-row mb-3 p-3 border rounded bg-white">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" onclick="removeItem(this)">✕</button>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Descripción del artículo *</label>
                                <input type="text" name="items[<?= $index ?>][item_description]" class="form-control" value="<?= htmlspecialchars($item['item_description'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Código</label>
                                <input type="text" name="items[<?= $index ?>][item_code]" class="form-control" value="<?= htmlspecialchars($item['item_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Cantidad *</label>
                                <input type="number" name="items[<?= $index ?>][quantity]" class="form-control" value="<?= (int)($item['quantity'] ?? 1) ?>" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio y Moneda *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="items[<?= $index ?>][price_amount]" class="form-control" value="<?= htmlspecialchars($item['price_amount'] ?? '') ?>" required>
                                    <select name="items[<?= $index ?>][price_currency]" class="form-select" style="max-width:100px;">
                                        <option value="USD" <?= ($item['price_currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                                        <option value="MXN" <?= ($item['price_currency'] ?? 'USD') === 'MXN' ? 'selected' : '' ?>>MXN</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Operación</label>
                                <select name="items[<?= $index ?>][operation]" class="form-select">
                                    <option value="">Seleccionar</option>
                                    <option value="Calibration" <?= ($item['operation'] ?? '') === 'Calibration' ? 'selected' : '' ?>>Calibration</option>
                                    <option value="Maintenance" <?= ($item['operation'] ?? '') === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    <option value="Packaging" <?= ($item['operation'] ?? '') === 'Packaging' ? 'selected' : '' ?>>Packaging</option>
                                    <option value="IT" <?= ($item['operation'] ?? '') === 'IT' ? 'selected' : '' ?>>IT</option>
                                    <option value="Production" <?= ($item['operation'] ?? '') === 'Production' ? 'selected' : '' ?>>Production</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <label class="form-label">Proveedor/Customizer</label>
                                <input type="text" name="items[<?= $index ?>][customizer]" class="form-control" value="<?= htmlspecialchars($item['customizer'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="items[<?= $index ?>][brand]" class="form-control" value="<?= htmlspecialchars($item['brand'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="items[<?= $index ?>][model]" class="form-control" value="<?= htmlspecialchars($item['model'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nuevo/Reemplazo</label>
                                <select name="items[<?= $index ?>][new_or_replace]" class="form-select">
                                    <option value="New" <?= ($item['new_or_replace'] ?? 'New') === 'New' ? 'selected' : '' ?>>New</option>
                                    <option value="Replace" <?= ($item['new_or_replace'] ?? 'New') === 'Replace' ? 'selected' : '' ?>>Replace</option>
                                    <option value="Service" <?= ($item['new_or_replace'] ?? 'New') === 'Service' ? 'selected' : '' ?>>Service</option>
                                    <option value="Pkg" <?= ($item['new_or_replace'] ?? 'New') === 'Pkg' ? 'selected' : '' ?>>Pkg</option>
                                    <option value="Maintenance" <?= ($item['new_or_replace'] ?? 'New') === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <label class="form-label">Razón / Área de aplicación</label>
                                <input type="text" name="items[<?= $index ?>][reason]" class="form-control" value="<?= htmlspecialchars($item['reason'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <script>let itemIndex = <?= count($items) ?>;</script>
            <?php else: ?>
                <div class="item-row mb-3 p-3 border rounded bg-white">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" onclick="removeItem(this)" style="display:none;">✕</button>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Descripción del artículo *</label>
                            <input type="text" name="items[0][item_description]" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Código</label>
                            <input type="text" name="items[0][item_code]" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" name="items[0][quantity]" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio y Moneda *</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="items[0][price_amount]" class="form-control" required>
                                <select name="items[0][price_currency]" class="form-select" style="max-width:100px;">
                                    <option value="USD">USD</option>
                                    <option value="MXN">MXN</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Operación</label>
                            <select name="items[0][operation]" class="form-select">
                                <option value="">Seleccionar</option>
                                <option value="Calibration">Calibration</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Packaging">Packaging</option>
                                <option value="IT">IT</option>
                                <option value="Production">Production</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <label class="form-label">Proveedor/Customizer</label>
                            <input type="text" name="items[0][customizer]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Marca</label>
                            <input type="text" name="items[0][brand]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="items[0][model]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nuevo/Reemplazo</label>
                            <select name="items[0][new_or_replace]" class="form-select">
                                <option value="New">New</option>
                                <option value="Replace">Replace</option>
                                <option value="Service">Service</option>
                                <option value="Pkg">Pkg</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <label class="form-label">Razón / Área de aplicación</label>
                            <input type="text" name="items[0][reason]" class="form-control">
                        </div>
                    </div>
                </div>
                <script>let itemIndex = 1;</script>
            <?php endif; ?>
        </div>

        <button type="button" class="btn btn-outline-secondary mb-3" onclick="addItemRow()">+ Agregar otro ítem</button>

        <div class="mb-3">
            <label class="form-label">Cotizaciones (PDF, JPG, PNG)</label>
            <input type="file" name="quotations[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
            <div class="form-text">Puedes adjuntar múltiples archivos.</div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Vista Previa y Enviar</button>
            <a href="/requiem/public/" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
function removeItem(button) {
    if (document.querySelectorAll('.item-row').length > 1) {
        button.closest('.item-row').remove();
    } else {
        alert('Debe haber al menos un ítem.');
    }
}

function addItemRow() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row mb-3 p-3 border rounded bg-white';
    newRow.innerHTML = `
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" onclick="removeItem(this)">✕</button>
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Descripción del artículo *</label>
                <input type="text" name="items[${itemIndex}][item_description]" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Código</label>
                <input type="text" name="items[${itemIndex}][item_code]" class="form-control">
            </div>
            <div class="col-md-1">
                <label class="form-label">Cantidad *</label>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control" value="1" min="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Precio y Moneda *</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="items[${itemIndex}][price_amount]" class="form-control" required>
                    <select name="items[${itemIndex}][price_currency]" class="form-select" style="max-width:100px;">
                        <option value="USD">USD</option>
                        <option value="MXN">MXN</option>
                    </select>
                </div>
            </div>
            <div class="col-md-1">
                <label class="form-label">Operación</label>
                <select name="items[${itemIndex}][operation]" class="form-select">
                    <option value="">Seleccionar</option>
                    <option value="Calibration">Calibration</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Packaging">Packaging</option>
                    <option value="IT">IT</option>
                    <option value="Production">Production</option>
                </select>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-3">
                <label class="form-label">Proveedor/Customizer</label>
                <input type="text" name="items[${itemIndex}][customizer]" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Marca</label>
                <input type="text" name="items[${itemIndex}][brand]" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Modelo</label>
                <input type="text" name="items[${itemIndex}][model]" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nuevo/Reemplazo</label>
                <select name="items[${itemIndex}][new_or_replace]" class="form-select">
                    <option value="New">New</option>
                    <option value="Replace">Replace</option>
                    <option value="Service">Service</option>
                    <option value="Pkg">Pkg</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <label class="form-label">Razón / Área de aplicación</label>
                <input type="text" name="items[${itemIndex}][reason]" class="form-control">
            </div>
        </div>
    `;
    container.appendChild(newRow);
    itemIndex++;
}
</script>
</body>
</html>