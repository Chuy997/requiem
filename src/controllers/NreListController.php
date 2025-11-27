<?php
// src/controllers/NreListController.php

require_once __DIR__ . '/../models/Nre.php';
require_once __DIR__ . '/../config/db.php';

class NreListController {
    private Nre $nreModel;

    public function __construct() {
        $this->nreModel = new Nre();
    }

    public function listMyNres(int $requesterId, bool $includeCompleted = false): array {
        $statuses = $includeCompleted 
            ? ['Draft','Approved','In Process','Arrived','Cancelled']
            : ['Draft','Approved','In Process'];
        
        $placeholders = str_repeat('?,', count($statuses) - 1) . '?';
        $sql = "SELECT * FROM nres 
                WHERE requester_id = ? AND status IN ($placeholders)
                ORDER BY created_at DESC";
        
        $database = Database::getInstance();
        $db = $database->getConnection();
        
        $stmt = $db->prepare($sql);
        array_unshift($statuses, $requesterId); // requesterId es el primer parámetro
        $types = 'i' . str_repeat('s', count($statuses) - 1);
        $stmt->bind_param($types, ...$statuses);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function markAsInProcess(string $nreNumber, int $requesterId): bool {
        return $this->nreModel->markAsInProcess($nreNumber, $requesterId);
    }

    public function cancelNre(string $nreNumber, int $requesterId): bool {
        return $this->nreModel->cancelNre($nreNumber, $requesterId);
    }

    public function markAsArrived(string $nreNumber, int $requesterId, string $arrivalDate): bool {
        return $this->nreModel->markAsArrived($nreNumber, $requesterId, $arrivalDate);
    }
}