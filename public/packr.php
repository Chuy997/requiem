<?php
// public/packr.php
// Página para crear Pack Requirements desde PDF de SAP

require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';
require_once __DIR__ . '/../src/controllers/PackRequirementController.php';

requireAuth();

$currentUser = new User($_SESSION['user_id']);
$message = $_SESSION['packr_message'] ?? null;
$error = $_SESSION['packr_error'] ?? null;

unset($_SESSION['packr_message'], $_SESSION['packr_error']);

// ─── VERIFICACIÓN CRÍTICA: Tipo de cambio del mes actual ──────────────────────
$exchangeRateModel = new ExchangeRate();
$exchangeRateOk    = $exchangeRateModel->isCurrentMonthRateSet();
$currentMonthLabel = $exchangeRateModel->getCurrentMonthLabel();
$currentPeriod     = $exchangeRateModel->getCurrentMonthPeriod();
// ─────────────────────────────────────────────────────────────────────────────

$previewData = null;
$tempPdf = null;

// Procesar upload y confirmación SOLO si el tipo de cambio está configurado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$exchangeRateOk) {
        // Bloquear cualquier acción POST si no hay tipo de cambio
        $error = "Operación bloqueada: no existe tipo de cambio configurado para $currentMonthLabel (período $currentPeriod). Configure el tipo de cambio antes de importar requerimientos de empaque.";
    } else {
        $controller = new PackRequirementController();

        if (isset($_POST['action']) && $_POST['action'] === 'confirm') {
            $result = $controller->confirmUpload($_POST, $_SESSION['user_id']);
            if ($result['success']) {
                $_SESSION['packr_message'] = $result['message'];
                header('Location: packr.php');
                exit;
            } else {
                $error = $result['message'];
                // Re-armar vista previa para no perder los datos ante un error
                $tempPdf = $_POST['temp_pdf'] ?? null;
                $previewData = [
                    'sap_document_number' => $_POST['sap_document_number'] ?? '',
                    'currency' => $_POST['currency'] ?? 'MXN',
                    'exchange_rate' => $_POST['exchange_rate'] ?? 20.0,
                    'exchange_rate_period' => $_POST['exchange_rate_period'] ?? '',
                    'exchange_rate_is_fallback' => ($_POST['exchange_rate_is_fallback'] ?? '0') === '1',
                    'comments' => $_POST['comments'] ?? '',
                    'items' => $_POST['items'] ?? []
                ];
            }
        } elseif (isset($_FILES['sap_pdf'])) {
            $result = $controller->parseUpload($_FILES['sap_pdf']);
            if ($result['success']) {
                $previewData = $result['data'];
                $tempPdf = $result['temp_pdf'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = 'Nuevo Pack Requirement';
include __DIR__ . '/../templates/components/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-seam"></i> Nuevo Requerimiento de Empaque</h2>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>

        <?php if (!$exchangeRateOk): ?>
            <!-- BLOQUEO CRÍTICO: Sin tipo de cambio del mes actual -->
            <div class="alert alert-danger border-danger shadow-sm" role="alert" style="border-left: 6px solid #dc3545;">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-shield-lock-fill fs-2 text-danger flex-shrink-0 mt-1"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1">
                            🔒 Importación de Requerimientos Bloqueada
                        </h5>
                        <p class="mb-2">
                            No se puede crear ningún requerimiento de empaque porque el
                            <strong>tipo de cambio de <?= htmlspecialchars($currentMonthLabel) ?></strong>
                            (período <code><?= htmlspecialchars($currentPeriod) ?></code>)
                            aún no ha sido configurado.
                        </p>
                        <p class="mb-2 fw-semibold text-danger">
                            Todo requerimiento generado en este mes debe utilizar el tipo de cambio correspondiente a <?= htmlspecialchars($currentMonthLabel) ?>.
                        </p>
                        <?php if ($currentUser->isAdmin()): ?>
                            <a href="exchange-rates.php" class="btn btn-danger btn-sm mt-1">
                                <i class="bi bi-currency-exchange"></i> Configurar Tipo de Cambio Ahora
                            </a>
                        <?php else: ?>
                            <p class="mb-0 text-muted">
                                <i class="bi bi-info-circle"></i> Por favor, contacta al administrador del sistema para que configure el tipo de cambio de <?= htmlspecialchars($currentMonthLabel) ?>.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success alert-sm d-flex align-items-center gap-2 py-2 mb-3" role="alert">
                <i class="bi bi-check-circle-fill text-success"></i>
                <small>
                    Tipo de cambio de <strong><?= htmlspecialchars($currentMonthLabel) ?></strong> activo:
                    <strong>$<?= number_format($exchangeRateModel->getRateForPeriod($currentPeriod), 4) ?> MXN/USD</strong>
                </small>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($previewData): ?>
            <!-- Vista Previa de Requerimientos -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm mb-4 border-warning">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-eye"></i> Vista Previa de Requerimientos a Importar
                            </h5>
                            <span class="badge bg-dark">SAP Doc: <?= htmlspecialchars($previewData['sap_document_number']) ?></span>
                        </div>
                        <div class="card-body">
                            <?php if ($previewData['exchange_rate_is_fallback']): ?>
                                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                                    <div>
                                        <strong>Advertencia de Tipo de Cambio:</strong> No se encontró tipo de cambio configurado para el mes en curso (Periodo: <?= htmlspecialchars(date('Ym')) ?>). Se usará el del periodo <strong><?= htmlspecialchars($previewData['exchange_rate_period']) ?></strong> (<strong>$<?= number_format($previewData['exchange_rate'], 4) ?> MXN/USD</strong>) como fallback.
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                    <div>
                                        Tipo de cambio oficial del mes aplicado: <strong>$<?= number_format($previewData['exchange_rate'], 4) ?> MXN/USD</strong> (Periodo: <?= htmlspecialchars($previewData['exchange_rate_period']) ?>).
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="confirmForm">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="temp_pdf" value="<?= htmlspecialchars($tempPdf) ?>">
                                <input type="hidden" name="sap_document_number" value="<?= htmlspecialchars($previewData['sap_document_number']) ?>">
                                <input type="hidden" name="currency" value="<?= htmlspecialchars($previewData['currency']) ?>">
                                <input type="hidden" name="exchange_rate" value="<?= htmlspecialchars($previewData['exchange_rate']) ?>">
                                <input type="hidden" name="exchange_rate_period" value="<?= htmlspecialchars($previewData['exchange_rate_period']) ?>">
                                <input type="hidden" name="exchange_rate_is_fallback" value="<?= $previewData['exchange_rate_is_fallback'] ? '1' : '0' ?>">
                                <input type="hidden" name="comments" value="<?= htmlspecialchars($previewData['comments'] ?? '') ?>">

                                <div class="row mb-3 bg-light p-3 rounded mx-0">
                                    <div class="col-md-4">
                                        <strong>Moneda de Compra:</strong>
                                        <span class="badge bg-secondary fs-6 ms-2"><?= htmlspecialchars($previewData['currency']) ?></span>
                                    </div>
                                    <div class="col-md-8">
                                        <strong>Comentarios de Solicitud:</strong>
                                        <span class="text-muted ms-2"><?= htmlspecialchars($previewData['comments'] ?: 'Ninguno') ?></span>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="width: 15%">Código/SKU <span class="text-danger">*</span></th>
                                                <th style="width: 35%">Descripción <span class="text-danger">*</span></th>
                                                <th style="width: 13%">Fecha Necesaria <span class="text-danger">*</span></th>
                                                <th style="width: 8%">Cant. <span class="text-danger">*</span></th>
                                                <th style="width: 12%">P. Unit (<?= htmlspecialchars($previewData['currency']) ?>) <span class="text-danger">*</span></th>
                                                <th style="width: 9%">Proyecto <span class="text-danger">*</span></th>
                                                <th style="width: 8%">Depto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($previewData['items'] as $index => $item): ?>
                                                <tr>
                                                    <td>
                                                        <input type="text" 
                                                               name="items[<?= $index ?>][item_code]" 
                                                               class="form-control form-control-sm" 
                                                               value="<?= htmlspecialchars($item['item_code']) ?>" 
                                                               required>
                                                    </td>
                                                    <td>
                                                        <textarea name="items[<?= $index ?>][item_description]" 
                                                                  class="form-control form-control-sm" 
                                                                  rows="2" 
                                                                  required><?= htmlspecialchars($item['item_description']) ?></textarea>
                                                    </td>
                                                    <td>
                                                        <input type="date" 
                                                               name="items[<?= $index ?>][needed_date]" 
                                                               class="form-control form-control-sm" 
                                                               value="<?= htmlspecialchars($item['needed_date']) ?>" 
                                                               required>
                                                    </td>
                                                    <td>
                                                        <input type="number" 
                                                               name="items[<?= $index ?>][quantity]" 
                                                               class="form-control form-control-sm text-end" 
                                                               value="<?= htmlspecialchars($item['quantity']) ?>" 
                                                               min="1" 
                                                               required>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" 
                                                                   step="0.0001" 
                                                                   name="items[<?= $index ?>][unit_price]" 
                                                                   class="form-control text-end" 
                                                                   value="<?= htmlspecialchars($item['unit_price']) ?>" 
                                                                   min="0" 
                                                                   required>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" 
                                                               name="items[<?= $index ?>][project]" 
                                                               class="form-control form-control-sm" 
                                                               value="<?= htmlspecialchars($item['project']) ?>" 
                                                               required>
                                                    </td>
                                                    <td>
                                                        <input type="text" 
                                                               name="items[<?= $index ?>][department]" 
                                                               class="form-control form-control-sm" 
                                                               value="<?= htmlspecialchars($item['department'] ?? '') ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-secondary mt-3">
                                    <i class="bi bi-info-circle"></i>
                                    Puedes realizar cualquier edición necesaria directamente en la tabla anterior. Al hacer clic en <strong>Confirmar e Importar</strong>, el sistema creará cada fila como un registro individual en la base de datos.
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="packr.php" class="btn btn-outline-secondary btn-lg">
                                        <i class="bi bi-x-circle"></i> Cancelar / Descartar
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-circle"></i> Confirmar e Importar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Formulario de Upload -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-cloud-upload"></i> Cargar PDF de SAP
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                <div class="mb-3">
                                    <label for="sap_pdf" class="form-label">
                                        Documento PDF de SAP <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="sap_pdf" 
                                           name="sap_pdf" 
                                           accept=".pdf" 
                                           required>
                                    <div class="form-text">
                                        Solo archivos PDF de Solicitud de Compra de SAP (máx. 10MB)
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Nota:</strong> El PDF debe ser una Solicitud de Compra generada por SAP.
                                    El sistema extraerá automáticamente toda la información para que puedas previsualizarla antes de guardarla definitivamente.
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" <?= !$exchangeRateOk ? 'disabled title="Configure el tipo de cambio del mes antes de importar"' : '' ?>>
                                        <i class="bi bi-upload"></i> Cargar y Procesar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Instrucciones -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="bi bi-question-circle"></i> Instrucciones
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold">¿Cómo funciona?</h6>
                            <ol>
                                <li class="mb-2">
                                    <strong>Genera el PDF</strong> de la Solicitud de Compra desde SAP
                                </li>
                                <li class="mb-2">
                                    <strong>Sube el PDF</strong> usando el formulario de la izquierda
                                </li>                            
                            </ol>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Importante:</strong> Asegúrate de que el PDF tenga el formato estándar
                                de SAP Business One para Solicitudes de Compra.
                            </div>
                            
                            <h6 class="fw-bold mt-4">Formato esperado del PDF</h6>
                            <ul>
                                <li>Título: "SOLICITUD DE COMPRA NO."</li>
                                <li>Campos: Fecha, Solicitante, Moneda</li>
                                <li>Tabla con: Código, Descripción, Cantidad, Precio, etc.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Vista Previa de PDF Ejemplo -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="bi bi-file-earmark-pdf"></i> Ejemplo de Formato Esperado
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th colspan="8" class="text-center bg-primary text-white">
                                                SOLICITUD DE COMPRA NO. XXXX
                                            </th>
                                        </tr>
                                        <tr>
                                            <th>CÓDIGO</th>
                                            <th>DESCRIPCIÓN</th>
                                            <th>FECHA NECESARIA</th>
                                            <th>CANTIDAD</th>
                                            <th>PRECIO</th>
                                            <th>DEPARTAMENTO</th>
                                            <th>PROYECTO</th>
                                            <th>TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>EXP00075</code></td>
                                            <td>Wooden Pallet, 800*600</td>
                                            <td>09/12/2025</td>
                                            <td>200</td>
                                            <td>$247.50</td>
                                            <td>PRODUCTION</td>
                                            <td>00114</td>
                                            <td>$49,500.00</td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-muted">
                                                <small><i class="bi bi-info-circle"></i> El sistema procesará cada línea como un PackR individual</small>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Validación del formulario de carga
const uploadForm = document.getElementById('uploadForm');
if (uploadForm) {
    uploadForm.addEventListener('submit', function(e) {
        const fileInput = document.getElementById('sap_pdf');
        
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Por favor selecciona un archivo PDF');
            return false;
        }
        
        const file = fileInput.files[0];
        
        // Validar extensión
        if (!file.name.toLowerCase().endsWith('.pdf')) {
            e.preventDefault();
            alert('El archivo debe ser un PDF');
            return false;
        }
        
        // Validar tamaño (10MB)
        if (file.size > 10 * 1024 * 1024) {
            e.preventDefault();
            alert('El archivo es demasiado grande (máximo 10MB)');
            return false;
        }
        
        // Mostrar loading
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
    });
}

// Validación y loading para el formulario de confirmación
const confirmForm = document.getElementById('confirmForm');
if (confirmForm) {
    confirmForm.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    });
}
</script>

<?php include __DIR__ . '/../templates/components/footer.php'; ?>
