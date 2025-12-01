<?php
// src/controllers/NreListController.php

require_once __DIR__ . '/../models/Nre.php';
require_once __DIR__ . '/../config/db.php';

class NreListController {
    private Nre $nreModel;

    public function __construct() {
        $this->nreModel = new Nre();
    }

    public function listNres(int $userId, bool $isAdmin, bool $includeCompleted = false): array {
        $statuses = $includeCompleted 
            ? ['Draft','Approved','In Process','Arrived','Cancelled']
            : ['Draft','Approved','In Process'];
        
        $placeholders = str_repeat('?,', count($statuses) - 1) . '?';
        
        $database = Database::getInstance();
        $db = $database->getConnection();

        if ($isAdmin) {
            // Admin ve todo
            $sql = "SELECT n.*, u.full_name as requester_name 
                    FROM nres n
                    LEFT JOIN users u ON n.requester_id = u.id
                    WHERE n.status IN ($placeholders)
                    ORDER BY n.created_at DESC";
            $stmt = $db->prepare($sql);
            $types = str_repeat('s', count($statuses));
            $stmt->bind_param($types, ...$statuses);
        } else {
            // Engineer solo ve lo suyo
            $sql = "SELECT n.*, u.full_name as requester_name 
                    FROM nres n
                    LEFT JOIN users u ON n.requester_id = u.id
                    WHERE n.requester_id = ? AND n.status IN ($placeholders)
                    ORDER BY n.created_at DESC";
            $stmt = $db->prepare($sql);
            // Agregar userId al inicio de params
            $params = array_merge([$userId], $statuses);
            $types = 'i' . str_repeat('s', count($statuses));
            $stmt->bind_param($types, ...$params);
        }
        
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