<?php
// src/models/ExchangeRate.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ExchangeRateHistory.php';

class ExchangeRate {
    private $connection;

    public function __construct() {
        $database = Database::getInstance();
        $this->connection = $database->getConnection();
    }

    public function getRateForPeriod(string $period): ?float {
        $stmt = $this->connection->prepare("SELECT rate_mxn_per_usd FROM exchange_rates WHERE period = ?");
        $stmt->bind_param('s', $period);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? (float) $row['rate_mxn_per_usd'] : null;
    }

    public function getLastMonthPeriod(): string {
        $lastMonth = new DateTime('first day of last month');
        return $lastMonth->format('Ym'); // Ej: '202510'
    }

    public function getAllRates(): array {
        $result = $this->connection->query("SELECT * FROM exchange_rates ORDER BY period DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateRate(string $period, float $newRate, int $userId, string $reason): bool {
        // Verificar si existe para obtener old_rate
        $oldRate = $this->getRateForPeriod($period);
        
        if ($oldRate !== null) {
            // Actualizar
            $stmt = $this->connection->prepare("UPDATE exchange_rates SET rate_mxn_per_usd = ? WHERE period = ?");
            $stmt->bind_param("ds", $newRate, $period);
        } else {
            // Insertar
            $stmt = $this->connection->prepare("INSERT INTO exchange_rates (period, rate_mxn_per_usd) VALUES (?, ?)");
            $stmt->bind_param("sd", $period, $newRate);
            $oldRate = 0.0; // Valor inicial para log
        }

        if ($stmt->execute()) {
            // Log en historial
            $history = new ExchangeRateHistory();
            $history->logChange($userId, $oldRate, $newRate, $reason);
            return true;
        }

        return false;
    }
}