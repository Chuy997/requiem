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

    public function markAsArrived(string $nreNumber, int $userId, string $arrivalDate, bool $isAdmin): bool {
        return $this->nreModel->markAsArrived($nreNumber, $userId, $arrivalDate, $isAdmin);
    }
}