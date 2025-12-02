<?php
// src/controllers/NreController.php

require_once __DIR__ . '/../models/Nre.php';
require_once __DIR__ . '/../models/ExchangeRate.php';
require_once __DIR__ . '/../models/User.php'; // ← Añadido
require_once __DIR__ . '/../services/EmailService.php';

class NreController {
    private Nre $nreModel;
    private ExchangeRate $exchangeRateModel;
    private EmailService $emailService;

    public function __construct() {
        $this->nreModel = new Nre();
        $this->exchangeRateModel = new ExchangeRate();
        $this->emailService = new EmailService();
    }

    // Ahora acepta rutas de archivos temporales
    public function createFromForm(array $items, array $tempFilePaths, int $user_id = 1): bool {
        $today = new DateTime();
        $neededDate = clone $today;
        $neededDate->modify('+14 days');

        // Validar que existe el tipo de cambio del mes ACTUAL
        $currentPeriod = $this->exchangeRateModel->getCurrentMonthPeriod();
        $currentMonth = $today->format('F Y'); // Ej: "December 2025"
        $rate = $this->exchangeRateModel->getRateForPeriod($currentPeriod);
        
        if ($rate === null) {
            error_log("[NreController] Tipo de cambio no encontrado para el período actual: $currentPeriod");
            throw new \Exception("No se puede crear el NRE. El tipo de cambio para $currentMonth aún no está configurado. Por favor, configure el tipo de cambio del mes en curso antes de continuar.");
        }

        // Cargar datos del usuario para nombre y email
        try {
            $user = new User($user_id);
            $requesterName = $user->getFullName();
            $requesterEmail = $user->getEmail();
        } catch (Exception $e) {
            error_log("[NreController] Usuario $user_id no válido: " . $e->getMessage());
            return false;
        }

        // Validar límite mensual de $4000 USD para NREs
        $currentMonthlyTotal = $this->nreModel->getMonthlyTotalUsd($user_id);
        $newRequestTotalUsd = 0;
        
        foreach ($items as $item) {
            $priceAmount = (float) $item['price_amount'];
            $currency = $item['price_currency'] ?? 'USD';
            $qty = (int) ($item['quantity'] ?? 1);
            
            if ($currency === 'USD') {
                $newRequestTotalUsd += $priceAmount * $qty;
            } else {
                $newRequestTotalUsd += ($priceAmount / $rate) * $qty;
            }
        }
        
        if (($currentMonthlyTotal + $newRequestTotalUsd) > 4000) {
            $remaining = 4000 - $currentMonthlyTotal;
            throw new \Exception("Esta solicitud excede tu límite mensual de $4,000 USD para NREs. Has gastado $" . number_format($currentMonthlyTotal, 2) . " este mes. Disponible: $" . number_format(max(0, $remaining), 2));
        }

        $nreNumbers = $_SESSION['nre_nre_numbers'] ?? [];

        $savedFiles = [];
        // Procesar archivos temporales
        foreach ($tempFilePaths as $tempPath) {
            if (file_exists($tempPath)) {
                $fileName = basename($tempPath);
                // Quitar el prefijo uniqid temporal si se desea, o dejarlo
                // Aquí movemos el archivo de temp a final
                $targetPath = __DIR__ . '/../../uploads/quotations/' . $fileName;
                
                if (rename($tempPath, $targetPath)) {
                    $savedFiles[] = $targetPath;
                } else {
                    error_log("[NreController] Error al mover archivo temporal: $tempPath");
                }
            }
        }

        foreach ($items as $index => $item) {
            $nreNumber = $nreNumbers[$index] ?? Nre::generateNextNreNumber();

            $priceAmount = (float) $item['price_amount'];
            $currency = $item['price_currency'] ?? 'USD';

            if ($currency === 'USD') {
                $unitPriceUsd = $priceAmount;
                $unitPriceMxn = round($priceAmount * $rate, 2);
            } else { // MXN
                $unitPriceMxn = $priceAmount;
                $unitPriceUsd = round($priceAmount / $rate, 2);
            }

            $this->nreModel->create([
                'nre_number' => $nreNumber,
                'requester_id' => $user_id,
                'item_description' => $item['item_description'],
                'item_code' => $item['item_code'] ?? null,
                'operation' => $item['operation'] ?? null,
                'customizer' => $item['customizer'] ?? null,
                'brand' => $item['brand'] ?? null,
                'model' => $item['model'] ?? null,
                'new_or_replace' => $item['new_or_replace'] ?? 'New',
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price_usd' => $unitPriceUsd,
                'unit_price_mxn' => $unitPriceMxn,
                'needed_date' => $neededDate->format('Y-m-d'),
                'arrival_date' => null,
                'reason' => $item['reason'] ?? null,
                'quotation_filename' => !empty($savedFiles) ? basename($savedFiles[0]) : null, // Asocia el primer archivo
                'status' => 'Draft'
            ]);
        }

        // Generar correo con nombre real del creador
        $finalNreNumbers = $_SESSION['nre_nre_numbers'] ?? $nreNumbers;
        $emailBody = $this->generateEmailPreview($items, $rate, $finalNreNumbers, $requesterName);
        $subject = "Purchase Request Approval – NREs " . implode(', ', $finalNreNumbers);

        // ✅ El EmailService debe usar $requesterEmail como remitente (tu implementación actual lo soporta si está configurado)
        return $this->emailService->sendApprovalRequest($subject, $emailBody, $savedFiles, $requesterEmail);
    }

