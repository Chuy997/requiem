<?php
// src/middleware/AuthMiddleware.php

/**
 * Middleware para proteger rutas que requieren autenticación.
 * Redirige a login.php si no hay sesión activa.
 */
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}