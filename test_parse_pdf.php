<?php
require_once __DIR__ . '/src/services/PdfParser.php';
$pdfPath = __DIR__ . '/Solicitud de compra_20260527_91236AM.pdf';
$data = PdfParser::parseSapPurchaseRequest($pdfPath);
print_r($data);
