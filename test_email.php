<?php
require_once __DIR__ . '/src/services/EmailService.php';

$emailService = new EmailService();

$subject = "Prueba de conexión SMTP - Sistema NRE";
$body = "<h3>✅ Prueba exitosa</h3><p>Este es un mensaje de prueba del sistema de NREs.</p>";

$success = $emailService->sendApprovalRequest($subject, $body);

if ($success) {
    echo "✅ Correo de prueba enviado.\n";
} else {
    echo "❌ Falló el envío del correo.\n";
}