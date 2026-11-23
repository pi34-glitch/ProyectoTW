USE `clinicadmin_db`;

CREATE TABLE IF NOT EXISTS `monitoreos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `servicio_id` INT UNSIGNED NOT NULL,
  `estado` ENUM('activo', 'caido', 'error') NOT NULL,
  `tiempo_respuesta_ms` INT UNSIGNED NULL,
  `detalle` VARCHAR(500) NULL,
  `verificado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_monitoreos_servicio_fecha` (`servicio_id`, `verificado_en`),
  KEY `idx_monitoreos_fecha` (`verificado_en`),
  CONSTRAINT `fk_monitoreos_servicio` FOREIGN KEY (`servicio_id`)
    REFERENCES `servicios`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `incidentes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `servicio_id` INT UNSIGNED NULL,
  `titulo` VARCHAR(160) NOT NULL,
  `descripcion` TEXT NULL,
  `estado` ENUM('abierto', 'en_proceso', 'resuelto', 'cerrado') NOT NULL DEFAULT 'abierto',
  `prioridad` ENUM('baja', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
  `detectado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resuelto_en` DATETIME NULL,
  `asignado_a` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_incidentes_estado_fecha` (`estado`, `detectado_en`),
  CONSTRAINT `fk_incidentes_servicio` FOREIGN KEY (`servicio_id`)
    REFERENCES `servicios`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_incidentes_usuario` FOREIGN KEY (`asignado_a`)
    REFERENCES `usuarios`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `alertas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `servicio_id` INT UNSIGNED NULL,
  `monitoreo_id` BIGINT UNSIGNED NULL,
  `incidente_id` BIGINT UNSIGNED NULL,
  `destinatario` VARCHAR(254) NOT NULL,
  `asunto` VARCHAR(200) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `estado_envio` ENUM('pendiente', 'enviado', 'fallido') NOT NULL DEFAULT 'pendiente',
  `error_envio` VARCHAR(500) NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `enviado_en` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alertas_estado_fecha` (`estado_envio`, `creado_en`),
  CONSTRAINT `fk_alertas_servicio` FOREIGN KEY (`servicio_id`)
    REFERENCES `servicios`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_alertas_monitoreo` FOREIGN KEY (`monitoreo_id`)
    REFERENCES `monitoreos`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_alertas_incidente` FOREIGN KEY (`incidente_id`)
    REFERENCES `incidentes`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comandos_ejecutados` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL,
  `host_destino` VARCHAR(253) NOT NULL,
  `accion` VARCHAR(100) NOT NULL,
  `comando_ejecutado` VARCHAR(500) NOT NULL,
  `resultado` ENUM('exitoso', 'fallido') NOT NULL,
  `salida` TEXT NULL,
  `ejecutado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comandos_usuario_fecha` (`usuario_id`, `ejecutado_en`),
  KEY `idx_comandos_host_fecha` (`host_destino`, `ejecutado_en`),
  CONSTRAINT `fk_comandos_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
