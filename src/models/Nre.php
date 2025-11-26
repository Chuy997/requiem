<?php
// src/models/Nre.php

class Nre {
    private $db;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO nres (
                nre_number, requester_id, item_description, item_code, operation,
                customizer, brand, model, new_or_replace, quantity,
                unit_price_usd, unit_price_mxn, needed_date, reason,
                quotation_filename, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'sisssssssiddssss',
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
            $data['reason'],
            $data['quotation_filename'],
            $data['status']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        } else {
            error_log("[Nre::create] DB Error: " . $this->db->error);
            throw new Exception("No se pudo crear el NRE.");
        }
    }
        public static function generateNextNreNumber(): string {
        $prefix = 'XY';
        $today = date('Ymd'); // Ej: 20251126

        // Obtener conexión
        $database = Database::getInstance();
        $db = $database->getConnection();

        // Contar cuántos NREs existen hoy con este prefijo
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

        // Obtener conexión
        $database = Database::getInstance();
        $db = $database->getConnection();

        // Contar cuántos NREs existen HOY con este prefijo
        $stmt = $db->prepare("SELECT COUNT(*) AS count FROM nres WHERE nre_number LIKE ?");
        $pattern = $prefix . $today . '%';
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $baseSeq = (int)$row['count'];

        // Generar secuencia en memoria
        $numbers = [];
        for ($i = 1; $i <= $count; $i++) {
            $seq = $baseSeq + $i;
            $numbers[] = $prefix . $today . str_pad($seq, 2, '0', STR_PAD_LEFT);
        }
        return $numbers;
    }

        public function getByRequester(int $requesterId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM nres 
            WHERE requester_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param('i', $requesterId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}