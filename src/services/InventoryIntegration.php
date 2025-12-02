<?php
// src/services/InventoryIntegration.php

class InventoryIntegration {
    private $pdo;
    
    public function __construct() {
        // Configuración hardcoded basada en la info del usuario
        // Idealmente esto iría en un archivo de config separado
        $host = 'localhost';
        $dbname = 'almacen';
        $username = 'jmuro';
        $password = 'Monday.03';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

        try {
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Si falla la conexión, logueamos pero no detenemos todo el proceso si no es crítico
            // O lanzamos excepción si es crítico.
            error_log("Error conectando a DB Almacen: " . $e->getMessage());
            throw new Exception("No se pudo conectar al sistema de inventario.");
        }
    }
    
    /**
     * Obtiene todas las localidades disponibles
     */
    public function getLocalidades(): array {
        $stmt = $this->pdo->query("SELECT nombre FROM localidades ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Retorna la conexión PDO (para uso en tests)
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Verifica si un SKU existe en el inventario
     */
    public function getProductBySku(string $sku): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM inventario WHERE codigo_sku = ? LIMIT 1");
        $stmt->execute([$sku]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Registra una entrada de inventario (siempre en localidad DE_PASO)
     */
    public function registerInbound(string $sku, int $quantity, string $location, int $userId): bool {
        try {
            $this->pdo->beginTransaction();
            
            // IMPORTANTE: Todo el material se ingresa a DE_PASO independientemente del parámetro
            $targetLocation = 'DE_PASO';
            
            // 1. Buscar el producto específicamente en la localidad DE_PASO
            $stmt = $this->pdo->prepare("
                SELECT * FROM inventario 
                WHERE codigo_sku = ? AND localidad = ?
                LIMIT 1
            ");
            $stmt->execute([$sku, $targetLocation]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                // Si no existe en DE_PASO, verificar si existe en otra localidad
                $stmt = $this->pdo->prepare("
                    SELECT * FROM inventario 
                    WHERE codigo_sku = ?
                    LIMIT 1
                ");
                $stmt->execute([$sku]);
                $productAnyLocation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($productAnyLocation) {
                    // El producto existe en otra localidad, crear registro en DE_PASO
                    $stmt = $this->pdo->prepare("
                        INSERT INTO inventario (
                            codigo_sku, descripcion, unidad, cantidad, localidad, categoria
                        ) VALUES (
                            ?, ?, ?, 0, ?, ?
                        )
                    ");
                    $stmt->execute([
                        $sku,
                        $productAnyLocation['descripcion'],
                        $productAnyLocation['unidad'],
                        $targetLocation,
                        $productAnyLocation['categoria']
                    ]);
                    $productId = $this->pdo->lastInsertId();
                    error_log("InventoryIntegration: Creado registro de '$sku' en DE_PASO (ID: $productId)");
                } else {
                    // El producto no existe en ninguna localidad
                    throw new Exception("El SKU '$sku' no existe en el sistema de inventario. Por favor, ingrese el material manualmente en el sistema de gestión de empaque antes de marcar como recibido.");
                }
            } else {
                $productId = $product['id'];
            }
            
            // 2. Registrar movimiento
            // Mapear usuario: Los IDs no coinciden entre sistemas. 
            // Usamos ID 90 (Jesus Muro) como usuario por defecto para movimientos automáticos
            $almacenUserId = 90;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO movimientos (
                    tipo_movimiento, inventario_id, cantidad_cambiada, 
                    localidad_destino, fecha_movimiento, usuario_responsable
                ) VALUES (
                    'inbound', ?, ?, ?, NOW(), ?
                )
            ");
            
            $stmt->execute([$productId, $quantity, $targetLocation, $almacenUserId]);
            
            // 3. Actualizar stock
            $stmt = $this->pdo->prepare("
                UPDATE inventario 
                SET cantidad = cantidad + ?
                WHERE id = ?
            ");
            $stmt->execute([$quantity, $productId]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error en InventoryIntegration::registerInbound: " . $e->getMessage());
            throw $e;
        }
    }
}
