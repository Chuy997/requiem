<?php
// scripts/import_2025_nres.php
// Script para importar NREs desde 2025.csv

require_once __DIR__ . '/../src/config/db.php';

function parseDate($dateStr) {
    if (empty($dateStr) || $dateStr === 'N/A') {
        return null;
    }
    
    // Try different date formats
    $formats = ['m/d/Y', 'd/m/Y', 'Y-m-d', 'd/m/Y H:i:s', 'm/d/Y H:i:s'];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, trim($dateStr));
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    return null;
}

function parsePrice($priceStr) {
    if (empty($priceStr) || $priceStr === 'N/A' || $priceStr === 'pending' || strpos($priceStr, '#REF!') !== false) {
        return 0;
    }
    
    // Remove currency symbols and commas
    $cleaned = preg_replace('/[^0-9.-]/', '', $priceStr);
    return floatval($cleaned);
}

function determineStatus($nreNumber, $statusText) {
    // Los que dicen "Cancelled" -> Cancelled
    if (stripos($statusText, 'Cancelled') !== false || stripos($statusText, 'cancelled') !== false) {
        return 'Cancelled';
    }
    
    // Del ZL25011401 al ZL25110308 ya están completados (Arrived)
    if (preg_match('/^ZL250(0[1-9]|10)\d{4}$/', $nreNumber)) {
        return 'Arrived';
    }
    if (preg_match('/^ZL25110[123](0[0-8])$/', $nreNumber)) {
        return 'Arrived';
    }
    
    // Del ZL25110309 al ZL25120106 están "In Process" 
    if (preg_match('/^ZL2511(03(09|[1-3][0-9])|[0-1][0-9]\d{2})/', $nreNumber) ||
        preg_match('/^ZL2512(01(0[1-6]))$/', $nreNumber)) {
        // Los que dicen "Pending" -> Draft
        if (stripos($statusText, 'Pending') !== false || stripos($statusText, 'pending') !== false) {
            return 'Draft';
        }
        return 'In Process';
    }
    
    // Los demás approved están arrived
    if (stripos($statusText, 'Approved') !== false) {
        return 'Arrived';
    }
    
    return 'Draft';
}

function getUserIdByName($mysqli, $ownerName) {
    // Mapeo de nombres a IDs de usuario
    $nameMap = [
        'Jonas Navarro' => 2,
        'Jesus Muro' => 1,
        'Juan Mata' => 3,
        'Cesar Gutierrez' => 4
    ];
    
    return $nameMap[$ownerName] ?? 1; // Default al admin
}

echo "=== Importación de NREs desde 2025.csv ===\n\n";

// Conectar a la base de datos
try {
    $db = Database::getInstance();
    $mysqli = $db->getConnection();
} catch (Exception $e) {
    die("❌ Error conectando a la base de datos: " . $e->getMessage() . "\n");
}

// Paso 1: Eliminar solo requerimientos
echo "Paso 1: Eliminando requerimientos existentes...\n";
try {
    $mysqli->begin_transaction();
    
    $result = $mysqli->query("DELETE FROM nres");
    $nreCount = $mysqli->affected_rows;
    
    // $result = $mysqli->query("DELETE FROM pack_requirements WHERE 1=1");
    // $packCount = $mysqli->affected_rows;
    
    $mysqli->commit();
    
    echo "✅ Eliminados $nreCount NREs.\n";
    echo "✅ Tipos de cambio mantenidos intactos.\n\n";
} catch (Exception $e) {
    $mysqli->rollback();
    die("❌ Error eliminando requerimientos: " . $e->getMessage() . "\n");
}

// Paso 2: Leer CSV
echo "Paso 2: Leyendo archivo CSV...\n";
$csvFile = __DIR__ . '/../2025.csv';

if (!file_exists($csvFile)) {
    die("❌ Error: No se encuentra el archivo 2025.csv\n");
}

$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("❌ Error abriendo el archivo CSV\n");
}

// Leer encabezados
$headers = fgetcsv($handle);

$inserted = 0;
$skipped = 0;
$errors = 0;

echo "Paso 3: Insertando NREs en la base de datos...\n\n";

