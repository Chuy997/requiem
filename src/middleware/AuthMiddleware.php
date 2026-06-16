<?php
// src/middleware/AuthMiddleware.php

/**
 * Middleware para proteger rutas que requieren autenticación.
 * - Configura cookies de sesión seguras (httponly, samesite)
 * - Aplica timeout de inactividad de 15 minutos
 * - Regenera el session ID periódicamente
 * - Redirige a login.php si no hay sesión activa o expiró
 */

/** Tiempo máximo de inactividad en segundos (15 minutos) */
define('SESSION_INACTIVITY_TIMEOUT', 15 * 60);

/** Intervalo para regenerar el session ID (30 minutos) */
define('SESSION_REGENERATE_INTERVAL', 30 * 60);

function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar parámetros de cookie de sesión ANTES de iniciar la sesión
        // Nota: cookie_secure = false porque el servidor usa HTTP (sin HTTPS/proxy SSL)
        session_set_cookie_params([
            'lifetime' => 0,          // Cookie de sesión (se borra al cerrar el navegador)
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,      // Cambiar a true si se habilita HTTPS en el futuro
            'httponly' => true,       // Bloquea acceso JS a la cookie (protege contra XSS)
            'samesite' => 'Lax',      // Protege contra CSRF en navegación normal
        ]);
        session_start();
    }

    // ── 1. Verificar autenticación ────────────────────────────────────────────
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    // ── 2. Verificar timeout de inactividad (15 minutos) ─────────────────────
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        if ($inactive > SESSION_INACTIVITY_TIMEOUT) {
            // Sesión expirada por inactividad — limpiar y redirigir
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), '',
                    time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            session_destroy();
            // Redirigir con mensaje de expiración
            header('Location: login.php?expired=1');
            exit();
        }
    }

    // ── 3. Actualizar marca de actividad ─────────────────────────────────────
    $_SESSION['last_activity'] = time();

    // ── 4. Regenerar session ID periódicamente (cada 30 min) ─────────────────
    if (!isset($_SESSION['last_regenerated'])) {
        $_SESSION['last_regenerated'] = time();
    } elseif ((time() - $_SESSION['last_regenerated']) > SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = time();
    }
}