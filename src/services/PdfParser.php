<?php
// src/services/PdfParser.php
// Servicio para extraer información de PDFs de SAP

class PdfParser {
    
    /**
     * Extrae texto de un PDF usando pdftotext con layout
     */
    public static function extractText(string $pdfPath): string {
        if (!file_exists($pdfPath)) {
            throw new Exception("El archivo PDF no existe: $pdfPath");
        }
        
        // Usar -layout para mantener la estructura tabular
        $command = "pdftotext -layout " . escapeshellarg($pdfPath) . " -";
        $output = shell_exec($command);
        
        if ($output === null) {
            throw new Exception("Error al extraer texto del PDF");
        }
        
        return $output;
    }
    
    /**
     * Parsea un PDF de solicitud de compra SAP
     * Retorna un array con la información extraída
     */
    public static function parseSapPurchaseRequest(string $pdfPath): array {
        $text = self::extractText($pdfPath);
        
        $data = [
            'sap_document_number' => null,
            'document_date' => null,
            'requester_name' => null,
            'currency' => 'MXN',
            'items' => [],
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'comments' => null
        ];
        
        // Extraer número de solicitud
        if (preg_match('/SOLICITUD DE COMPRA NO\.\s+(\d+)/i', $text, $matches)) {
            $data['sap_document_number'] = $matches[1];
        }
        
        // Extraer fecha de documento
        if (preg_match('/FECHA DE DOCUMENTO\s+(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $data['document_date'] = DateTime::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
        }
        
        // Extraer nombre de solicitante
        if (preg_match('/NOMBRE DE SOLICITANTE\s+([^\n]+)/i', $text, $matches)) {
            $data['requester_name'] = trim($matches[1]);
        }
        
        // Extraer moneda
        if (preg_match('/Moneda:\s+([A-Z]{3})/i', $text, $matches)) {
            $data['currency'] = $matches[1];
        }
        
        // Extraer comentarios
        if (preg_match('/COMENTARIOS:\s+([^\n]+)/i', $text, $matches)) {
            $data['comments'] = trim($matches[1]);
        }
        
        // Extraer items (líneas de la tabla)
        $lines = explode("\n", $text);
        $currentItem = null;
        
        foreach ($lines as $line) {
            // Buscar línea principal del item (empieza con código EXP...)
            // Regex ajustada para espacios múltiples
            if (preg_match('/^\s*([A-Z0-9]+)\s+(.+?)\s+(\d{1,2}\/\d{1,2}\/\d{4})\s+(\d+)\s+([\d,\.]+)\s+([A-Z]+)\s+(\d+)\s+([\d,\.]+)\s*([A-Z]+)?/i', $line, $matches)) {
                
                // Si había un item anterior, guardarlo
                if ($currentItem) {
                    $data['items'][] = $currentItem;
                }
                
                $quantity = (int)$matches[4];
                $unitPrice = (float)str_replace(',', '', $matches[5]);
                $total = (float)str_replace(',', '', $matches[8]);
                
                // Extraer SKU del formato XXX-NNNNN de la descripción
                $description = trim($matches[2]);
                $sku = null;
                if (preg_match('/([A-Z]{3}-\d{5})/', $description, $skuMatches)) {
                    $sku = $skuMatches[1];
                }
                
                $currentItem = [
                    'item_code' => $sku ?: $matches[1], // Usar SKU extraído o código original como fallback
                    'item_description' => $description,
                    'needed_date' => DateTime::createFromFormat('d/m/Y', $matches[3])->format('Y-m-d'),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'department' => $matches[6],
                    'project' => $matches[7],
                    'total' => $total,
                    'currency' => $data['currency']
                ];
            } 
            // Si es una línea de continuación de descripción (indentada y sin fecha/cantidades)
            elseif ($currentItem && preg_match('/^\s{20,}(.+)$/', $line, $matches)) {
                // Verificar que no sea parte de los totales o footer
                if (!preg_match('/SUBTOTAL|IMPUESTO|TOTAL|COMENTARIOS|NOMBRE Y FIRMA/i', $line)) {
                    $descPart = trim($matches[1]);
                    // Evitar agregar basura
                    if (strlen($descPart) > 0) {
                        $currentItem['item_description'] .= ' ' . $descPart;
                        
                        // Intentar extraer SKU de líneas adicionales si no se encontró antes
                        if (!preg_match('/[A-Z]{3}-\d{5}/', $currentItem['item_code']) && 
                            preg_match('/([A-Z]{3}-\d{5})/', $descPart, $skuMatches)) {
                            $currentItem['item_code'] = $skuMatches[1];
                        }
                    }
                }
            }
        }
        
        // Agregar el último item
        if ($currentItem) {
            $data['items'][] = $currentItem;
        }
        
        // Extraer totales
        if (preg_match('/PETICIÓN SUBTOTAL:\s+([\d,\.]+)\s*USD/i', $text, $matches)) {
            $data['subtotal'] = (float)str_replace(',', '', $matches[1]);
        }
        
        if (preg_match('/IMPUESTO:\s+([\d,\.]+)\s*USD/i', $text, $matches)) {
            $data['tax'] = (float)str_replace(',', '', $matches[1]);
        }
        
        if (preg_match('/IMPORTE TOTAL:\s+([\d,\.]+)\s*USD/i', $text, $matches)) {
            $data['total'] = (float)str_replace(',', '', $matches[1]);
        }
        
        return $data;
    }
    
    /**
     * Valida que el PDF tenga el formato esperado de SAP
     */
    public static function validateSapFormat(string $pdfPath): bool {
        try {
            $text = self::extractText($pdfPath);
            
            // Verificar que contenga elementos clave del formato SAP
            $hasRequestNumber = preg_match('/SOLICITUD DE COMPRA NO\./i', $text);
            $hasDate = preg_match('/FECHA DE DOCUMENTO/i', $text);
            $hasRequester = preg_match('/NOMBRE DE SOLICITANTE/i', $text);
            
            return $hasRequestNumber && $hasDate && $hasRequester;
            
        } catch (Exception $e) {
            error_log("Error validando formato SAP: " . $e->getMessage());
            return false;
        }
    }
}
