<?php
// tests/test_packr_flow.php
// Prueba automatizada para el flujo de Pack Requirements

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/PackRequirement.php';
require_once __DIR__ . '/../src/controllers/PackRequirementController.php';

// Configuración de prueba
$pdfPath = __DIR__ . '/../requerimiento SAP consumibles.pdf';
$userId = 1; // Asumimos que existe el usuario con ID 1 (admin)

echo "=== Iniciando prueba de PackR ===\n";

// 1. Verificar que existe el PDF de prueba
if (!file_exists($pdfPath)) {
    die("❌ Error: No se encuentra el PDF de prueba: $pdfPath\n");
}
echo "✅ PDF de prueba encontrado\n";

// 2. Probar el parser de PDF
echo "\n--- Probando PdfParser ---\n";
try {
    $text = PdfParser::extractText($pdfPath);
    echo "📄 Texto extraído:\n" . $text . "\n-------------------\n";
    
    $data = PdfParser::parseSapPurchaseRequest($pdfPath);
    echo "✅ PDF parseado correctamente\n";
    echo "   Documento SAP: " . $data['sap_document_number'] . "\n";
    echo "   Items encontrados: " . count($data['items']) . "\n";
    
    foreach ($data['items'] as $i => $item) {
        echo "   Item " . ($i+1) . ": " . $item['item_code'] . " - " . $item['item_description'] . "\n";
        echo "      Cant: " . $item['quantity'] . ", Precio: " . $item['unit_price'] . " " . $item['currency'] . "\n";
    }
} catch (Exception $e) {
    die("❌ Error parseando PDF: " . $e->getMessage() . "\n");
}

// 3. Probar creación de PackR
echo "\n--- Probando creación de PackR ---\n";
$controller = new PackRequirementController();

// Simular upload
$fileData = [
    'tmp_name' => $pdfPath,
    'size' => filesize($pdfPath),
    'type' => 'application/pdf',
    'error' => 0
];

try {
    $result = $controller->createFromPdfUpload($fileData, $userId);
    
    if ($result['success']) {
        echo "✅ PackR creado exitosamente\n";
    } else {
        die("❌ Error creando PackR: " . $result['message'] . "\n");
    }
} catch (Exception $e) {
    die("❌ Excepción creando PackR: " . $e->getMessage() . "\n");
}

// 4. Verificar en base de datos
echo "\n--- Verificando en Base de Datos ---\n";
$model = new PackRequirement();
$packrs = $model->getAll(['sap_document' => $data['sap_document_number']]);

if (count($packrs) > 0) {
    echo "✅ Registros encontrados en DB: " . count($packrs) . "\n";
    foreach ($packrs as $p) {
        echo "   ID: " . $p['nre_number'] . " | Estado: " . $p['status'] . " | Tipo: " . $p['requirement_type'] . "\n";
        
        if ($p['status'] !== 'In Process') {
            echo "   ⚠️ Advertencia: El estado debería ser 'In Process', es '" . $p['status'] . "'\n";
        }
        
        if ($p['requirement_type'] !== 'PackR') {
            echo "   ⚠️ Advertencia: El tipo debería ser 'PackR', es '" . $p['requirement_type'] . "'\n";
        }
    }
} else {
    echo "❌ No se encontraron registros en la DB\n";
}

echo "\n=== Prueba finalizada ===\n";
