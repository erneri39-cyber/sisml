-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-11-2025 a las 00:05:31
-- Versión del servidor: 10.4.21-MariaDB
-- Versión de PHP: 7.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sisfarmarialourdes`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `batch`
--

CREATE TABLE `batch` (
  `id_batch` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `batch_number` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `expiration_date` date NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `purchase_price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `sale_price_2` decimal(10,2) DEFAULT 0.00,
  `sale_price_3` decimal(10,2) DEFAULT 0.00,
  `sale_price_b` decimal(10,2) DEFAULT NULL,
  `sale_price_c` decimal(10,2) DEFAULT NULL,
  `id_branch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `batch`
--

INSERT INTO `batch` (`id_batch`, `id_product`, `batch_number`, `expiration_date`, `stock`, `purchase_price`, `sale_price`, `sale_price_2`, `sale_price_3`, `sale_price_b`, `sale_price_c`, `id_branch`) VALUES
(1, 3, 'INI-1762036354', '2030-11-01', 0, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 1),
(2, 4, 'INI-1762036514', '2030-11-01', 0, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 1),
(3, 3, 'EY5A', '2026-02-01', 1, '9.80', '15.45', '0.00', '13.15', '0.00', '12.35', 1),
(4, 4, '2409394', '2026-07-01', 1, '9.25', '14.95', '0.00', '11.95', '0.00', '11.25', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `branch`
--

CREATE TABLE `branch` (
  `id_branch` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `branch`
--

INSERT INTO `branch` (`id_branch`, `name`, `address`, `phone`, `is_active`) VALUES
(1, 'Casa Matriz', '4a. avenida norte 1-10a', '78354316', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `category`
--

CREATE TABLE `category` (
  `id_category` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `category`
--

INSERT INTO `category` (`id_category`, `name`, `is_active`) VALUES
(1, 'Laxantes', 1),
(2, 'Vitaminas en tabletas', 1),
(3, 'Suplemento dietético', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detail_purchase`
--

