<?php
// src/services/EmailService.php

require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $smtpHost;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpPort;
    private $smtpEncryption;

    public function __construct() {
        // Cargar .env (reutiliza la misma lógica que en db.php o haz una función compartida después)
        $this->loadEnv();
        
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->smtpPort = (int)($_ENV['SMTP_PORT'] ?? 465);
        $this->smtpEncryption = $_ENV['SMTP_ENCRYPTION'] ?? 'ssl';
    }

    private function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && $line[0] !== '#') {
                    [$key, $value] = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }

    /**
     * Envía un correo usando alertservice como cuenta SMTP,
     * pero mostrando al solicitante como remitente visible.
     *
     * @param string $requesterEmail Correo del creador del NRE
     * @param string $requesterName Nombre del creador
     * @param array $to Lista de destinatarios (strings)
     * @param string $subject
     * @param string $htmlBody
     * @param array $attachments Rutas absolutas de archivos a adjuntar
     * @return bool
     */
    public function sendNreNotification(
        string $requesterEmail,
        string $requesterName,
        array $to,
        string $subject,
        string $htmlBody,
        array $attachments = []
    ): bool {
        $mail = new PHPMailer(true);

        try {
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->Port = $this->smtpPort;

            if ($this->smtpPort == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ssl
            } elseif ($this->smtpPort == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Cuenta técnica (no visible para el usuario final)
            $mail->setFrom($this->smtpUsername, 'Sistema de Requerimientos - Xinya');
            
            // El solicitante es el "Reply-To" y se muestra como remitente en el cuerpo
            $mail->addReplyTo($requesterEmail, $requesterName);

            // Destinatarios
            foreach ($to as $email) {
                $mail->addAddress($email);
            }

            // Asunto y cuerpo
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;

            // Adjuntos
            foreach ($attachments as $filePath) {
                if (file_exists($filePath)) {
                    $mail->addAttachment($filePath);
                }
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("[EMAIL] Error: " . $e->getMessage());
            return false;
        }
    }
}