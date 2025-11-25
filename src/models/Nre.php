<?php
// src/models/Nre.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ExchangeRate.php';

class Nre {
    private $id;
    private $nreNumber;
    private $requesterId;
    private $itemDescription;
    private $itemCode;
    private $projectCode;
    private $department;
    private $quantity;
    private $unitPriceUsd;
    private $neededDate;
    private $reason;
    private $quotationFilename;
    private $status;
    private $approvalSignedDate;
    private $actualArrivalDate;

    // Getters
    public function getId() { return $this->id; }
    public function getNreNumber() { return $this->nreNumber; }
    public function getRequesterId() { return $this->requesterId; }
    public function getItemDescription() { return $this->itemDescription; }
    public function getItemCode() { return $this->itemCode; }
    public function getProjectCode() { return $this->projectCode; }
    public function getDepartment() { return $this->department; }
    public function getQuantity() { return $this->quantity; }
    public function getUnitPriceUsd() { return $this->unitPriceUsd; }
    public function getNeededDate() { return $this->neededDate; }
    public function getReason() { return $this->reason; }
    public function getQuotationFilename() { return $this->quotationFilename; }
    public function getStatus() { return $this->status; }
    public function getApprovalSignedDate() { return $this->approvalSignedDate; }
    public function getActualArrivalDate() { return $this->actualArrivalDate; }

    /**
     * Crea un nuevo NRE en la base de datos.
     *
     * @param array $data Campos: requester_id, item_description, item_code, project_code, department,
     *                    quantity, unit_price_usd, needed_date, reason, quotation_temp_path
     * @return Nre|null
     */
        public static function create(array $data): ?self {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Validar datos mínimos
        if (!isset($data['requester_id'], $data['item_description'], $data['quantity'], $data['unit_price_usd'], $data['needed_date'], $data['quotation_temp_path'])) {
            error_log("[NRE] Missing required fields");
            return null;
        }

        // Obtener tipo de cambio del mes anterior
        $exchangeRate = ExchangeRate::getRateForPreviousMonth();
        if ($exchangeRate === null) {
            error_log("[NRE] No exchange rate available for previous month");
            return null;
        }

        // Calcular precios en MXN
        $unitPriceMxn = round($data['unit_price_usd'] / $exchangeRate, 2);

        // Generar número único de NRE
        $nreNumber = self::generateNreNumber($conn);

        // Subir cotización
        $quotationPath = self::handleQuotationUpload($data['quotation_temp_path']);
        if (!$quotationPath) {
            return null;
        }

        // Insertar en base de datos con nuevos campos
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
            $quotationPath
        );

        if (!$stmt->execute()) {
            error_log("[NRE] DB insert failed: " . $stmt->error);
            return null;
        }

        $newId = $stmt->insert_id;
        return new self($newId);
    }

    private static function generateNreNumber($conn): string {
        $datePart = date('Ymd'); // Ej. 20251125
        // Buscar último número del día
        $stmt = $conn->prepare("
            SELECT nre_number FROM nres 
            WHERE nre_number LIKE ? 
            ORDER BY id DESC LIMIT 1
        ");
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

    private static function handleQuotationUpload(string $tmpPath): ?string {
        if (!is_uploaded_file($tmpPath)) {
            error_log("[NRE] Invalid upload file");
            return null;
        }

        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['quotation']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            error_log("[NRE] Invalid file type: $ext");
            return null;
        }

        $safeName = uniqid('quot_', true) . '.' . $ext;
        $uploadDir = __DIR__ . '/../../uploads/quotations/';
        $fullPath = $uploadDir . $safeName;

        if (!move_uploaded_file($tmpPath, $fullPath)) {
            error_log("[NRE] Failed to move uploaded file");
            return null;
        }

        return $safeName;
    }

    // Cargar NRE por ID
    public function __construct(int $id) {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            SELECT * FROM nres WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->id = $row['id'];
            $this->nreNumber = $row['nre_number'];
            $this->requesterId = $row['requester_id'];
            $this->itemDescription = $row['item_description'];
            $this->itemCode = $row['item_code'];
            $this->projectCode = $row['project_code'];
            $this->department = $row['department'];
            $this->quantity = $row['quantity'];
            $this->unitPriceUsd = $row['unit_price_usd'];
            $this->neededDate = $row['needed_date'];
            $this->reason = $row['reason'];
            $this->quotationFilename = $row['quotation_filename'];
            $this->status = $row['status'];
            $this->approvalSignedDate = $row['approval_signed_date'];
            $this->actualArrivalDate = $row['actual_arrival_date'];
        } else {
            throw new Exception("NRE not found");
        }
    }

    // Métodos para transiciones de estado
    public function approve(string $signedDate): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            UPDATE nres 
            SET status = 'Approved', approval_signed_date = ? 
            WHERE id = ? AND status = 'Draft'
        ");
        $stmt->bind_param("si", $signedDate, $this->id);
        return $stmt->execute();
    }

    public function markAsArrived(): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            UPDATE nres 
            SET status = 'Arrived', actual_arrival_date = CURDATE() 
            WHERE id = ? AND status IN ('Approved', 'In Process')
        ");
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function cancel(): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            UPDATE nres 
            SET status = 'Cancelled' 
            WHERE id = ? AND status != 'Arrived'
        ");
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    // Obtener ruta absoluta de cotización
    public function getQuotationFullPath(): string {
        return __DIR__ . '/../../uploads/quotations/' . $this->quotationFilename;
    }
}