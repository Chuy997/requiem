<?php
// public/logout.php

session_start();
$_SESSION = []; // Destruir todas las variables de sesión

// Si se usan cookies de sesión, eliminarlas
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Redirigir a login
header('Location: login.php');
exit();
