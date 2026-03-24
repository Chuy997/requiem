<?php
// templates/dashboard/view.php
// Responsive view for Manager Dashboard
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="text-secondary fw-bold">Recent Requirements</h2>
        <p class="text-muted">Live overview of all purchase requests.</p>
    </div>
</div>

<!-- Desktop Table View (Hidden on mobile) -->
<div class="d-none d-md-block">
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4">NRE #</th>
                            <th>Status</th>
                            <th>Requester</th>
                            <th>Item Description</th>
                            <th>Qty</th>
                            <th>Created</th>
                            <th class="pe-4">Arrival Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nres as $nre): 
                            $statusClass = match($nre['status']) {
                                'Approved' => 'bg-success bg-opacity-10 text-success',
                                'Draft' => 'bg-secondary bg-opacity-10 text-secondary',
                                'In Process' => 'bg-primary bg-opacity-10 text-primary',
                                'Arrived' => 'bg-info bg-opacity-10 text-info',
                                'Cancelled' => 'bg-danger bg-opacity-10 text-danger',
                                default => 'bg-light text-dark'
                            };
                            $icon = match($nre['status']) {
                                'Approved' => 'bi-check-circle',
                                'Draft' => 'bi-pencil',
                                'In Process' => 'bi-hourglass-split',
                                'Arrived' => 'bi-box-seam',
                                'Cancelled' => 'bi-x-circle',
                                default => 'bi-circle'
                            };
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?= $nre['nre_number'] ?></td>
                            <td>
                                <span class="badge <?= $statusClass ?> px-3 py-2 rounded-pill">
                                    <i class="bi <?= $icon ?> me-1"></i> <?= $nre['status'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary me-2 fw-bold" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                        <?= strtoupper(substr($nre['requester_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <span class="small text-muted"><?= htmlspecialchars($nre['requester_name'] ?? 'Unknown') ?></span>
                                </div>
                            </td>
                            <td class="fw-semibold text-wrap" style="max-width: 300px;">
                                <?= htmlspecialchars($nre['item_description']) ?>
                            </td>
                            <td><?= $nre['quantity'] ?></td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($nre['created_at'])) ?></td>
                            <td class="pe-4 text-muted small">
                                <?= $nre['arrival_date'] ? date('M d, Y', strtotime($nre['arrival_date'])) : '&mdash;' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Card View (Visible only on mobile) -->
<div class="d-md-none">
    <?php foreach ($nres as $nre): 
        $statusBorder = match($nre['status']) {
            'Approved' => 'border-success',
            'In Process' => 'border-primary',
            'Arrived' => 'border-info',
            'Cancelled' => 'border-danger',
            default => 'border-secondary'
        };
        $statusText = match($nre['status']) {
            'Approved' => 'text-success',
            'In Process' => 'text-primary',
            'Arrived' => 'text-info',
            'Cancelled' => 'text-danger',
            default => 'text-secondary'
        };
    ?>
    <div class="card mb-3 shadow-sm border-0 border-start border-4 <?= $statusBorder ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-light text-dark border"><?= $nre['nre_number'] ?></span>
                <span class="fw-bold small <?= $statusText ?>"><?= $nre['status'] ?></span>
            </div>
            
            <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($nre['item_description']) ?></h5>
            
            <div class="d-flex align-items-center mb-3">
                <small class="text-muted me-2">By: <?= htmlspecialchars($nre['requester_name'] ?? 'Unknown') ?></small>
            </div>
            
            <div class="row g-2 text-muted small border-top pt-2">
                <div class="col-6">
                    <i class="bi bi-calendar3 me-1"></i> Created:<br>
                    <?= date('M d, Y', strtotime($nre['created_at'])) ?>
                </div>
                <div class="col-6">
                    <i class="bi bi-box-seam me-1"></i> Qty: <strong><?= $nre['quantity'] ?></strong>
                </div>
                <?php if ($nre['arrival_date']): ?>
                <div class="col-12 mt-2 text-info">
                    <i class="bi bi-truck me-1"></i> Arrived: <?= date('M d, Y', strtotime($nre['arrival_date'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mt-4 text-center text-muted small">
    <p>Showing latest requirements. Login to see more details.</p>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
