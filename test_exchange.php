<?php
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/models/ExchangeRate.php';

$exchangeRateModel = new ExchangeRate();

// 1. Verificar que getLastMonthPeriod() devuelve '202510' (asumiendo hoy es noviembre 2025)
$lastMonth = $exchangeRateModel->getLastMonthPeriod();
echo "Período del mes anterior: $lastMonth\n";

// 2. Obtener el tipo de cambio para ese período
$rate = $exchangeRateModel->getRateForPeriod($lastMonth);
if ($rate !== null) {
    echo "Tipo de cambio MXN/USD: $rate\n";
} else {
    echo "❌ No se encontró tipo de cambio para $lastMonth\n";
}