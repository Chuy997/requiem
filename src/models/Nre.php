<?php
// src/models/Nre.php

require_once __DIR__ . '/../config/db.php';

class Nre {
    private $connection;

    public function __construct() {
        $database = Database::getInstance();
        $this->connection = $database->getConnection();
    }

    public function create(array $data): int {
        $createdAt = date('Y-m-d H:i:s');

        $stmt = $this->connection->prepare("
            INSERT INTO nres (
                nre_number, requester_id, item_description, item_code, operation,
                customizer, brand, model, new_or_replace, quantity,
                unit_price_usd, unit_price_mxn, needed_date, arrival_date, reason,
                quotation_filename, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $arrivalDate = $data['arrival_date'] ?? null;
        $quotationFilename = $data['quotation_filename'] ?? null;

        $stmt->bind_param(
            'sisssssssiddsssssss',
            $data['nre_number'],
            $data['requester_id'],
            $data['item_description'],
            $data['item_code'],
            $data['operation'],
            $data['customizer'],
            $data['brand'],
            $data['model'],
            $data['new_or_replace'],
            $data['quantity'],
            $data['unit_price_usd'],
            $data['unit_price_mxn'],
            $data['needed_date'],
            $arrivalDate,
            $data['reason'],
            $quotationFilename,
            $data['status'],
            $createdAt,
            $createdAt
        );

        if ($stmt->execute()) {
            return $this->connection->insert_id;
        } else {
            error_log("[Nre::create] DB Error: " . $this->connection->error);
            throw new \Exception("No se pudo crear el NRE.");
        }
    }

    public static function generateNextNreNumber(): string {
        $prefix = 'XY';
        $today = date('Ymd');

        $database = Database::getInstance();
        $db = $database->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) AS count FROM nres WHERE nre_number LIKE ?");
        $pattern = $prefix . $today . '%';
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextSeq = (int)$row['count'] + 1;

        return $prefix . $today . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
    }

    public static function getNextNreNumbers(int $count): array {
        $prefix = 'XY';
        $today = date('Ymd');

        $database = Database::getInstance();
        $db = $database->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) AS count FROM nres WHERE nre_number LIKE ?");
        $pattern = $prefix . $today . '%';
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $baseSeq = (int)$row['count'];

        $numbers = [];
        for ($i = 1; $i <= $count; $i++) {
            $seq = $baseSeq + $i;
            $numbers[] = $prefix . $today . str_pad($seq, 2, '0', STR_PAD_LEFT);
        }
        return $numbers;
    }

