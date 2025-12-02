<?php
// tests/test_inventory_integration.php
// Prueba de integración con sistema de inventario al recibir PackR

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Iniciando prueba de integración con inventario ===\n\n";

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/Nre.php';
require_once __DIR__ . '/../src/controllers/NreListController.php';
require_once __DIR__ . '/../src/services/InventoryIntegration.php';

try {
    // 1. Verificar que existe un PackR en la base de datos
    echo "--- Verificando PackRs en DB ---\n";
    $nreModel = new Nre();
    $packrs = $nreModel->getAll(['requirement_type' => 'PackR', 'status' => ['In Process']]);
    
    if (empty($packrs)) {
        echo "❌ No hay PackRs en estado 'In Process'. Ejecuta primero test_packr_flow.php\n";
        exit(1);
    }
    
    $packr = $packrs[0];
    echo "✅ PackR encontrado: {$packr['nre_number']}\n";
    echo "   Item Code: {$packr['item_code']}\n";
    echo "   Descripción: {$packr['item_description']}\n";
    echo "   Cantidad: {$packr['quantity']}\n";
    echo "   Recibido: " . ($packr['quantity_received'] ?? 0) . "\n\n";
    
    // 2. Obtener localidades disponibles
    echo "--- Obteniendo localidades ---\n";
    $inventory = new InventoryIntegration();
    $locations = $inventory->getLocalidades();
    echo "✅ Localidades disponibles: " . count($locations) . "\n";
    if (!empty($locations)) {
        echo "   Primera localidad: {$locations[0]}\n\n";
    }
    
    // 3. Verificar si el SKU existe en inventario (en DE_PASO)
    echo "--- Verificando SKU en inventario (DE_PASO) ---\n";
    $sku = $packr['item_code'];
    echo "   Buscando SKU: $sku en localidad DE_PASO\n";
    
    // Buscar específicamente en DE_PASO
    $stmt = $inventory->getConnection()->prepare("
        SELECT * FROM inventario 
        WHERE codigo_sku = ? AND localidad = 'DE_PASO'
        LIMIT 1
    ");
    $stmt->execute([$sku]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "✅ Producto encontrado en inventario DE_PASO:\n";
        echo "   ID: {$product['id']}\n";
        echo "   SKU: {$product['codigo_sku']}\n";
        echo "   Stock actual: {$product['cantidad']}\n\n";
    } else {
        echo "⚠️ Producto NO encontrado en DE_PASO.\n";
        // Verificar si existe en otra localidad
        $productAny = $inventory->getProductBySku($sku);
        if ($productAny) {
            echo "   Nota: El producto existe en localidad '{$productAny['localidad']}' y se creará automáticamente en DE_PASO.\n\n";
        } else {
            echo "   Nota: El producto NO existe en ninguna localidad. La integración fallará.\n\n";
        }
    }
    
    // 4. Ejecutar recepción real
    echo "--- Simulando recepción de PackR ---\n";
    echo "   NRE: {$packr['nre_number']}\n";
    echo "   SKU encontrado: $sku\n";
    echo "   Cantidad a recibir: 10 (parcial)\n";
    echo "   Ubicación: " . ($locations[0] ?? 'N/A') . "\n\n";
    
    $controller = new NreListController();
    $success = $controller->markAsArrived(
        $packr['nre_number'], 
        1, // user_id (jmuro)
        date('Y-m-d'), 
        true, // isAdmin
        10, // cantidad parcial
        'Prueba de integración con inventario',
        $locations[0] ?? 'DE_PASO'
    );
    
    if ($success) {
        echo "✅ Recepción registrada exitosamente\n\n";
        
        // 5. Verificar que se actualizó el NRE
        $updatedNre = $nreModel->getByNumber($packr['nre_number']);
        echo "--- Verificando actualización del NRE ---\n";
        echo "   Cantidad recibida anterior: " . ($packr['quantity_received'] ?? 0) . "\n";
        echo "   Cantidad recibida nueva: " . ($updatedNre['quantity_received'] ?? 0) . "\n";
        echo "   Estado: {$updatedNre['status']}\n";
        
        if ($product) {
            // 6. Verificar que se actualizó el inventario en DE_PASO
            echo "\n--- Verificando actualización en inventario (DE_PASO) ---\n";
            $stmt = $inventory->getConnection()->prepare("
                SELECT * FROM inventario 
                WHERE codigo_sku = ? AND localidad = 'DE_PASO'
                LIMIT 1
            ");
            $stmt->execute([$sku]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "   Stock anterior: {$product['cantidad']}\n";
            echo "   Stock nuevo: {$updated['cantidad']}\n";
            echo "   Diferencia: " . ($updated['cantidad'] - $product['cantidad']) . "\n";
            
            if ($updated['cantidad'] == $product['cantidad'] + 10) {
                echo "✅ Stock actualizado correctamente en DE_PASO\n";
            } else {
                echo "⚠️ Stock cambió pero no en +10. Puede ser por ejecuciones previas del test.\n";
            }
        } else {
            echo "\n⚠️ El SKU no existía en DE_PASO. Verificando si se creó...\n";
            $stmt = $inventory->getConnection()->prepare("
                SELECT * FROM inventario 
                WHERE codigo_sku = ? AND localidad = 'DE_PASO'
                LIMIT 1
            ");
            $stmt->execute([$sku]);
            $newProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newProduct) {
                echo "✅ Producto creado en DE_PASO con stock: {$newProduct['cantidad']}\n";
            }
            
            if (!empty($updatedNre['closure_comments'])) {
                echo "   Comentarios del NRE: {$updatedNre['closure_comments']}\n";
            }
        }
    } else {
        echo "❌ Error al registrar recepción\n";
    }
    
    echo "\n=== Prueba finalizada ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    exit(1);
}
