<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis NREs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-draft { color: #6c757d; }
        .status-approved { color: #198754; }
        .status-inprocess { color: #0d6efd; }
        .status-arrived { color: #6f42c1; }
        .status-cancelled { color: #dc3545; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mis NREs</h2>
        <div class="d-flex gap-2">
            <a href="/requiem/public/?action=new" class="btn btn-primary">+ Nuevo NRE</a>
            <?php if (!isset($_GET['show_completed'])): ?>
                <a href="/requiem/public/?show_completed=1" class="btn btn-outline-secondary">Ver Completados</a>
            <?php else: ?>
                <a href="/requiem/public/" class="btn btn-outline-secondary">Ocultar Completados</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($nres)): ?>
        <div class="alert alert-info">No tienes NREs en este estado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
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
                            <td><?= htmlspecialchars($nre['nre_number']) ?></td>
                            <td><?= htmlspecialchars($nre['item_description']) ?></td>
                            <td><?= htmlspecialchars($nre['item_code'] ?? '') ?></td>
                            <td><?= (int)$nre['quantity'] ?></td>
                            <td><?= htmlspecialchars($nre['customizer'] ?? '') ?></td>
                            <td><?= htmlspecialchars($nre['operation'] ?? '') ?></td>
                            <td>
                                <?php
                                $statusClass = match($nre['status']) {
                                    'Draft' => 'status-draft',
                                    'Approved' => 'status-approved',
                                    'In Process' => 'status-inprocess',
                                    'Arrived' => 'status-arrived',
                                    'Cancelled' => 'status-cancelled',
                                    default => ''
                                };
                                ?>
                                <span class="<?= $statusClass ?>"><?= htmlspecialchars($nre['status']) ?></span>
                            </td>
                            <td><?= $nre['created_at'] ? date('d/m/Y', strtotime($nre['created_at'])) : '' ?></td>
                            <td><?= $nre['arrival_date'] ? date('d/m/Y', strtotime($nre['arrival_date'])) : '—' ?></td>
                            <td>$<?= number_format((float)($nre['unit_price_mxn'] * $nre['quantity']), 2) ?></td>
                            <td>
                                <?php if ($nre['status'] === 'In Process'): ?>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#arrivalModal<?= htmlspecialchars($nre['nre_number']) ?>">
                                        Finalizar
                                    </button>
                                    <div class="modal fade" id="arrivalModal<?= htmlspecialchars($nre['nre_number']) ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Registrar recepción</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="/requiem/public/index.php?action=mark_arrived">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                                        <label class="form-label">Fecha de recepción</label>
                                                        <input type="date" name="arrival_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-success">Confirmar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif (in_array($nre['status'], ['Draft', 'Approved'])): ?>
                                    <form method="POST" action="/requiem/public/index.php?action=mark_in_process" style="display:inline;" class="d-inline">
                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"
                                                onclick="return confirm('¿Confirmar que ya está en SAP?');">
                                            En SAP
                                        </button>
                                    </form>
                                    <form method="POST" action="/requiem/public/index.php?action=cancel" style="display:inline;" class="d-inline">
                                        <input type="hidden" name="nre_number" value="<?= htmlspecialchars($nre['nre_number']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('¿Cancelar este NRE?');">
                                            Cancelar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>