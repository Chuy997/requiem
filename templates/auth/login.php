<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Requiem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/requiem/public/assets/css/laravel-theme.css" rel="stylesheet">
</head>
<body class="login-page">
    
    <a href="#" class="login-logo">
        REQUIEM
    </a>

    <div class="login-card">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-4">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus placeholder="nombre@xinya-la.com">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label text-muted" for="remember" style="font-size: 0.875rem;">
                        Recordarme
                    </label>
                </div>
                <!-- <a href="#" class="text-decoration-none" style="color: var(--laravel-red); font-size: 0.875rem;">
                    ¿Olvidaste tu contraseña?
                </a> -->
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                INICIAR SESIÓN
            </button>
        </form>
    </div>

    <div class="mt-4 text-muted" style="font-size: 0.875rem;">
        &copy; <?= date('Y') ?> Xinya Electronics.
    </div>

</body>
</html>