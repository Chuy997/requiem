<?php
// public/index.php

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/controllers/NreController.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';
require_once __DIR__ . '/../src/controllers/NreListController.php';

session_start();

$action = $_GET['action'] ?? 'list';

// --- Creación y vista previa ---
if ($action === 'preview' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $items = $_POST['items'] ?? [];
        if (empty($items)) throw new Exception("No se enviaron ítems.");
        foreach ($items as $item) {
            if (empty($item['item_description']) || empty($item['price_amount']) || !is_numeric($item['price_amount'])) {
                throw new Exception("Campos requeridos faltantes en un ítem.");
            }
        }
        $nreNumbers = Nre::getNextNreNumbers(count($items));
        $_SESSION['nre_items'] = $items;
        $_SESSION['nre_nre_numbers'] = $nreNumbers;
        $_SESSION['nre_quotations'] = $_FILES['quotations'] ?? null;
        include __DIR__ . '/../templates/nre/preview.php';
        exit;
    } catch (Exception $e) {
        $_SESSION['nre_form_data'] = $_POST;
        $_SESSION['nre_form_error'] = $e->getMessage();
        header('Location: /requiem/public/?action=new');
        exit;
    }
}

if ($action === 'confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['nre_items'])) throw new Exception("No hay datos para enviar.");
        $controller = new NreController();
        $success = $controller->createFromForm($_SESSION['nre_items'], $_SESSION['nre_quotations'] ?? []);
        unset($_SESSION['nre_items'], $_SESSION['nre_quotations']);
        if ($success) {
            $_SESSION['nre_message'] = "✅ Solicitud enviada. Revisa tu correo.";
            header('Location: /requiem/public/');
            exit;
        } else {
            throw new Exception("Error al procesar la solicitud.");
        }
    } catch (Exception $e) {
        error_log("[Index] Error: " . $e->getMessage());
        $_SESSION['nre_form_error'] = $e->getMessage();
        header('Location: /requiem/public/?action=new');
        exit;
    }
}

// --- Acciones de gestión ---
if ($action === 'mark_in_process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nreNumber = $_POST['nre_number'] ?? '';
    if ($nreNumber) {
        $listController = new NreListController();
        if ($listController->markAsInProcess($nreNumber, 1)) {
            $_SESSION['nre_message'] = "✅ NRE $nreNumber marcado como 'En Proceso'.";
        } else {
            $_SESSION['nre_error'] = "❌ No se pudo actualizar el NRE.";
        }
    }
    header('Location: /requiem/public/');
    exit;
}

if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nreNumber = $_POST['nre_number'] ?? '';
    if ($nreNumber) {
        $listController = new NreListController();
        if ($listController->cancelNre($nreNumber, 1)) {
            $_SESSION['nre_message'] = "✅ NRE $nreNumber cancelado.";
        } else {
            $_SESSION['nre_error'] = "❌ No se pudo cancelar el NRE.";
        }
    }
    header('Location: /requiem/public/');
    exit;
}

if ($action === 'mark_arrived' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nreNumber = $_POST['nre_number'] ?? '';
    $arrivalDate = $_POST['arrival_date'] ?? date('Y-m-d');
    if ($nreNumber) {
        $listController = new NreListController();
        if ($listController->markAsArrived($nreNumber, 1, $arrivalDate)) {
            $_SESSION['nre_message'] = "✅ NRE $nreNumber finalizado.";
        } else {
            $_SESSION['nre_error'] = "❌ No se pudo finalizar el NRE.";
        }
    }
    header('Location: /requiem/public/');
    exit;
}

// --- Mostrar éxito tras creación ---
if (isset($_GET['success'])) {
    $_SESSION['nre_message'] = "✅ Solicitud enviada. Revisa tu correo.";
    header('Location: /requiem/public/');
    exit;
}

// --- Mostrar formulario de nuevo NRE ---
if ($action === 'new') {
    require_once __DIR__ . '/../templates/nre/create.php';
    exit;
}

// --- Mostrar lista principal ---
$includeCompleted = isset($_GET['show_completed']);
$listController = new NreListController();
$nres = $listController->listMyNres(1, $includeCompleted);

if (!empty($_SESSION['nre_message'])) {
    echo "<div class='alert alert-success m-4'>" . htmlspecialchars($_SESSION['nre_message']) . "</div>";
    unset($_SESSION['nre_message']);
}
if (!empty($_SESSION['nre_error'])) {
    echo "<div class='alert alert-danger m-4'>" . htmlspecialchars($_SESSION['nre_error']) . "</div>";
    unset($_SESSION['nre_error']);
}

require_once __DIR__ . '/../templates/nre/list.php';