<?php
// src/controllers/NreListController.php

require_once __DIR__ . '/../models/Nre.php';
require_once __DIR__ . '/../config/db.php';

class NreListController {
    private Nre $nreModel;

    public function __construct() {
        $this->nreModel = new Nre();
    }

    public function listNres(int $userId, bool $isAdmin, bool $includeCompleted = false, ?string $type = null): array {
        $filters = [];
        
        // Filtro de estado
        $filters['status'] = $includeCompleted 
            ? ['Draft','Approved','In Process','Arrived','Cancelled']
            : ['Draft','Approved','In Process'];
            
        // Filtro de usuario (si no es admin)
        if (!$isAdmin) {
            $filters['requester_id'] = $userId;
        }
        
        // Filtro por tipo de requerimiento
        if ($type) {
            $filters['requirement_type'] = $type;
        }
        
        return $this->nreModel->getAll($filters);
    }

    public function markAsInProcess(string $nreNumber, int $userId, bool $isAdmin): bool {
        return $this->nreModel->markAsInProcess($nreNumber, $userId, $isAdmin);
    }

    public function cancelNre(string $nreNumber, int $userId, bool $isAdmin): bool {
        return $this->nreModel->cancelNre($nreNumber, $userId, $isAdmin);
    }

    public function markAsArrived(string $nreNumber, int $userId, string $arrivalDate, bool $isAdmin, int $quantityReceived = 0, string $comments = '', string $location = ''): bool {
        // Obtener estado previo para calcular cantidad real si se envía 0
        $nre = $this->nreModel->getByNumber($nreNumber);
        if (!$nre) return false;
        
        $qtyToAdd = $quantityReceived;
        if ($qtyToAdd <= 0) {
            $currentReceived = $nre['quantity_received'] ?? 0;
            $qtyToAdd = $nre['quantity'] - $currentReceived;
        }

        $success = $this->nreModel->markAsArrived($nreNumber, $userId, $arrivalDate, $isAdmin, $quantityReceived, $comments);
        
        if ($success) {
            // Integración con Inventario (Solo para PackR)
            if (($nre['requirement_type'] ?? 'NRE') === 'PackR' && !empty($location)) {
                require_once __DIR__ . '/../services/InventoryIntegration.php';
                try {
                    $inventory = new InventoryIntegration();
                    $sku = $nre['item_code']; 
                    
                    if ($qtyToAdd > 0) {
                        $inventory->registerInbound($sku, $qtyToAdd, $location, $userId);
                        error_log("InventoryIntegration: Material agregado exitosamente. SKU: $sku, Cantidad: $qtyToAdd, Ubicación: $location");
                    }
                    
                } catch (Exception $e) {
                    // Capturar error de inventario y agregarlo como comentario
                    $inventoryError = "\n[ADVERTENCIA - " . date('Y-m-d H:i') . "] Integración con inventario: " . $e->getMessage();
                    error_log("Inventory Integration Error for NRE $nreNumber: " . $e->getMessage());
                    
                    // Actualizar comentarios con la advertencia
                    $this->nreModel->markAsArrived($nreNumber, $userId, $arrivalDate, $isAdmin, 0, $inventoryError);
                    
                    // No fallamos la transacción principal, pero logueamos el error.
                }
            }
        }
        
        return $success;
    }
}