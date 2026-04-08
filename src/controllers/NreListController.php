<?php
// src/controllers/NreListController.php

require_once __DIR__ . '/../models/Nre.php';
require_once __DIR__ . '/../config/db.php';

class NreListController {
    private Nre $nreModel;

    public function __construct() {
        $this->nreModel = new Nre();
    }

    public function listNres(int $userId, bool $isAdmin, bool $includeCompleted = false, ?string $type = null, int $limit = 20, int $offset = 0): array {
        $filters = $this->buildFilters($userId, $isAdmin, $includeCompleted, $type);
        $filters['limit'] = $limit;
        $filters['offset'] = $offset;
        
        return $this->nreModel->getAll($filters);
    }

    public function getTotalNres(int $userId, bool $isAdmin, bool $includeCompleted = false, ?string $type = null): int {
        $filters = $this->buildFilters($userId, $isAdmin, $includeCompleted, $type);
        return $this->nreModel->countAll($filters);
    }

    private function buildFilters(int $userId, bool $isAdmin, bool $includeCompleted, ?string $type): array {
        $filters = [];
        
        // Filtro de estado
        if (!$includeCompleted) {
            $filters['status'] = ['Draft', 'Approved', 'In Process'];
        }
            
        // Filtro de usuario (si no es admin)
        if (!$isAdmin) {
            $filters['requester_id'] = $userId;
        }
        
        // Filtro por tipo de requerimiento
        if ($type) {
            $filters['requirement_type'] = $type;
        }

        return $filters;
    }

    public function markAsInProcess(string $nreNumber, int $userId, bool $isAdmin, string $sapNumber = ''): bool {
        return $this->nreModel->markAsInProcess($nreNumber, $userId, $isAdmin, $sapNumber);
    }

    public function cancelNre(string $nreNumber, int $userId, bool $isAdmin, string $cancelReason = ''): bool {
        return $this->nreModel->cancelNre($nreNumber, $userId, $isAdmin, $cancelReason);
    }

    public function markAsArrived(string $nreNumber, int $userId, string $arrivalDate, bool $isAdmin, int $quantityReceived = 0, string $comments = '', string $location = '', string $materialId = ''): bool {
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
                    
                    // Si el usuario seleccionó un material explícito desde el dropdown, usarlo
                    $skuToUse = !empty($materialId) ? $materialId : $nre['item_code']; 
                    
                    if (empty($skuToUse)) {
                        throw new Exception("No se especificó un material válido para la integración con inventario.");
                    }
                    
                    if ($qtyToAdd > 0) {
                        $inventory->registerInbound($skuToUse, $qtyToAdd, $location, $userId);
                        error_log("InventoryIntegration: Material agregado exitosamente. SKU: $skuToUse, Cantidad: $qtyToAdd, Ubicación: $location");
                    }
                    
                } catch (Exception $e) {
                    // Capturar error de inventario y agregarlo como comentario
                    $inventoryError = "\n[ADVERTENCIA - " . date('Y-m-d H:i') . "] Integración con inventario: " . $e->getMessage();
                    error_log("Inventory Integration Error for NRE $nreNumber: " . $e->getMessage());
                    
                    // Actualizar comentarios con la advertencia
                    $this->nreModel->appendClosureComment($nreNumber, $inventoryError);
                    
                    // No fallamos la transacción principal, pero logueamos el error.
                }
            }
        }
        
        return $success;
    }

    public function deleteNre(string $nreNumber, User $currentUser): bool {
        if (!$currentUser->isSuperAdmin()) {
            throw new Exception("Acceso denegado: Se requieren privilegios de Super Administrador.");
        }
        return $this->nreModel->delete($nreNumber);
    }
    
    public function reassignNre(string $nreNumber, int $newRequesterId, User $currentUser): bool {
        if (!$currentUser->isSuperAdmin()) {
            throw new Exception("Acceso denegado: Se requieren privilegios de Super Administrador.");
        }
        return $this->nreModel->reassign($nreNumber, $newRequesterId);
    }
}