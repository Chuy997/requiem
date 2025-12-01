<?php
// templates/nre/list.php
// Lista de NREs del usuario con header global

$pageTitle = 'Mis NREs';
include __DIR__ . '/../components/header.php';

// Verificar si el usuario puede editar (admin o creador en Draft)
$canEditNre = function($nre) use ($currentUser) {
    $nreModel = new Nre();
    return $nreModel->canEdit($nre['nre_number'], $currentUser->getId(), $currentUser->isAdmin());
};
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list-ul"></i> Mis NREs</h2>
            <div class="d-flex gap-2">
                <a href="index.php?action=new" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nuevo NRE
                </a>
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
        
        <?php if (empty($nres)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No tienes NREs en este estado.
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>NRE</th>
                                    <th>Item</th>
                                    <th>Código</th>
                                    <th>Qty</th>
                                    <th>Customizer</th>
                                    <th>Operation</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th>Fecha Arribo</th>
                                    <th>Total MXN</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nres as $nre): ?>
                                    <tr>
                                        <td>
                                            <code><?= htmlspecialchars($nre['nre_number']) ?></code>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars(substr($nre['item_description'], 0, 50)) ?><?= strlen($nre['item_description']) > 50 ? '...' : '' ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($nre['item_code'] ?? '') ?></td>
                                        <td><?= (int)$nre['quantity'] ?></td>
                                        <td>
                                            <small><?= htmlspecialchars($nre['customizer'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($nre['operation'] ?? '') ?></small>
                                        </td>
                                        <td>
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
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <?= htmlspecialchars($nre['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= $nre['created_at'] ? date('d/m/Y', strtotime($nre['created_at'])) : '' ?></small>
                                        </td>
                                        <td>
                                            <small><?= $nre['arrival_date'] ? date('d/m/Y', strtotime($nre['arrival_date'])) : '—' ?></small>
                                        </td>
                                        <td>
                                            <strong>$<?= number_format((float)($nre['unit_price_mxn'] * $nre['quantity']), 2) ?></strong>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($canEditNre($nre)): ?>
                                                    <a href="edit-nre.php?nre=<?= urlencode($nre['nre_number']) ?>" 
                                                       class="btn btn-outline-primary"
                                                       title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($nre['status'] === 'In Process'): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#arrivalModal<?= htmlspecialchars($nre['nre_number']) ?>"
                                                            title="Finalizar">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    
                                                    <!-- Modal Finalizar -->
                                                    <div class="modal fade" id="arrivalModal<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
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
                                                    
                                                <?php elseif (in_array($nre['status'], ['Draft', 'Approved'])): ?>
                                                    <form method="POST" action="index.php?action=mark_in_process" class="d-inline">
                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                        <button type="submit" 
                                                                class="btn btn-outline-info"
                                                                onclick="return confirm('¿Confirmar que ya está en SAP?');"
                                                                title="Marcar como En SAP">
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
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>