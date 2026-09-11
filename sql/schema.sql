-- ============================================================
-- SIASISTE - Sistema de Gestión de Asistencia Estudiantil
-- IESP "Víctor Álvarez Huapaya" - Ayacucho, Perú
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Crear base de datos
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `siasiste`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `siasiste`;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(50) NOT NULL,
  `contrasena` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `rol` ENUM('admin','docente','tutor','psicologo') NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario`),
  INDEX `idx_rol` (`rol`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: programas_estudio
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `programas_estudio`;
CREATE TABLE `programas_estudio` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `duracion_semestres` TINYINT UNSIGNED NOT NULL DEFAULT 6,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: periodos_academicos
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `periodos_academicos`;
CREATE TABLE `periodos_academicos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: docentes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `docentes`;
CREATE TABLE `docentes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `especialidad` VARCHAR(150) DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_usuario_id` (`usuario_id`),
  INDEX `idx_activo` (`activo`),
  CONSTRAINT `fk_docentes_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: estudiantes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `estudiantes`;
CREATE TABLE `estudiantes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `grado` VARCHAR(30) DEFAULT NULL,
  `seccion` CHAR(1) DEFAULT NULL,
  `programa_id` INT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`),
  INDEX `idx_programa_id` (`programa_id`),
  INDEX `idx_grado_seccion` (`grado`, `seccion`),
  INDEX `idx_activo` (`activo`),
  CONSTRAINT `fk_estudiantes_programa` FOREIGN KEY (`programa_id`)
    REFERENCES `programas_estudio` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: matriculas
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `matriculas`;
CREATE TABLE `matriculas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estudiante_id` INT UNSIGNED NOT NULL,
  `programa_id` INT UNSIGNED NOT NULL,
  `periodo_id` INT UNSIGNED NOT NULL,
  `semestre` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `estado` ENUM('activo','retirado','egresado') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  INDEX `idx_estudiante_id` (`estudiante_id`),
  INDEX `idx_programa_id` (`programa_id`),
  INDEX `idx_periodo_id` (`periodo_id`),
  INDEX `idx_estado` (`estado`),
  CONSTRAINT `fk_matriculas_estudiante` FOREIGN KEY (`estudiante_id`)
    REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_matriculas_programa` FOREIGN KEY (`programa_id`)
    REFERENCES `programas_estudio` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_matriculas_periodo` FOREIGN KEY (`periodo_id`)
    REFERENCES `periodos_academicos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: unidades_didacticas
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `unidades_didacticas`;
CREATE TABLE `unidades_didacticas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `programa_id` INT UNSIGNED DEFAULT NULL,
  `docente_id` INT UNSIGNED DEFAULT NULL,
  `semestre` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_programa_id` (`programa_id`),
  INDEX `idx_docente_id` (`docente_id`),
  INDEX `idx_activo` (`activo`),
  CONSTRAINT `fk_unidades_programa` FOREIGN KEY (`programa_id`)
    REFERENCES `programas_estudio` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_unidades_docente` FOREIGN KEY (`docente_id`)
    REFERENCES `docentes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: aulas
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `aulas`;
CREATE TABLE `aulas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `capacidad` SMALLINT UNSIGNED NOT NULL DEFAULT 40,
  `edificio` VARCHAR(100) DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aula_codigo` (`codigo`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: horarios
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `horarios`;
CREATE TABLE `horarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dia` ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `aula_id` INT UNSIGNED DEFAULT NULL,
  `unidad_id` INT UNSIGNED DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_dia` (`dia`),
  INDEX `idx_aula_id` (`aula_id`),
  INDEX `idx_unidad_id` (`unidad_id`),
  INDEX `idx_activo` (`activo`),
  CONSTRAINT `fk_horarios_aula` FOREIGN KEY (`aula_id`)
    REFERENCES `aulas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_horarios_unidad` FOREIGN KEY (`unidad_id`)
    REFERENCES `unidades_didacticas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: asistencias
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `asistencias`;
CREATE TABLE `asistencias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estudiante_id` INT UNSIGNED NOT NULL,
  `unidad_id` INT UNSIGNED DEFAULT NULL,
  `fecha` DATE NOT NULL,
  `estado` ENUM('presente','ausente','justificado') NOT NULL DEFAULT 'presente',
  `registrado_por` INT UNSIGNED DEFAULT NULL,
  `synced` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_estudiante_id` (`estudiante_id`),
  INDEX `idx_unidad_id` (`unidad_id`),
  INDEX `idx_fecha` (`fecha`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_registrado_por` (`registrado_por`),
  INDEX `idx_estudiante_fecha` (`estudiante_id`, `fecha`),
  CONSTRAINT `fk_asistencias_estudiante` FOREIGN KEY (`estudiante_id`)
    REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asistencias_unidad` FOREIGN KEY (`unidad_id`)
    REFERENCES `unidades_didacticas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asistencias_usuario` FOREIGN KEY (`registrado_por`)
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: alertas
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `alertas`;
CREATE TABLE `alertas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estudiante_id` INT UNSIGNED NOT NULL,
  `porcentaje_inasistencia` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `nivel` ENUM('verde','amarillo','rojo') NOT NULL DEFAULT 'verde',
  `fecha_deteccion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` TEXT DEFAULT NULL,
  `derivado_a` VARCHAR(150) DEFAULT NULL,
  `estado` ENUM('activa','atendida','cerrada') NOT NULL DEFAULT 'activa',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_estudiante_id` (`estudiante_id`),
  INDEX `idx_nivel` (`nivel`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_fecha_deteccion` (`fecha_deteccion`),
  INDEX `idx_estudiante_estado` (`estudiante_id`, `estado`),
  CONSTRAINT `fk_alertas_estudiante` FOREIGN KEY (`estudiante_id`)
    REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS INICIALES (SEED DATA)
-- ============================================================

-- ------------------------------------------------------------
-- Programas de estudio
-- ------------------------------------------------------------
INSERT INTO `programas_estudio` (`nombre`, `descripcion`, `duracion_semestres`, `activo`) VALUES
('Educación Secundaria', 'Programa de educación secundaria completa del IEP Víctor Álvarez Huapaya', 6, 1),
('Programa de Educación Técnica', 'Programa técnico-productivo para estudiantes de secundaria', 4, 1);

-- ------------------------------------------------------------
-- Período académico
-- ------------------------------------------------------------
INSERT INTO `periodos_academicos` (`nombre`, `fecha_inicio`, `fecha_fin`, `activo`) VALUES
('Año Académico 2026', '2026-03-09', '2026-12-18', 1);

-- ------------------------------------------------------------
-- Usuarios (contraseñas con hash bcrypt)
-- ------------------------------------------------------------
INSERT INTO `usuarios` (`usuario`, `contrasena`, `nombre`, `email`, `rol`, `activo`) VALUES
('admin',      '$2y$10$yW5MQkVNlzjLVLZryFJvmuZFS6r3dZhC7yLNBPg.qplFFVKMv6BXC', 'Administrador General', 'admin@siasiste.edu.pe',      'admin',      1),
('docente',    '$2y$10$yW5MQkVNlzjLVLZryFJvmuZFS6r3dZhC7yLNBPg.qplFFVKMv6BXC', 'Carlos Mendoza Quispe',   'cmendoza@siasiste.edu.pe',  'docente',    1),
('tutor',      '$2y$10$yW5MQkVNlzjLVLZryFJvmuZFS6r3dZhC7yLNBPg.qplFFVKMv6BXC', 'María Torres Huamán',     'mtorres@siasiste.edu.pe',   'tutor',      1),
('psicologo',  '$2y$10$yW5MQkVNlzjLVLZryFJvmuZFS6r3dZhC7yLNBPg.qplFFVKMv6BXC', 'Rosa Gutiérrez Luyo',     'rgutierrez@siasiste.edu.pe','psicologo',  1);

-- ------------------------------------------------------------
-- Docentes
-- ------------------------------------------------------------
INSERT INTO `docentes` (`usuario_id`, `nombre`, `apellido`, `email`, `telefono`, `especialidad`, `activo`) VALUES
(2, 'Carlos',  'Mendoza Quispe',  'cmendoza@siasiste.edu.pe',  '951234567', 'Matemáticas',        1),
(NULL, 'Ana',    'Ramos Huanca',    'aramos@siasiste.edu.pe',    '952345678', 'Comunicación',        1),
(NULL, 'Luis',   'Pérez Ccallo',    'lperez@siasiste.edu.pe',    '953456789', 'Ciencias',            1),
(NULL, 'Carmen', 'Flores Ticona',   'cflores@siasiste.edu.pe',   '954567890', 'Historia y Geografía',1);

-- ------------------------------------------------------------
-- Estudiantes
-- ------------------------------------------------------------
INSERT INTO `estudiantes` (`codigo`, `nombre`, `apellido`, `grado`, `seccion`, `programa_id`, `email`, `telefono`, `activo`) VALUES
('EST-2026-001', 'Juan',    'Huamán Ticona',   '1er Grado', 'A', 1, 'jhuaman@gmail.com',   '961111111', 1),
('EST-2026-002', 'Lucía',   'Quispe Condori',  '1er Grado', 'A', 1, 'lquispe@gmail.com',   '962222222', 1),
('EST-2026-003', 'Pedro',   'Alvarez Ramos',   '2do Grado', 'B', 1, 'palvarez@gmail.com',  '963333333', 1),
('EST-2026-004', 'Sofía',   'Torres Luyo',     '2do Grado', 'B', 1, 'storres@gmail.com',   '964444444', 1),
('EST-2026-005', 'Miguel',  'García Medina',   '3er Grado', 'A', 1, 'mgarcia@gmail.com',   '965555555', 1),
('EST-2026-006', 'Valentina','Cruz Soto',      '3er Grado', 'A', 1, 'vcruz@gmail.com',     '966666666', 1);

-- ------------------------------------------------------------
-- Matrículas
-- ------------------------------------------------------------
INSERT INTO `matriculas` (`estudiante_id`, `programa_id`, `periodo_id`, `semestre`, `estado`) VALUES
(1, 1, 1, 1, 'activo'),
(2, 1, 1, 1, 'activo'),
(3, 1, 1, 1, 'activo'),
(4, 1, 1, 1, 'activo'),
(5, 1, 1, 1, 'activo'),
(6, 1, 1, 1, 'activo');

-- ------------------------------------------------------------
-- Unidades didácticas
-- ------------------------------------------------------------
INSERT INTO `unidades_didacticas` (`nombre`, `descripcion`, `programa_id`, `docente_id`, `semestre`, `activo`) VALUES
('Matemáticas I',    'Álgebra y geometría básica - 1er grado',       1, 1, 1, 1),
('Comunicación I',   'Lenguaje, redacción y literatura - 1er grado', 1, 2, 1, 1),
('Ciencias I',       'Biología y medio ambiente - 2do grado',        1, 3, 2, 1),
('Historia I',       'Historia del Perú - 3er grado',                1, 4, 3, 1);

-- ------------------------------------------------------------
-- Aulas
-- ------------------------------------------------------------
INSERT INTO `aulas` (`codigo`, `nombre`, `capacidad`, `edificio`, `activo`) VALUES
('A-101', 'Aula 101', 40, 'Bloque A - Principal', 1),
('A-202', 'Aula 202', 35, 'Bloque A - Principal', 1),
('B-101', 'Aula B-101', 45, 'Bloque B - Anexo',   1);

-- ------------------------------------------------------------
-- Horarios
-- ------------------------------------------------------------
INSERT INTO `horarios` (`dia`, `hora_inicio`, `hora_fin`, `aula_id`, `unidad_id`, `activo`) VALUES
('Lunes',     '08:00:00', '09:30:00', 1, 1, 1),
('Martes',    '08:00:00', '09:30:00', 1, 2, 1),
('Miércoles', '08:00:00', '09:30:00', 2, 3, 1),
('Jueves',    '08:00:00', '09:30:00', 2, 4, 1),
('Viernes',   '10:00:00', '11:30:00', 3, 1, 1);

-- ------------------------------------------------------------
-- Asistencias - muestra para julio 2026
-- ------------------------------------------------------------
INSERT INTO `asistencias` (`estudiante_id`, `unidad_id`, `fecha`, `estado`, `registrado_por`, `created_at`) VALUES
-- 1 de julio - Matemáticas
(1, 1, '2026-07-01', 'presente',   2, '2026-07-01 08:05:00'),
(2, 1, '2026-07-01', 'presente',   2, '2026-07-01 08:05:00'),
(3, 1, '2026-07-01', 'ausente',    2, '2026-07-01 08:05:00'),
(4, 1, '2026-07-01', 'justificado',2, '2026-07-01 08:05:00'),
(5, 1, '2026-07-01', 'presente',   2, '2026-07-01 08:05:00'),
(6, 1, '2026-07-01', 'presente',   2, '2026-07-01 08:05:00'),
-- 2 de julio - Comunicación
(1, 2, '2026-07-02', 'presente',   2, '2026-07-02 08:05:00'),
(2, 2, '2026-07-02', 'ausente',    2, '2026-07-02 08:05:00'),
(3, 2, '2026-07-02', 'presente',   2, '2026-07-02 08:05:00'),
(4, 2, '2026-07-02', 'presente',   2, '2026-07-02 08:05:00'),
(5, 2, '2026-07-02', 'ausente',    2, '2026-07-02 08:05:00'),
(6, 2, '2026-07-02', 'presente',   2, '2026-07-02 08:05:00'),
-- 3 de julio - Ciencias
(1, 3, '2026-07-03', 'presente',   2, '2026-07-03 08:05:00'),
(2, 3, '2026-07-03', 'presente',   2, '2026-07-03 08:05:00'),
(3, 3, '2026-07-03', 'ausente',    2, '2026-07-03 08:05:00'),
(4, 3, '2026-07-03', 'presente',   2, '2026-07-03 08:05:00'),
(5, 3, '2026-07-03', 'presente',   2, '2026-07-03 08:05:00'),
(6, 3, '2026-07-03', 'ausente',    2, '2026-07-03 08:05:00'),
-- 7 de julio - Matemáticas
(1, 1, '2026-07-07', 'presente',   2, '2026-07-07 08:05:00'),
(2, 1, '2026-07-07', 'ausente',    2, '2026-07-07 08:05:00'),
(3, 1, '2026-07-07', 'presente',   2, '2026-07-07 08:05:00'),
(4, 1, '2026-07-07', 'presente',   2, '2026-07-07 08:05:00'),
(5, 1, '2026-07-07', 'ausente',    2, '2026-07-07 08:05:00'),
(6, 1, '2026-07-07', 'presente',   2, '2026-07-07 08:05:00'),
-- 8 de julio - Comunicación
(1, 2, '2026-07-08', 'presente',   2, '2026-07-08 08:05:00'),
(2, 2, '2026-07-08', 'presente',   2, '2026-07-08 08:05:00'),
(3, 2, '2026-07-08', 'ausente',    2, '2026-07-08 08:05:00'),
(4, 2, '2026-07-08', 'justificado',2, '2026-07-08 08:05:00'),
(5, 2, '2026-07-08', 'presente',   2, '2026-07-08 08:05:00'),
(6, 2, '2026-07-08', 'presente',   2, '2026-07-08 08:05:00'),
-- 9 de julio - Ciencias
(1, 3, '2026-07-09', 'presente',   2, '2026-07-09 08:05:00'),
(2, 3, '2026-07-09', 'ausente',    2, '2026-07-09 08:05:00'),
(3, 3, '2026-07-09', 'presente',   2, '2026-07-09 08:05:00'),
(4, 3, '2026-07-09', 'presente',   2, '2026-07-09 08:05:00'),
(5, 3, '2026-07-09', 'ausente',    2, '2026-07-09 08:05:00'),
(6, 3, '2026-07-09', 'ausente',    2, '2026-07-09 08:05:00'),
-- 10 de julio - Historia
(1, 4, '2026-07-10', 'presente',   2, '2026-07-10 08:05:00'),
(2, 4, '2026-07-10', 'presente',   2, '2026-07-10 08:05:00'),
(3, 4, '2026-07-10', 'ausente',    2, '2026-07-10 08:05:00'),
(4, 4, '2026-07-10', 'ausente',    2, '2026-07-10 08:05:00'),
(5, 4, '2026-07-10', 'presente',   2, '2026-07-10 08:05:00'),
(6, 4, '2026-07-10', 'presente',   2, '2026-07-10 08:05:00'),
-- 14 de julio - Matemáticas
(1, 1, '2026-07-14', 'presente',   2, '2026-07-14 08:05:00'),
(2, 1, '2026-07-14', 'ausente',    2, '2026-07-14 08:05:00'),
(3, 1, '2026-07-14', 'presente',   2, '2026-07-14 08:05:00'),
(4, 1, '2026-07-14', 'presente',   2, '2026-07-14 08:05:00'),
(5, 1, '2026-07-14', 'ausente',    2, '2026-07-14 08:05:00'),
(6, 1, '2026-07-14', 'justificado',2, '2026-07-14 08:05:00');

-- ------------------------------------------------------------
-- Alertas iniciales (estudiantes con inasistencia elevada)
-- ------------------------------------------------------------
INSERT INTO `alertas` (`estudiante_id`, `porcentaje_inasistencia`, `nivel`, `fecha_deteccion`, `observaciones`, `derivado_a`, `estado`, `created_at`) VALUES
(2, 42.86, 'rojo',     '2026-07-14 09:00:00', 'Inasistencia recurrente en la semana. Se requiere intervención urgente.',   'Tutor María Torres',   'activa', '2026-07-14 09:00:00'),
(5, 42.86, 'rojo',     '2026-07-14 09:00:00', 'Tres faltas consecutivas en Matemáticas y Ciencias.',                     'Tutor María Torres',   'activa', '2026-07-14 09:00:00'),
(3, 28.57, 'amarillo', '2026-07-14 09:00:00', 'Inasistencia se acerca al umbral del 30%. Monitorear evolución.',          'Psicóloga Rosa Gutiérrez', 'activa', '2026-07-14 09:00:00'),
(6, 28.57, 'amarillo', '2026-07-14 09:00:00', 'Dos faltas en Ciencias y una justificada. Seguimiento continuo.',           'Psicóloga Rosa Gutiérrez', 'activa', '2026-07-14 09:00:00');

SET FOREIGN_KEY_CHECKS = 1;
