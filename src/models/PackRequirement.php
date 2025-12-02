<?php
// src/models/PackRequirement.php
// Modelo para Packing Requirements (Material de Empaque)

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PdfParser.php';

class PackRequirement {
    private $connection;
    
    public function __construct() {
        $this->connection = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea un PackR desde un PDF de SAP
     * El PackR se crea directamente en estado "In Process"
     */
    public function createFromPdf(string $pdfPath, int $userId): bool {
        try {
            // Validar formato del PDF
            if (!PdfParser::validateSapFormat($pdfPath)) {
                throw new Exception("El PDF no tiene el formato esperado de SAP");
            }
            
            // Parsear PDF
            $data = PdfParser::parseSapPurchaseRequest($pdfPath);
            
            if (empty($data['items'])) {
                throw new Exception("No se pudieron extraer items del PDF");
            }
            
            // Obtener información del usuario
            $userStmt = $this->connection->prepare("
                SELECT full_name, email FROM users WHERE id = ?
            ");
            $userStmt->bind_param('i', $userId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $user = $userResult->fetch_assoc();
            
            if (!$user) {
                throw new Exception("Usuario no encontrado");
            }
            
            // Validar si el documento SAP ya existe
            $checkStmt = $this->connection->prepare("SELECT COUNT(*) as count FROM nres WHERE sap_document_number = ? AND requirement_type = 'PackR'");
            $checkStmt->bind_param('s', $data['sap_document_number']);
            $checkStmt->execute();
            $result = $checkStmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception("El documento SAP " . $data['sap_document_number'] . " ya ha sido procesado anteriormente.");
            }

            // Guardar PDF en uploads
            $uploadDir = __DIR__ . '/../../uploads/packr/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $pdfFileName = 'SAP_' . $data['sap_document_number'] . '_' . time() . '.pdf';
            $pdfDestination = $uploadDir . $pdfFileName;
            
            if (!copy($pdfPath, $pdfDestination)) {
                $error = error_get_last();
                throw new Exception("Error al guardar el PDF en $pdfDestination: " . ($error['message'] ?? 'Desconocido'));
            }
            
            // Crear cada item como un registro PackR
            $this->connection->begin_transaction();
            
            try {
                foreach ($data['items'] as $item) {
                    // Generar número de PackR
                    $packrNumber = $this->generatePackRNumber();
                    
                    // Convertir precio a USD si es MXN
                    $unitPriceUsd = $item['unit_price'];
                    $unitPriceMxn = $item['unit_price'];
                    
                    if ($data['currency'] === 'MXN' || $data['currency'] === 'MXP') {
                        // Obtener tipo de cambio del mes actual
                        require_once __DIR__ . '/ExchangeRate.php';
                        $exchangeRate = new ExchangeRate();
                        $currentPeriod = $exchangeRate->getCurrentMonthPeriod();
                        $rate = $exchangeRate->getRateForPeriod($currentPeriod);
                        
                        if ($rate === null) {
                            throw new Exception("No hay tipo de cambio configurado para el mes actual");
                        }
                        
                        $unitPriceUsd = round($item['unit_price'] / $rate, 2);
                    } else {
                        // Asumir que ya está en USD
                        $rate = 20.0; // Valor por defecto
                        $unitPriceMxn = round($item['unit_price'] * $rate, 2);
                    }
                    
                    $stmt = $this->connection->prepare("
                        INSERT INTO nres (
                            requirement_type,
                            sap_document_number,
                            nre_number,
                            requester_id,
                            item_description,
                            item_code,
                            quantity,
                            unit_price_usd,
                            unit_price_mxn,
                            needed_date,
                            department,
                            project,
                            reason,
                            status,
                            quotation_filename,
                            created_at,
                            updated_at
                        ) VALUES (
                            'PackR',
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            'In Process',
                            ?,
                            NOW(),
                            NOW()
                        )
                    ");
                    
                    $reason = ($data['comments'] ?? 'Material de empaque') . ' - SAP Doc: ' . $data['sap_document_number'];
                    
                    $stmt->bind_param(
                        'ssissiddsssss',
                        $data['sap_document_number'],
                        $packrNumber,
                        $userId,
                        $item['item_description'],
                        $item['item_code'],
                        $item['quantity'],
                        $unitPriceUsd,
                        $unitPriceMxn,
                        $item['needed_date'],
                        $item['department'],
                        $item['project'],
                        $reason,
                        $pdfFileName
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error al insertar item: " . $stmt->error);
                    }
                }
                
                $this->connection->commit();
                return true;
                
            } catch (Exception $e) {
                $this->connection->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("[PackRequirement] Error creando desde PDF: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Genera un número único de PackR
     * Formato: PACKR-YYYY-XXXX
     */
    private function generatePackRNumber(): string {
        $year = date('Y');
        $prefix = "PACKR-{$year}-";
        
        // Obtener el último número del año actual
        $stmt = $this->connection->prepare("
            SELECT nre_number 
            FROM nres 
            WHERE requirement_type = 'PackR' 
              AND nre_number LIKE ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        $pattern = $prefix . '%';
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Extraer el número secuencial
            $lastNumber = (int)substr($row['nre_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obtiene todos los PackR con filtros opcionales
     */
    public function getAll(array $filters = []): array {
        $query = "
            SELECT * FROM nres 
            WHERE requirement_type = 'PackR'
        ";
        
        $params = [];
        $types = '';
        
        // Filtro por estado
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = implode(',', array_fill(0, count($filters['status']), '?'));
                $query .= " AND status IN ($placeholders)";
                foreach ($filters['status'] as $status) {
                    $params[] = $status;
                    $types .= 's';
                }
            } else {
                $query .= " AND status = ?";
                $params[] = $filters['status'];
                $types .= 's';
            }
        }
        
        // Filtro por fecha
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        // Filtro por solicitante
        if (!empty($filters['requester_id'])) {
            $query .= " AND requester_id = ?";
            $params[] = $filters['requester_id'];
            $types .= 'i';
        }
        
        // Filtro por documento SAP
        if (!empty($filters['sap_document'])) {
            $query .= " AND sap_document_number = ?";
            $params[] = $filters['sap_document'];
            $types .= 's';
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->connection->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Marca un PackR como recibido (Arrived)
     */
    public function markAsArrived(string $packrNumber, int $userId, string $arrivalDate, bool $isAdmin = false): bool {
        if ($isAdmin) {
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Arrived', arrival_date = ?, updated_at = NOW()
                WHERE nre_number = ? 
                  AND requirement_type = 'PackR'
                  AND status = 'In Process'
            ");
            
            $stmt->bind_param('ss', $arrivalDate, $packrNumber);
        } else {
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Arrived', arrival_date = ?, updated_at = NOW()
                WHERE nre_number = ? 
                  AND requester_id = ? 
                  AND requirement_type = 'PackR'
                  AND status = 'In Process'
            ");
            
            $stmt->bind_param('ssi', $arrivalDate, $packrNumber, $userId);
        }
        
        $executed = $stmt->execute();
        $affected = $stmt->affected_rows;
        
        return $executed && $affected > 0;
    }
    
    /**
     * Cancela un PackR
     */
    public function cancel(string $packrNumber, int $userId, bool $isAdmin = false): bool {
        if ($isAdmin) {
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Cancelled', updated_at = NOW()
                WHERE nre_number = ? 
                  AND requirement_type = 'PackR'
                  AND status NOT IN ('Arrived', 'Cancelled')
            ");
            
            $stmt->bind_param('s', $packrNumber);
        } else {
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Cancelled', updated_at = NOW()
                WHERE nre_number = ? 
                  AND requester_id = ? 
                  AND requirement_type = 'PackR'
                  AND status = 'In Process'
            ");
            
            $stmt->bind_param('si', $packrNumber, $userId);
        }
        
        $executed = $stmt->execute();
        $affected = $stmt->affected_rows;
        
        return $executed && $affected > 0;
    }
}