// Preparar statement
$sql = "INSERT INTO nres (
    nre_number, requester_id, item_description, item_code, operation,
    customizer, brand, model, new_or_replace, quantity,
    unit_price_usd, unit_price_mxn, needed_date, arrival_date,
    reason, status, department, requirement_type,
    created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("❌ Error preparando statement: " . $mysqli->error . "\n");
}

while (($row = fgetcsv($handle)) !== false) {
    // Verificar que haya un NRE number
    if (empty($row[0]) || !preg_match('/^ZL\d+$/', $row[0])) {
        $skipped++;
        continue;
    }
    
    $nreNumber = $row[0];
    $owner = $row[1] ?? '';
    $requestDate = parseDate($row[2] ?? '');
    $itemDescription = $row[3] ?? '';
    $itemCode = $row[4] ?? '';
    $reason = $row[5] ?? '';
    $operation = $row[6] ?? '';
    $customizer = $row[7] ?? '';
    $brand = $row[8] ?? '';
    $model = $row[9] ?? '';
    $statusColumn = $row[11] ?? '';
    $newOrReplace = $row[12] ?? 'New';
    $quantity = intval($row[13] ?? 1);
    $unitPriceMxn = parsePrice($row[14] ?? '0');
    $unitPriceUsd = parsePrice($row[17] ?? '0');
    $sapStatus = $row[22] ?? '';
    $arrivedDate = parseDate($row[23] ?? '');
    
    // Determinar estado
    $status = determineStatus($nreNumber, $sapStatus);
    
    // Obtener user ID
    $userId = getUserIdByName($mysqli, $owner);
    
    $createdAt = $requestDate ?? date('Y-m-d H:i:s');
    $updatedAt = date('Y-m-d H:i:s');
    $requirementType = 'NRE';
    $department = $operation;
    
    if ($quantity <= 0) $quantity = 1;
    
    try {
        $stmt->bind_param(
            'sisssssssiddssssssss',
            $nreNumber,
            $userId,
            $itemDescription,
            $itemCode,
            $operation,
            $customizer,
            $brand,
            $model,
            $newOrReplace,
            $quantity,
            $unitPriceUsd,
            $unitPriceMxn,
            $requestDate,
            $arrivedDate,
            $reason,
            $status,
            $department,
            $requirementType,
            $createdAt,
            $updatedAt
        );
        
        if ($stmt->execute()) {
            $inserted++;
            
            // Mostrar progreso cada 10 registros
            if ($inserted % 10 === 0) {
                echo "  Insertados: $inserted NREs...\n";
            }
        } else {
            $errors++;
            echo "❌ Error insertando NRE $nreNumber: " . $stmt->error . "\n";
        }
    } catch (Exception $e) {
        $errors++;
        echo "❌ Error insertando NRE $nreNumber: " . $e->getMessage() . "\n";
    }
}

$stmt->close();
fclose($handle);

echo "\n=== Resumen de importación ===\n";
echo "✅ Insertados: $inserted NREs\n";
echo "⏭️  Omitidos (sin NRE number): $skipped\n";
echo "❌ Errores: $errors\n";

// Verificar los 12 NREs abiertos
echo "\n=== Verificando NREs abiertos (ZL25110309 al ZL25120106) ===\n";

try {
    $result = $mysqli->query("
        SELECT nre_number, status, item_description 
        FROM nres 
        WHERE nre_number >= 'ZL25110309' AND nre_number <= 'ZL25120106'
        ORDER BY nre_number
    ");
    
    if ($result) {
        $openNres = $result->fetch_all(MYSQLI_ASSOC);
        
        echo "Total de NREs en rango abierto: " . count($openNres) . "\n\n";
        
        foreach ($openNres as $nre) {
            $statusIcon = $nre['status'] === 'In Process' ? '🔄' : 
                         ($nre['status'] === 'Draft' ? '📝' : '✅');
            echo "$statusIcon {$nre['nre_number']} - {$nre['status']} - " . 
                 substr($nre['item_description'], 0, 50) . "...\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error verificando NREs abiertos: " . $e->getMessage() . "\n";
}

echo "\n✅ Importación completada!\n";
