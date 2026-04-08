<?php
// templates/nre/list_monitor.php

// List of NREs for Monitor Mode (Read Only) without editing or user validations

$pageTitle = 'Monitor - Requirements Dashboard';
include __DIR__ . '/../components/header_monitor.php';

// Calcular estadísticas
$stats = [
    'total' => count($nres),
    'draft' => 0,
    'approved' => 0,
    'in_process' => 0,
    'arrived' => 0,
    'cancelled' => 0,
    'total_usd' => 0,
    'total_mxn' => 0
];

foreach ($nres as $nre) {
    if ($nre['status'] !== 'Cancelled') {
        $stats['total_usd'] += $nre['quantity'] * $nre['unit_price_usd'];
        $stats['total_mxn'] += $nre['quantity'] * $nre['unit_price_mxn'];
    }
    
    switch ($nre['status']) {
        case 'Draft': $stats['draft']++; break;
        case 'Approved': $stats['approved']++; break;
        case 'In Process': $stats['in_process']++; break;
        case 'Arrived': $stats['arrived']++; break;
        case 'Cancelled': $stats['cancelled']++; break;
    }
}
?>

<style>
.stat-card { border-left: 4px solid; transition: transform 0.2s, box-shadow 0.2s; height: 100%; }
.stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important; }
.stat-card.primary { border-left-color: #0d6efd; }
.stat-card.success { border-left-color: #198754; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.info { border-left-color: #0dcaf0; }
.stat-card.danger { border-left-color: #dc3545; }
.stat-card.secondary { border-left-color: #6c757d; }

.stat-icon { font-size: 2.5rem; opacity: 0.3; }

.table-compact { font-size: 0.8rem; }
.table-compact th { font-size: 0.75rem; font-weight: 600; white-space: nowrap; padding: 0.5rem 0.3rem; vertical-align: middle; }
.table-compact td { font-size: 0.75rem; vertical-align: middle; padding: 0.4rem 0.3rem; }
.table-compact code { font-size: 0.7rem; }
.table-compact small { font-size: 0.7rem; }

/* Width adjustments */
.table-compact th:nth-child(1), .table-compact td:nth-child(1) { width: 8%; } /* Type */
.table-compact th:nth-child(2), .table-compact td:nth-child(2) { width: 10%; } /* ID */
.table-compact th:nth-child(3), .table-compact td:nth-child(3) { width: 12%; } /* Requester */
.table-compact th:nth-child(4), .table-compact td:nth-child(4) { width: 22%; } /* Description */
.table-compact th:nth-child(5), .table-compact td:nth-child(5) { width: 6%; } /* Code */
.table-compact th:nth-child(6), .table-compact td:nth-child(6) { width: 4%; } /* Qty */
.table-compact th:nth-child(7), .table-compact td:nth-child(7) { width: 12%; } /* Extra Info */
.table-compact th:nth-child(8), .table-compact td:nth-child(8) { width: 8%; } /* Status */
.table-compact th:nth-child(9), .table-compact td:nth-child(9) { width: 6%; } /* Creation */
.table-compact th:nth-child(10), .table-compact td:nth-child(10) { width: 6%; } /* Arrival */
.table-compact th:nth-child(11), .table-compact td:nth-child(11) { width: 6%; } /* Total */

.text-truncate-custom {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-display"></i> Monitor Dashboard <span class="badge bg-secondary ms-2" style="font-size:0.5em"><i class="bi bi-eye"></i> Read Only</span></h2>
            <div class="d-flex gap-2">
                <div class="btn-group me-2">
                    <a href="monitor.php" class="btn btn-outline-dark <?= !isset($_GET['type']) ? 'active' : '' ?>">All</a>
                    <a href="monitor.php?type=NRE" class="btn btn-outline-dark <?= (isset($_GET['type']) && $_GET['type'] === 'NRE') ? 'active' : '' ?>">NREs</a>
                    <a href="monitor.php?type=PackR" class="btn btn-outline-dark <?= (isset($_GET['type']) && $_GET['type'] === 'PackR') ? 'active' : '' ?>">PackR</a>
                </div>
                
                <?php
                $urlParams = $_GET;
                ?>
                
                <?php if ($includeCompleted): ?>
                    <?php
                    $hideParams = $urlParams;
                    $hideParams['hide_completed'] = 1;
                    unset($hideParams['page']);
                    ?>
                    <a href="monitor.php?<?= http_build_query($hideParams) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-eye-slash"></i> Hide Completed
                    </a>
                <?php else: ?>
                    <?php
                    $showParams = $urlParams;
                    unset($showParams['hide_completed'], $showParams['page']);
                    ?>
                    <a href="monitor.php?<?= http_build_query($showParams) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> Show Completed
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estadísticas en Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total NREs</p>
                                <h3 class="mb-0 fw-bold"><?= $totalNres ?></h3>
                            </div>
                            <i class="bi bi-file-earmark-text stat-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card secondary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">In Approval</p>
                                <h3 class="mb-0 fw-bold"><?= $stats['draft'] ?></h3>
                            </div>
                            <i class="bi bi-pencil-square stat-icon text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">In Process</p>
                                <h3 class="mb-0 fw-bold"><?= $stats['in_process'] ?></h3>
                            </div>
                            <i class="bi bi-hourglass-split stat-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Finished</p>
                                <h3 class="mb-0 fw-bold"><?= $stats['arrived'] ?></h3>
                            </div>
                            <i class="bi bi-check-circle stat-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total USD</p>
                                <h4 class="mb-0 fw-bold"><?= number_format($stats['total_usd'], 0) ?></h4>
                                <small class="text-info fw-bold" style="font-size:0.75rem;">(+IVA: $<?= number_format($stats['total_usd'] * 1.16, 0) ?>)</small>
                            </div>
                            <i class="bi bi-currency-dollar stat-icon text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total MXN</p>
                                <h4 class="mb-0 fw-bold"><?= number_format($stats['total_mxn'], 0) ?></h4>
                                <small class="text-info fw-bold" style="font-size:0.75rem;">(+IVA: $<?= number_format($stats['total_mxn'] * 1.16, 0) ?>)</small>
                            </div>
                            <i class="bi bi-cash-stack stat-icon text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($nres)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No requirements available in this state.
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-table"></i> Requirements List
                        <span class="badge bg-primary ms-2"><?= count($nres) ?> of <?= $totalNres ?> records</span>
                    </h5>
                    
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Show:</small>
                        <div class="btn-group btn-group-sm">
                            <?php foreach ([20, 50, 100] as $l): ?>
                                <?php
                                $limParams = $urlParams;
                                $limParams['limit'] = $l;
                                $limParams['page'] = 1;
                                ?>
                                <a href="monitor.php?<?= http_build_query($limParams) ?>" 
                                   class="btn btn-outline-secondary <?= $limit == $l ? 'active' : '' ?>">
                                    <?= $l ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-compact mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Type</th>
                                <th>ID / SAP</th>
                                <th>Requester</th>
                                <th>Description</th>
                                <th>Code</th>
                                <th class="text-center">Qty.</th>
                                <th>Extra Info</th>
                                <th class="text-center">Status</th>
                                <th>Created</th>
                                <th>Arrived</th>
                                <th class="text-end">Total MXN + VAT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nres as $nre): ?>
                                <tr>
                                    <td>
                                        <?php if (($nre['requirement_type'] ?? 'NRE') === 'PackR'): ?>
                                            <span class="badge bg-info text-dark">PackR</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">NRE</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($nre['is_special_req'])): ?>
                                            <br><span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;" title="Requiere Presupuesto Especial (> $4000 USD)"><i class="bi bi-star-fill"></i> Especial</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="text-primary"><?= htmlspecialchars($nre['nre_number']) ?></code>
                                        <?php if (!empty($nre['sap_document_number'])): ?>
                                            <br><small class="text-muted">SAP: <?= htmlspecialchars($nre['sap_document_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($nre['requester_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <div class="text-truncate-custom" title="<?= htmlspecialchars($nre['item_description']) ?>">
                                            <small><?= htmlspecialchars($nre['item_description']) ?></small>
                                        </div>
                                    </td>
                                    <td><small><?= htmlspecialchars($nre['item_code'] ?? '-') ?></small></td>
                                    <td class="text-center"><strong><?= (int)$nre['quantity'] ?></strong></td>
                                    <td>
                                        <?php if (($nre['requirement_type'] ?? 'NRE') === 'PackR'): ?>
                                            <small class="d-block text-muted">Dept: <?= htmlspecialchars($nre['department'] ?? '-') ?></small>
                                            <small class="d-block text-muted">Proj: <?= htmlspecialchars($nre['project'] ?? '-') ?></small>
                                        <?php else: ?>
                                            <div class="text-truncate" style="max-width: 100px;" title="<?= htmlspecialchars($nre['customizer'] ?? '') ?>">
                                                <small>Supp: <?= htmlspecialchars($nre['customizer'] ?? '-') ?></small>
                                            </div>
                                            <small>Op: <?= htmlspecialchars($nre['operation'] ?? '-') ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $statusBadge = [
                                            'Draft' => 'secondary',
                                            'Approved' => 'primary',
                                            'In Process' => 'warning',
                                            'Arrived' => 'success',
                                            'Cancelled' => 'danger'
                                        ];
                                        $badgeClass = $statusBadge[$nre['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>" style="font-size: 0.65rem;">
                                            <?= htmlspecialchars($nre['status']) ?>
                                        </span>
                                        <?php if ($nre['status'] === 'In Process' && !empty($nre['created_at'])): ?>
                                            <?php
                                            $createdAt = new DateTime($nre['created_at']);
                                            $now = new DateTime();
                                            $daysInProcess = $now->diff($createdAt)->days;
                                            if ($daysInProcess > 30): ?>
                                                <br><span class="badge bg-danger mt-1" style="font-size: 0.65rem;" title="More than 1 month in process"><i class="bi bi-exclamation-octagon"></i> > 1 Month</span>
                                            <?php elseif ($daysInProcess > 14): ?>
                                                <br><span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;" title="More than 2 weeks in process"><i class="bi bi-exclamation-triangle"></i> > 2 Weeks</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= $nre['created_at'] ? date('d/m/y', strtotime($nre['created_at'])) : '' ?></small>
                                    </td>
                                    <td>
                                        <small><?= $nre['arrival_date'] ? date('d/m/y', strtotime($nre['arrival_date'])) : '—' ?></small>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success" style="font-size: 0.75rem;">$<?= number_format((float)($nre['unit_price_mxn'] * $nre['quantity'] * 1.16), 2) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Footer -->
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <?php
                    $totalPages = ceil($totalNres / $limit);
                    if ($totalPages < 1) $totalPages = 1;
                    ?>
                    <div>
                        <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Previous -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <?php
                                $prevParams = $urlParams;
                                $prevParams['page'] = $page - 1;
                                ?>
                                <a class="page-link" href="monitor.php?<?= http_build_query($prevParams) ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++):
                                $pgParams = $urlParams;
                                $pgParams['page'] = $i;
                            ?>
                                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                    <a class="page-link" href="monitor.php?<?= http_build_query($pgParams) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <?php
                                $nextParams = $urlParams;
                                $nextParams['page'] = $page + 1;
                                ?>
                                <a class="page-link" href="monitor.php?<?= http_build_query($nextParams) ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
    </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