CREATE TABLE `detail_purchase` (
  `id_detail_purchase` int(11) NOT NULL,
  `id_purchase` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `cod_pventa` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `detail_purchase`
--

INSERT INTO `detail_purchase` (`id_detail_purchase`, `id_purchase`, `id_batch`, `quantity`, `unit_price`, `cod_pventa`) VALUES
(1, 1, 3, 1, '9.80', 'P00001B00003'),
(2, 1, 4, 1, '9.25', 'P00001B00004');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detail_quotation`
--

CREATE TABLE `detail_quotation` (
  `id_detail_quotation` int(11) NOT NULL,
  `id_quotation` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_quoted` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detail_sale`
--

CREATE TABLE `detail_sale` (
  `id_detail_sale` int(11) NOT NULL,
  `id_sale` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `sale_price_applied` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `authorized_admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `drogueria`
--

CREATE TABLE `drogueria` (
  `id_drogueria` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `vendedor` varchar(255) DEFAULT NULL,
  `contacto` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `drogueria`
--

INSERT INTO `drogueria` (`id_drogueria`, `nombre`, `vendedor`, `contacto`, `telefono`, `correo`, `direccion`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Pueblo bodega', 'Joel', 'xx', 'xx', 'joel@pueblo.com', 'xx', 1, '2025-10-30 13:25:05', '2025-10-30 13:25:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_adjustment`
--

CREATE TABLE `inventory_adjustment` (
  `id_adjustment` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `adjustment_type` enum('Entrada','Salida') NOT NULL COMMENT 'Tipo de ajuste',
  `quantity` int(11) NOT NULL COMMENT 'Cantidad ajustada (siempre positiva)',
  `reason` varchar(255) NOT NULL COMMENT 'Motivo del ajuste',
  `adjustment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laboratorio`
--

CREATE TABLE `laboratorio` (
  `id_laboratorio` int(11) NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `contacto` varchar(100) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci COMMENT='Almacena los laboratorios o fabricantes de productos';

--
-- Volcado de datos para la tabla `laboratorio`
--

INSERT INTO `laboratorio` (`id_laboratorio`, `nombre`, `contacto`, `telefono`, `direccion`, `is_active`) VALUES
(1, 'Balaxi', 'xx', 'xx', 'san salvador', 1),
(2, 'Global pharma', 'xx', 'xx', 'xx', 1),
(3, 'GSK', 'xx', 'xx', 'xx\n', 1),
(4, 'Vijosa', 'xx', 'xx', 'xx', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medida`
--

CREATE TABLE `medida` (
  `id_medida` int(11) NOT NULL,
  `descripcion` varchar(100) COLLATE utf8mb4_spanish2_ci NOT NULL COMMENT 'Ej: Caja, Frasco, Blister',
  `presentacion` varchar(50) COLLATE utf8mb4_spanish2_ci DEFAULT NULL COMMENT 'Ej: 10 Unidades, 120 ml, 10 tabletas',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci COMMENT='Almacena las unidades de medida y presentaciones de productos';

--
-- Volcado de datos para la tabla `medida`
--

INSERT INTO `medida` (`id_medida`, `descripcion`, `presentacion`, `is_active`) VALUES
(1, 'Caja x 10 tabletas', NULL, 1),
(2, 'Bote x 400 grs.', NULL, 1),
(3, 'Caja x 30 tabletas recubiertas', NULL, 1),
(4, 'Caja con frasco x 50 softgels', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permission`
--

CREATE TABLE `permission` (
  `id_permission` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'Nombre clave del permiso (ej: manage_users)',
  `description` varchar(255) DEFAULT NULL COMMENT 'Descripción amigable de lo que hace el permiso',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `permission`
--

INSERT INTO `permission` (`id_permission`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'view_inventory_report', 'Permite ver el reporte de valor de inventario', 1, '2025-10-30 18:11:22', '2025-10-30 18:11:22'),
(2, 'manage_users', 'Permite crear, editar y eliminar usuarios del sistema.', 1, '2025-10-30 19:13:33', '2025-10-30 19:13:33'),
(3, 'manage_roles', 'Permite crear, editar y asignar permisos a los roles.', 1, '2025-10-30 19:13:33', '2025-10-30 19:13:33'),
(4, 'view_reports', 'Permite acceder a la sección de reportes.', 1, '2025-10-30 19:13:33', '2025-10-30 19:13:33'),
(5, 'create_sale', 'Permite realizar ventas en el Terminal Punto de Venta (TPV).', 1, '2025-10-30 19:13:33', '2025-10-30 19:13:33'),
(6, 'manage_products', 'Permite crear y editar productos en el catálogo.', 1, '2025-10-30 19:13:33', '2025-10-30 19:13:33'),
(7, 'manage_purchases', 'Permite registrar nuevas compras de productos a proveedores.', 1, '2025-10-30 19:13:33', '2025-10-30 19:13:33'),
(8, 'manage_inventory_adjustments', 'Permite realizar entradas y salidas manuales de inventario.', 1, '2025-10-30 19:38:04', '2025-10-30 19:38:04'),
(9, 'manage_clients', 'Permite crear, editar y eliminar clientes.', 1, '2025-10-31 01:05:41', '2025-10-31 01:05:41'),
(10, 'view_bestsellers_report', 'Permite ver el reporte de productos más vendidos.', 1, '2025-10-31 12:53:06', '2025-10-31 12:53:06'),
(11, 'view_sales_by_seller_report', 'Permite ver el reporte de ventas por vendedor.', 1, '2025-10-31 13:04:03', '2025-10-31 13:04:03'),
(12, 'manage_system_settings', 'Permite editar la configuración general del sistema.', 1, '2025-10-31 13:16:25', '2025-10-31 13:16:25'),
(13, 'manage_rutas', 'Permite gestionar los pedidos de ventas de rutero', 1, '2025-10-31 19:06:04', '2025-10-31 19:06:04'),
(14, 'use_ruta_tpv', 'Permite usar el TPV de Ruta para tomar pedidos.', 1, '2025-11-01 16:10:37', '2025-11-01 16:10:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `person`
--

CREATE TABLE `person` (
  `id_person` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `document_number` varchar(20) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `person_type` enum('Cliente','Empleado','Proveedor') COLLATE utf8mb4_spanish2_ci NOT NULL,
  `DUI` varchar(10) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `NIT` varchar(17) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `NRC` varchar(15) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `person`
--

INSERT INTO `person` (`id_person`, `name`, `document_number`, `address`, `phone`, `email`, `person_type`, `DUI`, `NIT`, `NRC`, `is_active`) VALUES
(2, 'Administrador del Sistema', '00000000-0', NULL, NULL, NULL, 'Empleado', NULL, NULL, NULL, 1),
(3, 'Marvin', '039165879', '', '', '', 'Empleado', NULL, NULL, NULL, 1),
(4, 'Roberto Molina', '00000000-1', 'Ahuchapan', '', '', 'Cliente', NULL, '', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product`
--

CREATE TABLE `product` (
  `id_product` int(11) NOT NULL,
  `id_category` int(11) NOT NULL,
  `id_measurement` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `id_laboratorio` int(11) DEFAULT NULL,
  `id_medida` int(11) DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `id_branch` int(11) NOT NULL,
  `location` varchar(50) COLLATE utf8mb4_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `product`
--

INSERT INTO `product` (`id_product`, `id_category`, `id_measurement`, `name`, `id_laboratorio`, `id_medida`, `code`, `stock`, `id_branch`, `location`) VALUES
(1, 1, 2, 'Fibra-flat sabor natural', 2, 2, '7406076101178', 0, 1, NULL),
(3, 2, NULL, 'Centrum silver ', 3, 3, '7441026003492', 0, 1, NULL),
(4, 3, NULL, 'Fortiplex omega 3 ', 4, 4, '7415100201241', 0, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_images`
--

CREATE TABLE `product_images` (
  `id_product_image` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 si es la imagen principal, 0 si no',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `product_images`
--

INSERT INTO `product_images` (`id_product_image`, `id_product`, `image_path`, `is_primary`, `created_at`) VALUES
(1, 1, 'uploads/products/6904d6bf585ed-descarga.jpg', 1, '2025-10-31 15:33:19'),
(2, 3, 'uploads/products/6906956391ab6-image-6ede5f3d63564847b3ab323ac00054ff.jpg', 1, '2025-11-01 23:18:59'),
(3, 4, 'uploads/products/6906957f34336-WhatsApp Image 2025-11-01 at 10.49.21 AM.jpeg', 1, '2025-11-01 23:19:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase`
--

CREATE TABLE `purchase` (
  `id_purchase` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_branch` int(11) NOT NULL,
  `purchase_date` datetime NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `document_number` varchar(50) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL DEFAULT 'Completada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `purchase`
--

INSERT INTO `purchase` (`id_purchase`, `id_supplier`, `id_user`, `id_branch`, `purchase_date`, `document_type`, `document_number`, `total_amount`, `status`) VALUES
(1, 1, 2, 1, '2025-11-03 00:00:00', 'Factura', 'DTE-01-M001P001-000000000005666', '19.05', 'Completada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quotation`
--

CREATE TABLE `quotation` (
  `id_quotation` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_branch` int(11) NOT NULL,
  `quotation_date` datetime NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pendiente','Aceptada','Cancelada','Venta Generada') COLLATE utf8mb4_spanish2_ci NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `name`, `is_active`) VALUES
(1, 'Administrador', 1),
(2, 'Gerente', 1),
(3, 'Vendedor', 1),
(5, 'Vendedor rutero', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_permission`
--

CREATE TABLE `role_permission` (
  `id_rol` int(11) NOT NULL,
  `id_permission` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `role_permission`
--

INSERT INTO `role_permission` (`id_rol`, `id_permission`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(3, 5),
(3, 9),
(5, 13),
(5, 14);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sale`
--

CREATE TABLE `sale` (
  `id_sale` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_branch` int(11) NOT NULL,
  `sale_date` datetime NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `tax_breakdown` text COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `electronic_status` enum('N/A','Aceptada MH','Rechazada MH','Contingencia - Pendiente de Envío','Contingencia - Enviada y Aceptada') COLLATE utf8mb4_spanish2_ci NOT NULL DEFAULT 'N/A',
  `codigo_generacion` varchar(50) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `sello_recibido` varchar(255) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `pdf_dte_path` varchar(255) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `contingency_serial` varchar(50) COLLATE utf8mb4_spanish2_ci DEFAULT NULL,
  `payment_method` enum('Efectivo','Tarjeta','Transferencia','Crédito') COLLATE utf8mb4_spanish2_ci NOT NULL,
  `cash_received` decimal(10,2) DEFAULT 0.00,
  `cash_change` decimal(10,2) DEFAULT 0.00,
  `print_status` enum('Completada','En Espera','Impresa') COLLATE utf8mb4_spanish2_ci NOT NULL DEFAULT 'Completada',
  `is_rutero_sale` tinyint(1) NOT NULL DEFAULT 0,
  `id_quotation` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_config`
--

CREATE TABLE `system_config` (
  `id_config` int(11) NOT NULL,
  `key_name` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `value` text COLLATE utf8mb4_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `system_config`
--

INSERT INTO `system_config` (`id_config`, `key_name`, `value`) VALUES
(1, 'FISCAL_MODE', 'TRADICIONAL'),
(2, 'EMPRESA_NOMBRE', 'Far-MaríadeLourdes'),
(3, 'EMPRESA_LOGO_PATH', 'assets/img/logo_empresa.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `id_person` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `id_rol` int(11) NOT NULL,
  `id_branch` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `user`
--

INSERT INTO `user` (`id_user`, `id_person`, `username`, `password`, `id_rol`, `id_branch`, `is_active`) VALUES
(2, 2, 'admin', '$2y$10$qL32.EPdmPpRxaubBnVUOeCSb5A12VJBo89iOe1O3ABvmeZXiTDmy', 1, 1, 1),
(3, 3, 'marvin', '$2y$10$paL67pKWHzDG1dI1u82GDOHhT.3rVlZN22DILZOYDSiYsU9QKb48O', 5, 1, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `batch`
--
ALTER TABLE `batch`
  ADD PRIMARY KEY (`id_batch`),
  ADD KEY `id_product` (`id_product`),
  ADD KEY `id_branch` (`id_branch`);

--
-- Indices de la tabla `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`id_branch`);

--
-- Indices de la tabla `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id_category`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `detail_purchase`
--
ALTER TABLE `detail_purchase`
  ADD PRIMARY KEY (`id_detail_purchase`),
  ADD UNIQUE KEY `cod_pventa` (`cod_pventa`),
  ADD KEY `id_purchase` (`id_purchase`),
  ADD KEY `id_batch` (`id_batch`);

--
-- Indices de la tabla `detail_quotation`
--
ALTER TABLE `detail_quotation`
  ADD PRIMARY KEY (`id_detail_quotation`),
  ADD KEY `id_quotation` (`id_quotation`),
  ADD KEY `fk_detail_quotation_product` (`id_product`),
  ADD KEY `fk_detail_quotation_batch` (`id_batch`);

--
-- Indices de la tabla `detail_sale`
--
ALTER TABLE `detail_sale`
  ADD PRIMARY KEY (`id_detail_sale`),
  ADD KEY `id_sale` (`id_sale`),
  ADD KEY `id_product` (`id_product`),
  ADD KEY `id_batch` (`id_batch`);

--
-- Indices de la tabla `drogueria`
--
ALTER TABLE `drogueria`
  ADD PRIMARY KEY (`id_drogueria`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `inventory_adjustment`
--
ALTER TABLE `inventory_adjustment`
  ADD PRIMARY KEY (`id_adjustment`),
  ADD KEY `fk_adj_product` (`id_product`),
  ADD KEY `fk_adj_batch` (`id_batch`),
  ADD KEY `fk_adj_user` (`id_user`);

--
-- Indices de la tabla `laboratorio`
--
ALTER TABLE `laboratorio`
  ADD PRIMARY KEY (`id_laboratorio`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `medida`
--
ALTER TABLE `medida`
  ADD PRIMARY KEY (`id_medida`),
  ADD UNIQUE KEY `descripcion` (`descripcion`);

--
-- Indices de la tabla `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`id_permission`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `person`
--
ALTER TABLE `person`
  ADD PRIMARY KEY (`id_person`);

--
-- Indices de la tabla `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id_product`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `id_branch` (`id_branch`),
  ADD KEY `fk_product_laboratorio` (`id_laboratorio`),
  ADD KEY `fk_product_medida` (`id_medida`),
  ADD KEY `idx_product_name` (`name`);

--
-- Indices de la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id_product_image`),
  ADD KEY `fk_product_images_product` (`id_product`);

--
-- Indices de la tabla `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`id_purchase`),
  ADD KEY `id_branch` (`id_branch`);

--
-- Indices de la tabla `quotation`
--
ALTER TABLE `quotation`
  ADD PRIMARY KEY (`id_quotation`),
  ADD KEY `id_branch` (`id_branch`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`id_rol`,`id_permission`),
  ADD KEY `fk_rolepermission_permission` (`id_permission`);

--
-- Indices de la tabla `sale`
--
ALTER TABLE `sale`
  ADD PRIMARY KEY (`id_sale`),
  ADD KEY `id_branch` (`id_branch`),
  ADD KEY `fk_sale_quotation` (`id_quotation`);

--
-- Indices de la tabla `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id_config`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `id_person` (`id_person`),
  ADD KEY `id_branch` (`id_branch`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `batch`
--
ALTER TABLE `batch`
  MODIFY `id_batch` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `branch`
--
ALTER TABLE `branch`
  MODIFY `id_branch` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `category`
--
ALTER TABLE `category`
  MODIFY `id_category` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detail_purchase`
--
ALTER TABLE `detail_purchase`
  MODIFY `id_detail_purchase` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detail_quotation`
--
ALTER TABLE `detail_quotation`
  MODIFY `id_detail_quotation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detail_sale`
--
ALTER TABLE `detail_sale`
  MODIFY `id_detail_sale` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `drogueria`
--
ALTER TABLE `drogueria`
  MODIFY `id_drogueria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inventory_adjustment`
--
ALTER TABLE `inventory_adjustment`
  MODIFY `id_adjustment` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laboratorio`
--
ALTER TABLE `laboratorio`
  MODIFY `id_laboratorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `medida`
--
ALTER TABLE `medida`
  MODIFY `id_medida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `permission`
--
ALTER TABLE `permission`
  MODIFY `id_permission` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `person`
--
ALTER TABLE `person`
  MODIFY `id_person` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `product`
--
ALTER TABLE `product`
  MODIFY `id_product` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id_product_image` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `purchase`
--
ALTER TABLE `purchase`
  MODIFY `id_purchase` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `quotation`
--
ALTER TABLE `quotation`
  MODIFY `id_quotation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sale`
--
ALTER TABLE `sale`
  MODIFY `id_sale` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `batch`
--
ALTER TABLE `batch`
  ADD CONSTRAINT `batch_ibfk_1` FOREIGN KEY (`id_product`) REFERENCES `product` (`id_product`),
  ADD CONSTRAINT `batch_ibfk_2` FOREIGN KEY (`id_branch`) REFERENCES `branch` (`id_branch`);

--
-- Filtros para la tabla `detail_purchase`
--
ALTER TABLE `detail_purchase`
  ADD CONSTRAINT `detail_purchase_ibfk_1` FOREIGN KEY (`id_purchase`) REFERENCES `purchase` (`id_purchase`),
  ADD CONSTRAINT `detail_purchase_ibfk_2` FOREIGN KEY (`id_batch`) REFERENCES `batch` (`id_batch`);

--
-- Filtros para la tabla `detail_quotation`
--
ALTER TABLE `detail_quotation`
  ADD CONSTRAINT `detail_quotation_ibfk_1` FOREIGN KEY (`id_quotation`) REFERENCES `quotation` (`id_quotation`),
  ADD CONSTRAINT `fk_detail_quotation_batch` FOREIGN KEY (`id_batch`) REFERENCES `batch` (`id_batch`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detail_quotation_product` FOREIGN KEY (`id_product`) REFERENCES `product` (`id_product`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detail_sale`
--
ALTER TABLE `detail_sale`
  ADD CONSTRAINT `detail_sale_ibfk_1` FOREIGN KEY (`id_sale`) REFERENCES `sale` (`id_sale`),
  ADD CONSTRAINT `detail_sale_ibfk_2` FOREIGN KEY (`id_product`) REFERENCES `product` (`id_product`),
  ADD CONSTRAINT `detail_sale_ibfk_3` FOREIGN KEY (`id_batch`) REFERENCES `batch` (`id_batch`);

--
-- Filtros para la tabla `inventory_adjustment`
--
ALTER TABLE `inventory_adjustment`
  ADD CONSTRAINT `fk_adj_batch` FOREIGN KEY (`id_batch`) REFERENCES `batch` (`id_batch`),
  ADD CONSTRAINT `fk_adj_product` FOREIGN KEY (`id_product`) REFERENCES `product` (`id_product`),
  ADD CONSTRAINT `fk_adj_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`);

--
-- Filtros para la tabla `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_laboratorio` FOREIGN KEY (`id_laboratorio`) REFERENCES `laboratorio` (`id_laboratorio`),
  ADD CONSTRAINT `fk_product_medida` FOREIGN KEY (`id_medida`) REFERENCES `medida` (`id_medida`),
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`id_branch`) REFERENCES `branch` (`id_branch`);

--
-- Filtros para la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`id_product`) REFERENCES `product` (`id_product`) ON DELETE CASCADE;

--
-- Filtros para la tabla `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`id_branch`) REFERENCES `branch` (`id_branch`);

--
-- Filtros para la tabla `quotation`
--
ALTER TABLE `quotation`
  ADD CONSTRAINT `quotation_ibfk_1` FOREIGN KEY (`id_branch`) REFERENCES `branch` (`id_branch`);

--
-- Filtros para la tabla `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `fk_rolepermission_permission` FOREIGN KEY (`id_permission`) REFERENCES `permission` (`id_permission`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rolepermission_role` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sale`
--
ALTER TABLE `sale`
  ADD CONSTRAINT `fk_sale_quotation` FOREIGN KEY (`id_quotation`) REFERENCES `quotation` (`id_quotation`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sale_ibfk_1` FOREIGN KEY (`id_branch`) REFERENCES `branch` (`id_branch`);

--
-- Filtros para la tabla `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`id_person`) REFERENCES `person` (`id_person`),
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`id_branch`) REFERENCES `branch` (`id_branch`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
