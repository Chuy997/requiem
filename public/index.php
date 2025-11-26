<?php
// public/index.php

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/controllers/NreController.php';

session_start();

$action = $_GET['action'] ?? 'home';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $controller = new NreController();
        $items = $_POST['items'] ?? [];

        // Validar ítems
        if (empty($items)) {
            throw new Exception("No se enviaron ítems.");
        }

        foreach ($items as $item) {
            if (empty($item['item_description']) || empty($item['price_amount']) || !is_numeric($item['price_amount'])) {
                throw new Exception("Campos requeridos faltantes en un ítem.");
            }
        }

        $success = $controller->createFromForm($items, $_FILES['quotations'] ?? []);

        if ($success) {
            // ✅ Éxito: redirigir a la página principal
            header('Location: /requiem/public/?success=1');
            exit;
        } else {
            throw new Exception("Error al procesar la solicitud.");
        }
    } catch (Exception $e) {
        error_log("[Index] Error: " . $e->getMessage());
        // Guardar datos para rellenar el formulario
        $_SESSION['nre_form_data'] = $_POST;
        $_SESSION['nre_form_error'] = $e->getMessage();
        header('Location: /requiem/public/');
        exit;
    }
}

// Mostrar mensaje de éxito
$showSuccess = isset($_GET['success']);
if ($showSuccess) {
    echo "<div class='alert alert-success m-4'>✅ Solicitud enviada. Revisa tu correo para la confirmación.</div>";
    echo "<a href='/requiem/public/' class='btn btn-primary ms-4'>Crear otra solicitud</a>";
    exit;
}

// Mostrar formulario
require_once __DIR__ . '/../templates/nre/create.php';