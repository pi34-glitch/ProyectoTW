-- ============================================================================
-- Script SQL: Esquema Completo ClinicAdmin (VM 1 y VM 2)
-- Proyecto: ClinicAdmin - Gestión de Infraestructura Médica (Clínica Don Juan)
-- Compatible con MariaDB 10.5+ / MySQL 5.7+ / 8.0+ (Rocky Linux 9 & Laragon QA)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `infraestructura_medica`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `infraestructura_medica`;

-- ----------------------------------------------------------------------------
-- 1. Tabla: usuarios
-- Almacena las credenciales y metadatos de acceso de los administradores TI
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre_completo` VARCHAR(120) NOT NULL,
  `rol` ENUM('admin_ti', 'operador', 'auditor') NOT NULL DEFAULT 'admin_ti',
  `activo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Activo, 0 = Bloqueado/Inactivo',
  `intentos_fallidos` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `bloqueado_hasta` DATETIME NULL DEFAULT NULL,
  `ultimo_acceso` DATETIME NULL DEFAULT NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_username` (`username`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_rol_activo` (`rol`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario inicial (Administrador de TI)
-- Usuario:   admin.ti
-- Email:     admin@clinicadonjuan.bo
-- Password:  AdminTI2026!
INSERT INTO `usuarios` (`username`, `email`, `password_hash`, `nombre_completo`, `rol`, `activo`)
VALUES (
  'admin.ti',
  'admin@clinicadonjuan.bo',
  '$2y$12$vuoqY.a1DB8GX9t.mmO9RueYS/LNLpy/NxvbfBqoxsyevoJ9/gx6m',
  'Bianca Morales',
  'admin_ti',
  1
)
ON DUPLICATE KEY UPDATE
  `nombre_completo` = VALUES(`nombre_completo`),
  `rol` = VALUES(`rol`),
  `activo` = VALUES(`activo`);

-- ----------------------------------------------------------------------------
-- 2. Tabla: servicios
-- Catálogo de los 7 servicios monitoreados por fsockopen en VM 1 y VM 2.
-- Incluye `ultimo_estado` para detectar transiciones ONLINE -> OFFLINE y
-- enviar alertas por SMTP (Mailpit) sin generar spam en recargas sucesivas.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `servicios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `categoria` VARCHAR(60) NOT NULL DEFAULT 'General',
  `nodo` VARCHAR(100) NOT NULL DEFAULT 'Nodo Central (admin.clinicasanjose.lan)',
  `host` VARCHAR(120) NOT NULL,
  `puerto` INT UNSIGNED NOT NULL,
  `protocolo` VARCHAR(16) NOT NULL DEFAULT 'TCP',
  `tiempo_espera_segundos` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `servicio_ssh` VARCHAR(40) NULL DEFAULT NULL COMMENT 'Nombre systemd en VM 2 (httpd, mariadb) o NULL',
  `ultimo_estado` ENUM('activo', 'caido', 'desconocido') NOT NULL DEFAULT 'desconocido',
  `ultimo_cambio` DATETIME NULL DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_servicios_host_puerto` (`host`, `puerto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alteraciones seguras en caso de que la tabla `servicios` ya existiera sin las columnas multi-nodo
-- (Ejecutar manualmente si se actualiza una tabla antigua):
-- ALTER TABLE `servicios` ADD COLUMN IF NOT EXISTS `nodo` VARCHAR(100) NOT NULL DEFAULT 'Nodo Central (admin.clinicasanjose.lan)' AFTER `categoria`;
-- ALTER TABLE `servicios` ADD COLUMN IF NOT EXISTS `servicio_ssh` VARCHAR(40) NULL DEFAULT NULL AFTER `tiempo_espera_segundos`;
-- ALTER TABLE `servicios` ADD COLUMN IF NOT EXISTS `ultimo_estado` ENUM('activo', 'caido', 'desconocido') NOT NULL DEFAULT 'desconocido' AFTER `servicio_ssh`;
-- ALTER TABLE `servicios` ADD COLUMN IF NOT EXISTS `ultimo_cambio` DATETIME NULL DEFAULT NULL AFTER `ultimo_estado`;

INSERT INTO `servicios` (`id`, `nombre`, `categoria`, `nodo`, `host`, `puerto`, `protocolo`, `tiempo_espera_segundos`, `servicio_ssh`, `activo`) VALUES
  (1, 'Servidor Web Intranet (Apache)', 'Web', 'Nodo Central (admin.clinicasanjose.lan)', '127.0.0.1', 8080, 'TCP', 2, NULL, 1),
  (2, 'Base de Datos Central (MariaDB)', 'Base de datos', 'Nodo Central (admin.clinicasanjose.lan)', '127.0.0.1', 3306, 'TCP', 2, NULL, 1),
  (3, 'Servidor SMTP Mailpit', 'Correo', 'Nodo Central (admin.clinicasanjose.lan)', '127.0.0.1', 25, 'TCP', 2, NULL, 1),
  (4, 'Servidor DNS Technitium', 'DNS / Red', 'Nodo Central (admin.clinicasanjose.lan)', '127.0.0.1', 53, 'TCP', 2, NULL, 1),
  (5, 'Portal Web Rayos X (Apache)', 'Web Médica', 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)', 'rayosx.clinicasanjose.lan', 8080, 'TCP', 2, 'httpd', 1),
  (6, 'Base de Datos Médica (rayosx_db)', 'Base de datos Médica', 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)', 'rayosx.clinicasanjose.lan', 3306, 'TCP', 2, 'mariadb', 1),
  (7, 'Demonio de Gestión SSH (VM 2)', 'Administración remota', 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)', 'rayosx.clinicasanjose.lan', 22, 'TCP', 2, NULL, 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `categoria` = VALUES(`categoria`),
  `nodo` = VALUES(`nodo`),
  `host` = VALUES(`host`),
  `puerto` = VALUES(`puerto`),
  `tiempo_espera_segundos` = VALUES(`tiempo_espera_segundos`),
  `servicio_ssh` = VALUES(`servicio_ssh`),
  `activo` = VALUES(`activo`);

-- ----------------------------------------------------------------------------
-- 3. Tabla: registros_dns
-- Almacena los registros de la zona interna `clinicasanjose.lan` / `clinicasanjose.local`
-- Soporta tipos A, CNAME, MX y PTR (Resolución inversa - HU-03)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registros_dns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo` ENUM('A', 'CNAME', 'MX', 'PTR') NOT NULL,
  `valor` VARCHAR(255) NOT NULL,
  `prioridad` INT UNSIGNED NULL DEFAULT NULL,
  `ttl` INT UNSIGNED NOT NULL DEFAULT 3600,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dns_nombre_tipo_valor` (`nombre`, `tipo`, `valor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Garantizar que si la tabla ya existía sin PTR en el ENUM, se actualice sin pérdida de datos
ALTER TABLE `registros_dns`
  MODIFY COLUMN `tipo` ENUM('A', 'CNAME', 'MX', 'PTR') NOT NULL;

INSERT INTO `registros_dns` (`nombre`, `tipo`, `valor`, `prioridad`, `ttl`, `estado`) VALUES
  ('@', 'A', '192.168.50.10', NULL, 3600, 'activo'),
  ('admin', 'A', '192.168.50.10', NULL, 3600, 'activo'),
  ('mail', 'A', '192.168.50.10', NULL, 3600, 'activo'),
  ('rayosx', 'A', '192.168.50.20', NULL, 3600, 'activo'),
  ('intranet', 'CNAME', 'admin.clinicasanjose.lan.', NULL, 3600, 'activo'),
  ('laboratorio', 'CNAME', 'rayosx.clinicasanjose.lan.', NULL, 3600, 'activo'),
  ('@', 'MX', 'mail.clinicasanjose.lan.', 10, 3600, 'activo'),
  ('10.50.168.192.in-addr.arpa', 'PTR', 'admin.clinicasanjose.lan.', NULL, 3600, 'activo'),
  ('20.50.168.192.in-addr.arpa', 'PTR', 'rayosx.clinicasanjose.lan.', NULL, 3600, 'activo')
ON DUPLICATE KEY UPDATE
  `ttl` = VALUES(`ttl`),
  `estado` = VALUES(`estado`);

-- ----------------------------------------------------------------------------
-- 4. Tabla: bitacora_eventos
-- Registro histórico de monitoreo, alertas SMTP, control SSH, DNS y accesos
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bitacora_eventos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `evento` VARCHAR(60) NOT NULL,
  `tipo` ENUM('ok', 'info', 'alerta') NOT NULL DEFAULT 'info',
  `detalle` VARCHAR(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bitacora_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bitacora_eventos` (`evento`, `tipo`, `detalle`)
SELECT * FROM (
  SELECT 'Monitoreo' AS `evento`, 'ok' AS `tipo`, 'Monitoreo multi-nodo inicializado (VM 1: admin.clinicasanjose.lan / VM 2: rayosx.clinicasanjose.lan).' AS `detalle`
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `bitacora_eventos` LIMIT 1);
