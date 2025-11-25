<?php
// src/models/ExchangeRate.php

require_once __DIR__ . '/../config/db.php';

class ExchangeRate {
    /**
     * Obtiene el tipo de cambio USD → MXN para el mes anterior (relativo a hoy).
     * Ejemplo: si hoy es 26/nov/2025, devuelve el tipo de cambio para octubre 2025.
     *
     * @return float|null
     */
    public static function getRateForPreviousMonth(): ?float {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Primer día del mes anterior
        $firstDayOfPrevMonth = date('Y-m-01', strtotime('first day of last month'));

        $stmt = $conn->prepare("
            SELECT usd_to_mxn 
            FROM exchange_rates 
            WHERE month_year = ?
        ");
        $stmt->bind_param("s", $firstDayOfPrevMonth);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return (float)$row['usd_to_mxn'];
        }

        error_log("[ExchangeRate] No rate found for month: $firstDayOfPrevMonth");
        return null;
    }

    /**
     * Guarda o actualiza un tipo de cambio. 
     * Nota: el valor ya debe estar en formato USD → MXN (ej. 18.35).
     *
     * @param string $yearMonth (formato 'YYYY-MM')
     * @param float $usdToMxn
     * @return bool
     */
    public static function saveRate(string $yearMonth, float $usdToMxn): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $firstDay = $yearMonth . '-01';

        $stmt = $conn->prepare("
            INSERT INTO exchange_rates (month_year, usd_to_mxn)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE usd_to_mxn = ?
        ");
        $stmt->bind_param("sdd", $firstDay, $usdToMxn, $usdToMxn);
        return $stmt->execute();
    }
}