<?php
// templates/nre/create.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo Requerimiento de Compra (NRE)</title>
    <link href="/assets/css/styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function addRow() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'row g-2 item-row mb-3 p-3 border rounded bg-white';
            newRow.innerHTML = `
                <div class="col-md-2">
                    <input type="text" class="form-control" name="items[][item_description]" required placeholder="Descripción">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control" name="items[][item_code]" placeholder="Código">
                </div>
                <div class="col-md-1">
                    <input type="number" class="form-control" name="items[][quantity]" value="1" min="1" required>
                </div>
                <div class="col-md-1">
                    <input type="number" step="0.01" class="form-control" name="items[][unit_price_usd]" required placeholder="USD">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="items[][operation]" required>
                        <option value="">Operación</option>
                        <option value="Calibration">Calibration</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Packaging">Packaging</option>
                        <option value="IT">IT</option>
                        <option value="Warehouse">Warehouse</option>
                        <option value="EPA">EPA</option>
                        <option value="5´s">5´s</option>
                        <option value="Service">Service</option>
                        <option value="Consumable">Consumable</option>
                        <option value="Labeling">Labeling</option>
                        <option value="Industrial Eng.">Industrial Eng.</option>
                        <option value="Loading">Loading</option>
                        <option value="Cable cutting">Cable cutting</option>
                        <option value="Weight & sizes">Weight & sizes</option>
                        <option value="All areas">All areas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="items[][customizer]" placeholder="Customizer / Proveedor">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control" name="items[][brand]" placeholder="Marca">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control" name="items[][model]" placeholder="Modelo">
                </div>
                <div class="col-md-1">
                    <select class="form-select" name="items[][new_or_replace]">
                        <option value="">Tipo</option>
                        <option value="New">New</option>
                        <option value="Replace">Replace</option>
                        <option value="Service">Service</option>
                        <option value="Pkg">Pkg</option>
                        <option value="Replacement">Replacement</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.item-row').remove()">–</button>
                </div>
            `;
            container.appendChild(newRow);
        }

        function addFileField() {
            const container = document.getElementById('files-container');
            const count = container.children.length + 1;
            const newFile = document.createElement('div');
            newFile.className = 'mb-2';
            newFile.innerHTML = `
                <label class="form-label">Cotización ${count}</label>
                <input type="file" class="form-control" name="quotations[]" accept=".pdf,.jpg,.jpeg,.png" required>
            `;
            container.appendChild(newFile);
        }
    </script>
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">Nuevo Requerimiento de Compra (NRE)</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="/index.php?action=create_batch_nre" method="POST" enctype="multipart/form-data">
        <!-- Información común -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Proyecto</label>
                <input type="text" class="form-control" name="project_code" value="00114">
            </div>
            <div class="col-md-4">
                <label class="form-label">Departamento</label>
                <input type="text" class="form-control" name="department" value="PRODUCTION">
            </div>
            <div class="col-md-4">
                <label class="form-label">Razón / Área de aplicación *</label>
                <input type="text" class="form-control" name="reason" value="All areas" required>
            </div>
        </div>

        <!-- Ítems -->
        <h4>Ítems del requerimiento</h4>
        <div id="items-container">
            <!-- Primer ítem (obligatorio) -->
            <div class="row g-2 item-row mb-3 p-3 border rounded bg-white">
                <div class="col-md-2">
                    <input type="text" class="form-control" name="items[][item_description]" required placeholder="Descripción">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control" name="items[][item_code]" placeholder="Código">
                </div>
                <div class="col-md-1">
                    <input type="number" class="form-control" name="items[][quantity]" value="1" min="1" required>
                </div>
                <div class="col-md-1">
                    <input type="number" step="0.01" class="form-control" name="items[][unit_price_usd]" required placeholder="USD">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="items[][operation]" required>
                        <option value="">Operación</option>
                        <option value="Calibration">Calibration</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Packaging">Packaging</option>
                        <option value="IT">IT</option>
                        <option value="Warehouse">Warehouse</option>
                        <option value="EPA">EPA</option>
                        <option value="5´s">5´s</option>
                        <option value="Service">Service</option>
                        <option value="Consumable">Consumable</option>
                        <option value="Labeling">Labeling</option>
                        <option value="Industrial Eng.">Industrial Eng.</option>
                        <option value="Loading">Loading</option>
                        <option value="Cable cutting">Cable cutting</option>
                        <option value="Weight & sizes">Weight & sizes</option>
                        <option value="All areas">All areas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="items[][customizer]" placeholder="Customizer / Proveedor">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control" name="items[][brand]" placeholder="Marca">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control" name="items[][model]" placeholder="Modelo">
                </div>
                <div class="col-md-1">
                    <select class="form-select" name="items[][new_or_replace]">
                        <option value="">Tipo</option>
                        <option value="New">New</option>
                        <option value="Replace">Replace</option>
                        <option value="Service">Service</option>
                        <option value="Pkg">Pkg</option>
                        <option value="Replacement">Replacement</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-danger" disabled>–</button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-success mb-3" onclick="addRow()">+ Agregar ítem</button>

        <!-- Archivos -->
        <h4 class="mt-4">Cotizaciones (una o más)</h4>
        <div id="files-container">
            <div class="mb-2">
                <label class="form-label">Cotización 1</label>
                <input type="file" class="form-control" name="quotations[]" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addFileField()">+ Adjuntar otra cotización</button>

        <!-- Fecha necesaria (común a todos) -->
        <div class="row mt-4">
            <div class="col-md-4">
                <label class="form-label">Fecha necesaria *</label>
                <input type="date" class="form-control" name="needed_date" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
            </div>
        </div>

        <!-- Botón -->
        <div class="mt-4">
            <button class="btn btn-primary" type="submit">Crear NREs y Enviar Notificación</button>
            <a href="/index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>