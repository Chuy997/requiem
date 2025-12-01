<?php
// templates/components/header.php
// Header de navegación global para el sistema Requiem

if (!isset($_SESSION['user_id'])) {
    return; // No mostrar header si no hay sesión
}

require_once __DIR__ . '/../../src/models/User.php';

$currentUser = new User($_SESSION['user_id']);
$isAdmin = $currentUser->isAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$action = $_GET['action'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Sistema Requiem' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="/requiem/public/assets/css/laravel-theme.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary" href="index.php">
                <i class="bi bi-layers-fill me-2"></i>
                <span>REQUIEM</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index' && $action === '' ? 'active' : '' ?>" href="index.php">
                            Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'new' ? 'active' : '' ?>" href="index.php?action=new">
                            Nuevo NRE
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>" href="reports.php">
                            Reportes
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'exchange-rates' ? 'active' : '' ?>" href="exchange-rates.php">
                            Tipos de Cambio
                        </a>
                    </li>
                    
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'admin-users' ? 'active' : '' ?>" href="admin-users.php">
                            Usuarios
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="d-flex flex-column text-end lh-1 d-none d-md-block">
                                <span class="fw-semibold" style="font-size: 0.9rem;"><?= htmlspecialchars($currentUser->getFullName()) ?></span>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= $isAdmin ? 'Administrador' : 'Ingeniero' ?></span>
                            </div>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold border" style="width: 35px; height: 35px;">
                                <?= strtoupper(substr($currentUser->getFullName(), 0, 1)) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2">
                            <li class="px-3 py-2 text-muted" style="font-size: 0.8rem;">
                                <?= htmlspecialchars($currentUser->getEmail()) ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="main-content py-5">
        <div class="container">
