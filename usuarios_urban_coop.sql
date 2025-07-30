-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-07-2025 a las 03:22:04
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
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `usr_name` varchar(100) NOT NULL,
  `usr_surname` varchar(100) NOT NULL,
  `usr_email` varchar(150) NOT NULL,
  `usr_pass` varchar(255) NOT NULL,
  `usr_ci` varchar(20) NOT NULL,
  `usr_phone` varchar(20) NOT NULL,
  `is_admin` int(11) NOT NULL DEFAULT 0,
  `estado` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `usr_name`, `usr_surname`, `usr_email`, `usr_pass`, `usr_ci`, `usr_phone`, `is_admin`, `estado`, `created_at`) VALUES
(2, 'a', 'b', 'admin@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '123456', '123456', 0, 2, '2025-07-28 22:00:15');

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
  `estado` int(11) NOT NULL DEFAULT 1 COMMENT '1=Pendiente, 2=Aprobado, 3=Rechazado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal de usuarios del sistema Urban Coop';

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `usr_name`, `usr_surname`, `usr_email`, `usr_pass`, `usr_ci`, `usr_phone`, `is_admin`, `estado`) VALUES
(3, 'jeremias', 'molina', 'chermis@gmail.com', 'c0ee7ff4b10fa37e9ccdfe0faca45136', 1234, 12345, 1, 2),
(4, 'a', 'n', 'jeremias@gmail.com', '729df251ee41cf92d45ec11a87c60ec0', 1123, 1123, 0, 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usr_email` (`usr_email`),
  ADD UNIQUE KEY `usr_ci` (`usr_ci`);

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
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
