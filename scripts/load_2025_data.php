<?php
// scripts/load_2025_data.php
// Script para cargar datos históricos del 2025.xlsx

require_once __DIR__ . '/../config/database.php';

// Función para leer Excel usando PhpSpreadsheet
function loadExcelData($filePath) {
    // Verificar si existe PhpSpreadsheet
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        echo "Error: PhpSpreadsheet no está instalado.\n";
        echo "Instalar con: composer require phpoffice/phpspreadsheet\n";
        return false;
    }
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $data = [];
        $headers = [];
        $firstRow = true;
        
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            
            if ($firstRow) {
                $headers = $rowData;
                $firstRow = false;
            } else {
                if (!empty($rowData[0])) { // Solo si hay NRE number
                    $data[] = array_combine($headers, $rowData);
                }
            }
        }
        
        return $data;
    } catch (Exception $e) {
        echo "Error leyendo Excel: " . $e->getMessage() . "\n";
        return false;
    }
}

// Función para eliminar solo los requerimientos (NREs y PackR)
function deleteRequirements($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Eliminar NREs
        $stmt = $pdo->prepare("DELETE FROM nre");
        $stmt->execute();
        $nreCount = $stmt->rowCount();
        
        // Eliminar PackR si existe la tabla
        $stmt = $pdo->prepare("DELETE FROM pack_requirements");
        $stmt->execute();
        $packCount = $stmt->rowCount();
        
        $pdo->commit();
        
        echo "✅ Eliminados $nreCount NREs y $packCount PackR de la base de datos.\n";
        echo "✅ Tipos de cambio mantenidos intactos.\n";
        
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ Error eliminando requerimientos: " . $e->getMessage() . "\n";
        return false;
    }
}

// Función para determinar el estado según la información del Excel
function determineStatus($nreNumber, $statusText, $backgroundColor) {
    // Del ZL25011401 al ZL25110308 ya están completados (Arrived)
    if (preg_match('/ZL(2501|2502|2503|2504|2505|2506|2507|2508|2509|2510)\d{4}/', $nreNumber) ||
        preg_match('/ZL251103(0[1-8])$/', $nreNumber)) {
        return 'Arrived';
    }
    
    // Los que están en amarillo y dicen "approved" -> Approved
    if (stripos($statusText, 'approved') !== false) {
        return 'Approved';
    }
    
    // Los que dicen "pending" -> Draft
    if (stripos($statusText, 'pending') !== false) {
        return 'Draft';
    }
    
    // Del ZL25110309 al ZL25120106 están abiertos
    // Si llegamos aquí, determinar por el número
    if (preg_match('/ZL2511(03(09|1\d|2\d|3\d)|04\d{2}|05\d{2}|06\d{2}|07\d{2}|08\d{2}|09\d{2}|10\d{2}|11\d{2}|12\d{2})/', $nreNumber) ||
        preg_match('/ZL2512(01(0[1-6]))/', $nreNumber)) {
        return 'In Process';
    }
    
    return 'Draft';
}

// Función para insertar NRE en la base de datos
function insertNre($pdo, $nreData, $userId = 1) {
    try {
        $sql = "INSERT INTO nre (
            nre_number, requester_id, item_description, item_code, operation,
            customizer, brand, model, new_or_replace, quantity,
            unit_price_usd, unit_price_mxn, needed_date, arrival_date,
            reason, status, sap_document_number, department, project,
            requirement_type, created_at, updated_at
        ) VALUES (
            :nre_number, :requester_id, :item_description, :item_code, :operation,
            :customizer, :brand, :model, :new_or_replace, :quantity,
            :unit_price_usd, :unit_price_mxn, :needed_date, :arrival_date,
            :reason, :status, :sap_document_number, :department, :project,
            :requirement_type, :created_at, :updated_at
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nre_number' => $nreData['nre_number'],
            ':requester_id' => $userId,
            ':item_description' => $nreData['item_description'] ?? '',
            ':item_code' => $nreData['item_code'] ?? '',
            ':operation' => $nreData['operation'] ?? '',
            ':customizer' => $nreData['customizer'] ?? '',
            ':brand' => $nreData['brand'] ?? '',
            ':model' => $nreData['model'] ?? '',
            ':new_or_replace' => $nreData['new_or_replace'] ?? 'New',
            ':quantity' => $nreData['quantity'] ?? 1,
            ':unit_price_usd' => $nreData['unit_price_usd'] ?? 0,
            ':unit_price_mxn' => $nreData['unit_price_mxn'] ?? 0,
            ':needed_date' => $nreData['needed_date'] ?? null,
            ':arrival_date' => $nreData['arrival_date'] ?? null,
            ':reason' => $nreData['reason'] ?? '',
            ':status' => $nreData['status'],
            ':sap_document_number' => $nreData['sap_document_number'] ?? null,
            ':department' => $nreData['department'] ?? '',
            ':project' => $nreData['project'] ?? '',
            ':requirement_type' => 'NRE',
            ':created_at' => $nreData['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    } catch (Exception $e) {
        echo "❌ Error insertando NRE {$nreData['nre_number']}: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main execution
echo "=== Carga de datos desde 2025.xlsx ===\n\n";

$excelFile = __DIR__ . '/../2025.xlsx';

if (!file_exists($excelFile)) {
    die("❌ Error: No se encuentra el archivo 2025.xlsx\n");
}

// Conectar a la base de datos
try {
    $pdo = getDbConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("❌ Error conectando a la base de datos: " . $e->getMessage() . "\n");
}

// Paso 1: Eliminar requerimientos existentes
echo "Paso 1: Eliminando requerimientos existentes...\n";
if (!deleteRequirements($pdo)) {
    die("❌ Error en la eliminación. Proceso abortado.\n");
}

echo "\nPaso 2: Leyendo archivo Excel...\n";

// Intentar con SimpleXLSX primero (más ligero)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Por ahora, vamos a crear un script manual para probar
// El usuario necesitará instalar PhpSpreadsheet o proporcionar los datos en otro formato

echo "\n⚠️  Para procesar el archivo Excel, necesitas instalar PhpSpreadsheet:\n";
echo "    cd /var/www/html/requiem\n";
echo "    composer require phpoffice/phpspreadsheet\n\n";

echo "Alternativamente, puedes exportar el Excel a CSV y usar otro script.\n";
echo "\n¿Deseas continuar con la carga manual de datos? (s/n)\n";
