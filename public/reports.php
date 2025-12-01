<?php
// public/reports.php
// Página de generación y descarga de reportes

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
        'requester_id' => $isAdmin && !empty($_GET['requester_id']) ? (int)$_GET['requester_id'] : null
    ];
    
    $nreModel = new Nre();
    $nres = $nreModel->getAll($filters);
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_nres_' . date('Ymd_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        fputcsv($output, [
            'NRE Number', 'Requester', 'Email', 'Item Description', 'Item Code',
            'Operation', 'Customizer', 'Brand', 'Model', 'New/Replace',
            'Quantity', 'Unit Price USD', 'Unit Price MXN', 'Total USD', 'Total MXN',
            'Needed Date', 'Arrival Date', 'Reason', 'Status', 'Created At', 'Updated At'
        ]);
        
        foreach ($nres as $nre) {
            $totalUsd = $nre['quantity'] * $nre['unit_price_usd'];
            $totalMxn = $nre['quantity'] * $nre['unit_price_mxn'];
            
            fputcsv($output, [
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
                $nre['updated_at']
            ]);
        }
        fclose($output);
        exit;
    } elseif ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_nres_' . date('Ymd_His') . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<table border="1"><thead><tr>';
        echo '<th>NRE Number</th><th>Requester</th><th>Email</th><th>Item Description</th><th>Item Code</th>';
        echo '<th>Operation</th><th>Customizer</th><th>Brand</th><th>Model</th><th>New/Replace</th>';
        echo '<th>Quantity</th><th>Unit Price USD</th><th>Unit Price MXN</th><th>Total USD</th><th>Total MXN</th>';
        echo '<th>Needed Date</th><th>Arrival Date</th><th>Reason</th><th>Status</th><th>Created At</th><th>Updated At</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($nres as $nre) {
            $totalUsd = $nre['quantity'] * $nre['unit_price_usd'];
            $totalMxn = $nre['quantity'] * $nre['unit_price_mxn'];
            echo '<tr>';
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
    'requester_id' => $isAdmin && !empty($_GET['requester_id']) ? (int)$_GET['requester_id'] : null
];

$nreModel = new Nre();
$nres = $nreModel->getAll($filters);

$users = $isAdmin ? User::getAllUsers() : [];

$stats = [
    'total' => count($nres),
    'total_usd' => 0,
    'total_mxn' => 0,
    'by_status' => ['Draft' => 0, 'Approved' => 0, 'In Process' => 0, 'Arrived' => 0, 'Cancelled' => 0]
];

foreach ($nres as $nre) {
    $stats['total_usd'] += $nre['quantity'] * $nre['unit_price_usd'];
    $stats['total_mxn'] += $nre['quantity'] * $nre['unit_price_mxn'];
    if (isset($stats['by_status'][$nre['status']])) {
        $stats['by_status'][$nre['status']]++;
    }
}

$pageTitle = 'Reportes de NREs';
include __DIR__ . '/../templates/components/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-file-earmark-bar-graph"></i> Reportes de NREs
        </h2>
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-dark bg-primary">
                    <div class="card-body">
                        <h6 class="card-title">Total NREs</h6>
                        <h2 class="mb-0"><?= $stats['total'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-dark bg-success">
                    <div class="card-body">
                        <h6 class="card-title">Total USD</h6>
                        <h2 class="mb-0">$<?= number_format($stats['total_usd'], 2) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-dark bg-info">
                    <div class="card-body">
                        <h6 class="card-title">Total MXN</h6>
                        <h2 class="mb-0">$<?= number_format($stats['total_mxn'], 2) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-dark bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">En Proceso</h6>
                        <h2 class="mb-0"><?= $stats['by_status']['In Process'] ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 text-primary"><i class="bi bi-funnel"></i> Filtros</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="reports.php" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="status[]" class="form-select" multiple size="5">
                            <?php 
                            $selectedStatuses = is_array($filters['status']) ? $filters['status'] : [];
                            if (is_string($filters['status']) && !empty($filters['status'])) {
                                $selectedStatuses = [$filters['status']];
                            }
                            $options = ['Draft', 'Approved', 'In Process', 'Arrived', 'Cancelled'];
                            foreach ($options as $opt): 
                            ?>
                                <option value="<?= $opt ?>" <?= in_array($opt, $selectedStatuses) ? 'selected' : '' ?>>
                                    <?= $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.75rem;">Ctrl+Click para seleccionar varios</small>
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
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/components/footer.php'; ?>
