<?php
// src/models/ExchangeRateHistory.php

require_once __DIR__ . '/../config/db.php';

class ExchangeRateHistory {
    private $connection;

    public function __construct() {
        $db = Database::getInstance();
        $this->connection = $db->getConnection();
    }

    public function logChange(int $userId, float $oldRate, float $newRate, string $reason): bool {
        $stmt = $this->connection->prepare("
            INSERT INTO exchange_rate_history (user_id, old_rate, new_rate, reason)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("idds", $userId, $oldRate, $newRate, $reason);
        return $stmt->execute();
    }

    public function getHistory(int $limit = 50): array {
        $stmt = $this->connection->prepare("
            SELECT h.*, u.full_name as changed_by
            FROM exchange_rate_history h
            LEFT JOIN users u ON h.user_id = u.id
            ORDER BY h.changed_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}