    public function getByRequester(int $requesterId): array {
        $stmt = $this->connection->prepare("
            SELECT * FROM nres 
            WHERE requester_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param('i', $requesterId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function markAsInProcess(string $nreNumber, int $requesterId, bool $isAdmin = false): bool {
        if ($isAdmin) {
            // Admin puede marcar cualquier NRE
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'In Process', updated_at = NOW()
                WHERE nre_number = ? AND status IN ('Draft', 'Approved')
            ");
            if (!$stmt) {
                error_log("Prepare failed (markAsInProcess): " . $this->connection->error);
                return false;
            }
            $stmt->bind_param('s', $nreNumber);
        } else {
            // Usuario normal solo puede marcar sus propios NREs
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'In Process', updated_at = NOW()
                WHERE nre_number = ? AND requester_id = ? AND status IN ('Draft', 'Approved')
            ");
            if (!$stmt) {
                error_log("Prepare failed (markAsInProcess): " . $this->connection->error);
                return false;
            }
            $stmt->bind_param('si', $nreNumber, $requesterId);
        }
        $executed = $stmt->execute();
        $affected = $stmt->affected_rows;
        if (!$executed) {
            error_log("Execute failed (markAsInProcess): " . $stmt->error);
        }
        return $executed && $affected > 0;
    }

    public function cancelNre(string $nreNumber, int $requesterId, bool $isAdmin = false): bool {
        if ($isAdmin) {
            // Admin puede cancelar cualquier NRE en cualquier estado excepto Arrived y Cancelled
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Cancelled', updated_at = NOW()
                WHERE nre_number = ? AND status NOT IN ('Arrived', 'Cancelled')
            ");
            if (!$stmt) {
                error_log("Prepare failed (cancelNre): " . $this->connection->error);
                return false;
            }
            $stmt->bind_param('s', $nreNumber);
        } else {
            // Usuario normal solo puede cancelar sus propios NREs en Draft o Approved
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Cancelled', updated_at = NOW()
                WHERE nre_number = ? AND requester_id = ? AND status IN ('Draft', 'Approved')
            ");
            if (!$stmt) {
                error_log("Prepare failed (cancelNre): " . $this->connection->error);
                return false;
            }
            $stmt->bind_param('si', $nreNumber, $requesterId);
        }
        $executed = $stmt->execute();
        $affected = $stmt->affected_rows;
        if (!$executed) {
            error_log("Execute failed (cancelNre): " . $stmt->error);
        }
        return $executed && $affected > 0;
    }

    public function markAsArrived(string $nreNumber, int $requesterId, string $arrivalDate, bool $isAdmin = false): bool {
        if ($isAdmin) {
            // Admin puede finalizar cualquier NRE en estado In Process
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Arrived', arrival_date = ?, updated_at = NOW()
                WHERE nre_number = ? AND status = 'In Process'
            ");
            if (!$stmt) {
                error_log("Prepare failed (markAsArrived): " . $this->connection->error);
                return false;
            }
            $stmt->bind_param('ss', $arrivalDate, $nreNumber);
        } else {
            // Usuario normal solo puede finalizar sus propios NREs
            $stmt = $this->connection->prepare("
                UPDATE nres 
                SET status = 'Arrived', arrival_date = ?, updated_at = NOW()
                WHERE nre_number = ? AND requester_id = ? AND status = 'In Process'
            ");
            if (!$stmt) {
                error_log("Prepare failed (markAsArrived): " . $this->connection->error);
                return false;
            }
            $stmt->bind_param('ssi', $arrivalDate, $nreNumber, $requesterId);
        }
        $executed = $stmt->execute();
        $affected = $stmt->affected_rows;
        if (!$executed) {
            error_log("Execute failed (markAsArrived): " . $stmt->error);
        }
        return $executed && $affected > 0;
    }

    public function getByNumber(string $nreNumber): ?array {
        $stmt = $this->connection->prepare("
            SELECT * FROM nres 
            WHERE nre_number = ?
        ");
        $stmt->bind_param('s', $nreNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function update(string $nreNumber, array $data): bool {
        $stmt = $this->connection->prepare("
            UPDATE nres 
            SET item_description = ?,
                item_code = ?,
                operation = ?,
                customizer = ?,
                brand = ?,
                model = ?,
                new_or_replace = ?,
                quantity = ?,
                unit_price_usd = ?,
                unit_price_mxn = ?,
                needed_date = ?,
                reason = ?,
                updated_at = NOW()
            WHERE nre_number = ?
        ");
        
        $stmt->bind_param(
            'sssssssiddsss',
            $data['item_description'],
            $data['item_code'],
            $data['operation'],
            $data['customizer'],
            $data['brand'],
            $data['model'],
            $data['new_or_replace'],
            $data['quantity'],
            $data['unit_price_usd'],
            $data['unit_price_mxn'],
            $data['needed_date'],
            $data['reason'],
            $nreNumber
        );
        
        return $stmt->execute();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT n.*, u.full_name as requester_name, u.email as requester_email
                FROM nres n
                LEFT JOIN users u ON n.requester_id = u.id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Filtro por tipo de requerimiento
        if (!empty($filters['requirement_type'])) {
            $sql .= " AND n.requirement_type = ?";
            $params[] = $filters['requirement_type'];
            $types .= 's';
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = str_repeat('?,', count($filters['status']) - 1) . '?';
                $sql .= " AND n.status IN ($placeholders)";
                foreach ($filters['status'] as $s) {
                    $params[] = $s;
                    $types .= 's';
                }
            } else {
                $sql .= " AND n.status = ?";
                $params[] = $filters['status'];
                $types .= 's';
            }
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND n.created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND n.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }
        
        if (!empty($filters['requester_id'])) {
            if (is_array($filters['requester_id'])) {
                $placeholders = str_repeat('?,', count($filters['requester_id']) - 1) . '?';
                $sql .= " AND n.requester_id IN ($placeholders)";
                foreach ($filters['requester_id'] as $id) {
                    $params[] = (int)$id;
                    $types .= 'i';
                }
            } else {
                $sql .= " AND n.requester_id = ?";
                $params[] = (int)$filters['requester_id'];
                $types .= 'i';
            }
        }
        
        $sql .= " ORDER BY n.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->connection->prepare($sql);
            if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    $result = $this->connection->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
    
    public function canEdit(string $nreNumber, int $userId, bool $isAdmin): bool {
        $nre = $this->getByNumber($nreNumber);
        
        if (!$nre) {
            return false;
        }
        
        if ($isAdmin) {
            return true;
        }
        
        return ($nre['requester_id'] == $userId && $nre['status'] === 'Draft');
    }
}