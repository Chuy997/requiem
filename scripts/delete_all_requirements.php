<?php
/**
 * Script para eliminar TODOS los requerimientos (NREs y PackR)
 * Creado: 2026-02-04
 * Mantiene intactos: usuarios, tipos de cambio, y todos los demás datos
 */

require_once __DIR__ . '/../src/config/db.php';

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   ELIMINACIÓN DE TODOS LOS REQUERIMIENTOS - INICIO 2026      ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

try {
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✅ Conexión a la base de datos establecida.\n\n";
    
    // ================================================
    // PASO 1: Crear respaldo antes de eliminar
    // ================================================
    echo "PASO 1: Creando respaldo de seguridad...\n";
    echo str_repeat("-", 63) . "\n";
    
    $backupDir = __DIR__ . '/../backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_His');
    $backupFile = $backupDir . "/requirements_backup_$timestamp.sql";
    
    // Contar cuántos registros se van a respaldar
    $result = $conn->query("SELECT COUNT(*) as total FROM nres WHERE requirement_type = 'NRE'");
    $nreCount = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM nres WHERE requirement_type = 'PackR'");
    $packCount = $result->fetch_assoc()['total'];
    
    $totalRequirements = $nreCount + $packCount;
    
    echo "📊 Registros encontrados:\n";
    echo "   • NREs: $nreCount\n";
    echo "   • PackR: $packCount\n";
    echo "   • TOTAL: $totalRequirements\n\n";
    
    if ($totalRequirements === 0) {
        echo "⚠️  No hay requerimientos para eliminar.\n";
        echo "✅ Proceso completado.\n";
        exit(0);
    }
    
    // Crear respaldo SQL
    $backupContent = "-- Respaldo de requerimientos generado el $timestamp\n";
    $backupContent .= "-- Total de registros: $totalRequirements\n\n";
    $backupContent .= "USE requiem;\n\n";
    
    // Respaldar todos los NREs
    $result = $conn->query("SELECT * FROM nres");
    
    while ($req = $result->fetch_assoc()) {
        $columns = [];
        $values = [];
        
        foreach ($req as $key => $value) {
            $columns[] = "`$key`";
            if ($value === null) {
                $values[] = "NULL";
            } elseif (is_numeric($value)) {
                $values[] = $value;
            } else {
                $values[] = "'" . $conn->real_escape_string($value) . "'";
            }
        }
        
        $backupContent .= "INSERT INTO nres (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
    }
    
    file_put_contents($backupFile, $backupContent);
    echo "✅ Respaldo creado exitosamente:\n";
    echo "   $backupFile\n\n";
    
    // ================================================
    // PASO 2: Confirmación del usuario
    // ================================================
    echo "PASO 2: Confirmación de eliminación\n";
    echo str_repeat("-", 63) . "\n";
    echo "⚠️  ADVERTENCIA: Estás a punto de eliminar $totalRequirements requerimientos.\n";
    echo "   Esta acción NO se puede deshacer.\n";
    echo "   El respaldo está guardado en:\n";
    echo "   $backupFile\n\n";
    echo "¿Deseas continuar? Escribe 'SI' para confirmar: ";
    
    $confirmation = trim(fgets(STDIN));
    
    if (strtoupper($confirmation) !== 'SI') {
        echo "\n❌ Operación cancelada por el usuario.\n";
        echo "✅ Los datos permanecen intactos.\n";
        exit(0);
    }
    
    echo "\n";
    
    // ================================================
    // PASO 3: Eliminar todos los requerimientos
    // ================================================
    echo "PASO 3: Eliminando requerimientos...\n";
    echo str_repeat("-", 63) . "\n";
    
    $conn->begin_transaction();
    
    try {
        // Eliminar TODOS los registros de la tabla nres (incluye NRE y PackR)
        if ($conn->query("DELETE FROM nres")) {
            $deletedCount = $conn->affected_rows;
            $conn->commit();
            
            echo "✅ Eliminados exitosamente: $deletedCount requerimientos\n\n";
        } else {
            throw new Exception("Error al ejecutar DELETE: " . $conn->error);
        }
        
        // ================================================
        // PASO 4: Verificación
        // ================================================
        echo "PASO 4: Verificación del resultado\n";
        echo str_repeat("-", 63) . "\n";
        
        $result = $conn->query("SELECT COUNT(*) as total FROM nres");
        $remainingCount = $result->fetch_assoc()['total'];
        
        echo "📊 Requerimientos restantes: $remainingCount\n\n";
        
        // Verificar que otros datos están intactos
        $result = $conn->query("SELECT COUNT(*) as total FROM users");
        $usersCount = $result->fetch_assoc()['total'];
        
        $result = $conn->query("SELECT COUNT(*) as total FROM exchange_rates");
        $ratesCount = $result->fetch_assoc()['total'];
        
        echo "✅ Otros datos verificados e intactos:\n";
        echo "   • Usuarios: $usersCount\n";
        echo "   • Tipos de cambio: $ratesCount\n\n";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "❌ Error durante la eliminación: " . $e->getMessage() . "\n";
        echo "⚠️  Se ha revertido la transacción. Los datos permanecen intactos.\n";
        echo "📁 El respaldo está disponible en: $backupFile\n";
        exit(1);
    }
    
    // ================================================
    // RESUMEN FINAL
    // ================================================
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                    PROCESO COMPLETADO                         ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "📊 RESUMEN:\n";
    echo "   • Registros eliminados: $deletedCount\n";
    echo "   • Registros restantes: $remainingCount\n";
    echo "   • Respaldo guardado: $backupFile\n\n";
    echo "✅ Sistema listo para empezar 2026 sin requerimientos.\n";
    echo "🎉 ¡Proceso completado exitosamente!\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
    echo "⚠️  Por favor, contacta al administrador del sistema.\n";
    exit(1);
}
