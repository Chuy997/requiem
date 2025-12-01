<?php
// public/login.php

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/User.php';

session_start();

// Si ya está autenticado, redirigir al inicio
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

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
                $_SESSION['user_id'] = (int)$row['id'];
                $_SESSION['user_name'] = $row['full_name']; // Para el navbar
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