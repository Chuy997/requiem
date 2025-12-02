<?php
// tests/test_inventory_missing_sku.php
// Prueba de integración cuando el SKU NO existe en el inventario

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Prueba - SKU Inexistente ===\n\n";

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/Nre.php';
require_once __DIR__ . '/../src/controllers/NreListController.php';
require_once __DIR__ . '/../src/services/InventoryIntegration.php';

try {
    $nreModel = new Nre();
    $inventory = new InventoryIntegration();
    $locations = $inventory->getLocalidades();
    
    // Buscar el PackR con SKU inexistente
    $packr = $nreModel->getByNumber('PACKR-2025-0015');
    
    if (!$packr) {
        echo "❌ PackR PACKR-2025-0015 no encontrado. Ejecuta test_packr_flow.php primero.\n";
        exit(1);
    }
    
    $sku = $packr['item_code'];
    echo "PackR: {$packr['nre_number']}\n";
    echo "SKU: $sku\n";
    echo "Descripción: {$packr['item_description']}\n\n";
    
    // Verificar que NO existe en inventario
    $product = $inventory->getProductBySku($sku);
    if ($product) {
        echo "⚠️ El SKU existe en inventario. Esta prueba requiere un SKU inexistente.\n";
        exit(0);
    }
    
    echo "✅ Confirmado: SKU '$sku' NO existe en inventario.\n\n";
    
    // Intentar marcar como recibido
    echo "--- Intentando marcar como recibido ---\n";
    $controller = new NreListController();
    $success = $controller->markAsArrived(
        $packr['nre_number'], 
        1,
        date('Y-m-d'), 
        true,
        10,
        'Prueba con SKU inexistente',
        $locations[0] ?? 'DE_PASO'
    );
    
    if ($success) {
        echo "✅ Recepción procesada (se capturó el error de inventario)\n\n";
        
        // Verificar comentarios
        $updated = $nreModel->getByNumber($packr['nre_number']);
        echo "--- Verificando comentarios del NRE ---\n";
        if (!empty($updated['closure_comments'])) {
            echo "Comentarios guardados:\n";
            echo $updated['closure_comments'] . "\n\n";
            
            if (strpos($updated['closure_comments'], 'no existe en el sistema de inventario') !== false) {
                echo "✅ El mensaje de advertencia se guardó correctamente\n";
            } else {
                echo "❌ El mensaje de advertencia no se encontró en los comentarios\n";
            }
        } else {
            echo "❌ No se guardaron comentarios de advertencia\n";
        }
    } else {
        echo "❌ La recepción falló completamente\n";
    }
    
    echo "\n=== Prueba finalizada ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
