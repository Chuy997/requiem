<?php
// src/controllers/NreController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Nre.php';
require_once __DIR__ . '/../services/EmailService.php';

class NreController {
    /**
     * Crea múltiples NREs desde un lote y envía un solo correo con todas las cotizaciones.
     */
    public static function createBatchNre(array $formData, array $fileData, int $currentUserId): array {
        // Validar al menos un ítem
        if (empty($formData['items']) || !is_array($formData['items'])) {
            return ['success' => false, 'message' => 'Debe agregar al menos un ítem.'];
        }

        // Validar cotizaciones
        if (empty($fileData['quotations']['tmp_name']) || !is_array($fileData['quotations']['tmp_name'])) {
            return ['success' => false, 'message' => 'Al menos una cotización es requerida.'];
        }

        // Cargar solicitante
        try {
            $requester = new User($currentUserId);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Solicitante no válido.'];
        }

        if (!$requester->canCreateNre()) {
            return ['success' => false, 'message' => 'No tienes permiso para crear NREs.'];
        }

        // Guardar cotizaciones (una vez, para todo el lote)
        $savedQuotationPaths = [];
        foreach ($fileData['quotations']['tmp_name'] as $index => $tmpPath) {
            if (!is_uploaded_file($tmpPath)) continue;

            $ext = strtolower(pathinfo($fileData['quotations']['name'][$index], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) continue;

            $safeName = uniqid('quot_', true) . '.' . $ext;
            $uploadDir = __DIR__ . '/../../uploads/quotations/';
            $fullPath = $uploadDir . $safeName;

            if (move_uploaded_file($tmpPath, $fullPath)) {
                $savedQuotationPaths[] = $fullPath;
            }
        }

        if (empty($savedQuotationPaths)) {
            return ['success' => false, 'message' => 'No se pudieron guardar las cotizaciones.'];
        }

        // Crear cada NRE
        $createdNres = [];
        foreach ($formData['items'] as $itemData) {
            if (empty($itemData['item_description']) || empty($itemData['unit_price_usd'])) continue;

            $data = [
                'requester_id' => $currentUserId,
                'item_description' => $itemData['item_description'],
                'item_code' => $itemData['item_code'] ?? null,
                'project_code' => $formData['project_code'] ?? '00114',
                'department' => $formData['department'] ?? 'PRODUCTION',
                'operation' => $itemData['operation'] ?? null,
                'customizer' => null,
                'brand' => null,
                'model' => null,
                'new_or_replace' => null,
                'quantity' => (int)($itemData['quantity'] ?? 1),
                'unit_price_usd' => (float)$itemData['unit_price_usd'],
                'needed_date' => $formData['needed_date'] ?? date('Y-m-d', strtotime('+2 weeks')),
                'reason' => $formData['reason'] ?? 'All areas',
                'quotation_temp_path' => $savedQuotationPaths[0] // Usa la primera cotización para el registro
            ];

            $nre = Nre::createSingle($data, $savedQuotationPaths[0]); // Método auxiliar (ver abajo)
            if ($nre) {
                $createdNres[] = $nre;
            }
        }

        if (empty($createdNres)) {
            return ['success' => false, 'message' => 'No se creó ningún NRE.'];
        }

        // Enviar correo (solo a ti en pruebas)
        $emailService = new EmailService();
        $htmlBody = self::buildBatchEmailBody($createdNres, $requester);
        $sent = $emailService->sendNreNotification(
            $requester->getEmail(),
            $requester->getFullName(),
            ['jesus.muro@xinya-la.com'], // Solo tu correo en pruebas
            "NREs Creados – " . count($createdNres) . " ítems",
            $htmlBody,
            $savedQuotationPaths
        );

        return ['success' => true, 'message' => 'Se crearon ' . count($createdNres) . ' NREs y se envió notificación.'];
    }

    // Método auxiliar: crear un solo NRE sin subir archivo (el archivo ya está guardado)
    public static function createSingle(array $data, string $quotationFilename): ?Nre {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $exchangeRate = ExchangeRate::getRateForPreviousMonth();
        if ($exchangeRate === null) return null;

        $unitPriceMxn = round($data['unit_price_usd'] / $exchangeRate, 2);
        $nreNumber = self::generateNreNumber($conn);

        $stmt = $conn->prepare("
            INSERT INTO nres (
                nre_number, requester_id, item_description, item_code, project_code,
                department, operation, customizer, brand, model, new_or_replace,
                quantity, unit_price_usd, unit_price_mxn, needed_date,
                reason, quotation_filename, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft')
        ");

        $stmt->bind_param(
            "sissssssssssisdds",
            $nreNumber,
            $data['requester_id'],
            $data['item_description'],
            $data['item_code'] ?? null,
            $data['project_code'] ?? '00114',
            $data['department'] ?? 'PRODUCTION',
            $data['operation'] ?? null,
            $data['customizer'] ?? null,
            $data['brand'] ?? null,
            $data['model'] ?? null,
            $data['new_or_replace'] ?? null,
            $data['quantity'],
            $data['unit_price_usd'],
            $unitPriceMxn,
            $data['needed_date'],
            $data['reason'] ?? null,
            basename($quotationFilename)
        );

        if ($stmt->execute()) {
            return new Nre($stmt->insert_id);
        }
        return null;
    }

    private static function generateNreNumber($conn): string {
        $datePart = date('Ymd');
        $stmt = $conn->prepare("SELECT nre_number FROM nres WHERE nre_number LIKE ? ORDER BY id DESC LIMIT 1");
        $likePattern = "XY{$datePart}%";
        $stmt->bind_param("s", $likePattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $counter = 1;
        if ($row = $result->fetch_assoc()) {
            $lastNum = (int)substr($row['nre_number'], -2);
            $counter = $lastNum + 1;
        }
        return "XY{$datePart}" . str_pad($counter, 2, '0', STR_PAD_LEFT);
    }

    private static function buildBatchEmailBody(array $nres, User $requester): string {
        $rows = '';
        $totalUsd = 0;
        foreach ($nres as $nre) {
            $qty = $nre->getQuantity();
            $unitUsd = $nre->getUnitPriceUsd();
            $sub = $qty * $unitUsd;
            $totalUsd += $sub;
            $rows .= "
            <tr>
                <td>{$nre->getNreNumber()}</td>
                <td>{$qty}</td>
                <td>" . htmlspecialchars($nre->getItemDescription()) . "</td>
                <td>" . htmlspecialchars($nre->getItemCode() ?? 'N/A') . "</td>
                <td>\$ " . number_format($unitUsd, 2) . "</td>
                <td>\$ " . number_format($sub, 2) . "</td>
            </tr>";
        }

        return "
        <h3>Nuevos NREs Creados</h3>
        <p><strong>Solicitante:</strong> {$requester->getFullName()} ({$requester->getEmail()})</p>
        <table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; margin: 16px 0;'>
            <thead><tr>
                <th>NRE</th><th>Cant.</th><th>Descripción</th><th>Código</th><th>Precio Unit. (USD)</th><th>Subtotal (USD)</th>
            </tr></thead>
            <tbody>$rows</tbody>
        </table>
        <p><strong>Total general:</strong> \$ " . number_format($totalUsd, 2) . " USD</p>
        <p>Adjunto(s): cotización(es) del proveedor.</p>
        ";
    }
}