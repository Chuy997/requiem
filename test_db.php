<?php
require_once __DIR__ . '/src/config/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $result = $conn->query("SELECT 1 AS connection_ok");
    if ($result && $result->fetch_assoc()['connection_ok'] == 1) {
        echo "✅ Conexión a la base de datos exitosa.\n";
    } else {
        echo "❌ La consulta de prueba falló.\n";
    }
} catch (Exception $e) {
    echo "❌ Error al conectar: " . $e->getMessage() . "\n";
}