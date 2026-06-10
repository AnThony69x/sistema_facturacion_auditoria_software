-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-06-2026 a las 15:35:31
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `facturacion_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'General', NULL, 1),
(2, 'Electrónica', NULL, 1),
(3, 'Alimentos', NULL, 1),
(4, 'Ropa', NULL, 1),
(5, 'Servicios', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `tipo_identificacion` enum('cedula','ruc','pasaporte') DEFAULT 'cedula',
  `nombres` varchar(150) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `cedula`, `tipo_identificacion`, `nombres`, `direccion`, `telefono`, `correo`, `activo`, `created_at`, `updated_at`) VALUES
(1, '9999999999999', 'ruc', 'CONSUMIDOR FINAL', 'GUAYAQUIL - ECUADOR', '0000000000', 'consumidorfinal@sri.gob.ec', 1, '2026-03-31 21:35:32', '2026-03-31 21:35:32'),
(2, '1310978422', 'cedula', 'VANESSA VIVIANA VERA VERA', 'C. 11 & Av. 43', '0993733648', 'vivianavera100@gmail.com', 1, '2026-03-31 21:58:02', '2026-06-10 11:30:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `razon_social` varchar(200) NOT NULL,
  `nombre_comercial` varchar(200) DEFAULT NULL,
  `ruc` varchar(20) NOT NULL,
  `direccion_matriz` text DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `ambiente` enum('1','2') DEFAULT '1' COMMENT '1=Pruebas, 2=Produccion',
  `tipo_emision` enum('1') DEFAULT '1' COMMENT '1=Normal',
  `secuencial` int(11) DEFAULT 1,
  `establecimiento` varchar(3) DEFAULT '001',
  `punto_emision` varchar(3) DEFAULT '001',
  `certificado_p12` varchar(255) DEFAULT NULL,
  `clave_certificado` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(150) DEFAULT 'smtp.gmail.com',
  `smtp_port` int(11) DEFAULT 587,
  `smtp_user` varchar(150) DEFAULT NULL,
  `smtp_pass` varchar(255) DEFAULT NULL,
  `smtp_from_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `razon_social`, `nombre_comercial`, `ruc`, `direccion_matriz`, `telefono`, `correo`, `logo`, `ambiente`, `tipo_emision`, `secuencial`, `establecimiento`, `punto_emision`, `certificado_p12`, `clave_certificado`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_from_name`, `created_at`) VALUES
(1, 'MI EMPRESA S.A.', 'MI EMPRESA', '0999999999001', 'AV. PRINCIPAL 123, GUAYAQUIL', '0999999999', 'empresa@correo.com', NULL, '1', '1', 1, '001', '001', NULL, NULL, 'smtp.gmail.com', 587, NULL, NULL, NULL, '2026-03-31 21:35:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `numero_factura` varchar(20) NOT NULL,
  `clave_acceso` varchar(50) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_emision` date NOT NULL,
  `subtotal_sin_iva` decimal(10,4) DEFAULT 0.0000,
  `subtotal_con_iva` decimal(10,4) DEFAULT 0.0000,
  `descuento` decimal(10,4) DEFAULT 0.0000,
  `iva_total` decimal(10,4) DEFAULT 0.0000,
  `total` decimal(10,4) DEFAULT 0.0000,
  `forma_pago` enum('efectivo','tarjeta','transferencia','cheque','credito') DEFAULT 'efectivo',
  `observaciones` text DEFAULT NULL,
  `estado` enum('borrador','autorizada','anulada','pendiente') DEFAULT 'borrador',
  `estado_sri` varchar(50) DEFAULT NULL,
  `xml_generado` longtext DEFAULT NULL,
  `xml_autorizado` longtext DEFAULT NULL,
  `fecha_autorizacion` datetime DEFAULT NULL,
  `numero_autorizacion` varchar(50) DEFAULT NULL,
  `enviado_correo` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_detalle`
--

CREATE TABLE `factura_detalle` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `cantidad` decimal(10,4) NOT NULL,
  `precio_unitario` decimal(10,4) NOT NULL,
  `descuento` decimal(10,4) DEFAULT 0.0000,
  `iva_porcentaje` decimal(5,2) DEFAULT 15.00,
  `subtotal` decimal(10,4) NOT NULL,
  `iva_valor` decimal(10,4) NOT NULL,
  `total` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_id` int(11) DEFAULT 1,
  `existencia` decimal(10,2) DEFAULT 0.00,
  `precio_compra` decimal(10,4) DEFAULT 0.0000,
  `precio_venta` decimal(10,4) DEFAULT 0.0000,
  `iva` decimal(5,2) DEFAULT 15.00,
  `unidad_medida` varchar(30) DEFAULT 'UNIDAD',
  `foto` varchar(255) DEFAULT 'default_product.png',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombres` varchar(150) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT 'default.png',
  `rol` enum('admin','cajero','bodeguero') DEFAULT 'cajero',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombres`, `username`, `password`, `foto`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Administrador Sistema', 'admin', '$2y$12$u62ThRK9IKL53/3FKY.uWeKar9LyHczQq0crO6Zz0iP/aPf3teJPe', 'default.png', 'admin', 1, '2026-03-31 21:35:31', '2026-06-10 11:27:21');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_facturas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_facturas` (
`id` int(11)
,`numero_factura` varchar(20)
,`clave_acceso` varchar(50)
,`fecha_emision` date
,`cedula` varchar(20)
,`cliente` varchar(150)
,`cliente_correo` varchar(150)
,`usuario` varchar(150)
,`subtotal_sin_iva` decimal(10,4)
,`subtotal_con_iva` decimal(10,4)
,`descuento` decimal(10,4)
,`iva_total` decimal(10,4)
,`total` decimal(10,4)
,`forma_pago` enum('efectivo','tarjeta','transferencia','cheque','credito')
,`estado` enum('borrador','autorizada','anulada','pendiente')
,`estado_sri` varchar(50)
,`enviado_correo` tinyint(1)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_facturas`
--
DROP TABLE IF EXISTS `v_facturas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_facturas`  AS SELECT `f`.`id` AS `id`, `f`.`numero_factura` AS `numero_factura`, `f`.`clave_acceso` AS `clave_acceso`, `f`.`fecha_emision` AS `fecha_emision`, `c`.`cedula` AS `cedula`, `c`.`nombres` AS `cliente`, `c`.`correo` AS `cliente_correo`, `u`.`nombres` AS `usuario`, `f`.`subtotal_sin_iva` AS `subtotal_sin_iva`, `f`.`subtotal_con_iva` AS `subtotal_con_iva`, `f`.`descuento` AS `descuento`, `f`.`iva_total` AS `iva_total`, `f`.`total` AS `total`, `f`.`forma_pago` AS `forma_pago`, `f`.`estado` AS `estado`, `f`.`estado_sri` AS `estado_sri`, `f`.`enviado_correo` AS `enviado_correo`, `f`.`created_at` AS `created_at` FROM ((`facturas` `f` join `clientes` `c` on(`f`.`cliente_id` = `c`.`id`)) join `usuarios` `u` on(`f`.`usuario_id` = `u`.`id`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD UNIQUE KEY `clave_acceso` (`clave_acceso`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `factura_detalle`
--
ALTER TABLE `factura_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `factura_detalle`
--
ALTER TABLE `factura_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `facturas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `factura_detalle`
--
ALTER TABLE `factura_detalle`
  ADD CONSTRAINT `factura_detalle_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `factura_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
