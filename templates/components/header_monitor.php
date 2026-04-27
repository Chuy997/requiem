<?php
// templates/components/header_monitor.php
// Public navigation header for monitor mode (Read Only)

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$action = $_GET['action'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="/requiem/favicon.png?v=1">
    <link rel="shortcut icon" type="image/png" href="/requiem/favicon.png?v=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Monitor Requiem' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="/requiem/public/assets/css/laravel-theme.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-white" href="monitor.php">
                <i class="bi bi-display me-2 text-info"></i>
                <span>MONITOR REQUIEM</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'monitor' ? 'active text-info' : 'text-light' ?>" href="monitor.php">
                            Monitor Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'monitor_reports' ? 'active text-info' : 'text-light' ?>" href="monitor_reports.php">
                            Reports
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-shield-lock"></i> Read-Only View Mode
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="main-content py-5">
        <div class="container">
