<?php

/**
 * scripts/weekly_alert.php
 *
 * Cron Job — Alerta Semanal de Requerimientos Estancados
 * Se ejecuta todos los lunes a primera hora (configurado vía crontab).
 *
 * Filtro: status = 'In Process' AND DATEDIFF(NOW(), created_at) > 15
 * Destinatario: jesus.muro@xinya-la.com
 *
 * Instalación del cron (ejecutar como root o el usuario del servidor web):
 *   crontab -e
 *   Agregar la siguiente línea:
 *   0 7 * * 1 /usr/bin/php /var/www/html/requiem/scripts/weekly_alert.php >> /var/www/html/requiem/logs/weekly_alert.log 2>&1
 */

// ── Bootstrap ───────────────────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/src/config/db.php';
require_once ROOT_PATH . '/vendor/phpmailer/src/PHPMailer.php';
require_once ROOT_PATH . '/vendor/phpmailer/src/SMTP.php';
require_once ROOT_PATH . '/vendor/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar .env manualmente (mismo patrón que el resto del proyecto)
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// ── Función de log ───────────────────────────────────────────────────────────
function logAlert(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}" . PHP_EOL;
}

logAlert("=== Inicio de Alerta Semanal de Requerimientos ===");

// ── Consulta a la base de datos ──────────────────────────────────────────────
try {
    $db = Database::getInstance()->getConnection();

    $sql = "
        SELECT
            n.nre_number         AS id,
            n.item_description   AS descripcion,
            n.requirement_type   AS tipo,
            n.department         AS departamento,
            n.status             AS status,
            n.created_at         AS fecha_creacion,
            DATEDIFF(NOW(), n.created_at) AS dias_en_proceso,
            u.full_name          AS responsable,
            u.email              AS responsable_email
        FROM nres n
        LEFT JOIN users u ON n.requester_id = u.id
        WHERE n.status = 'In Process'
          AND DATEDIFF(NOW(), n.created_at) > 15
        ORDER BY dias_en_proceso DESC
    ";

    $result = $db->query($sql);

    if (!$result) {
        logAlert("ERROR: Fallo en la consulta SQL: " . $db->error);
        exit(1);
    }

    $requerimientos = $result->fetch_all(MYSQLI_ASSOC);
    $total = count($requerimientos);

    logAlert("Requerimientos detectados con más de 15 días en proceso: {$total}");

    if ($total === 0) {
        logAlert("Sin requerimientos críticos esta semana. No se enviará correo.");
        logAlert("=== Fin del proceso ===");
        exit(0);
    }
} catch (Exception $e) {
    logAlert("ERROR DB: " . $e->getMessage());
    exit(1);
}

// ── Construcción del HTML del correo ─────────────────────────────────────────
$fechaHoy = date('d/m/Y');

/**
 * Formatea una fecha larga en español sin usar strftime (deprecated en PHP 8.1+)
 */
function fechaLargaEspanol(string $fechaYmd): string
{
    $dias   = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses  = [
        '',
        'enero',
        'febrero',
        'marzo',
        'abril',
        'mayo',
        'junio',
        'julio',
        'agosto',
        'septiembre',
        'octubre',
        'noviembre',
        'diciembre'
    ];
    $ts    = strtotime($fechaYmd);
    $dia   = $dias[(int)date('w', $ts)];
    $dNum  = (int)date('j', $ts);
    $mes   = $meses[(int)date('n', $ts)];
    $anio  = date('Y', $ts);
    return "{$dia}, {$dNum} de {$mes} de {$anio}";
}

$fechaHoyLong = fechaLargaEspanol(date('Y-m-d'));

