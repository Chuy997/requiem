<?php
// public/reports.php
// Página de generación y descarga de reportes con gráficas

require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Nre.php';

requireAuth();

$currentUser = new User($_SESSION['user_id']);
$isAdmin = $currentUser->isAdmin();

// Procesar descarga de reporte
if (isset($_GET['download'])) {
    $format = $_GET['format'] ?? 'csv';
    $filters = [
        'status' => $_GET['status'] ?? [],
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'requester_id' => $isAdmin && !empty($_GET['requester_id']) ? (int)$_GET['requester_id'] : null,
        'requirement_type' => $_GET['requirement_type'] ?? ''
    ];
    
    $nreModel = new Nre();
    $nres = $nreModel->getAll($filters);
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_requerimientos_' . date('Ymd_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        fputcsv($output, [
            'Type', 'NRE Number', 'Requester', 'Email', 'Item Description', 'Item Code',
            'Operation', 'Customizer', 'Brand', 'Model', 'New/Replace',
            'Quantity', 'Unit Price USD', 'Unit Price MXN', 'Total USD', 'Total MXN',
            'Needed Date', 'Arrival Date', 'Reason', 'Status', 'Created At', 'Updated At',
            'SAP Document', 'Department', 'Project'
        ]);
        
        foreach ($nres as $nre) {
            $totalUsd = $nre['quantity'] * $nre['unit_price_usd'];
            $totalMxn = $nre['quantity'] * $nre['unit_price_mxn'];
            
            fputcsv($output, [
                $nre['requirement_type'] ?? 'NRE',
                $nre['nre_number'],
                $nre['requester_name'] ?? '',
                $nre['requester_email'] ?? '',
                $nre['item_description'],
                $nre['item_code'] ?? '',
                $nre['operation'] ?? '',
                $nre['customizer'] ?? '',
                $nre['brand'] ?? '',
                $nre['model'] ?? '',
                $nre['new_or_replace'] ?? '',
                $nre['quantity'],
                number_format($nre['unit_price_usd'], 2, '.', ''),
                number_format($nre['unit_price_mxn'], 2, '.', ''),
                number_format($totalUsd, 2, '.', ''),
                number_format($totalMxn, 2, '.', ''),
                $nre['needed_date'] ?? '',
                $nre['arrival_date'] ?? '',
                $nre['reason'] ?? '',
                $nre['status'],
                $nre['created_at'],
                $nre['updated_at'],
                $nre['sap_document_number'] ?? '',
                $nre['department'] ?? '',
                $nre['project'] ?? ''
            ]);
        }
        fclose($output);
        exit;
    } elseif ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_requerimientos_' . date('Ymd_His') . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<table border="1"><thead><tr>';
        echo '<th>Type</th><th>NRE Number</th><th>Requester</th><th>Email</th><th>Item Description</th><th>Item Code</th>';
        echo '<th>Operation</th><th>Customizer</th><th>Brand</th><th>Model</th><th>New/Replace</th>';
        echo '<th>Quantity</th><th>Unit Price USD</th><th>Unit Price MXN</th><th>Total USD</th><th>Total MXN</th>';
        echo '<th>Needed Date</th><th>Arrival Date</th><th>Reason</th><th>Status</th><th>Created At</th><th>Updated At</th>';
        echo '<th>SAP Document</th><th>Department</th><th>Project</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($nres as $nre) {
            $totalUsd = $nre['quantity'] * $nre['unit_price_usd'];
            $totalMxn = $nre['quantity'] * $nre['unit_price_mxn'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($nre['requirement_type'] ?? 'NRE') . '</td>';
            echo '<td>' . htmlspecialchars($nre['nre_number']) . '</td>';
            echo '<td>' . htmlspecialchars($nre['requester_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['requester_email'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['item_description']) . '</td>';
            echo '<td>' . htmlspecialchars($nre['item_code'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['operation'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['customizer'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['brand'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['model'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['new_or_replace'] ?? '') . '</td>';
            echo '<td>' . $nre['quantity'] . '</td>';
            echo '<td>' . number_format($nre['unit_price_usd'], 2) . '</td>';
            echo '<td>' . number_format($nre['unit_price_mxn'], 2) . '</td>';
            echo '<td>' . number_format($totalUsd, 2) . '</td>';
            echo '<td>' . number_format($totalMxn, 2) . '</td>';
            echo '<td>' . htmlspecialchars($nre['needed_date'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['arrival_date'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['reason'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['status']) . '</td>';
            echo '<td>' . htmlspecialchars($nre['created_at']) . '</td>';
            echo '<td>' . htmlspecialchars($nre['updated_at']) . '</td>';
            echo '<td>' . htmlspecialchars($nre['sap_document_number'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['department'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($nre['project'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        exit;
    }
}

// Obtener datos para vista
$filters = [
    'status' => $_GET['status'] ?? [],
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'requester_id' => $isAdmin && !empty($_GET['requester_id']) ? (int)$_GET['requester_id'] : null,
    'requirement_type' => $_GET['requirement_type'] ?? ''
];

$nreModel = new Nre();
$nres = $nreModel->getAll($filters);

$users = $isAdmin ? User::getAllUsers() : [];

// Estadísticas generales
$stats = [
    'total' => count($nres),
    'total_usd' => 0,
    'total_mxn' => 0,
    'by_status' => ['Draft' => 0, 'Approved' => 0, 'In Process' => 0, 'Arrived' => 0, 'Cancelled' => 0],
    'by_operation' => [],
    'by_month' => [],
    'by_requester' => []
];

foreach ($nres as $nre) {
    $stats['total_usd'] += $nre['quantity'] * $nre['unit_price_usd'];
    $stats['total_mxn'] += $nre['quantity'] * $nre['unit_price_mxn'];
    
    if (isset($stats['by_status'][$nre['status']])) {
        $stats['by_status'][$nre['status']]++;
    }
    
    // Por operación
    $operation = $nre['operation'] ?? 'Sin especificar';
    if (!isset($stats['by_operation'][$operation])) {
        $stats['by_operation'][$operation] = 0;
    }
    $stats['by_operation'][$operation]++;
    
    // Por mes
    $month = date('Y-m', strtotime($nre['created_at']));
    if (!isset($stats['by_month'][$month])) {
        $stats['by_month'][$month] = ['count' => 0, 'total_mxn' => 0];
    }
    $stats['by_month'][$month]['count']++;
    $stats['by_month'][$month]['total_mxn'] += $nre['quantity'] * $nre['unit_price_mxn'];
    
    // Por solicitante (solo para admin)
    if ($isAdmin) {
        $requester = $nre['requester_name'] ?? 'N/A';
        if (!isset($stats['by_requester'][$requester])) {
            $stats['by_requester'][$requester] = ['count' => 0, 'total_mxn' => 0];
        }
        $stats['by_requester'][$requester]['count']++;
        $stats['by_requester'][$requester]['total_mxn'] += $nre['quantity'] * $nre['unit_price_mxn'];
    }
}

// Ordenar por mes
ksort($stats['by_month']);

// Preparar datos para gráficas
$chartStatusLabels = json_encode(array_keys($stats['by_status']));
$chartStatusData = json_encode(array_values($stats['by_status']));

$chartOperationLabels = json_encode(array_keys($stats['by_operation']));
$chartOperationData = json_encode(array_values($stats['by_operation']));

$chartMonthLabels = json_encode(array_map(function($m) {
    return date('M Y', strtotime($m . '-01'));
}, array_keys($stats['by_month'])));
$chartMonthCounts = json_encode(array_column($stats['by_month'], 'count'));
$chartMonthTotals = json_encode(array_column($stats['by_month'], 'total_mxn'));

if ($isAdmin) {
    arsort($stats['by_requester']);
    $topRequesters = array_slice($stats['by_requester'], 0, 10, true);
    $chartRequesterLabels = json_encode(array_keys($topRequesters));
    $chartRequesterData = json_encode(array_column($topRequesters, 'count'));
}

$pageTitle = 'Reportes y Análisis';
include __DIR__ . '/../templates/components/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 20px;
}
</style>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-graph-up-arrow"></i> Reportes y Análisis de NREs
        </h2>
        
        <!-- Estadísticas Principales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-dark bg-primary bg-opacity-25 shadow-sm border border-primary">
                    <div class="card-body">
                        <h6 class="card-title text-primary"><i class="bi bi-file-earmark-text"></i> Total NREs</h6>
                        <h2 class="mb-0 text-primary fw-bold"><?= $stats['total'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-dark bg-success bg-opacity-25 shadow-sm border border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success"><i class="bi bi-currency-dollar"></i> Total USD</h6>
                        <h2 class="mb-0 text-success fw-bold">$<?= number_format($stats['total_usd'], 0) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-dark bg-info bg-opacity-25 shadow-sm border border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info"><i class="bi bi-cash-stack"></i> Total MXN</h6>
                        <h2 class="mb-0 text-info fw-bold">$<?= number_format($stats['total_mxn'], 0) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-dark bg-warning bg-opacity-25 shadow-sm border border-warning">
                    <div class="card-body">
                        <h6 class="card-title text-warning"><i class="bi bi-hourglass-split"></i> En Proceso</h6>
                        <h2 class="mb-0 text-warning fw-bold"><?= $stats['by_status']['In Process'] ?></h2>
                    </div>
                </div>
            </div>
        </div>
           
        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 text-primary"><i class="bi bi-funnel"></i> Filtros</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="reports.php" class="row g-3">
                    <div class="col-md-3">
    <label class="form-label">Tipo</label>
    <select name="requirement_type" class="form-select">
        <option value="">Todos</option>
        <option value="NRE" <?= ($filters['requirement_type'] === 'NRE') ? 'selected' : '' ?>>NRE</option>
        <option value="PackR" <?= ($filters['requirement_type'] === 'PackR') ? 'selected' : '' ?>>PackR</option>
    </select>
</div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <div class="dropdown">
    <button class="btn btn-outline-primary dropdown-toggle w-100 text-start" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        Seleccionar Estado
    </button>
    <ul class="dropdown-menu" aria-labelledby="statusDropdown">
        <?php 
        $options = ['Draft', 'Approved', 'In Process', 'Arrived', 'Cancelled'];
        foreach ($options as $opt): 
        ?>
            <li>
                <label class="dropdown-item">
                    <input type="checkbox" class="form-check-input me-1" name="status[]" value="<?= $opt ?>" <?= in_array($opt, $filters['status'] ?? []) ? 'checked' : '' ?>>
                    <?= $opt ?>
                </label>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Fecha Desde</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?= htmlspecialchars($filters['date_from']) ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Fecha Hasta</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?= htmlspecialchars($filters['date_to']) ?>">
                    </div>
                    
                    <?php if ($isAdmin): ?>
                    <div class="col-md-3">
                        <label class="form-label">Solicitante</label>
                        <select name="requester_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" 
                                    <?= ($filters['requester_id'] == $user['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="reports.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Botones de Descarga -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 text-success"><i class="bi bi-download"></i> Descargar Reporte</h5>
            </div>
            <div class="card-body">
                <p>Descargar los datos filtrados en el formato deseado:</p>
                <div class="btn-group">
                    <a href="reports.php?download=1&format=csv&<?= http_build_query($filters) ?>" 
                       class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Descargar CSV
                    </a>
                    <a href="reports.php?download=1&format=excel&<?= http_build_query($filters) ?>" 
                       class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-excel"></i> Descargar Excel
                    </a>
                </div>
                <small class="text-muted d-block mt-2">
                    Total de registros: <strong><?= $stats['total'] ?></strong>
                </small>
            </div>
        </div>
        
        <!-- Vista Previa de Datos -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 text-muted"><i class="bi bi-table"></i> Vista Previa de Datos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th>NRE</th>
                                <th>Solicitante</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Precio USD</th>
                                <th>Precio MXN</th>
                                <th>Total USD</th>
                                <th>Total MXN</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($nres)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <i class="bi bi-inbox"></i> No hay datos para mostrar
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($nres, 0, 50) as $nre): ?>
                                <tr>
                                    <td>
                                        <?php if (($nre['requirement_type'] ?? 'NRE') === 'PackR'): ?>
                                            <span class="badge bg-info text-dark">PackR</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">NRE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($nre['nre_number']) ?></code></td>
                                    <td>
                                        <small><?= htmlspecialchars($nre['requester_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($nre['item_description'], 0, 50)) ?>...</small>
                                    </td>
                                    <td><?= $nre['quantity'] ?></td>
                                    <td>$<?= number_format($nre['unit_price_usd'], 2) ?></td>
                                    <td>$<?= number_format($nre['unit_price_mxn'], 2) ?></td>
                                    <td><strong>$<?= number_format($nre['quantity'] * $nre['unit_price_usd'], 2) ?></strong></td>
                                    <td><strong>$<?= number_format($nre['quantity'] * $nre['unit_price_mxn'], 2) ?></strong></td>
                                    <td>
                                        <?php
                                        $badgeClass = [
                                            'Draft' => 'secondary',
                                            'Approved' => 'primary',
                                            'In Process' => 'warning',
                                            'Arrived' => 'success',
                                            'Cancelled' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $badgeClass[$nre['status']] ?? 'secondary' ?>">
                                            <?= $nre['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($nre['created_at'])) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($nres) > 50): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        <i class="bi bi-info-circle"></i>
                                        Mostrando primeros 50 de <?= count($nres) ?> registros. 
                                        Descarga el reporte completo para ver todos.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Gráficas -->
        <div class="row mb-4">
            <!-- Gráfica de Estado -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark"><i class="bi bi-pie-chart"></i> NREs por Estado</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfica de Operación -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark"><i class="bi bi-bar-chart"></i> NREs por Operación</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="operationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfica de Tendencia Mensual -->
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark"><i class="bi bi-graph-up"></i> Tendencia Mensual</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 350px;">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($isAdmin && !empty($topRequesters)): ?>
            <!-- Gráfica de Top Solicitantes -->
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark"><i class="bi bi-people"></i> Top 10 Solicitantes</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 350px;">
                            <canvas id="requesterChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configuración de colores
const colors = {
    primary: '#0d6efd',
    success: '#198754',
    warning: '#ffc107',
    danger: '#dc3545',
    info: '#0dcaf0',
    secondary: '#6c757d'
};

// Gráfica de Estado (Dona)
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= $chartStatusLabels ?>,
        datasets: [{
            data: <?= $chartStatusData ?>,
            backgroundColor: [
                colors.secondary,
                colors.primary,
                colors.warning,
                colors.success,
                colors.danger
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Gráfica de Operación (Barras Horizontales)
const operationCtx = document.getElementById('operationChart').getContext('2d');
new Chart(operationCtx, {
    type: 'bar',
    data: {
        labels: <?= $chartOperationLabels ?>,
        datasets: [{
            label: 'Cantidad de NREs',
            data: <?= $chartOperationData ?>,
            backgroundColor: colors.info,
            borderColor: colors.info,
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfica de Tendencia Mensual (Línea + Barras)
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?= $chartMonthLabels ?>,
        datasets: [
            {
                label: 'Cantidad de NREs',
                data: <?= $chartMonthCounts ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.5)',
                borderColor: colors.primary,
                borderWidth: 2,
                yAxisID: 'y'
            },
            {
                label: 'Total MXN',
                data: <?= $chartMonthTotals ?>,
                type: 'line',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderColor: colors.success,
                borderWidth: 3,
                fill: true,
                yAxisID: 'y1',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Cantidad de NREs'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Total MXN'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

<?php if ($isAdmin && !empty($topRequesters)): ?>
// Gráfica de Top Solicitantes
const requesterCtx = document.getElementById('requesterChart').getContext('2d');
new Chart(requesterCtx, {
    type: 'bar',
    data: {
        labels: <?= $chartRequesterLabels ?>,
        datasets: [{
            label: 'Cantidad de NREs',
            data: <?= $chartRequesterData ?>,
            backgroundColor: colors.primary,
            borderColor: colors.primary,
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../templates/components/footer.php'; ?>
