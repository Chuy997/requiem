<?php
// src/services/InventoryIntegration.php

class InventoryIntegration {
    private $pdo;
    
    public function __construct() {
        // DB Config as requested
        $host = 'localhost';
        $dbname = 'inventory_system_20251006';
        $username = 'jmuro';
        $password = 'Monday.03';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

        try {
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Error conectando a DB Inventory: " . $e->getMessage());
            throw new Exception("No se pudo conectar al sistema de inventario nuevo.");
        }
    }
    
    /**
     * Obtiene todas las localidades disponibles (ID => Nombre)
     */
    public function getLocalidades(): array {
        $stmt = $this->pdo->query("SELECT id, COALESCE(nombre,'') AS nombre FROM locations ORDER BY id ASC");
        
        $locations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $locations[$row['id']] = $row['nombre'];
        }
        
        return $locations;
    }
    
    /**
     * Obtiene el catálogo de materiales disponibles
     */
    public function getMaterials(): array {
        $stmt = $this->pdo->query("SELECT id, COALESCE(HWcode,'') AS HWcode, COALESCE(descripcion,'') AS descripcion FROM materials ORDER BY id ASC");
        $materials = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $materials[] = $row;
        }
        return $materials;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Registra una entrada de inventario en la nueva base de datos
     */
    public function registerInbound(string $materialId, int $quantity, string $locationId, int $userId): bool {
        try {
            // Verificar material
            $stmt = $this->pdo->prepare("SELECT 1 FROM materials WHERE id = ? LIMIT 1");
            $stmt->execute([$materialId]);
            if (!$stmt->fetch()) {
                throw new Exception("El material '$materialId' no existe en el catálogo principal del inventario.");
            }
            
            // Verificar localidad
            $stmt = $this->pdo->prepare("SELECT 1 FROM locations WHERE id = ? LIMIT 1");
            $stmt->execute([$locationId]);
            if (!$stmt->fetch()) {
                throw new Exception("La localidad destino '$locationId' seleccionada no existe en el sistema.");
            }
            
            $fecha = date("Y-m-d H:i:s");
            
            $this->pdo->beginTransaction();
            
            // Upsert en inventory_locations
            $stmtSel = $this->pdo->prepare("SELECT cantidad FROM inventory_locations WHERE material_id = ? AND location_id = ?");
            $stmtSel->execute([$materialId, $locationId]);
            
            if ($stmtSel->fetch()) {
                $stmtUpd = $this->pdo->prepare("UPDATE inventory_locations SET cantidad = cantidad + ? WHERE material_id = ? AND location_id = ?");
                $stmtUpd->execute([$quantity, $materialId, $locationId]);
            } else {
                $stmtIns = $this->pdo->prepare("INSERT INTO inventory_locations (material_id, location_id, cantidad) VALUES (?, ?, ?)");
                $stmtIns->execute([$materialId, $locationId, $quantity]);
            }
            
            // Movimientos
            $stmtMov = $this->pdo->prepare("
                INSERT INTO inventory_movements (material_id, tipo_movimiento, cantidad, fecha, localidad_destino)
                VALUES (?, 'entrada', ?, ?, ?)
            ");
            $stmtMov->execute([$materialId, $quantity, $fecha, $locationId]);
            
            // Historial
            $stmtHist = $this->pdo->prepare("
                INSERT INTO inventory_history (material_id, movimiento, cantidad, fecha, descripcion)
                VALUES (?, 'entrada', ?, ?, 'Entrada de material por PackR API')
            ");
            $stmtHist->execute([$materialId, $quantity, $fecha]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error en InventoryIntegration::registerInbound: " . $e->getMessage());
            throw $e;
        }
    }
}

