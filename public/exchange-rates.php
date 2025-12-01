<?php
// public/exchange-rates.php
// Gestión de tipos de cambio

require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';
require_once __DIR__ . '/../src/models/ExchangeRateHistory.php';

requireAuth();

$currentUser = new User($_SESSION['user_id']);

if (!$currentUser->isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para gestionar tipos de cambio';
    header('Location: index.php');
    exit;
}

$exchangeRateModel = new ExchangeRate();
$historyModel = new ExchangeRateHistory();

$error = '';
$success = '';

// Generar opciones para selectores
$months = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];
$currentYear = date('Y');
$years = range($currentYear - 1, $currentYear + 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Construir periodo desde selectores
    $selYear = $_POST['year'] ?? date('Y');
    $selMonth = $_POST['month'] ?? date('m');
    $period = $selYear . $selMonth;
    
    // Tasa siempre en USD (ej: 0.054505)
    $rateUsdPerMxn = (float)($_POST['rate_usd_per_mxn'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    
    if ($rateUsdPerMxn <= 0) {
        $error = 'La tasa debe ser mayor a 0.';
    } elseif (empty($reason)) {
        $error = 'Debes especificar un motivo.';
    } else {
        // Calcular inversa para guardar (MXN por 1 USD)
        $rateMxnPerUsd = 1 / $rateUsdPerMxn;
        
        if ($exchangeRateModel->updateRate($period, $rateMxnPerUsd, $currentUser->getId(), $reason)) {
            $success = 'Tipo de cambio actualizado exitosamente.';
        } else {
            $error = 'Error al actualizar el tipo de cambio.';
        }
    }
}

$rates = $exchangeRateModel->getAllRates();
$history = $historyModel->getHistory();

$pageTitle = 'Gestión de Tipos de Cambio';
include __DIR__ . '/../templates/components/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2><i class="bi bi-currency-exchange"></i> Gestión de Tipos de Cambio</h2>
    </div>

    <?php if ($success): ?>
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">

            <div class="card-header bg-dark text-white">
                <h5 class="mb-0 text-white"><i class="bi bi-table"></i> Tasas Registradas</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Periodo</label>
                        <div class="row g-2">
                            <div class="col-8">
                                <select name="month" class="form-select" required>
                                    <?php foreach ($months as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= date('m') === $num ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <select name="year" class="form-select" required>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?= $y ?>" <?= $currentYear == $y ? 'selected' : '' ?>>
                                            <?= $y ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tasa de Tabla (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                            <input type="number" step="0.000001" name="rate_usd_per_mxn" id="rateInput" 
                                   class="form-control" placeholder="Ej: 0.054505" required>
                        </div>
                        <div class="form-text text-muted">
                            Ingresa el valor exacto de la columna "对美元折算率"
                        </div>
                    </div>

                    <!-- Calculadora Visual -->
                    <div class="alert alert-info" id="conversionPreview" style="display:none;">
                        <h6 class="alert-heading"><i class="bi bi-calculator"></i> Conversión Automática</h6>
                        <hr>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Valor Ingresado:</span>
                            <strong><span id="previewUsd">0.000000</span> USD</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Equivalente Sistema:</span>
                            <strong class="text-primary">$<span id="previewMxn">0.0000</span> MXN</strong>
                        </div>
                        <small class="d-block mt-2 text-muted fst-italic">
                            (1 USD = <span id="previewRate">0.00</span> MXN)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <textarea name="reason" class="form-control" rows="2" required placeholder="Ej: Actualización mensual SAFE"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Guardar Tipo de Cambio
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Tasas Actuales -->
    <div class="col-md-7">
        <div class="card shadow-sm mb-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-download"></i> Descargar Reporte</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Periodo</th>
                                <th>Tasa Sistema (MXN)</th>
                                <th>Valor Tabla (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rates as $r): 
                                $rateMxn = (float)$r['rate_mxn_per_usd'];
                                $rateUsd = $rateMxn > 0 ? (1 / $rateMxn) : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                        $year = substr($r['period'], 0, 4);
                                        $month = substr($r['period'], 4, 2);
                                        echo $months[$month] . " " . $year;
                                    ?>
                                </td>
                                <td>
                                    <strong>$<?= number_format($rateMxn, 4) ?></strong> MXN
                                </td>
                                <td class="text-muted">
                                    <?= number_format($rateUsd, 6) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Historial -->
        <div class="card shadow-sm">
        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0 text-white"><i class="bi bi-funnel"></i> Filtros</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($history, 0, 5) as $h): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="fw-bold"><?= htmlspecialchars($h['changed_by']) ?></small>
                                <br>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['changed_at'])) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-dark border">
                                    <?= number_format($h['new_rate'], 4) ?> MXN
                                </span>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($h['reason']) ?></small>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('rateInput').addEventListener('input', function(e) {
    const val = parseFloat(e.target.value);
    const preview = document.getElementById('conversionPreview');
    
    if (val > 0) {
        preview.style.display = 'block';
        document.getElementById('previewUsd').textContent = val.toFixed(6);
        
        // Calcular inversa (MXN por 1 USD)
        const mxnRate = 1 / val;
        document.getElementById('previewMxn').textContent = mxnRate.toFixed(4);
        document.getElementById('previewRate').textContent = mxnRate.toFixed(4);
    } else {
        preview.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../templates/components/footer.php'; ?>