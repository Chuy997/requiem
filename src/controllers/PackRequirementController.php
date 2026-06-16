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
            $parseResult = $this->parseUpload($fileData);
            if (!$parseResult['success']) {
                return $parseResult;
            }
            
            $tempPdfPath = __DIR__ . '/../../uploads/packr/temp/' . $parseResult['temp_pdf'];
            
            // Simular confirmación directa (para compatibilidad legacy)
            $confirmData = $parseResult['data'];
            return $this->confirmUpload(array_merge($confirmData, [
                'temp_pdf' => $parseResult['temp_pdf']
            ]), $userId);
            
        } catch (Exception $e) {
            error_log("[PackRequirementController] Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Sube un PDF a la carpeta temporal y lo procesa para generar la vista previa
     */
    public function parseUpload(array $fileData): array {
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
            
            // Crear directorio temporal si no existe
            $tempDir = __DIR__ . '/../../uploads/packr/temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            $tempFileName = 'temp_' . time() . '_' . uniqid() . '.pdf';
            $tempPath = $tempDir . $tempFileName;
            
            if (!copy($fileData['tmp_name'], $tempPath)) {
                throw new Exception("Error al guardar archivo temporal de subida");
            }
            
            $parsedData = $this->model->parsePdfForPreview($tempPath);
            
            return [
                'success' => true,
                'temp_pdf' => $tempFileName,
                'data' => $parsedData
            ];
            
        } catch (Exception $e) {
            // Registrar fallo en log de diagnóstico si existía el archivo temporal o el subido
            $fileToLog = $fileData['tmp_name'] ?? null;
            if ($fileToLog && file_exists($fileToLog)) {
                PdfParser::logFailedParse($fileToLog, $e->getMessage());
            }
            
            error_log("[PackRequirementController] Error al parsear subida: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirma la creación definitiva de los PackR a partir de los datos validados
     */
    public function confirmUpload(array $postData, int $userId): array {
        try {
            if (empty($postData['temp_pdf'])) {
                throw new Exception("Falta la referencia al archivo temporal del PDF.");
            }
            
            $tempPdfPath = __DIR__ . '/../../uploads/packr/temp/' . basename($postData['temp_pdf']);
            
            $success = $this->model->createFromPreviewData($postData, $tempPdfPath, $userId);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Requerimientos de empaque creados exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al procesar la confirmación'
                ];
            }
        } catch (Exception $e) {
            error_log("[PackRequirementController] Error en confirmación: " . $e->getMessage());
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
