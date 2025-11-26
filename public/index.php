<?php
// public/index.php

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/controllers/NreController.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';

session_start();

$action = $_GET['action'] ?? 'home';

// Acción: mostrar vista previa
if ($action === 'preview' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $items = $_POST['items'] ?? [];
        if (empty($items)) {
            throw new Exception("No se enviaron ítems.");
        }

        foreach ($items as $item) {
            if (empty($item['item_description']) || empty($item['price_amount']) || !is_numeric($item['price_amount'])) {
                throw new Exception("Campos requeridos faltantes en un ítem.");
            }
        }

        $nreNumbers = Nre::getNextNreNumbers(count($items));

        // ✅ Guardar en sesión
        $_SESSION['nre_items'] = $items;
        $_SESSION['nre_nre_numbers'] = $nreNumbers;
        $_SESSION['nre_quotations'] = $_FILES['quotations'] ?? null;

        // Renderizar vista previa
        include __DIR__ . '/../templates/nre/preview.php';
        exit;
    } catch (Exception $e) {
        $_SESSION['nre_form_data'] = $_POST;
        $_SESSION['nre_form_error'] = $e->getMessage();
        header('Location: /requiem/public/');
        exit;
    }
}

// Acción: confirmar y enviar
if ($action === 'confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['nre_items'])) {
            throw new Exception("No hay datos para enviar.");
        }

        $controller = new NreController();
        $success = $controller->createFromForm($_SESSION['nre_items'], $_SESSION['nre_quotations'] ?? []);

        // Limpiar sesión
        unset($_SESSION['nre_items'], $_SESSION['nre_quotations']);

        if ($success) {
            header('Location: /requiem/public/?success=1');
            exit;
        } else {
            throw new Exception("Error al procesar la solicitud.");
        }
    } catch (Exception $e) {
        error_log("[Index] Error: " . $e->getMessage());
        $_SESSION['nre_form_error'] = $e->getMessage();
        header('Location: /requiem/public/');
        exit;
    }
}

// Mostrar mensaje de éxito
if (isset($_GET['success'])) {
    echo "<div class='alert alert-success m-4'>✅ Solicitud enviada. Revisa tu correo para la confirmación.</div>";
    echo "<a href='/requiem/public/' class='btn btn-primary ms-4'>Crear otra solicitud</a>";
    exit;
}

// Mostrar formulario principal
require_once __DIR__ . '/../templates/nre/create.php';