// Construir filas de la tabla
$filasHtml = '';
foreach ($requerimientos as $index => $req) {
    $bgRow       = ($index % 2 === 0) ? '#FFFFFF' : '#F8F9FA';
    $diasNum     = (int)$req['dias_en_proceso'];
    $diasColor   = $diasNum > 30 ? '#C0392B' : ($diasNum > 20 ? '#D35400' : '#856404');
    $diasBg      = $diasNum > 30 ? '#FDEDEC' : ($diasNum > 20 ? '#FEF0E7' : '#FFF9E6');

    $id          = htmlspecialchars($req['id']);
    $descripcion = htmlspecialchars(mb_strimwidth($req['descripcion'], 0, 80, '…'));
    $tipo        = htmlspecialchars($req['tipo'] ?? '—');
    $responsable = htmlspecialchars($req['responsable'] ?? '—');
    $dpto        = htmlspecialchars($req['departamento'] ?? '—');

    $filasHtml .= <<<ROW
        <tr style="background-color:{$bgRow}; border-bottom:1px solid #E9ECEF;">
            <td style="padding:12px 16px; font-size:13px; font-weight:600; color:#0A2540; white-space:nowrap;">{$id}</td>
            <td style="padding:12px 16px; font-size:13px; color:#212529;">{$descripcion}</td>
            <td style="padding:12px 16px; font-size:13px; color:#4A5568; white-space:nowrap;">{$tipo}</td>
            <td style="padding:12px 16px; font-size:13px; color:#4A5568;">{$responsable}</td>
            <td style="padding:12px 16px; font-size:13px; color:#4A5568;">{$dpto}</td>
            <td style="padding:12px 16px; text-align:center;">
                <span style="display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; color:{$diasColor}; background-color:{$diasBg}; white-space:nowrap;">{$diasNum} días</span>
            </td>
        </tr>
ROW;
}

// Resumen para el badge del encabezado
$countCritico  = count(array_filter($requerimientos, fn($r) => $r['dias_en_proceso'] > 30));
$countAtencion = count(array_filter($requerimientos, fn($r) => $r['dias_en_proceso'] > 20 && $r['dias_en_proceso'] <= 30));
$countNormal   = $total - $countCritico - $countAtencion;

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Seguimiento de Requerimientos</title>
</head>
<body style="margin:0; padding:0; font-family: system-ui, -apple-system, 'Helvetica Neue', Arial, sans-serif; background-color:#F0F2F5;">

    <!-- Wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F0F2F5; padding:40px 20px;">
        <tr>
            <td align="center">
                <!-- Tarjeta principal -->
                <table width="680" cellpadding="0" cellspacing="0" border="0" style="max-width:680px; width:100%; background-color:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                    <!-- ── HEADER ── -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0A2540 0%, #1a3d5c 100%); padding:36px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <p style="margin:0 0 6px 0; font-size:11px; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:#7EB8E0; opacity:0.9;">Sistema de Gestión · XINYA Latin America</p>
                                        <h1 style="margin:0 0 8px 0; font-size:22px; font-weight:700; color:#FFFFFF; line-height:1.3;">⚠️&nbsp; Alerta de Seguimiento</h1>
                                        <p style="margin:0; font-size:14px; color:#A8C8E8; line-height:1.5;">Requerimientos con más de 15 días en proceso</p>
                                    </td>
                                    <td align="right" valign="top" style="padding-left:20px;">
                                        <div style="background-color:rgba(255,255,255,0.12); border-radius:10px; padding:14px 18px; text-align:center; white-space:nowrap;">
                                            <p style="margin:0; font-size:28px; font-weight:800; color:#FFFFFF; line-height:1;">{$total}</p>
                                            <p style="margin:4px 0 0 0; font-size:11px; font-weight:500; color:#A8C8E8; text-transform:uppercase; letter-spacing:1px;">Detectados</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ── FECHA ── -->
                    <tr>
                        <td style="background-color:#F8F9FA; padding:12px 40px; border-bottom:1px solid #E9ECEF;">
                            <p style="margin:0; font-size:12px; color:#6C757D;">
                                <strong style="color:#4A5568;">Revisión semanal</strong> &nbsp;·&nbsp; {$fechaHoyLong}
                            </p>
                        </td>
                    </tr>

                    <!-- ── SALUDO Y CONTEXTO ── -->
                    <tr>
                        <td style="padding:32px 40px 24px 40px;">
                            <p style="margin:0 0 16px 0; font-size:15px; color:#212529; line-height:1.7;">
                                Hola Equipo de Compras,
                            </p>
                            <p style="margin:0 0 16px 0; font-size:14px; color:#4A5568; line-height:1.7;">
                                Este es el reporte automático de seguimiento semanal. El sistema ha identificado <strong style="color:#0A2540;">{$total} requerimiento(s)</strong> que llevan <strong>más de 15 días</strong> con estatus <em>"In Process"</em> sin actualizarse.
                            </p>
                            <p style="margin:0; font-size:14px; color:#4A5568; line-height:1.7;">
                                Te pedimos revisar cada caso y reportar el estatus de la compra.
                            </p>
                        </td>
                    </tr>

                    <!-- ── BADGES DE RESUMEN ── -->
                    <tr>
                        <td style="padding:0 40px 28px 40px;">
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
HTML;

