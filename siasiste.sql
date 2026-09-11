-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 30-07-2026 a las 12:47:19
-- Versión del servidor: 8.4.3
-- Versión de PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `siasiste`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

CREATE TABLE `alertas` (
  `id` int UNSIGNED NOT NULL,
  `estudiante_id` int UNSIGNED NOT NULL,
  `porcentaje_inasistencia` decimal(5,2) NOT NULL DEFAULT '0.00',
  `nivel` enum('verde','amarillo','rojo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'verde',
  `fecha_deteccion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `derivado_a` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activa','atendida','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alertas`
--

INSERT INTO `alertas` (`id`, `estudiante_id`, `porcentaje_inasistencia`, `nivel`, `fecha_deteccion`, `observaciones`, `derivado_a`, `estado`, `created_at`, `updated_at`) VALUES
(2, 5, 42.86, 'rojo', '2026-07-14 09:00:00', 'Tres faltas consecutivas en Matemáticas y Ciencias.', 'Tutor María Torres', 'activa', '2026-07-14 09:00:00', '2026-07-15 17:38:52'),
(3, 3, 28.57, 'amarillo', '2026-07-14 09:00:00', 'Inasistencia se acerca al umbral del 30%. Monitorear evolución.', 'Psicóloga Rosa Gutiérrez', 'activa', '2026-07-14 09:00:00', '2026-07-15 17:38:52'),
(4, 6, 28.57, 'amarillo', '2026-07-14 09:00:00', 'Dos faltas en Ciencias y una justificada. Seguimiento continuo.', 'Psicóloga Rosa Gutiérrez', 'activa', '2026-07-14 09:00:00', '2026-07-15 17:38:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id` int UNSIGNED NOT NULL,
  `estudiante_id` int UNSIGNED NOT NULL,
  `unidad_id` int UNSIGNED DEFAULT NULL,
  `fecha` date NOT NULL,
  `estado` enum('presente','tardanza','ausente','justificado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'presente',
  `registrado_por` int UNSIGNED DEFAULT NULL,
  `synced` tinyint(1) NOT NULL DEFAULT '1',
  `registro_tardio` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencias`
--

INSERT INTO `asistencias` (`id`, `estudiante_id`, `unidad_id`, `fecha`, `estado`, `registrado_por`, `synced`, `registro_tardio`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 2, '2026-07-14', 'presente', 1, 1, 1, '2026-07-15 22:29:12', NULL, NULL),
(2, 3, 2, '2026-07-14', 'presente', 1, 1, 1, '2026-07-15 22:29:12', '2026-07-15 22:37:20', NULL),
(3, 5, 2, '2026-07-14', 'justificado', 1, 1, 1, '2026-07-15 22:29:12', '2026-07-15 22:35:06', NULL),
(4, 6, 2, '2026-07-14', 'ausente', 1, 1, 1, '2026-07-15 22:29:12', '2026-07-15 22:34:37', NULL),
(5, 8, 2, '2026-07-14', 'presente', 1, 1, 1, '2026-07-15 22:29:12', NULL, NULL),
(6, 13, 2, '2026-07-14', 'presente', 1, 1, 1, '2026-07-15 22:29:12', NULL, NULL),
(7, 14, 2, '2026-07-14', 'presente', 1, 1, 1, '2026-07-15 22:29:12', NULL, NULL),
(8, 1, 9, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:39:19', NULL, NULL),
(9, 3, 9, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:39:19', NULL, NULL),
(10, 5, 9, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:39:19', NULL, NULL),
(11, 6, 9, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:39:19', NULL, NULL),
(12, 8, 9, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:39:19', NULL, NULL),
(13, 13, 9, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:39:19', NULL, NULL),
(14, 14, 9, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:39:19', NULL, NULL),
(15, 1, 2, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:54:22', NULL, NULL),
(16, 3, 2, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:54:22', NULL, NULL),
(17, 5, 2, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:54:22', NULL, NULL),
(18, 6, 2, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:54:22', NULL, NULL),
(19, 8, 2, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:54:22', NULL, NULL),
(20, 13, 2, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:54:22', NULL, NULL),
(21, 14, 2, '2026-07-15', 'presente', 1, 1, 0, '2026-07-15 23:54:22', NULL, NULL),
(22, 1, 4, '2026-07-20', 'presente', 1, 1, 1, '2026-07-22 08:13:30', NULL, NULL),
(23, 3, 4, '2026-07-20', 'presente', 1, 1, 1, '2026-07-22 08:13:30', NULL, NULL),
(24, 5, 4, '2026-07-20', 'presente', 1, 1, 1, '2026-07-22 08:13:30', NULL, NULL),
(25, 6, 4, '2026-07-20', 'presente', 1, 1, 1, '2026-07-22 08:13:30', NULL, NULL),
(26, 8, 4, '2026-07-20', 'presente', 1, 1, 1, '2026-07-22 08:13:30', NULL, NULL),
(27, 13, 4, '2026-07-20', 'presente', 1, 1, 1, '2026-07-22 08:13:30', NULL, NULL),
(28, 14, 4, '2026-07-20', 'presente', 1, 1, 1, '2026-07-22 08:13:30', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia_detalles`
--

CREATE TABLE `asistencia_detalles` (
  `id` int UNSIGNED NOT NULL,
  `asistencia_id` int UNSIGNED NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'tardanza, ausente, justificado',
  `motivo_id` int UNSIGNED DEFAULT NULL,
  `otro_motivo` text COLLATE utf8mb4_unicode_ci,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencia_detalles`
--

INSERT INTO `asistencia_detalles` (`id`, `asistencia_id`, `estado`, `motivo_id`, `otro_motivo`, `observaciones`, `created_at`, `updated_at`) VALUES
(2, 4, 'ausente', 7, 'null', 'null', '2026-07-15 22:34:37', NULL),
(3, 3, 'justificado', 2, 'null', 'null', '2026-07-15 22:35:06', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int UNSIGNED NOT NULL,
  `usuario_id` int UNSIGNED DEFAULT NULL,
  `accion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` int UNSIGNED DEFAULT NULL,
  `detalle` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `entidad`, `entidad_id`, `detalle`, `ip`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'curl/8.21.0', '2026-07-29 23:52:17'),
(2, 1, 'logout', 'usuarios', 1, 'Cierre de sesión: admin', '127.0.0.1', 'curl/8.21.0', '2026-07-29 23:52:37'),
(3, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-29 23:59:13'),
(4, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'curl/8.21.0', '2026-07-29 23:59:18'),
(5, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:02:07'),
(6, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:02:15'),
(7, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:02:22'),
(8, 1, 'logout', 'usuarios', 1, 'Cierre de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:02:57'),
(9, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-30 00:04:51'),
(10, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:05:14'),
(11, 1, 'logout', 'usuarios', 1, 'Cierre de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:05:49'),
(12, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:08:05'),
(13, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:08:16'),
(14, 1, 'logout', 'usuarios', 1, 'Cierre de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:09:30'),
(15, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:09:56'),
(16, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:10:47'),
(17, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-30 00:11:33'),
(18, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-30 00:11:33'),
(19, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-30 00:12:12'),
(20, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-30 00:12:12'),
(21, 1, 'logout', 'usuarios', 1, 'Cierre de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:15:14'),
(22, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 00:15:23'),
(23, 1, 'logout', 'usuarios', 1, 'Cierre de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 01:02:13'),
(24, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-07-30 12:34:51'),
(25, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-30 12:39:55'),
(26, 1, 'login', 'usuarios', 1, 'Inicio de sesión: admin', '::1', 'curl/8.21.0', '2026-07-30 12:40:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id` int UNSIGNED NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad` smallint UNSIGNED NOT NULL DEFAULT '40',
  `edificio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id`, `codigo`, `nombre`, `capacidad`, `edificio`, `tipo`, `activo`) VALUES
(1, 'Aula 101', 'Aula 101', 40, 'Bloque A - Principal', NULL, 1),
(2, 'Aula 202', 'Aula 202', 35, 'Bloque A - Principal', NULL, 1),
(3, 'B-101', 'Aula B-101', 45, 'Bloque B - Anexo', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes`
--

CREATE TABLE `docentes` (
  `id` int UNSIGNED NOT NULL,
  `usuario_id` int UNSIGNED DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `especialidad_id` int UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `docentes`
--

INSERT INTO `docentes` (`id`, `usuario_id`, `nombre`, `apellido`, `dni`, `email`, `telefono`, `especialidad_id`, `activo`) VALUES
(1, 2, 'Ana', 'Mamani Quispe', '12345678', 'cmendoza@siasiste.edu.pe', '951234567', 1, 1),
(2, NULL, 'Ana', 'Ramos Huanca', '70452356', 'aramos@siasiste.edu.pe', '952345678', 2, 1),
(3, NULL, 'Luis', 'Pérez Ccallo', '70417822', 'lperez@siasiste.edu.pe', '953456789', 3, 1),
(4, NULL, 'Patricia', 'Campos Nunez', '74586621', 'pcampos@escuela.edu.pe', '999888777', 4, 1),
(7, NULL, 'ruben', 'condo ochoa', '70417802', 'rubencondo8a@gmail.com', '952728439', 5, 1),
(8, NULL, 'Test', 'Docente', '70451215', '', '123456789', 5, 1),
(9, NULL, 'Lopez', 'Perez', '70415245', 'lopez@mail.com', '951111111', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id`, `nombre`, `activo`, `created_at`) VALUES
(1, 'Matematicas', 1, '2026-07-16 01:31:43'),
(2, 'Comunicacion', 1, '2026-07-16 01:31:43'),
(3, 'Ciencias', 1, '2026-07-16 01:31:43'),
(4, 'Redes y Comunicaciones', 1, '2026-07-16 01:31:43'),
(5, 'Computación e informática', 1, '2026-07-16 01:31:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int UNSIGNED NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grado` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seccion` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `programa_id` int UNSIGNED DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `codigo`, `dni`, `nombre`, `apellido`, `grado`, `seccion`, `programa_id`, `email`, `telefono`, `activo`) VALUES
(1, 'EST001', '87654321', 'Juan', 'Huamán Ticona', '2° Ciclo', 'A', 1, 'jhuaman@gmail.com', '961111111', 1),
(3, 'EST-2026-003', '78456232', 'Pedro', 'Alvarez Ramos', '1° Ciclo', 'B', 1, 'palvarez@gmail.com', '963333333', 1),
(5, 'EST-2026-005', NULL, 'Miguel', 'García Medina', '1° Ciclo', 'A', 1, 'mgarcia@gmail.com', '965555555', 1),
(6, 'EST-2026-006', '70415236', 'Valentina', 'Cruz Soto', '6° Ciclo', 'A', 1, 'vcruz@gmail.com', '966666666', 1),
(7, 'EST-TEST-001', '78526546', 'Test', 'Estudiante', '1° Ciclo', 'A', NULL, NULL, NULL, 1),
(8, 'EST-2026-010', '99999999', 'Maria', 'Garcia Lopez', '1° Ciclo', 'B', 1, 'mgarcia@estudiante.edu.pe', '999111222', 1),
(13, 'EST-2026-200', NULL, 'Ana Maria', 'Rodriguez Flores', '3° Ciclo', 'A', 1, 'ana@mail.com', '953333333', 1),
(14, 'EST-2026-201', NULL, 'Jose Luis', 'Torres Ramos', '2° Ciclo', 'B', 1, 'jose@mail.com', '954444444', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id` int UNSIGNED NOT NULL,
  `dia` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `aula_id` int UNSIGNED DEFAULT NULL,
  `unidad_id` int UNSIGNED DEFAULT NULL,
  `docente_id` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id`, `dia`, `hora_inicio`, `hora_fin`, `aula_id`, `unidad_id`, `docente_id`, `activo`) VALUES
(29, 'Lunes', '07:30:00', '09:00:00', 2, 2, 4, 1),
(30, 'Miércoles', '07:30:00', '09:00:00', 2, 9, 7, 1),
(31, 'Martes', '07:30:00', '09:00:00', 3, 2, 8, 1),
(32, 'Martes', '09:00:00', '12:30:00', 2, 9, 7, 1),
(33, 'Martes', '09:00:00', '12:30:00', 1, 2, 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas`
--

CREATE TABLE `matriculas` (
  `id` int UNSIGNED NOT NULL,
  `estudiante_id` int UNSIGNED NOT NULL,
  `programa_id` int UNSIGNED NOT NULL,
  `periodo_id` int UNSIGNED NOT NULL,
  `semestre` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `estado` enum('activo','retirado','egresado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `matriculas`
--

INSERT INTO `matriculas` (`id`, `estudiante_id`, `programa_id`, `periodo_id`, `semestre`, `estado`) VALUES
(1, 1, 1, 1, 1, 'egresado'),
(3, 3, 1, 1, 1, 'activo'),
(5, 5, 1, 1, 1, 'activo'),
(7, 1, 1, 1, 2, 'activo'),
(8, 13, 1, 1, 3, 'activo'),
(9, 14, 1, 1, 2, 'activo'),
(12, 6, 1, 1, 6, 'activo'),
(13, 8, 1, 1, 1, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motivos`
--

CREATE TABLE `motivos` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `motivos`
--

INSERT INTO `motivos` (`id`, `nombre`, `descripcion`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Enfermedad', 'Por enfermedad o problemas de salud', 1, '2026-07-15 22:18:31', NULL),
(2, 'Citas Médicas', 'Por cita médica programada', 1, '2026-07-15 22:18:31', NULL),
(3, 'Problemas Personales', 'Por problemas personales o familiares', 1, '2026-07-15 22:18:31', NULL),
(4, 'Asunto Familiar', 'Por asuntos familiares', 1, '2026-07-15 22:18:31', NULL),
(5, 'Trámite Personal', 'Por trámites personales', 1, '2026-07-15 22:18:31', NULL),
(6, 'Retardo', 'Llegada tarde a clase', 1, '2026-07-15 22:18:31', NULL),
(7, 'Falta Injustificada', 'Ausencia sin justificación', 1, '2026-07-15 22:18:31', NULL),
(8, 'Otro', 'Otro motivo no especificado', 1, '2026-07-15 22:18:31', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos_academicos`
--

CREATE TABLE `periodos_academicos` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodos_academicos`
--

INSERT INTO `periodos_academicos` (`id`, `nombre`, `fecha_inicio`, `fecha_fin`, `activo`) VALUES
(1, 'Año Académico 2026', '2026-03-09', '2026-12-18', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programas_estudio`
--

CREATE TABLE `programas_estudio` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `duracion_semestres` tinyint UNSIGNED NOT NULL DEFAULT '6',
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `programas_estudio`
--

INSERT INTO `programas_estudio` (`id`, `nombre`, `descripcion`, `duracion_semestres`, `activo`) VALUES
(1, 'Arquitectura de plataformas y servicios de tecnologías de la información', 'Técnico en Redes e Informática - Formación Superior Tecnológica del IESP Víctor Álvarez Huapaya', 6, 1),
(2, 'Contabilidad', 'Técnico en Contabilidad y Administración - Formación Superior Tecnológica del IESP Víctor Álvarez Huapaya', 6, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_mostrar` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `nombre_mostrar`, `descripcion`, `activo`) VALUES
(1, 'admin', 'Administrador', 'Acceso total al sistema', 1),
(2, 'docente', 'Docente', 'Registro de asistencia y horarios', 1),
(3, 'tutor', 'Tutor', 'Seguimiento de estudiantes', 1),
(4, 'psicologo', 'Psicólogo', 'Atención psicológica', 1),
(5, 'coordinador', 'Coordinador', 'Coordinación académica', 1),
(6, 'asistenta_social', 'Asistenta Social', 'Atención social', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades_didacticas`
--

CREATE TABLE `unidades_didacticas` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `semestre` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `seccion` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horas_semanales` tinyint UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `unidades_didacticas`
--

INSERT INTO `unidades_didacticas` (`id`, `nombre`, `descripcion`, `semestre`, `seccion`, `horas_semanales`, `activo`) VALUES
(2, 'Comunicación I', 'Lenguaje, redacción y literatura - 1er grado', 1, 'A', NULL, 1),
(4, 'Historia I', 'Historia del Perú - 3er grado', 3, 'Única', NULL, 1),
(9, 'Desarrollo de aplicaciones web', 'Desarrollar aplicaciones de tipo web actuales', 5, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_docente`
--

CREATE TABLE `unidad_docente` (
  `id` int UNSIGNED NOT NULL,
  `unidad_id` int UNSIGNED NOT NULL,
  `docente_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `unidad_docente`
--

INSERT INTO `unidad_docente` (`id`, `unidad_id`, `docente_id`) VALUES
(11, 2, 4),
(12, 2, 8),
(13, 4, 4),
(10, 9, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_programa`
--

CREATE TABLE `unidad_programa` (
  `unidad_id` int UNSIGNED NOT NULL,
  `programa_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `unidad_programa`
--

INSERT INTO `unidad_programa` (`unidad_id`, `programa_id`) VALUES
(2, 1),
(4, 1),
(9, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int UNSIGNED NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `contrasena`, `role_id`, `nombre`, `email`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$yW5MQkVNlzjLVLZryFJvmuZFS6r3dZhC7yLNBPg.qplFFVKMv6BXC', 1, 'Administrador General', 'admin@siasiste.edu.pe', 1, '2026-07-15 17:38:52', '2026-07-15 23:51:29'),
(2, 'docente', '$2y$10$ua3SsMOAmPZbhgm2F2tFmuu7fjoBDKudY9vPzOUHVgwcoAcjz06ym', 2, 'ruben condo ochoa', 'rcondo@siasiste.edu.pe', 1, '2026-07-15 17:38:52', '2026-07-15 23:51:29'),
(3, 'tutor', '$2y$10$yW5MQkVNlzjLVLZryFJvmuZFS6r3dZhC7yLNBPg.qplFFVKMv6BXC', 3, 'María Torres Huamán', 'mtorres@siasiste.edu.pe', 1, '2026-07-15 17:38:52', '2026-07-15 23:51:29'),
(4, 'psicologo', '$2y$10$yW5MQkVNlzjLVLZryFJvmuZFS6r3dZhC7yLNBPg.qplFFVKMv6BXC', 4, 'Rosa Gutiérrez Luyo', 'rgutierrez@siasiste.edu.pe', 1, '2026-07-15 17:38:52', '2026-07-15 23:51:29');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante_id` (`estudiante_id`),
  ADD KEY `idx_nivel` (`nivel`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_deteccion` (`fecha_deteccion`),
  ADD KEY `idx_estudiante_estado` (`estudiante_id`,`estado`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asistencia_uniq` (`estudiante_id`,`unidad_id`,`fecha`),
  ADD KEY `idx_estudiante_id` (`estudiante_id`),
  ADD KEY `idx_unidad_id` (`unidad_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_registrado_por` (`registrado_por`),
  ADD KEY `idx_estudiante_fecha` (`estudiante_id`,`fecha`);

--
-- Indices de la tabla `asistencia_detalles`
--
ALTER TABLE `asistencia_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asistencia_id` (`asistencia_id`),
  ADD KEY `idx_motivo_id` (`motivo_id`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_accion` (`accion`),
  ADD KEY `idx_auditoria_entidad` (`entidad`,`entidad_id`),
  ADD KEY `idx_auditoria_usuario` (`usuario_id`),
  ADD KEY `idx_auditoria_created` (`created_at`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_aula_codigo` (`codigo`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `fk_docente_especialidad` (`especialidad_id`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_codigo` (`codigo`),
  ADD KEY `idx_programa_id` (`programa_id`),
  ADD KEY `idx_grado_seccion` (`grado`,`seccion`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dia` (`dia`),
  ADD KEY `idx_aula_id` (`aula_id`),
  ADD KEY `idx_unidad_id` (`unidad_id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante_id` (`estudiante_id`),
  ADD KEY `idx_programa_id` (`programa_id`),
  ADD KEY `idx_periodo_id` (`periodo_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `motivos`
--
ALTER TABLE `motivos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `periodos_academicos`
--
ALTER TABLE `periodos_academicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `programas_estudio`
--
ALTER TABLE `programas_estudio`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `unidades_didacticas`
--
ALTER TABLE `unidades_didacticas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `unidad_docente`
--
ALTER TABLE `unidad_docente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ud_unico` (`unidad_id`,`docente_id`),
  ADD KEY `docente_id` (`docente_id`);

--
-- Indices de la tabla `unidad_programa`
--
ALTER TABLE `unidad_programa`
  ADD PRIMARY KEY (`unidad_id`,`programa_id`),
  ADD KEY `fk_up_programa` (`programa_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario` (`usuario`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `fk_usuario_rol` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `asistencia_detalles`
--
ALTER TABLE `asistencia_detalles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `docentes`
--
ALTER TABLE `docentes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `motivos`
--
ALTER TABLE `motivos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `periodos_academicos`
--
ALTER TABLE `periodos_academicos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `programas_estudio`
--
ALTER TABLE `programas_estudio`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `unidades_didacticas`
--
ALTER TABLE `unidades_didacticas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `unidad_docente`
--
ALTER TABLE `unidad_docente`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD CONSTRAINT `fk_alertas_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `fk_asistencias_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `asistencia_detalles`
--
ALTER TABLE `asistencia_detalles`
  ADD CONSTRAINT `asistencia_detalles_ibfk_1` FOREIGN KEY (`asistencia_id`) REFERENCES `asistencias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asistencia_detalles_ibfk_2` FOREIGN KEY (`motivo_id`) REFERENCES `motivos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD CONSTRAINT `fk_docente_especialidad` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_docentes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `fk_estudiantes_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas_estudio` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `fk_horarios_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_horarios_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `fk_matriculas_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matriculas_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos_academicos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matriculas_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas_estudio` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `unidad_docente`
--
ALTER TABLE `unidad_docente`
  ADD CONSTRAINT `unidad_docente_ibfk_1` FOREIGN KEY (`unidad_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unidad_docente_ibfk_2` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `unidad_programa`
--
ALTER TABLE `unidad_programa`
  ADD CONSTRAINT `fk_up_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas_estudio` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_up_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
