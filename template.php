<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Far-María de Lourdes - <?php echo $pageTitle ?? 'Inicio'; ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Estilos personalizados -->
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .main-wrapper {
            display: flex;
            flex: 1;
        }
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.toggled {
            margin-left: -250px;
        }
        #sidebar .nav-link {
            color: #adb5bd;
        }
        #sidebar .nav-link:hover {
            color: #fff;
            background: #495057;
        }
        #sidebar .nav-link .bi {
            margin-right: 10px;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.toggled {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- Menú Lateral -->
    <nav id="sidebar" class="d-flex flex-column p-3 text-white bg-dark">
        <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-capsule-pill me-2"></i>
            <span class="fs-4">Far-MDL</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
            <li><a href="sucursales.php" class="nav-link"><i class="bi bi-shop-window"></i>Sucursales</a></li>
            <li><a href="products.php" class="nav-link"><i class="bi bi-box-seam"></i>Productos</a></li>
            <!-- <li><a href="lotes.php" class="nav-link"><i class="bi bi-boxes"></i>Lotes</a></li> -->
            <!-- <li><a href="cotizaciones.php" class="nav-link"><i class="bi bi-file-earmark-text"></i>Cotizaciones</a></li> -->
            <hr>
            <!-- <li><a href="ventas_pendientes.php" class="nav-link"><i class="bi bi-cart-check"></i>Ventas Pendientes</a></li> -->
            <!-- <li><a href="ventas_contingencia.php" class="nav-link text-warning"><i class="bi bi-exclamation-triangle"></i>Ventas Contingencia</a></li> -->
            <hr>
            <!-- <li><a href="configuracion_fiscal.php" class="nav-link"><i class="bi bi-gear"></i>Configuración Fiscal</a></li> -->
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="https://via.placeholder.com/32" alt="" width="32" height="32" class="rounded-circle me-2">
                <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#">Perfil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
            </ul>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="content">
        <?php // El contenido de cada vista se insertará aquí ?>
    </main>
</div>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>