if ($countCritico > 0) {
    $htmlBody .= <<<BADGE
                                    <td style="padding-right:10px;">
                                        <span style="display:inline-block; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; color:#C0392B; background-color:#FDEDEC; border:1px solid #F5C6CB;">
                                            🔴 {$countCritico} Crítico(s) &gt;30 días
                                        </span>
                                    </td>
BADGE;
}

if ($countAtencion > 0) {
    $htmlBody .= <<<BADGE
                                    <td style="padding-right:10px;">
                                        <span style="display:inline-block; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; color:#D35400; background-color:#FEF0E7; border:1px solid #FDDCB5;">
                                            🟠 {$countAtencion} En atención &gt;20 días
                                        </span>
                                    </td>
BADGE;
}

if ($countNormal > 0) {
    $htmlBody .= <<<BADGE
                                    <td style="padding-right:10px;">
                                        <span style="display:inline-block; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; color:#856404; background-color:#FFF9E6; border:1px solid #FFE99A;">
                                            🟡 {$countNormal} Seguimiento 16–20 días
                                        </span>
                                    </td>
BADGE;
}

$htmlBody .= <<<HTML
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ── TABLA DE REQUERIMIENTOS ── -->
                    <tr>
                        <td style="padding:0 40px 32px 40px;">
                            <p style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:#0A2540; text-transform:uppercase; letter-spacing:1px;">Detalle de Requerimientos</p>

                            <!-- Tabla responsive wrapper -->
                            <div style="border-radius:8px; overflow:hidden; border:1px solid #E9ECEF;">
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                    <!-- Encabezados -->
                                    <thead>
                                        <tr style="background-color:#F8F9FA; border-bottom:2px solid #DEE2E6;">
                                            <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:700; color:#6C757D; text-transform:uppercase; letter-spacing:1px; white-space:nowrap;">ID / NRE</th>
                                            <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:700; color:#6C757D; text-transform:uppercase; letter-spacing:1px;">Descripción</th>
                                            <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:700; color:#6C757D; text-transform:uppercase; letter-spacing:1px;">Tipo</th>
                                            <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:700; color:#6C757D; text-transform:uppercase; letter-spacing:1px;">Responsable</th>
                                            <th style="padding:11px 16px; text-align:left; font-size:11px; font-weight:700; color:#6C757D; text-transform:uppercase; letter-spacing:1px;">Depto.</th>
                                            <th style="padding:11px 16px; text-align:center; font-size:11px; font-weight:700; color:#6C757D; text-transform:uppercase; letter-spacing:1px; white-space:nowrap;">Días</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {$filasHtml}
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- ── CTA ── -->
                    <tr>
                        <td style="padding:0 40px 36px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: linear-gradient(135deg, #F0F7FF 0%, #E8F4FD 100%); border-radius:8px; border-left:4px solid #0A2540;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 10px 0; font-size:14px; font-weight:700; color:#0A2540;">📋 Acción requerida</p>
                                        <p style="margin:0 0 16px 0; font-size:13px; color:#4A5568; line-height:1.6;">
                                            </p>
                                        <a href="http://192.168.1.36/requiem/public/monitor.php" style="display:inline-block; padding:11px 24px; background-color:#0A2540; color:#FFFFFF; text-decoration:none; border-radius:6px; font-size:13px; font-weight:600; letter-spacing:0.3px;">Ir al Sistema de Requerimientos →</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ── FOOTER ── -->
                    <tr>
                        <td style="background-color:#F8F9FA; padding:20px 40px; border-top:1px solid #E9ECEF; border-radius:0 0 12px 12px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <p style="margin:0; font-size:11px; color:#ADB5BD; line-height:1.6;">
                                            Este correo fue generado automáticamente por el sistema de gestión de NREs de XINYA Latin America.<br>
                                            Frecuencia: <strong>Lunes · 8:00 AM</strong> &nbsp;·&nbsp; No responder a este mensaje directamente.
                                        </p>
                                    </td>
                                    <td align="right" style="white-space:nowrap; padding-left:20px;">
                                        <p style="margin:0; font-size:11px; color:#CED4DA;">© {$fechaHoy} XINYA-LA</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
                <!-- /Tarjeta principal -->
            </td>
        </tr>
    </table>

