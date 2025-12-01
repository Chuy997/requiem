<?php
// templates/partials/header.php
if (!isset($title)) $title = 'NRE System';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php if (isset($_SESSION['user_id']) && in_array($_SESSION['user_id'], [1, 2, 3], true)): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/">NRE System</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/?action=new">Nuevo NRE</a></li>
                <?php if ($_SESSION['user_id'] === 1): ?>
                    <li class="nav-item"><a class="nav-link" href="/exchange-rates">Tipos de Cambio</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text">Hola, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></span>
            <a href="/logout.php" class="btn btn-outline-light ms-2">Salir</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<div class="container mt-4">