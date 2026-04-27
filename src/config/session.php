<?php
// src/config/session.php

function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración segura de cookies de sesión
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Solo HTTPS si está disponible
            'httponly' => true, // Evita acceso desde JavaScript (XSS)
            'samesite' => 'Lax' // Previene envío en peticiones cross-site (CSRF mitigation)
        ]);

        session_start();
    }

    // Comprobar tiempo de inactividad (30 minutos = 1800 segundos)
    $timeout_duration = 1800;

    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        if ($elapsed_time > $timeout_duration) {
            // Sesión expirada por inactividad
            session_unset();
            session_destroy();
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['nre_error'] = "Tu sesión ha expirado por inactividad.";
            header("Location: /requiem/public/login.php");
            exit;
        }
    }

    // Actualizar la última actividad
    $_SESSION['last_activity'] = time();
}
