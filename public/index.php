<?php
// public/index.php

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';
require_once __DIR__ . '/../src/models/Nre.php';
require_once __DIR__ . '/../src/services/EmailService.php';
require_once __DIR__ . '/../src/controllers/NreController.php';

define('CURRENT_USER_ID', 1);

$action = $_GET['action'] ?? 'show_form';

if ($action === 'show_form') {
    include __DIR__ . '/../templates/nre/create.php';
} elseif ($action === 'preview_batch') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método no permitido.');
    }

    if (empty($_POST['items']) || empty($_FILES['quotations']['tmp_name'])) {
        $error = 'Debe incluir al menos un ítem y una cotización.';
        include __DIR__ . '/../templates/nre/create.php';
        exit;
    }

    // Guardar cotizaciones temporalmente (sin guardar en DB aún)
    $savedQuotationPaths = [];
    foreach ($_FILES['quotations']['tmp_name'] as $index => $tmpPath) {
        if (!is_uploaded_file($tmpPath)) continue;
        $ext = strtolower(pathinfo($_FILES['quotations']['name'][$index], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) continue;
        $safeName = uniqid('temp_quot_', true) . '.' . $ext;
        $fullPath = __DIR__ . '/../uploads/quotations/' . $safeName;
        if (move_uploaded_file($tmpPath, $fullPath)) {
            $savedQuotationPaths[] = $fullPath;
        }
    }

    if (empty($savedQuotationPaths)) {
        $error = 'No se pudieron procesar las cotizaciones.';
        include __DIR__ . '/../templates/nre/create.php';
        exit;
    }

    // Calcular datos para vista previa
    $requester = new User(CURRENT_USER_ID);
    $exchangeRateValue = ExchangeRate::getRateForPreviousMonth(); // USD → MXN

    if ($exchangeRateValue === null) {
        $error = 'No hay tipo de cambio disponible para el mes anterior.';
        include __DIR__ . '/../templates/nre/create.php';
        exit;
    }

    $itemsPreview = [];
    foreach ($_POST['items'] as $item) {
        if (empty($item['item_description']) || !isset($item['unit_price_usd'])) continue;

        $qty = (int)($item['quantity'] ?? 1);
        $unitUsd = (float)$item['unit_price_usd'];
        $unitMxn = round($unitUsd * $exchangeRateValue, 2);
        $totalUsd = $qty * $unitUsd;
        $totalMxn = $qty * $unitMxn;

        $itemsPreview[] = [
            'item_description' => $item['item_description'],
            'item_code' => $item['item_code'] ?? 'N/A',
            'operation' => $item['operation'] ?? 'N/A',
            'customizer' => $item['customizer'] ?? 'N/A',
            'brand' => $item['brand'] ?? 'N/A',
            'model' => $item['model'] ?? 'N/A',
            'new_or_replace' => $item['new_or_replace'] ?? 'N/A',
            'quantity' => $qty,
            'unit_price_usd' => $unitUsd,
            'unit_price_mxn' => $unitMxn,
            'total_usd' => $totalUsd,
            'total_mxn' => $totalMxn,
        ];
    }

    if (empty($itemsPreview)) {
        $error = 'No hay ítems válidos para mostrar.';
        include __DIR__ . '/../templates/nre/create.php';
        exit;
    }

    // Pasar datos a la vista de vista previa
    include __DIR__ . '/../templates/nre/preview.php';

} elseif ($action === 'send_batch_nre') {
    // Este paso se ejecuta SOLO tras confirmar la vista previa
    $items = json_decode($_POST['items_json'], true);
    $quotations = json_decode($_POST['quotations_json'], true);
    $project_code = $_POST['project_code'] ?? '00114';
    $department = $_POST['department'] ?? 'PRODUCTION';
    $reason = $_POST['reason'] ?? 'All areas';
    $needed_date = $_POST['needed_date'] ?? date('Y-m-d', strtotime('+2 weeks'));

    // Reconstruir $_FILES-like structure from paths
    $fileData = ['quotations' => ['tmp_name' => [], 'name' => []]];
    foreach ($quotations as $path) {
        $fileData['quotations']['tmp_name'][] = $path;
        $fileData['quotations']['name'][] = basename($path);
    }

    // Reconstruir $_POST-like structure
    $postData = [
        'items' => $items,
        'project_code' => $project_code,
        'department' => $department,
        'reason' => $reason,
        'needed_date' => $needed_date
    ];

    $result = NreController::createBatchNre($postData, $fileData, CURRENT_USER_ID);

    if ($result['success']) {
        echo '<div class="container mt-4"><div class="alert alert-success">' . htmlspecialchars($result['message']) . '</div>';
        echo '<a href="/requiem/public/" class="btn btn-primary">Crear otro lote</a></div>';
        // Limpiar archivos temporales si no se reutilizan
    } else {
        $error = $result['message'];
        include __DIR__ . '/../templates/nre/create.php';
    }
} else {
    http_response_code(404);
    echo 'Página no encontrada.';
}