<?php
// src/controllers/PackRequirementController.php
// Controlador para manejar Pack Requirements

require_once __DIR__ . '/../models/PackRequirement.php';

class PackRequirementController {
    private $model;
    
    public function __construct() {
        $this->model = new PackRequirement();
    }
    
    /**
     * Procesa el upload de un PDF de SAP y crea los PackR
     */
    public function createFromPdfUpload(array $fileData, int $userId): array {
        try {
            // Validar que se subió un archivo
            if (!isset($fileData['tmp_name']) || empty($fileData['tmp_name'])) {
                throw new Exception("No se recibió ningún archivo");
            }
            
            // Validar que es un PDF
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileData['tmp_name']);
            finfo_close($finfo);
            
            if ($mimeType !== 'application/pdf') {
                throw new Exception("El archivo debe ser un PDF");
            }
            
            // Validar tamaño (máx 10MB)
            if ($fileData['size'] > 10 * 1024 * 1024) {
                throw new Exception("El archivo es demasiado grande (máximo 10MB)");
            }
            
            // Crear PackR desde el PDF
            $success = $this->model->createFromPdf($fileData['tmp_name'], $userId);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Requerimientos de empaque creados exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al procesar el PDF'
                ];
            }
            
        } catch (Exception $e) {
            error_log("[PackRequirementController] Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene lista de PackR con filtros
     */
    public function getList(array $filters = []): array {
        return $this->model->getAll($filters);
    }
    
    /**
     * Marca un PackR como recibido
     */
    public function markAsArrived(string $packrNumber, int $userId, string $arrivalDate, bool $isAdmin = false): bool {
        return $this->model->markAsArrived($packrNumber, $userId, $arrivalDate, $isAdmin);
    }
    
    /**
     * Cancela un PackR
     */
    public function cancel(string $packrNumber, int $userId, bool $isAdmin = false): bool {
        return $this->model->cancel($packrNumber, $userId, $isAdmin);
    }
}
