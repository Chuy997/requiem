<?php
// public/edit-nre.php
// Página para editar un NRE existente

require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Nre.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';

requireAuth();

$currentUser = new User($_SESSION['user_id']);
$isAdmin = $currentUser->isAdmin();

$nreNumber = $_GET['nre'] ?? '';
if (empty($nreNumber)) {
    $_SESSION['error'] = 'Número de NRE no especificado';
    header('Location: index.php');
    exit;
}

$nreModel = new Nre();
$nre = $nreModel->getByNumber($nreNumber);

if (!$nre) {
    $_SESSION['error'] = 'NRE no encontrado';
    header('Location: index.php');
    exit;
}

// Verificar permisos
if (!$nreModel->canEdit($nreNumber, $_SESSION['user_id'], $isAdmin)) {
    $_SESSION['error'] = 'No tienes permisos para editar este NRE';
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

$exchangeRateModel = new ExchangeRate();
$availableRates = $exchangeRateModel->getAllRates();
$currentPeriod = $exchangeRateModel->getCurrentMonthPeriod();

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $priceAmount = (float)$_POST['price_amount'];
        $currency = $_POST['price_currency'] ?? 'USD';
        
        // Obtener tipo de cambio seleccionado
        $selectedPeriod = $_POST['exchange_rate_period'] ?? $currentPeriod;
        $rate = $exchangeRateModel->getRateForPeriod($selectedPeriod);
        
        if ($rate === null) {
            $today = new DateTime();
            $currentMonth = $today->format('F Y'); // Ej: "December 2025"
            throw new Exception("No se puede actualizar el NRE. El tipo de cambio para $currentMonth aún no está configurado. Por favor, configure el tipo de cambio del mes en curso antes de continuar.");
        }
        
        // Calcular precios
        if ($currency === 'USD') {
            $unitPriceUsd = $priceAmount;
            $unitPriceMxn = round($priceAmount * $rate, 2);
        } else {
            $unitPriceMxn = $priceAmount;
            $unitPriceUsd = round($priceAmount / $rate, 2);
        }
        
        $data = [
            'item_description' => $_POST['item_description'],
            'item_code' => $_POST['item_code'] ?? null,
            'operation' => $_POST['operation'] ?? null,
            'customizer' => $_POST['customizer'] ?? null,
            'brand' => $_POST['brand'] ?? null,
            'model' => $_POST['model'] ?? null,
            'new_or_replace' => $_POST['new_or_replace'] ?? 'New',
            'quantity' => (int)$_POST['quantity'],
            'unit_price_usd' => $unitPriceUsd,
            'unit_price_mxn' => $unitPriceMxn,
            'needed_date' => $_POST['needed_date'],
            'reason' => $_POST['reason'] ?? null
        ];
        
        if ($nreModel->update($nreNumber, $data)) {
            $_SESSION['success'] = "NRE $nreNumber actualizado exitosamente";
            header('Location: index.php');
            exit;
        } else {
            throw new Exception('Error al actualizar el NRE');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Editar NRE';
include __DIR__ . '/../templates/components/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-pencil-square"></i> Editar NRE: 
                <code><?= htmlspecialchars($nreNumber) ?></code>
            </h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($nre['status'] !== 'Draft' && !$isAdmin): ?>
            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i>
                <strong>Nota:</strong> Este NRE está en estado "<?= $nre['status'] ?>". 
                Solo los administradores pueden editar NREs que no están en Draft.
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-file-earmark-text"></i> Información del NRE
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Descripción del Artículo *</label>
                            <textarea name="item_description" class="form-control" rows="3" required><?= htmlspecialchars($nre['item_description']) ?></textarea>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Código del Artículo</label>
                            <input type="text" name="item_code" class="form-control" 
                                   value="<?= htmlspecialchars($nre['item_code'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" name="quantity" class="form-control" 
                                   value="<?= $nre['quantity'] ?>" min="1" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Precio Unitario *</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="price_amount" class="form-control" 
                                       value="<?= number_format($nre['unit_price_usd'], 2, '.', '') ?>" required>
                                <select name="price_currency" class="form-select" style="max-width:80px;">
                                    <option value="USD" selected>USD</option>
                                    <option value="MXN">MXN</option>
                                </select>
                            </div>
                            <small class="text-muted">
                                Actual: $<?= number_format($nre['unit_price_usd'], 2) ?> USD
                            </small>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tipo de Cambio</label>
                            <select name="exchange_rate_period" class="form-select">
                                <?php foreach ($availableRates as $rateOption): ?>
                                    <option value="<?= $rateOption['period'] ?>" <?= ($rateOption['period'] === $currentPeriod) ? 'selected' : '' ?>>
                                        <?= $rateOption['period'] ?> - $<?= number_format($rateOption['rate_mxn_per_usd'], 2) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Operación</label>
                            <select name="operation" class="form-select">
                                <option value="">Seleccionar</option>
                                <option value="Calibration" <?= ($nre['operation'] === 'Calibration') ? 'selected' : '' ?>>Calibration</option>
                                <option value="Maintenance" <?= ($nre['operation'] === 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
                                <option value="Packaging" <?= ($nre['operation'] === 'Packaging') ? 'selected' : '' ?>>Packaging</option>
                                <option value="IT" <?= ($nre['operation'] === 'IT') ? 'selected' : '' ?>>IT</option>
                                <option value="Production" <?= ($nre['operation'] === 'Production') ? 'selected' : '' ?>>Production</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fecha Necesaria *</label>
                            <input type="date" name="needed_date" class="form-control" 
                                   value="<?= $nre['needed_date'] ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Proveedor/Customizer</label>
                            <input type="text" name="customizer" class="form-control" 
                                   value="<?= htmlspecialchars($nre['customizer'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" name="brand" class="form-control" 
                                   value="<?= htmlspecialchars($nre['brand'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="model" class="form-control" 
                                   value="<?= htmlspecialchars($nre['model'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Nuevo/Reemplazo</label>
                            <select name="new_or_replace" class="form-select">
                                <option value="New" <?= ($nre['new_or_replace'] === 'New') ? 'selected' : '' ?>>New</option>
                                <option value="Replace" <?= ($nre['new_or_replace'] === 'Replace') ? 'selected' : '' ?>>Replace</option>
                                <option value="Service" <?= ($nre['new_or_replace'] === 'Service') ? 'selected' : '' ?>>Service</option>
                                <option value="Pkg" <?= ($nre['new_or_replace'] === 'Pkg') ? 'selected' : '' ?>>Pkg</option>
                                <option value="Maintenance" <?= ($nre['new_or_replace'] === 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Razón / Área de Aplicación</label>
                            <input type="text" name="reason" class="form-control" 
                                   value="<?= htmlspecialchars($nre['reason'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Información:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Estado actual: <strong><?= $nre['status'] ?></strong></li>
                            <li>Creado: <?= date('d/m/Y H:i', strtotime($nre['created_at'])) ?></li>
                            <li>Última actualización: <?= date('d/m/Y H:i', strtotime($nre['updated_at'])) ?></li>
                        </ul>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/components/footer.php'; ?>
