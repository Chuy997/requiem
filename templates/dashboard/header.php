<?php
// templates/dashboard/header.php
// Simplified header for public dashboard
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="/requiem/favicon.png?v=1">
    <link rel="shortcut icon" type="image/png" href="/requiem/favicon.png?v=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requiem - Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="/requiem/public/assets/css/laravel-theme.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dashboard-main {
            min-height: calc(100vh - 160px);
            background-color: #f8fafc;
        }
        .status-badge {
            font-size: 0.85em;
            padding: 0.4em 0.8em;
            border-radius: 9999px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="dashboard-header mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-layers-fill fs-3"></i>
                <div>
                    <h1 class="h4 m-0 fw-bold">REQUIEM</h1>
                    <small class="opacity-75">Manager Dashboard</small>
                </div>
            </div>
            <div>
                <a href="login.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-person-circle me-1"></i> Login
                </a>
            </div>
        </div>
    </header>
    
    <div class="dashboard-main pb-5">
        <div class="container">
