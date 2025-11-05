<!DOCTYPE html> 
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Far-María de Lourdes</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body>
    <main class="form-signin text-center">
        <form action="../controllers/login_controller.php" method="POST"> <!-- Esta ruta ahora es correcta -->
            <h1 class="h3 mb-3 fw-normal">Far-María de Lourdes</h1>
            <h2 class="h5 mb-3 fw-normal">Por favor, inicie sesión</h2>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Usuario" required>
                <label for="username">Nombre de Usuario</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                <label for="password">Contraseña</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Iniciar Sesión</button>
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?></p>
        </form>
    </main>
</body>
</html>