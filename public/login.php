<?php
// public/login.php

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/User.php';

// Configurar parámetros de cookie seguros ANTES de session_start (consistencia con AuthMiddleware)
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,    // Cambiar a true si se habilita HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Si ya está autenticado, redirigir al inicio
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Detectar si el usuario fue redirigido por expiración de sesión
$sessionExpired = isset($_GET['expired']) && $_GET['expired'] === '1';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Correo y contraseña son requeridos.';
    } else {
        // Validar que el usuario exista
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id, email, full_name, password_hash, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                // ── Prevenir Session Fixation: regenerar ID antes de escribir datos ──
                session_regenerate_id(true);

                $_SESSION['user_id']         = (int)$row['id'];
                $_SESSION['user_name']       = $row['full_name'];
                $_SESSION['last_activity']   = time();   // Iniciar contador de inactividad
                $_SESSION['last_regenerated'] = time();  // Para regeneración periódica

                header('Location: index.php');
                exit();
            } else {
                $error = 'Credenciales inválidas.';
            }
        } else {
            $error = 'Credenciales inválidas.';
        }
    }
}

// Mostrar formulario de login
require_once __DIR__ . '/../templates/auth/login.php';