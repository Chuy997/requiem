-- Script para agregar soporte de PackR (Packing Requirements)
-- Fecha: 2025-12-02

-- Modificar tabla nres para incluir tipo de requerimiento
ALTER TABLE nres 
ADD COLUMN requirement_type ENUM('NRE', 'PackR') NOT NULL DEFAULT 'NRE' AFTER id,
ADD COLUMN sap_document_number VARCHAR(50) NULL AFTER nre_number,
ADD COLUMN department VARCHAR(100) NULL AFTER operation,
ADD COLUMN project VARCHAR(50) NULL AFTER department;

-- Crear índice para búsquedas por tipo
CREATE INDEX idx_requirement_type ON nres(requirement_type);
CREATE INDEX idx_sap_document ON nres(sap_document_number);

-- Actualizar registros existentes como tipo NRE
UPDATE nres SET requirement_type = 'NRE' WHERE requirement_type IS NULL;

-- Verificar cambios
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'nres' 
  AND TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME IN ('requirement_type', 'sap_document_number', 'department', 'project')
ORDER BY ORDINAL_POSITION;
