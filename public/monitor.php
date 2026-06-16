<?php
// public/monitor.php
// Punto de entrada sin autenticación para modo consulta deashboard

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/Nre.php';
require_once __DIR__ . '/../src/controllers/NreListController.php';

// Variables de entorno virtuales para permitir flujo público como "Admin Virtual"
$isAdmin = true;
$isCompras = false;
$canViewAll = true;
$user_id = 0; // ID Dummy para bypass seguro

$includeCompleted = isset($_GET['show_completed']);
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$type = $_GET['type'] ?? null;
$listController = new NreListController();
$nres = $listController->listNres($user_id, $canViewAll, $includeCompleted, $type, $limit, $offset);
$totalNres = $listController->getTotalNres($user_id, $canViewAll, $includeCompleted, $type);

require_once __DIR__ . '/../templates/nre/list_monitor.php';
