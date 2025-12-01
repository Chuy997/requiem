<?php
// public/index.php

require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/controllers/NreController.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';
require_once __DIR__ . '/../src/controllers/NreListController.php';

requireAuth();
$user_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? 'list';

// --- Preview: solo muestra datos, NO procesa archivos ---
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
        
        // Manejar subida temporal de archivos
        $tempFiles = [];
        if (!empty($_FILES['quotations']['tmp_name'][0])) {
            $tempDir = __DIR__ . '/../uploads/temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            foreach ($_FILES['quotations']['tmp_name'] as $index => $tmpName) {
                if ($_FILES['quotations']['error'][$index] === UPLOAD_ERR_OK) {
                    $name = $_FILES['quotations']['name'][$index];
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                    // Usar uniqid para evitar colisiones
                    $tempPath = $tempDir . '/' . uniqid() . '_' . $safeName;
                    
                    if (move_uploaded_file($tmpName, $tempPath)) {
                        $tempFiles[] = $tempPath;
                    }
                }
            }
        }
        $_SESSION['nre_temp_files'] = $tempFiles;

        include __DIR__ . '/../templates/nre/preview.php';
        exit;
    } catch (Exception $e) {
        $_SESSION['nre_form_data'] = $_POST;
        $_SESSION['nre_form_error'] = $e->getMessage();
        header('Location: ./?action=new');
        exit;
    }
}

// --- Confirm: procesa datos + archivos reales ---
if ($action === 'confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['nre_items'])) {
            throw new Exception("No hay datos para enviar.");
        }
        
        $tempFiles = $_SESSION['nre_temp_files'] ?? [];
        $controller = new NreController();
        
        // Pasamos rutas temporales en lugar de $_FILES
        $success = $controller->createFromForm($_SESSION['nre_items'], $tempFiles, $user_id);
        
        unset($_SESSION['nre_items'], $_SESSION['nre_nre_numbers'], $_SESSION['nre_temp_files']);
        
        if ($success) {
            $_SESSION['nre_message'] = "✅ Solicitud enviada. Revisa tu correo.";
            header('Location: ./');
            exit;
        } else {
            throw new Exception("Error al procesar la solicitud.");
        }
    } catch (Exception $e) {
        error_log("[Index] Error en confirm: " . $e->getMessage());
        $_SESSION['nre_form_error'] = $e->getMessage();
        header('Location: ./?action=new');
        exit;
    }
}

// --- Acciones de gestión ---
if ($action === 'mark_in_process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nreNumber = $_POST['nre_number'] ?? '';
    if ($nreNumber) {
        $listController = new NreListController();
        if ($listController->markAsInProcess($nreNumber, $user_id)) {
            $_SESSION['nre_message'] = "✅ NRE $nreNumber marcado como 'En Proceso'.";
        } else {
            $_SESSION['nre_error'] = "❌ No se pudo actualizar el NRE.";
        }
    }
    header('Location: ./');
    exit;
}

if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nreNumber = $_POST['nre_number'] ?? '';
    if ($nreNumber) {
        $listController = new NreListController();
        if ($listController->cancelNre($nreNumber, $user_id)) {
            $_SESSION['nre_message'] = "✅ NRE $nreNumber cancelado.";
        } else {
            $_SESSION['nre_error'] = "❌ No se pudo cancelar el NRE.";
        }
    }
    header('Location: ./');
    exit;
}

if ($action === 'mark_arrived' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nreNumber = $_POST['nre_number'] ?? '';
    $arrivalDate = $_POST['arrival_date'] ?? date('Y-m-d');
    if ($nreNumber) {
        $listController = new NreListController();
        if ($listController->markAsArrived($nreNumber, $user_id, $arrivalDate)) {
            $_SESSION['nre_message'] = "✅ NRE $nreNumber finalizado.";
        } else {
            $_SESSION['nre_error'] = "❌ No se pudo finalizar el NRE.";
        }
    }
    header('Location: ./');
    exit;
}

// --- Redirección de éxito (legado) ---
if (isset($_GET['success'])) {
    $_SESSION['nre_message'] = "✅ Solicitud enviada. Revisa tu correo.";
    header('Location: ./');
    exit;
}

// --- Mostrar formulario de nuevo NRE ---
if ($action === 'new') {
    require_once __DIR__ . '/../templates/nre/create.php';
    exit;
}

// --- Mostrar lista principal ---
require_once __DIR__ . '/../src/models/User.php';
$currentUser = new User($user_id);
$isAdmin = $currentUser->isAdmin();

$includeCompleted = isset($_GET['show_completed']);
$listController = new NreListController();
$nres = $listController->listNres($user_id, $isAdmin, $includeCompleted);

// Mostrar mensajes globales
if (!empty($_SESSION['nre_message'])) {
    echo "<div class='alert alert-success m-4'>" . htmlspecialchars($_SESSION['nre_message']) . "</div>";
    unset($_SESSION['nre_message']);
}
if (!empty($_SESSION['nre_error'])) {
    echo "<div class='alert alert-danger m-4'>" . htmlspecialchars($_SESSION['nre_error']) . "</div>";
    unset($_SESSION['nre_error']);
}

require_once __DIR__ . '/../templates/nre/list.php';