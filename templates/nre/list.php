<?php
// templates/nre/list.php
// Lista de NREs del usuario con header global y estadísticas

$pageTitle = 'Dashboard - NREs';
include __DIR__ . '/../components/header.php';

// Verificar si el usuario puede editar (admin o creador en Draft)
$canEditNre = function($nre) use ($currentUser) {
    $nreModel = new Nre();
    return $nreModel->canEdit($nre['nre_number'], $currentUser->getId(), $currentUser->isAdmin());
};

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
    $stats['total_usd'] += $nre['quantity'] * $nre['unit_price_usd'];
    $stats['total_mxn'] += $nre['quantity'] * $nre['unit_price_mxn'];
    
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
.stat-card {
    border-left: 4px solid;
    transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}
.stat-card.primary { border-left-color: #0d6efd; }
.stat-card.success { border-left-color: #198754; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.info { border-left-color: #0dcaf0; }
.stat-card.danger { border-left-color: #dc3545; }
.stat-card.secondary { border-left-color: #6c757d; }

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.3;
}

.table-compact {
    font-size: 0.8rem;
}

.table-compact th {
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    padding: 0.5rem 0.3rem;
    vertical-align: middle;
}

.table-compact td {
    font-size: 0.75rem;
    vertical-align: middle;
    padding: 0.4rem 0.3rem;
}

.table-compact code {
    font-size: 0.7rem;
}

.table-compact .btn-group-sm .btn {
    padding: 0.15rem 0.3rem;
    font-size: 0.7rem;
}

.table-compact small {
    font-size: 0.7rem;
}

/* Ajustes de ancho de columnas */
.table-compact th:nth-child(1), .table-compact td:nth-child(1) { width: 8%; } /* NRE */
.table-compact th:nth-child(2), .table-compact td:nth-child(2) { width: 10%; } /* Solicitante */
.table-compact th:nth-child(3), .table-compact td:nth-child(3) { width: 18%; } /* Descripción */
.table-compact th:nth-child(4), .table-compact td:nth-child(4) { width: 6%; } /* Código */
.table-compact th:nth-child(5), .table-compact td:nth-child(5) { width: 4%; } /* Cant */
.table-compact th:nth-child(6), .table-compact td:nth-child(6) { width: 10%; } /* Proveedor */
.table-compact th:nth-child(7), .table-compact td:nth-child(7) { width: 8%; } /* Operación */
.table-compact th:nth-child(8), .table-compact td:nth-child(8) { width: 8%; } /* Estado */
.table-compact th:nth-child(9), .table-compact td:nth-child(9) { width: 7%; } /* Creación */
.table-compact th:nth-child(10), .table-compact td:nth-child(10) { width: 7%; } /* Arribo */
.table-compact th:nth-child(11), .table-compact td:nth-child(11) { width: 8%; } /* Total */
.table-compact th:nth-child(12), .table-compact td:nth-child(12) { width: 6%; } /* Acciones */

.text-truncate-custom {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 1400px) {
    .table-compact th:nth-child(3), .table-compact td:nth-child(3) { width: 15%; }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-speedometer2"></i> Dashboard - <?= $isAdmin ? 'Todos los Requerimientos' : 'Mis Requerimientos' ?></h2>
            <div class="d-flex gap-2">
                <div class="btn-group me-2">
                    <a href="index.php" class="btn btn-outline-dark <?= !isset($_GET['type']) ? 'active' : '' ?>">Todos</a>
                    <a href="index.php?type=NRE" class="btn btn-outline-dark <?= (isset($_GET['type']) && $_GET['type'] === 'NRE') ? 'active' : '' ?>">NREs</a>
                    <a href="index.php?type=PackR" class="btn btn-outline-dark <?= (isset($_GET['type']) && $_GET['type'] === 'PackR') ? 'active' : '' ?>">PackR</a>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?action=new"><i class="bi bi-file-earmark-text"></i> Nuevo NRE</a></li>
                        <li><a class="dropdown-item" href="packr.php"><i class="bi bi-box-seam"></i> Nuevo PackR (SAP PDF)</a></li>
                    </ul>
                </div>
                
                <?php if (!isset($_GET['show_completed'])): ?>
                    <a href="index.php?show_completed=1" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> Ver Completados
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-eye-slash"></i> Ocultar Completados
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
                                <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
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
                                <p class="text-muted mb-1 small">Draft</p>
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
                                <p class="text-muted mb-1 small">En Proceso</p>
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
                                <p class="text-muted mb-1 small">Finalizados</p>
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
                            </div>
                            <i class="bi bi-cash-stack stat-icon text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($nres)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No tienes NREs en este estado.
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-table"></i> Lista de Requerimientos
                        <span class="badge bg-primary ms-2"><?= count($nres) ?> registros</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-compact mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipo</th>
                                <th>ID / SAP</th>
                                <?php if ($isAdmin): ?>
                                    <th>Solicitante</th>
                                <?php endif; ?>
                                <th>Descripción</th>
                                <th>Código</th>
                                <th class="text-center">Cant.</th>
                                <th>Info Extra</th>
                                <th class="text-center">Estado</th>
                                <th>Creación</th>
                                <th>Arribo</th>
                                <th class="text-end">Total MXN</th>
                                <th class="text-center">Acciones</th>
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
                                    </td>
                                    <td>
                                        <code class="text-primary"><?= htmlspecialchars($nre['nre_number']) ?></code>
                                        <?php if (!empty($nre['sap_document_number'])): ?>
                                            <br><small class="text-muted">SAP: <?= htmlspecialchars($nre['sap_document_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($nre['requester_name'] ?? 'N/A') ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="text-truncate-custom" title="<?= htmlspecialchars($nre['item_description']) ?>">
                                            <small><?= htmlspecialchars($nre['item_description']) ?></small>
                                        </div>
                                    </td>
                                    <td><small><?= htmlspecialchars($nre['item_code'] ?? '-') ?></small></td>
                                    <td class="text-center"><strong><?= (int)$nre['quantity'] ?></strong></td>
                                    <td>
                                        <?php if (($nre['requirement_type'] ?? 'NRE') === 'PackR'): ?>
                                            <small class="d-block text-muted">Depto: <?= htmlspecialchars($nre['department'] ?? '-') ?></small>
                                            <small class="d-block text-muted">Proy: <?= htmlspecialchars($nre['project'] ?? '-') ?></small>
                                        <?php else: ?>
                                            <div class="text-truncate" style="max-width: 100px;" title="<?= htmlspecialchars($nre['customizer'] ?? '') ?>">
                                                <small>Prov: <?= htmlspecialchars($nre['customizer'] ?? '-') ?></small>
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
                                    </td>
                                    <td>
                                        <small><?= $nre['created_at'] ? date('d/m/y', strtotime($nre['created_at'])) : '' ?></small>
                                    </td>
                                    <td>
                                        <small><?= $nre['arrival_date'] ? date('d/m/y', strtotime($nre['arrival_date'])) : '—' ?></small>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success" style="font-size: 0.75rem;">$<?= number_format((float)($nre['unit_price_mxn'] * $nre['quantity']), 0) ?></strong>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php 
                                            $isOwner = ($nre['requester_id'] == $currentUser->getId());
                                            $canManage = $isAdmin || $isOwner;
                                            $isPackR = ($nre['requirement_type'] ?? 'NRE') === 'PackR';
                                            ?>
                                            
                                            <?php if (!$isPackR && $canEditNre($nre)): ?>
                                                <a href="edit-nre.php?nre=<?= urlencode($nre['nre_number']) ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($isPackR && $nre['attachment_path']): ?>
                                                <a href="../uploads/packr/<?= htmlspecialchars($nre['attachment_path']) ?>" 
                                                   class="btn btn-outline-info"
                                                   target="_blank"
                                                   title="Ver PDF SAP">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                                
                                                <?php if ($nre['status'] === 'In Process' && $canManage): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#arrivalModal-<?= htmlspecialchars($nre['nre_number']) ?>"
                                                            title="Finalizar">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    
                                                    <!-- Modal Finalizar -->
                                                    <div class="modal fade" id="arrivalModal-<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-success text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-check-circle"></i> Registrar Recepción
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="index.php?action=mark_arrived">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Fecha de Recepción</label>
                                                                            <input type="date" name="arrival_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                                        </div>
                                                                        <div class="alert alert-info">
                                                                            <i class="bi bi-info-circle"></i>
                                                                            <strong>NRE:</strong> <?= htmlspecialchars($nre['nre_number']) ?><br>
                                                                            <strong>Item:</strong> <?= htmlspecialchars($nre['item_description']) ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                        <button type="submit" class="btn btn-success">
                                                                            <i class="bi bi-check-circle"></i> Confirmar Recepción
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                <?php elseif (in_array($nre['status'], ['Draft', 'Approved']) && $canManage): ?>
                                                    <form method="POST" action="index.php?action=mark_in_process" class="d-inline">
                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                        <button type="submit" 
                                                                class="btn btn-outline-info"
                                                                onclick="return confirm('¿Confirmar que ya está en SAP?');"
                                                                title="Complete SAP">
                                                            <i class="bi bi-arrow-right-circle"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="POST" action="index.php?action=cancel" class="d-inline">
                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                        <button type="submit" 
                                                                class="btn btn-outline-danger"
                                                                onclick="return confirm('¿Cancelar este NRE?');"
                                                                title="Cancelar NRE">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($nre['status'] === 'In Process' && $isAdmin): ?>
                                                    <!-- Admin puede cancelar incluso en In Process -->
                                                    <form method="POST" action="index.php?action=cancel" class="d-inline">
                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                        <button type="submit" 
                                                                class="btn btn-outline-danger"
                                                                onclick="return confirm('¿Cancelar este NRE?');"
                                                                title="Cancelar NRE">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>