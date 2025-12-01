<?php
// tests/manual_test_nre_flow.php
// Script para validar el flujo de creación de NRE manualmente

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/controllers/NreController.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Nre.php';

// Configurar entorno simulado
session_start();
$_SESSION['user_id'] = 1; // Asumimos usuario ID 1 (Jesus Muro)

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         TEST MANUAL DE FLUJO DE CREACIÓN DE NRE             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

try {
    // 1. Crear archivo temporal de prueba
    $tempDir = __DIR__ . '/../uploads/temp';
    if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
    
    $testFile = $tempDir . '/test_quotation.txt';
    file_put_contents($testFile, "Contenido de prueba para cotización NRE.");
    echo "✅ Archivo temporal creado: $testFile\n";
    
    // 2. Preparar datos de prueba
    $items = [
        [
            'item_description' => 'Test Item ' . date('H:i:s'),
            'item_code' => 'TEST-001',
            'quantity' => 2,
            'price_amount' => 100.50,
            'price_currency' => 'USD',
            'operation' => 'IT',
            'reason' => 'Prueba automatizada de flujo'
        ]
    ];
    
    // Generar número NRE
    $nreNumber = Nre::generateNextNreNumber();
    $_SESSION['nre_nre_numbers'] = [$nreNumber];
    echo "✅ Número NRE generado: $nreNumber\n";
    
    // 3. Instanciar controlador
    $controller = new NreController();
    
    // 4. Ejecutar creación
    echo "🔄 Ejecutando NreController::createFromForm...\n";
    $result = $controller->createFromForm($items, [$testFile], $_SESSION['user_id']);
    
    if ($result) {
        echo "\n✅ ÉXITO: El NRE fue creado y el correo enviado (simulado o real).\n";
        
        // Verificar en BD
        $nreModel = new Nre();
        $nre = $nreModel->getByNumber($nreNumber);
        if ($nre) {
            echo "✅ Verificación BD: Registro encontrado.\n";
            echo "   ID: " . $nre['id'] . "\n";
            echo "   Status: " . $nre['status'] . "\n";
            echo "   Archivo: " . $nre['quotation_filename'] . "\n";
            
            // Verificar archivo final
            $finalPath = __DIR__ . '/../uploads/quotations/' . $nre['quotation_filename'];
            if (file_exists($finalPath)) {
                echo "✅ Verificación Archivo: El archivo se movió correctamente a uploads/quotations.\n";
            } else {
                echo "❌ ERROR: El archivo final no existe en $finalPath\n";
            }
        } else {
            echo "❌ ERROR: No se encontró el registro en la BD.\n";
        }
        
    } else {
        echo "\n❌ FALLO: El controlador devolvió false.\n";
        echo "   Revisa los logs de error de PHP/Apache.\n";
    }

} catch (Exception $e) {
    echo "\n❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "\n🏁 Test finalizado.\n";