</body>
</html>
HTML;

// ── Envío del correo ──────────────────────────────────────────────────────────
logAlert("Preparando envío de correo de alerta...");

try {
    $mail = new PHPMailer(true);

    // Configuración SMTP (mismo patrón que EmailService.php)
    $mail->SMTPDebug  = 0;
    $mail->DebugOutput = function ($str) {
        file_put_contents(ROOT_PATH . '/logs/smtp_debug.log', "SMTP: $str\n", FILE_APPEND);
    };

    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->Port       = (int)$_ENV['SMTP_PORT'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth   = true;
    $mail->AuthType   = 'LOGIN';
    $mail->CharSet    = 'UTF-8';
    $mail->Username   = $_ENV['SMTP_USERNAME'];
    $mail->Password   = $_ENV['SMTP_PASSWORD'];

    // Remitente
    $mail->setFrom($_ENV['SMTP_USERNAME'], 'NREs Sistema de Alertas · XINYA-LA', false);

    // Destinatario principal
    $mail->addAddress('jesus.muro@xinya-la.com',      'Jesús Muro');
    $mail->addAddress('rocio.cortes@xinya-la.com',    'Rocío Cortés');
    $mail->addAddress('laura.lopez@xinya-la.com',     'Laura López');
    $mail->addAddress('zaira.villegas@xinya-la.com',  'Zaira Villegas');
    $mail->addAddress('cesar.gutierrez@xinya-la.com', 'César Gutiérrez');
    $mail->addAddress('sergio.gomez@xinya-la.com',    'Sergio Gómez');
    $mail->addAddress('eros.ruiz@xinya-la.com',       'Eros Ruiz');
    $mail->addAddress('jaime.avalos@xinya-la.com',    'Jaime Ávalos');

    // Asunto con fecha de hoy
    $mail->Subject = "⚠️ Alerta de Seguimiento: Requerimientos con más de 15 días en proceso - {$fechaHoy}";
    $mail->isHTML(true);
    $mail->Body    = $htmlBody;
    $mail->AltBody = "Alerta Semanal — {$total} requerimiento(s) con más de 15 días 'In Process'. Por favor revisa el sistema: http://localhost/requiem/public/";

    $mail->send();

    logAlert("✅ Correo enviado correctamente a jesus.muro@xinya-la.com ({$total} requerimientos).");
} catch (Exception $e) {
    logAlert("❌ Error al enviar correo: " . $mail->ErrorInfo);
    exit(1);
}

logAlert("=== Fin del proceso ===");
exit(0);
