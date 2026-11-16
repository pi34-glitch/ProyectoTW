-- ============================================================================
-- Script SQL: Diseño y creación de la tabla `usuarios`
-- Proyecto: ClinicAdmin - Gestión de Infraestructura Médica
-- Historia de Usuario: Autenticación segura para Administrador de TI
-- Compatible con MySQL 5.7+ / 8.0+ / MariaDB (phpMyAdmin)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `clinicaadmin`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `clinicaadmin`;

-- ----------------------------------------------------------------------------
-- Tabla: usuarios
-- Almacena las credenciales y metadatos de acceso de los administradores TI
-- y personal autorizado para gestionar la infraestructura médica.
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

-- ----------------------------------------------------------------------------
-- Usuario inicial de prueba (Administrador de TI)
-- Usuario:   admin.ti
-- Email:     admin@clinicadonjuan.bo
-- Password:  AdminTI2026!
-- Hash generado con PHP password_hash('AdminTI2026!', PASSWORD_BCRYPT)
-- ----------------------------------------------------------------------------
INSERT INTO `usuarios` (`username`, `email`, `password_hash`, `nombre_completo`, `rol`, `activo`)
VALUES (
  'admin.ti',
  'admin@clinicadonjuan.bo',
  '$2y$12$vuoqY.a1DB8GX9t.mmO9RueYS/LNLpy/NxvbfBqoxsyevoJ9/gx6m',
  'Administrador de TI - Clínica Don Juan',
  'admin_ti',
  1
)
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `nombre_completo` = VALUES(`nombre_completo`),
  `rol` = VALUES(`rol`),
  `activo` = VALUES(`activo`);
