-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2025 a las 14:27:12
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `usuarios_urban_coop`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_unidades`
--

CREATE TABLE `asignaciones_unidades` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `unidad_id` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `estado` enum('activa','finalizada') DEFAULT 'activa',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias_reuniones`
--

CREATE TABLE `asistencias_reuniones` (
  `id` int(11) NOT NULL,
  `reunion_id` int(11) NOT NULL COMMENT 'ID de la reunión',
  `user_id` int(11) NOT NULL COMMENT 'ID del usuario',
  `asistio` tinyint(1) DEFAULT 0 COMMENT '1=Asistió, 0=No asistió',
  `fecha_confirmacion` datetime DEFAULT NULL COMMENT 'Fecha de confirmación de asistencia',
  `notas` text DEFAULT NULL COMMENT 'Notas sobre la asistencia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Control de asistencia a reuniones';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobantes_pago`
--

CREATE TABLE `comprobantes_pago` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID del usuario que subió el comprobante',
  `payment_month` varchar(2) NOT NULL COMMENT 'Mes del pago (01-12)',
  `payment_year` varchar(4) NOT NULL COMMENT 'Año del pago',
  `file_name` varchar(255) NOT NULL COMMENT 'Nombre original del archivo',
  `file_path` varchar(500) NOT NULL COMMENT 'Ruta donde se almacena el archivo',
  `file_size` int(11) NOT NULL COMMENT 'Tamaño del archivo en bytes',
  `file_type` varchar(50) NOT NULL COMMENT 'Tipo MIME del archivo',
  `description` text DEFAULT NULL COMMENT 'Descripción opcional del comprobante',
  `status` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente' COMMENT 'Estado del comprobante',
  `tipo_pago` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Pago mensual, 1=Pago atrasado, 2=Indemnización de horas',
  `monto` decimal(10,2) DEFAULT NULL COMMENT 'Monto del pago realizado',
  `admin_notes` text DEFAULT NULL COMMENT 'Notas del administrador sobre el pago',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de subida',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha de última actualización'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprobantes de pago subidos por los usuarios';

--
-- Volcado de datos para la tabla `comprobantes_pago`
--

INSERT INTO `comprobantes_pago` (`id`, `user_id`, `payment_month`, `payment_year`, `file_name`, `file_path`, `file_size`, `file_type`, `description`, `status`, `tipo_pago`, `monto`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 7, '01', '2025', 'comprobante_enero_pedro.pdf', 'uploads/comprobantes/1_01_2025.pdf', 245678, 'application/pdf', 'Comprobante de pago cooperativa enero 2025', 'pendiente', 0, NULL, NULL, '2025-10-28 01:32:27', '2025-10-28 01:32:27'),
(2, 2, '01', '2025', 'pago_enero_maria.jpg', 'uploads/comprobantes/2_01_2025.jpg', 189432, 'image/jpeg', 'Transferencia bancaria enero 2025', 'pendiente', 0, NULL, NULL, '2025-10-28 01:32:27', '2025-10-28 01:32:27'),
(3, 3, '12', '2024', 'diciembre_juan.pdf', 'uploads/comprobantes/3_12_2024.pdf', 321098, 'application/pdf', 'Último pago del año 2024', 'aprobado', 0, NULL, NULL, '2025-10-28 01:32:27', '2025-10-28 01:32:27'),
(4, 4, '01', '2025', 'enero_ana.png', 'uploads/comprobantes/4_01_2025.png', 156789, 'image/png', 'Captura de transferencia enero', 'pendiente', 0, NULL, NULL, '2025-10-28 01:32:27', '2025-10-28 01:32:27'),
(5, 5, '01', '2025', 'carlos_enero_2025.pdf', 'uploads/comprobantes/5_01_2025.pdf', 278945, 'application/pdf', 'Pago mensual cooperativa', 'pendiente', 0, NULL, NULL, '2025-10-28 01:32:27', '2025-10-28 01:32:27'),
(6, 6, '12', '2024', 'laura_diciembre.jpg', 'uploads/comprobantes/6_12_2024.jpg', 203456, 'image/jpeg', 'Pago diciembre 2024', 'rechazado', 0, NULL, NULL, '2025-10-28 01:32:27', '2025-10-28 01:32:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horas_trabajadas`
--

CREATE TABLE `horas_trabajadas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID del usuario que registró las horas',
  `work_date` date NOT NULL COMMENT 'Fecha del trabajo realizado',
  `hours_worked` decimal(4,2) NOT NULL COMMENT 'Horas trabajadas (con decimales)',
  `description` text NOT NULL COMMENT 'Descripción del trabajo realizado',
  `work_type` varchar(50) NOT NULL COMMENT 'Tipo de trabajo (Mantenimiento, Limpieza, etc.)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de horas trabajadas por cada usuario';

--
-- Volcado de datos para la tabla `horas_trabajadas`
--

INSERT INTO `horas_trabajadas` (`id`, `user_id`, `work_date`, `hours_worked`, `description`, `work_type`, `created_at`) VALUES
(1, 9, '2025-01-20', 6.50, 'Mantenimiento de jardines del área común principal', 'Mantenimiento', '2025-10-28 01:32:27'),
(2, 2, '2025-01-21', 4.00, 'Limpieza profunda del salón de eventos', 'Limpieza', '2025-10-28 01:32:27'),
(3, 3, '2025-01-22', 8.00, 'Reparación de grifería en baños comunes', 'Reparaciones', '2025-10-28 01:32:27'),
(4, 4, '2025-01-23', 5.50, 'Organización de reunión de vecinos', 'Eventos', '2025-10-28 01:32:27'),
(5, 5, '2025-01-24', 7.00, 'Pintura de paredes del hall de entrada', 'Mantenimiento', '2025-10-28 01:32:27'),
(6, 6, '2025-01-25', 3.50, 'Limpieza de escaleras y pasillos', 'Limpieza', '2025-10-28 01:32:27'),
(7, 7, '2025-01-26', 6.00, 'Instalación de luces LED en áreas comunes [APROBADO]', 'Mantenimiento', '2025-10-28 01:32:27'),
(8, 8, '2025-01-27', 4.50, 'Mantenimiento de ascensores [RECHAZADO]', 'Mantenimiento', '2025-10-28 01:32:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reuniones`
--

CREATE TABLE `reuniones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL COMMENT 'Título de la reunión',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción de la reunión',
  `fecha_hora` datetime NOT NULL COMMENT 'Fecha y hora de la reunión',
  `lugar` varchar(200) DEFAULT NULL COMMENT 'Lugar donde se realizará',
  `es_obligatoria` tinyint(1) DEFAULT 1 COMMENT '1=Obligatoria, 0=Opcional',
  `estado` enum('programada','en_curso','finalizada','cancelada') DEFAULT 'programada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reuniones programadas de la cooperativa';

--
-- Volcado de datos para la tabla `reuniones`
--

INSERT INTO `reuniones` (`id`, `titulo`, `descripcion`, `fecha_hora`, `lugar`, `es_obligatoria`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Reunión Mensual Octubre', 'Reunión mensual obligatoria para todos los cooperativistas', '2025-10-30 19:00:00', 'Salón Principal - Sede Central', 1, 'programada', '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(2, 'Asamblea General Noviembre', 'Asamblea general anual - Votación de proyectos', '2025-11-15 18:00:00', 'Salón de Eventos', 1, 'programada', '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(3, 'Taller de Mantenimiento', 'Taller opcional sobre mantenimiento del hogar', '2025-11-08 16:00:00', 'Sala de Capacitación', 0, 'programada', '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(4, 'Reunión de Comité', 'Reunión del comité directivo', '2025-11-22 19:30:00', 'Oficina Administrativa', 1, 'programada', '2025-10-28 01:32:53', '2025-10-28 01:32:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_unidad`
--

CREATE TABLE `solicitudes_unidad` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Usuario que solicita',
  `habitaciones` int(11) NOT NULL COMMENT 'Habitaciones solicitadas',
  `baños` int(11) NOT NULL COMMENT 'Baños solicitados',
  `personas` int(11) NOT NULL COMMENT 'Cantidad de personas',
  `preferencia_bloque` varchar(50) DEFAULT NULL COMMENT 'Preferencia de bloque',
  `preferencia_piso` varchar(50) DEFAULT NULL COMMENT 'Preferencia de piso',
  `observaciones` text DEFAULT NULL COMMENT 'Observaciones adicionales',
  `estado` enum('pendiente','en_revision','aprobada','rechazada','asignada') DEFAULT 'pendiente',
  `unidad_asignada_id` int(11) DEFAULT NULL COMMENT 'Unidad que se le asignó',
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_respuesta` datetime DEFAULT NULL,
  `respuesta_admin` text DEFAULT NULL COMMENT 'Respuesta del administrador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Solicitudes de unidades habitacionales';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id` int(11) NOT NULL,
  `numero_unidad` varchar(50) NOT NULL,
  `bloque` varchar(50) DEFAULT NULL,
  `piso` int(11) DEFAULT NULL,
  `cuartos` int(11) NOT NULL,
  `banos` int(11) NOT NULL,
  `tamano` decimal(8,2) NOT NULL COMMENT 'Tamaño en m2',
  `capacidad` int(11) NOT NULL COMMENT 'Capacidad de personas (2, 4 o 6)',
  `tipo_unidad` enum('apartamento','casa','local') DEFAULT 'apartamento',
  `estado` enum('disponible','ocupada','mantenimiento') DEFAULT 'disponible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades_habitacionales`
--

CREATE TABLE `unidades_habitacionales` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'Código de la unidad (ej: A-101)',
  `bloque` varchar(10) DEFAULT NULL COMMENT 'Bloque o edificio',
  `piso` int(11) DEFAULT NULL COMMENT 'Número de piso',
  `numero` int(11) DEFAULT NULL COMMENT 'Número de unidad',
  `habitaciones` int(11) NOT NULL COMMENT 'Cantidad de habitaciones',
  `baños` int(11) NOT NULL COMMENT 'Cantidad de baños',
  `metros_cuadrados` decimal(6,2) DEFAULT NULL COMMENT 'Superficie en m²',
  `estado` enum('disponible','ocupada','mantenimiento','reservada') DEFAULT 'disponible',
  `user_id` int(11) DEFAULT NULL COMMENT 'Usuario asignado a la unidad',
  `fecha_asignacion` date DEFAULT NULL COMMENT 'Fecha de asignación',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidades habitacionales de la cooperativa';

--
-- Volcado de datos para la tabla `unidades_habitacionales`
--

INSERT INTO `unidades_habitacionales` (`id`, `codigo`, `bloque`, `piso`, `numero`, `habitaciones`, `baños`, `metros_cuadrados`, `estado`, `user_id`, `fecha_asignacion`, `created_at`, `updated_at`) VALUES
(1, 'A-101', 'A', 1, 101, 2, 1, 65.50, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(2, 'A-102', 'A', 1, 102, 2, 1, 65.50, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(3, 'A-201', 'A', 2, 201, 2, 1, 65.50, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(4, 'A-202', 'A', 2, 202, 3, 2, 85.00, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(5, 'B-101', 'B', 1, 101, 1, 1, 45.00, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(6, 'B-102', 'B', 1, 102, 2, 1, 65.50, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(7, 'B-201', 'B', 2, 201, 3, 2, 85.00, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(8, 'B-202', 'B', 2, 202, 3, 2, 85.00, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(9, 'C-101', 'C', 1, 101, 2, 2, 75.00, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53'),
(10, 'C-102', 'C', 1, 102, 3, 2, 90.00, 'disponible', NULL, NULL, '2025-10-28 01:32:53', '2025-10-28 01:32:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `usr_name` varchar(100) NOT NULL COMMENT 'Nombre del usuario',
  `usr_surname` varchar(100) NOT NULL COMMENT 'Apellido del usuario',
  `usr_email` varchar(100) NOT NULL COMMENT 'Email del usuario',
  `usr_pass` varchar(100) NOT NULL COMMENT 'Contraseña',
  `usr_ci` int(11) NOT NULL COMMENT 'Cédula de identidad',
  `usr_phone` int(11) NOT NULL COMMENT 'Teléfono',
  `is_admin` int(11) NOT NULL DEFAULT 0 COMMENT '0=Usuario normal, 1=Administrador',
  `estado` int(11) NOT NULL DEFAULT 1 COMMENT '1=Pendiente, 2=Aprobado, 3=Rechazado',
  `tiene_pago_inicial` tinyint(1) DEFAULT 0 COMMENT '0=No pagó inicial, 1=Pagó inicial',
  `fecha_pago_inicial` date DEFAULT NULL COMMENT 'Fecha del pago inicial'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal de usuarios del sistema Urban Coop';

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `usr_name`, `usr_surname`, `usr_email`, `usr_pass`, `usr_ci`, `usr_phone`, `is_admin`, `estado`, `tiene_pago_inicial`, `fecha_pago_inicial`) VALUES
(1, 'a', 'b', 'ab@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 12345678, 99999999, 1, 2, 1, '0000-00-00'),
(2, 'Pedro', 'Garfhone', 'pedro@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 24966853, 93658842, 0, 2, 0, NULL),
(3, 'María', 'González', 'maria@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 25123456, 94567890, 0, 1, 0, NULL),
(4, 'Juan', 'Pérez', 'juan@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 26789012, 95123456, 0, 1, 0, NULL),
(5, 'Ana', 'López', 'ana@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 27345678, 96789012, 0, 1, 0, NULL),
(6, 'Carlos', 'Rodríguez', 'carlos@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 28901234, 97345678, 0, 1, 0, NULL),
(7, 'Laura', 'Martínez', 'laura@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 29567890, 98901234, 0, 1, 0, NULL),
(8, 'Diego', 'Fernández', 'diego@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 30123456, 99567890, 0, 1, 0, NULL),
(9, 'Sofia', 'Torres', 'sofia@gmail.com', '$2y$10$OonpAzg4gxjR0eNrS6OQl.tLcyajNwu6Da89niX//UMY2jQcf/8Au', 31789012, 91234567, 0, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_pagos_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_pagos_usuario` (
`user_id` int(11)
,`usr_name` varchar(100)
,`usr_surname` varchar(100)
,`total_pagos` bigint(21)
,`pagos_mensuales` decimal(22,0)
,`pagos_atrasados` decimal(22,0)
,`indemnizaciones` decimal(22,0)
,`total_monto_pagado` decimal(32,2)
,`monto_aprobado` decimal(32,2)
,`monto_pendiente` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reuniones_asistencias`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reuniones_asistencias` (
`id` int(11)
,`titulo` varchar(200)
,`fecha_hora` datetime
,`lugar` varchar(200)
,`es_obligatoria` tinyint(1)
,`estado` enum('programada','en_curso','finalizada','cancelada')
,`total_confirmados` bigint(21)
,`total_asistieron` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_usuarios_unidades`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_usuarios_unidades` (
`user_id` int(11)
,`usr_name` varchar(100)
,`usr_surname` varchar(100)
,`usr_email` varchar(100)
,`user_estado` int(11)
,`tiene_pago_inicial` tinyint(1)
,`fecha_pago_inicial` date
,`unidad_id` int(11)
,`unidad_codigo` varchar(20)
,`habitaciones` int(11)
,`baños` int(11)
,`metros_cuadrados` decimal(6,2)
,`fecha_asignacion` date
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_pagos_usuario`
--
DROP TABLE IF EXISTS `vista_pagos_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_pagos_usuario`  AS SELECT `u`.`id` AS `user_id`, `u`.`usr_name` AS `usr_name`, `u`.`usr_surname` AS `usr_surname`, count(`cp`.`id`) AS `total_pagos`, sum(case when `cp`.`tipo_pago` = 0 then 1 else 0 end) AS `pagos_mensuales`, sum(case when `cp`.`tipo_pago` = 1 then 1 else 0 end) AS `pagos_atrasados`, sum(case when `cp`.`tipo_pago` = 2 then 1 else 0 end) AS `indemnizaciones`, sum(`cp`.`monto`) AS `total_monto_pagado`, sum(case when `cp`.`status` = 'aprobado' then `cp`.`monto` else 0 end) AS `monto_aprobado`, sum(case when `cp`.`status` = 'pendiente' then `cp`.`monto` else 0 end) AS `monto_pendiente` FROM (`usuario` `u` left join `comprobantes_pago` `cp` on(`u`.`id` = `cp`.`user_id`)) GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reuniones_asistencias`
--
DROP TABLE IF EXISTS `vista_reuniones_asistencias`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reuniones_asistencias`  AS SELECT `r`.`id` AS `id`, `r`.`titulo` AS `titulo`, `r`.`fecha_hora` AS `fecha_hora`, `r`.`lugar` AS `lugar`, `r`.`es_obligatoria` AS `es_obligatoria`, `r`.`estado` AS `estado`, count(`ar`.`id`) AS `total_confirmados`, sum(case when `ar`.`asistio` = 1 then 1 else 0 end) AS `total_asistieron` FROM (`reuniones` `r` left join `asistencias_reuniones` `ar` on(`r`.`id` = `ar`.`reunion_id`)) GROUP BY `r`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_usuarios_unidades`
--
DROP TABLE IF EXISTS `vista_usuarios_unidades`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_usuarios_unidades`  AS SELECT `u`.`id` AS `user_id`, `u`.`usr_name` AS `usr_name`, `u`.`usr_surname` AS `usr_surname`, `u`.`usr_email` AS `usr_email`, `u`.`estado` AS `user_estado`, `u`.`tiene_pago_inicial` AS `tiene_pago_inicial`, `u`.`fecha_pago_inicial` AS `fecha_pago_inicial`, `uh`.`id` AS `unidad_id`, `uh`.`codigo` AS `unidad_codigo`, `uh`.`habitaciones` AS `habitaciones`, `uh`.`baños` AS `baños`, `uh`.`metros_cuadrados` AS `metros_cuadrados`, `uh`.`fecha_asignacion` AS `fecha_asignacion` FROM (`usuario` `u` left join `unidades_habitacionales` `uh` on(`u`.`id` = `uh`.`user_id`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaciones_unidades`
--
ALTER TABLE `asignaciones_unidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_unidad_id` (`unidad_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `asistencias_reuniones`
--
ALTER TABLE `asistencias_reuniones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_reunion` (`user_id`,`reunion_id`),
  ADD KEY `idx_reunion` (`reunion_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `comprobantes_pago`
--
ALTER TABLE `comprobantes_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_month_year` (`user_id`,`payment_month`,`payment_year`) COMMENT 'Un comprobante por usuario por mes/año',
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_date` (`payment_year`,`payment_month`),
  ADD KEY `idx_file_type` (`file_type`);

--
-- Indices de la tabla `horas_trabajadas`
--
ALTER TABLE `horas_trabajadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`work_date`) COMMENT 'Un usuario puede registrar solo una vez por día',
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_work_date` (`work_date`),
  ADD KEY `idx_work_type` (`work_type`);

--
-- Indices de la tabla `reuniones`
--
ALTER TABLE `reuniones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha_hora`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `solicitudes_unidad`
--
ALTER TABLE `solicitudes_unidad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unidad_asignada_id` (`unidad_asignada_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_unidad` (`numero_unidad`,`bloque`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_capacidad` (`capacidad`);

--
-- Indices de la tabla `unidades_habitacionales`
--
ALTER TABLE `unidades_habitacionales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_codigo` (`codigo`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usr_email` (`usr_email`),
  ADD UNIQUE KEY `usr_ci` (`usr_ci`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_admin` (`is_admin`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones_unidades`
--
ALTER TABLE `asignaciones_unidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencias_reuniones`
--
ALTER TABLE `asistencias_reuniones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comprobantes_pago`
--
ALTER TABLE `comprobantes_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `horas_trabajadas`
--
ALTER TABLE `horas_trabajadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `reuniones`
--
ALTER TABLE `reuniones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `solicitudes_unidad`
--
ALTER TABLE `solicitudes_unidad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `unidades_habitacionales`
--
ALTER TABLE `unidades_habitacionales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaciones_unidades`
--
ALTER TABLE `asignaciones_unidades`
  ADD CONSTRAINT `asignaciones_unidades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asignaciones_unidades_ibfk_2` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asistencias_reuniones`
--
ALTER TABLE `asistencias_reuniones`
  ADD CONSTRAINT `asistencias_reuniones_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `reuniones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asistencias_reuniones_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comprobantes_pago`
--
ALTER TABLE `comprobantes_pago`
  ADD CONSTRAINT `comprobantes_pago_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `horas_trabajadas`
--
ALTER TABLE `horas_trabajadas`
  ADD CONSTRAINT `horas_trabajadas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_unidad`
--
ALTER TABLE `solicitudes_unidad`
  ADD CONSTRAINT `solicitudes_unidad_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitudes_unidad_ibfk_2` FOREIGN KEY (`unidad_asignada_id`) REFERENCES `unidades_habitacionales` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `unidades_habitacionales`
--
ALTER TABLE `unidades_habitacionales`
  ADD CONSTRAINT `unidades_habitacionales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
