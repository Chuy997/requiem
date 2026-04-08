<?php
// templates/nre/list.php

// Obtener localidades y materiales para integración con inventario (PackR)
$locations = [];
$materials = [];
if (file_exists(__DIR__ . '/../../src/services/InventoryIntegration.php')) {
    require_once __DIR__ . '/../../src/services/InventoryIntegration.php';
    try {
        $invIntegration = new InventoryIntegration();
        $locations = $invIntegration->getLocalidades();
        $materials = $invIntegration->getMaterials();
    } catch (Exception $e) {
        // Ignorar error si no hay conexión al inventario
    }
}

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
.stat-card {
    border-left: 4px solid;
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
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
                
                <?php if (!$isCompras): ?>
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?action=new"><i class="bi bi-file-earmark-text"></i> Nuevo NRE</a></li>
                        <li><a class="dropdown-item" href="packr.php"><i class="bi bi-box-seam"></i> Nuevo PackR (SAP PDF)</a></li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php
                $urlParams = $_GET;
                ?>
                
                <?php if ($includeCompleted): ?>
                    <?php
                    $hideParams = $urlParams;
                    $hideParams['hide_completed'] = 1;
                    unset($hideParams['page']); // Reiniciar página al cambiar filtro
                    ?>
                    <a href="index.php?<?= http_build_query($hideParams) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-eye-slash"></i> Ocultar Completados
                    </a>
                <?php else: ?>
                    <?php
                    $showParams = $urlParams;
                    unset($showParams['hide_completed'], $showParams['page']);
                    ?>
                    <a href="index.php?<?= http_build_query($showParams) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> Ver Completados
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
                                <p class="text-muted mb-1 small">En aprobación</p>
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
                <i class="bi bi-info-circle"></i> No tienes NREs en este estado.
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-table"></i> Lista de Requerimientos
                        <span class="badge bg-primary ms-2"><?= count($nres) ?> de <?= $totalNres ?> registros</span>
                    </h5>
                    
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Mostrar:</small>
                        <div class="btn-group btn-group-sm">
                            <?php foreach ([20, 50, 100] as $l): ?>
                                <?php
                                $limParams = $urlParams;
                                $limParams['limit'] = $l;
                                $limParams['page'] = 1;
                                ?>
                                <a href="index.php?<?= http_build_query($limParams) ?>" 
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
                                <th class="text-end">Total MXN + IVA</th>
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
                                        
                                        <?php if (!empty($nre['is_special_req'])): ?>
                                            <br><span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;" title="Requiere Presupuesto Especial (> $4000 USD)"><i class="bi bi-star-fill"></i> </span>
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
                                        <?php if ($nre['status'] === 'In Process' && !empty($nre['created_at'])): ?>
                                            <?php
                                            $createdAt = new DateTime($nre['created_at']);
                                            $now = new DateTime();
                                            $daysInProcess = $now->diff($createdAt)->days;
                                            if ($daysInProcess > 30): ?>
                                                <br><span class="badge bg-danger mt-1" style="font-size: 0.65rem;" title="Más de 1 mes en proceso"><i class="bi bi-exclamation-octagon"></i> > 1 Mes</span>
                                            <?php elseif ($daysInProcess > 14): ?>
                                                <br><span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;" title="Más de 2 semanas en proceso"><i class="bi bi-exclamation-triangle"></i> > 2 Semanas</span>
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
                                                    
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#cancelModal-<?= htmlspecialchars($nre['nre_number']) ?>"
                                                            title="Cancelar NRE">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                    
                                                    <!-- Modal Cancelar -->
                                                    <div class="modal fade" id="cancelModal-<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content text-start">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-x-circle"></i> Cancelar Requerimiento
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="index.php?action=cancel">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                                        
                                                                        <div class="alert alert-warning">
                                                                            <strong>NRE:</strong> <?= htmlspecialchars($nre['nre_number']) ?><br>
                                                                            <strong>Item:</strong> <?= htmlspecialchars($nre['item_description']) ?>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Motivo de Cancelación <span class="text-danger">*</span></label>
                                                                            <textarea name="cancel_reason" class="form-control" rows="3" required placeholder="Escribe el motivo por el cual se cancela este requerimiento..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                        <button type="submit" class="btn btn-danger">
                                                                            <i class="bi bi-x-circle"></i> Confirmar Cancelación
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
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
                                                                        
                                                                        <div class="alert alert-info">
                                                                            <strong>NRE:</strong> <?= htmlspecialchars($nre['nre_number']) ?><br>
                                                                            <strong>Item:</strong> <?= htmlspecialchars($nre['item_description']) ?><br>
                                                                            <strong>Progreso:</strong> <?= $nre['quantity_received'] ?? 0 ?> / <?= $nre['quantity'] ?>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Fecha de Recepción</label>
                                                                            <input type="date" name="arrival_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Cantidad Recibida</label>
                                                                            <input type="number" name="quantity_received" class="form-control" 
                                                                                   min="1" max="<?= $nre['quantity'] - ($nre['quantity_received'] ?? 0) ?>" 
                                                                                   value="<?= $nre['quantity'] - ($nre['quantity_received'] ?? 0) ?>" required>
                                                                            <div class="form-text">Dejar el valor por defecto para recibir todo lo restante.</div>
                                                                        </div>
                                                                        <?php if ($isPackR && !empty($locations)): ?>
                                                                        <div class="mb-3 position-relative">
                                                                            <label class="form-label">Material (Para Inventario)</label>
                                                                            <input type="hidden" name="material_id" class="mat-hidden-<?= htmlspecialchars($nre['nre_number']) ?>" value="">
                                                                            <div class="typeahead-combo">
                                                                                <input type="text" class="form-control mat-search-<?= htmlspecialchars($nre['nre_number']) ?>" 
                                                                                       placeholder="Busca por ID / HWcode / descripción…" autocomplete="off" required>
                                                                                <div class="ta-panel mat-panel-<?= htmlspecialchars($nre['nre_number']) ?>" 
                                                                                     style="display:none; position:absolute; z-index:1050; background:#fff; border:1px solid #ccc; width:100%; max-height:200px; overflow-y:auto; border-radius:4px; box-shadow:0 4px 6px rgba(0,0,0,0.1);"></div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Ubicación de Almacén (Inventario)</label>
                                                                            <select name="location" class="form-select" required>
                                                                                <option value="">Seleccione ubicación...</option>
                                                                                <?php foreach ($locations as $locId => $locName): ?>
                                                                                    <option value="<?= htmlspecialchars($locId) ?>"><?= htmlspecialchars($locId . ' - ' . $locName) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                            <div class="form-text text-success"><i class="bi bi-box-seam"></i> Se agregará automáticamente al inventario.</div>
                                                                        </div>
                                                                        <?php endif; ?>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Comentarios / Notas</label>
                                                                            <textarea name="comments" class="form-control" rows="2" placeholder="Opcional"></textarea>
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
                                                    <button type="button" 
                                                            class="btn btn-outline-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#inProcessModal-<?= htmlspecialchars($nre['nre_number']) ?>"
                                                            title="Complete SAP">
                                                        <i class="bi bi-arrow-right-circle"></i>
                                                    </button>
                                                    
                                                    <!-- Modal In Process -->
                                                    <div class="modal fade" id="inProcessModal-<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content text-start">
                                                                <div class="modal-header bg-info text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-arrow-right-circle"></i> Completar SAP
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="index.php?action=mark_in_process">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                                        
                                                                        <div class="alert alert-info">
                                                                            <strong>NRE:</strong> <?= htmlspecialchars($nre['nre_number']) ?><br>
                                                                            <strong>Item:</strong> <?= htmlspecialchars($nre['item_description']) ?>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Número de requerimiento SAP <span class="text-danger">*</span></label>
                                                                            <input type="text" name="sap_number" class="form-control" required placeholder="Ej. 123456789">
                                                                            <div class="form-text">Por favor ingrese el número generado por SAP.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                        <button type="submit" class="btn btn-info text-white">
                                                                            <i class="bi bi-check-circle"></i> Confirmar
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#cancelModal-<?= htmlspecialchars($nre['nre_number']) ?>"
                                                            title="Cancelar NRE">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                    
                                                    <!-- Modal Cancelar -->
                                                    <div class="modal fade" id="cancelModal-<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content text-start">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-x-circle"></i> Cancelar Requerimiento
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="index.php?action=cancel">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                                        
                                                                        <div class="alert alert-warning">
                                                                            <strong>NRE:</strong> <?= htmlspecialchars($nre['nre_number']) ?><br>
                                                                            <strong>Item:</strong> <?= htmlspecialchars($nre['item_description']) ?>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Motivo de Cancelación <span class="text-danger">*</span></label>
                                                                            <textarea name="cancel_reason" class="form-control" rows="3" required placeholder="Escribe el motivo por el cual se cancela este requerimiento..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Está completamente seguro de que desea cancelar este requerimiento? Esta acción no se puede deshacer.');">
                                                                            <i class="bi bi-x-circle"></i> Confirmar Cancelación
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (isset($isSuperAdmin) && $isSuperAdmin): ?>
                                                    <!-- Super Admin: Reasignar -->
                                                    <button type="button" 
                                                            class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#reassignModal-<?= htmlspecialchars($nre['nre_number']) ?>"
                                                            title="Reasignar Requerimiento">
                                                        <i class="bi bi-person-lines-fill"></i>
                                                    </button>
                                                    
                                                    <!-- Super Admin: Eliminar -->
                                                    <button type="button" 
                                                            class="btn btn-outline-dark" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteNreModal-<?= htmlspecialchars($nre['nre_number']) ?>"
                                                            title="Eliminar Requerimiento Físicamente">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>

                                                    <!-- Modal Reasignar -->
                                                    <div class="modal fade" id="reassignModal-<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content text-start">
                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-person-lines-fill"></i> Reasignar Requerimiento
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="index.php?action=reassign_nre">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                                        <div class="alert alert-info">
                                                                            <strong>NRE:</strong> <?= htmlspecialchars($nre['nre_number']) ?><br>
                                                                            <strong>Titular Actual:</strong> <?= htmlspecialchars($nre['requester_name'] ?? 'Desconocido') ?>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Nuevo Usuario <span class="text-danger">*</span></label>
                                                                            <select name="new_requester_id" class="form-select" required>
                                                                                <option value="">Seleccione un usuario...</option>
                                                                                <?php foreach ($allUsersForReassign as $userOption): ?>
                                                                                    <option value="<?= $userOption['id'] ?>"><?= htmlspecialchars($userOption['full_name']) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                        <button type="submit" class="btn btn-primary" onclick="return confirm('¿Confirma que desea reasignar el NRE <?= htmlspecialchars($nre['nre_number']) ?> a este usuario?');">
                                                                            <i class="bi bi-person-check-fill"></i> Reasignar
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Modal Eliminar -->
                                                    <div class="modal fade" id="deleteNreModal-<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content text-start">
                                                                <div class="modal-header bg-dark text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-trash-fill"></i> Eliminar Requerimiento
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST" action="index.php?action=delete_nre">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                                        <div class="alert alert-danger">
                                                                            <strong>¡ATENCIÓN SUPER ADMIN!</strong><br>
                                                                            Estás a punto de eliminar el NRE <strong><?= htmlspecialchars($nre['nre_number']) ?></strong> de la base de datos permanentemente.<br>
                                                                            Esta acción destruirá todos los registros vinculados y no podrá deshacerse.
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('PELIGRO: ¿Está completa y absolutamente seguro de eliminar permanentemente el requerimiento <?= htmlspecialchars($nre['nre_number']) ?>?');">
                                                                            <i class="bi bi-trash-fill"></i> Confirmo la eliminación permanente
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
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
                        <small class="text-muted">Página <?= $page ?> de <?= $totalPages ?></small>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Previous -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <?php
                                $prevParams = $urlParams;
                                $prevParams['page'] = $page - 1;
                                ?>
                                <a class="page-link" href="index.php?<?= http_build_query($prevParams) ?>"><i class="bi bi-chevron-left"></i></a>
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
                                    <a class="page-link" href="index.php?<?= http_build_query($pgParams) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <?php
                                $nextParams = $urlParams;
                                $nextParams['page'] = $page + 1;
                                ?>
                                <a class="page-link" href="index.php?<?= http_build_query($nextParams) ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
    </div>
<script>
const materialsData = <?= json_encode($materials ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
document.addEventListener('DOMContentLoaded', function(){
    const matData = (materialsData || []).map(m => Object.assign({__key: String(m.id)}, m));
    
    function highlight(txt, q){
        if (!q) return txt;
        try {
            const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\$&') + ')', 'ig');
            return txt.replace(re, '<strong>$1</strong>');
        } catch { return txt; }
    }
    
    function materialLabel(m){ return `${m.id} — ${m.HWcode ? (m.HWcode + ' — ') : ''}${m.descripcion || ''}`; }
    function matMatch(m, s){
        return String(m.id).toLowerCase().includes(s)
            || String(m.HWcode || '').toLowerCase().includes(s)
            || String(m.descripcion || '').toLowerCase().includes(s);
    }
    
    // Attach to all modals
    document.querySelectorAll('.typeahead-combo').forEach(combo => {
        const input = combo.querySelector('input[type="text"]');
        const hidden = combo.previousElementSibling;
        const panel = combo.querySelector('.ta-panel');
        if(!input || !hidden || !panel) return;
        
        function setMaterialById(id){
            hidden.value = id || '';
            const m = matData.find(x => String(x.id) === String(id));
            input.value = m ? materialLabel(m) : (id ? id : '');
        }

        function render(items, q){
            panel.innerHTML = '';
            if (!items.length){ panel.style.display = 'none'; active=-1; return; }
            items.slice(0,50).forEach((item) => {
                const div = document.createElement('div');
                div.className = 'p-2 border-bottom cursor-pointer text-dark';
                div.style.cursor = 'pointer';
                div.style.fontSize = '0.9rem';
                div.setAttribute('role','option');
                div.dataset.key = item.__key || '';
                div.innerHTML = highlight(materialLabel(item), q);
                
                div.addEventListener('mouseover', () => {
                    Array.from(panel.children).forEach(c => c.style.backgroundColor = '');
                    div.style.backgroundColor = '#f8f9fa';
                });
                
                div.addEventListener('mousedown', (ev) => { 
                    setMaterialById(item.id); 
                    panel.style.display = 'none';
                    ev.preventDefault(); 
                });
                panel.appendChild(div);
            });
            panel.style.display = 'block';
            active = -1;
        }

        function filter(q){
            const s = q.trim().toLowerCase();
            if (!s) return [];
            return matData.filter(item => matMatch(item, s));
        }
        let active = -1;

        input.addEventListener('input', function(){
            render(filter(input.value), input.value);
            hidden.value = ''; // clear hidden if they start typing again
        });
        input.addEventListener('focus', function(){
            if (input.value.trim()){ render(filter(input.value), input.value); }
        });
        input.addEventListener('blur', function(){
            setTimeout(()=>{ panel.style.display='none'; }, 200);
            // Si el usuario escribió un ID exacto
            const rawM = (input.value || '').trim();
            const exactM = matData.find(m => String(m.id) === rawM);
            if (exactM) setMaterialById(exactM.id);
        });
    });
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>