<?php
// src/services/EmailService.php

require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;

    public function __construct() {
        if (!isset($_ENV['SMTP_HOST'])) {
            $this->loadEnv(__DIR__ . '/../../.env');
        }

        $this->mail = new PHPMailer(true);
        $this->mail->SMTPDebug = 0; // Cambia a 3 si necesitas depurar
        $this->mail->Debugoutput = function($str) {
            file_put_contents(__DIR__ . '/../../logs/smtp_debug.log', "SMTP: $str\n", FILE_APPEND);
        };

        $this->mail->isSMTP();
        $this->mail->Host       = $_ENV['SMTP_HOST']; // smtphz.qiye.163.com
        $this->mail->Port       = (int)$_ENV['SMTP_PORT']; // 465
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Usa constante para SSL en puerto 465
        $this->mail->SMTPAuth   = true;
        $this->mail->AuthType   = 'LOGIN';
        $this->mail->CharSet    = 'UTF-8';

        $this->mail->Username   = $_ENV['SMTP_USERNAME'];
        $this->mail->Password   = $_ENV['SMTP_PASSWORD'];

        // From debe coincidir con Username en muchos servidores corporativos
        $this->mail->setFrom($_ENV['SMTP_USERNAME'], 'Sistema de NREs', false);
        $this->mail->addAddress('jesus.muro@xinya-la.com');
    }

    private function loadEnv(string $envFile): void {
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

    public function sendApprovalRequest(string $subject, string $body, array $attachments = [], ?string $replyTo = null): bool {
        try {
            if ($replyTo) {
                $this->mail->addReplyTo($replyTo);
            }
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->AltBody = strip_tags($body);
            $this->mail->isHTML(true);

            // Limpiar adjuntos previos (por reutilización del objeto)
            $this->mail->clearAttachments();

            foreach ($attachments as $path) {
                if (file_exists($path)) {
                    $this->mail->addAttachment($path);
                }
            }

            return $this->mail->send();
        } catch (Exception $e) {
            error_log("[EmailService] Error: " . $e->getMessage());
            return false;
        }
    }
}