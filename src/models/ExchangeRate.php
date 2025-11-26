<?php
// src/models/ExchangeRate.php

class ExchangeRate {
    private $db;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
    }

    public function getRateForPeriod(string $yearMonth): ?float {
        $stmt = $this->db->prepare("SELECT rate_mxn_per_usd FROM exchange_rates WHERE period = ?");
        $stmt->bind_param('s', $yearMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? (float) $row['rate_mxn_per_usd'] : null;
    }

    public function getLastMonthPeriod(): string {
        $lastMonth = new DateTime('first day of last month');
        return $lastMonth->format('Ym'); // Ej: '202510'
    }
}