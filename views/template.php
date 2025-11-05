<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Iniciar la sesión solo si no hay una activa.
}
?>
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

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Favicon dinámico -->
    <link rel="icon" href="../<?php echo htmlspecialchars($_SESSION['EMPRESA_LOGO_PATH'] ?? 'assets/img/logo_empresa.png'); ?>">

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
        #sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd; /* Color primario de Bootstrap */
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
        <?php
        // Obtener el nombre del archivo de la página actual para el resaltado del menú
        $currentPage = basename($_SERVER['PHP_SELF']);
        ?>
        <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <!-- Logo de la empresa -->
            <img src="../<?php echo htmlspecialchars($_SESSION['EMPRESA_LOGO_PATH'] ?? 'assets/img/logo_empresa.png'); ?>" alt="Logo" style="height: 40px;" class="me-2">
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i>Dashboard</a>
            </li>
            <li>
                <a href="sucursales.php" class="nav-link <?php echo ($currentPage == 'sucursales.php') ? 'active' : ''; ?>"><i class="bi bi-shop-window"></i>Sucursales</a>
            </li>
            <li>
                <a href="products.php" class="nav-link <?php echo ($currentPage == 'products.php') ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i>Productos</a>
            </li>
            <li>
                <a href="laboratorios.php" class="nav-link <?php echo ($currentPage == 'laboratorios.php') ? 'active' : ''; ?>"><i class="bi bi-building"></i>Laboratorios</a>
            </li>
            <li>
                <a href="droguerias.php" class="nav-link <?php echo ($currentPage == 'droguerias.php') ? 'active' : ''; ?>"><i class="bi bi-truck"></i>Droguerías</a>
            </li>
            <li>
                <a href="medidas.php" class="nav-link <?php echo ($currentPage == 'medidas.php') ? 'active' : ''; ?>"><i class="bi bi-rulers"></i>Medidas</a>
            </li>
            <li>
                <a href="categories.php" class="nav-link <?php echo ($currentPage == 'categories.php') ? 'active' : ''; ?>"><i class="bi bi-tags"></i>Categorías</a>
            </li>
            <li>
                <a href="clients.php" class="nav-link <?php echo ($currentPage == 'clients.php') ? 'active' : ''; ?>"><i class="bi bi-people"></i>Clientes</a>
            </li>
            <li>
                <a href="#quotesSubmenu" data-bs-toggle="collapse" class="nav-link <?php echo in_array($currentPage, ['cotizaciones.php', 'lista_cotizaciones.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>Cotizaciones
                </a>
                <ul class="collapse list-unstyled <?php echo in_array($currentPage, ['cotizaciones.php', 'lista_cotizaciones.php']) ? 'show' : ''; ?>" id="quotesSubmenu">
                    <li class="ps-4"><a href="cotizaciones.php" class="nav-link <?php echo ($currentPage == 'cotizaciones.php') ? 'active' : ''; ?>">Crear Cotización</a></li>
                    <li class="ps-4"><a href="lista_cotizaciones.php" class="nav-link <?php echo ($currentPage == 'lista_cotizaciones.php') ? 'active' : ''; ?>">Ver Listado</a></li>
                </ul>
            </li>
            <?php if (isset($_SESSION['user_permissions']) && (in_array('manage_users', $_SESSION['user_permissions']) || in_array('manage_roles', $_SESSION['user_permissions']) || in_array('manage_system_settings', $_SESSION['user_permissions']))): ?>
            <li>
                <a href="#adminSubmenu" data-bs-toggle="collapse" class="nav-link <?php echo in_array($currentPage, ['users.php', 'roles.php', 'system_config.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-gear"></i>Administración
                </a>
                <ul class="collapse list-unstyled <?php echo in_array($currentPage, ['users.php', 'roles.php', 'system_config.php']) ? 'show' : ''; ?>" id="adminSubmenu">
                    <?php if (in_array('manage_users', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="users.php" class="nav-link <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">Usuarios</a></li>
                    <?php endif; ?>
                    <?php if (in_array('manage_system_settings', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="system_config.php" class="nav-link <?php echo ($currentPage == 'system_config.php') ? 'active' : ''; ?>">Configuración</a></li>
                    <?php endif; ?>
                    <?php if (in_array('manage_roles', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="roles.php" class="nav-link <?php echo ($currentPage == 'roles.php') ? 'active' : ''; ?>">Roles y Permisos</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <li>
                <a href="purchases.php" class="nav-link <?php echo in_array($currentPage, ['purchases.php', 'new_purchase.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-cart4"></i>Compras
                </a>
            </li>
            <?php if (in_array('view_reports', $_SESSION['user_permissions'])): ?>
            <li>
                <a href="#reportsSubmenu" data-bs-toggle="collapse" class="nav-link <?php echo in_array($currentPage, ['reporte_ventas.php', 'reporte_conversiones.php', 'reporte_inventario.php', 'reporte_vencimientos.php', 'reporte_historial_cliente.php', 'reporte_mas_vendidos.php', 'reporte_ventas_vendedor.php', 'reporte_ajustes.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i>Reportes
                </a>
                <ul class="collapse list-unstyled <?php echo in_array($currentPage, ['reporte_ventas.php', 'reporte_conversiones.php', 'reporte_inventario.php', 'reporte_vencimientos.php', 'reporte_historial_cliente.php', 'reporte_mas_vendidos.php', 'reporte_ventas_vendedor.php', 'reporte_ajustes.php']) ? 'show' : ''; ?>" id="reportsSubmenu">
                    <li class="ps-4"><a href="reporte_ventas.php" class="nav-link <?php echo ($currentPage == 'reporte_ventas.php') ? 'active' : ''; ?>">Ventas Generales</a></li>
                    <?php if (in_array('view_sales_by_seller_report', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="reporte_ventas_vendedor.php" class="nav-link <?php echo ($currentPage == 'reporte_ventas_vendedor.php') ? 'active' : ''; ?>">Ventas por Vendedor</a></li>
                    <?php endif; ?>
                    <?php if (in_array('view_bestsellers_report', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="reporte_mas_vendidos.php" class="nav-link <?php echo ($currentPage == 'reporte_mas_vendidos.php') ? 'active' : ''; ?>">Productos Más Vendidos</a></li>
                    <?php endif; ?>
                    <li class="ps-4"><a href="reporte_conversiones.php" class="nav-link <?php echo ($currentPage == 'reporte_conversiones.php') ? 'active' : ''; ?>">Conversión de Cotización</a></li>
                    <?php if (in_array('view_inventory_report', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="reporte_inventario.php" class="nav-link <?php echo ($currentPage == 'reporte_inventario.php') ? 'active' : ''; ?>">Valor de Inventario</a></li>
                    <?php endif; ?>
                    <?php if (in_array('view_expiration_report', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="reporte_vencimientos.php" class="nav-link <?php echo ($currentPage == 'reporte_vencimientos.php') ? 'active' : ''; ?>">Próximos a Vencer</a></li>
                    <?php endif; ?>
                    <?php if (in_array('view_client_purchase_history', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="reporte_historial_cliente.php" class="nav-link <?php echo ($currentPage == 'reporte_historial_cliente.php') ? 'active' : ''; ?>">Historial por Cliente</a></li>
                    <?php endif; ?>
                    <?php if (in_array('view_adjustment_report', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="reporte_ajustes.php" class="nav-link <?php echo ($currentPage == 'reporte_ajustes.php') ? 'active' : ''; ?>">Historial de Ajustes</a></li>
                    <?php endif; ?>
                    <?php if (in_array('view_audit_log', $_SESSION['user_permissions'])): ?>
                    <li class="ps-4"><a href="reporte_auditoria.php" class="nav-link <?php echo ($currentPage == 'reporte_auditoria.php') ? 'active' : ''; ?>">Auditoría del Sistema</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php if (in_array('manage_inventory_adjustments', $_SESSION['user_permissions'])): ?>
            <li>
                <a href="ajustes_inventario.php" class="nav-link <?php echo ($currentPage == 'ajustes_inventario.php') ? 'active' : ''; ?>"><i class="bi bi-sliders"></i>Ajustes de Inventario</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('manage_rutas', $_SESSION['user_permissions'])): ?>
            <li>
                <a href="rutas.php" class="nav-link <?php echo ($currentPage == 'rutas.php') ? 'active' : ''; ?>"><i class="bi bi-truck-front"></i>Gestión de Rutas</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('use_ruta_tpv', $_SESSION['user_permissions'])): ?>
            <li>
                <a href="ruta_tpv.php" class="nav-link <?php echo ($currentPage == 'ruta_tpv.php') ? 'active' : ''; ?>"><i class="bi bi-phone-vibrate"></i>TPV de Ruta</a>
            </li>
            <?php endif; ?>
            <li>
                <a href="tpv.php" class="nav-link <?php echo ($currentPage == 'tpv.php') ? 'active' : ''; ?>"><i class="bi bi-display"></i>TPV</a>
            </li>
            <hr>
            <!-- Los enlaces comentados no se verán afectados -->
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#">Perfil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../controllers/logout.php">Cerrar sesión</a></li>
            </ul>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="content">
        <?php echo $content ?? ''; // El contenido de cada vista se inserta aquí ?>
    </main>
</div>

<!-- jQuery (requerido por Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Bootstrap 5.3 JS (siempre al final si otros scripts no dependen de él) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>