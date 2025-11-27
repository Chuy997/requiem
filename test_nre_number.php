<?php
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/models/Nre.php';

// Limpiar NREs de hoy
$database = Database::getInstance();
$db = $database->getConnection();
$db->query("DELETE FROM nres WHERE nre_number LIKE 'XY20251126%'");

$nreModel = new Nre();

// Generar e insertar un NRE falso
$firstNumber = Nre::generateNextNreNumber();
$nreModel->create([
    'nre_number' => $firstNumber,
    'requester_id' => 1,
    'item_description' => 'Prueba generación',
    'item_code' => 'TEST001',
    'operation' => 'Testing',
    'customizer' => 'Proveedor Prueba',
    'brand' => 'Marca Prueba',
    'model' => 'Modelo Prueba',
    'new_or_replace' => 'New',
    'quantity' => 1,
    'unit_price_usd' => 100.00,
    'unit_price_mxn' => 1834.79,
    'needed_date' => '2025-12-10',
    'reason' => 'Prueba',
    'quotation_filename' => '',
    'status' => 'Draft'
]);

// Generar el siguiente
$secondNumber = Nre::generateNextNreNumber();

echo "Primer NRE: $firstNumber\n";
echo "Segundo NRE: $secondNumber\n";

if ($firstNumber === 'XY2025112601' && $secondNumber === 'XY2025112602') {
    echo "✅ Generación de NRE correcta.\n";
} else {
    echo "❌ Formato inesperado.\n";
}