    private function generateEmailPreview(array $items, float $rate, array $nreNumbers, string $requesterName): string {
        $html = "<p>Hi Kevin,<br>Could you please approve the following purchase request(s)?<br><br>Thank you in advance for your support.<br>If you need any further information, please let me know.<br><br>Best regards,<br>" . htmlspecialchars($requesterName) . "</p>";

        $html .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
        $html .= "<thead><tr>
            <th>NRE No.</th>
            <th>Owner</th>
            <th>Request date</th>
            <th>Item</th>
            <th>Code item</th>
            <th>Application reason / Area</th>
            <th>Operation</th>
            <th>Customizer</th>
            <th>Brand</th>
            <th>Model</th>
            <th>New or replace</th>
            <th>Qty required</th>
            <th>Quotation Unit Price (MXN)</th>
            <th>Total amount (MXN)</th>
            <th>MX total + IVA</th>
            <th>Amount (USD)</th>
            <th>Total (USD)</th>
            <th>Total + IVA USD</th>
        </tr></thead><tbody>";

        $iva = 0.16;

        foreach ($items as $index => $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $priceAmount = (float) $item['price_amount'];
            $currency = $item['price_currency'] ?? 'USD';

            if ($currency === 'USD') {
                $unitUsd = $priceAmount;
                $unitMxn = round($priceAmount * $rate, 2);
            } else {
                $unitMxn = $priceAmount;
                $unitUsd = round($priceAmount / $rate, 2);
            }

            $totalMxn = round($qty * $unitMxn, 2);
            $totalUsd = round($qty * $unitUsd, 2);
            $totalMxnIva = round($totalMxn * (1 + $iva), 2);
            $totalUsdIva = round($totalUsd * (1 + $iva), 2);

            $nre = $nreNumbers[$index] ?? '—';
            $requestDate = date('m/d/Y');

            $html .= "<tr>
                <td>$nre</td>
                <td>" . htmlspecialchars($requesterName) . "</td>
                <td>$requestDate</td>
                <td>" . htmlspecialchars($item['item_description']) . "</td>
                <td>" . htmlspecialchars($item['item_code'] ?? '') . "</td>
                <td>" . htmlspecialchars($item['reason'] ?? 'All areas') . "</td>
                <td>" . htmlspecialchars($item['operation'] ?? 'All areas') . "</td>
                <td>" . htmlspecialchars($item['customizer'] ?? '') . "</td>
                <td>" . htmlspecialchars($item['brand'] ?? '') . "</td>
                <td>" . htmlspecialchars($item['model'] ?? '') . "</td>
                <td>" . htmlspecialchars($item['new_or_replace'] ?? 'New') . "</td>
                <td>$qty</td>
                <td>\$" . number_format($unitMxn, 2) . "</td>
                <td>\$" . number_format($totalMxn, 2) . "</td>
                <td>\$" . number_format($totalMxnIva, 2) . "</td>
                <td>\$" . number_format($unitUsd, 2) . "</td>
                <td>\$" . number_format($totalUsd, 2) . "</td>
                <td>\$" . number_format($totalUsdIva, 2) . "</td>
            </tr>";
        }

        $html .= "</tbody></table>";
        $html .= "<p><em>Exchange rate: 1 USD = " . number_format($rate, 4) . " MXN</em></p>";

        return $html;
    }
}