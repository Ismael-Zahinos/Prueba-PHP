-- =====================================================================
-- ESQUEMA Y DATOS DE PRUEBA: GESTIÓN DE RESERVAS DE ALOJAMIENTO
-- =====================================================================

-- Opcional: Descomentar si deseas que el script cree y seleccione la BD
-- CREATE DATABASE IF NOT EXISTS `reservas_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `reservas_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reserva_evento`;
DROP TABLE IF EXISTS `reserva`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. TABLA: reserva
-- ---------------------------------------------------------------------
CREATE TABLE `reserva` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre_huesped` VARCHAR(150) NOT NULL,
    `email_huesped` VARCHAR(150) NOT NULL,
    `nombre_alojamiento` VARCHAR(150) NOT NULL,
    `fecha_entrada` DATE NOT NULL,
    `fecha_salida` DATE NOT NULL,
    `estado` ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    `importe` DECIMAL(10, 2) NOT NULL,
    `notas` TEXT NULL,
    `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices para optimizar las búsquedas y filtros combinados de la API
    INDEX `idx_estado` (`estado`),
    INDEX `idx_fechas` (`fecha_entrada`, `fecha_salida`),
    INDEX `idx_nombre_huesped` (`nombre_huesped`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. TABLA: reserva_evento (Historial / Timeline)
-- ---------------------------------------------------------------------
CREATE TABLE `reserva_evento` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reserva_id` INT UNSIGNED NOT NULL,
    `tipo` VARCHAR(50) NOT NULL, -- Ej: 'creada', 'confirmada', 'cancelada', 'actualizada'
    `descripcion` TEXT NOT NULL,
    `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_reserva_id` (`reserva_id`),
    CONSTRAINT `fk_reserva_evento_reserva` 
        FOREIGN KEY (`reserva_id`) 
        REFERENCES `reserva` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. DATOS DE EJEMPLO (SEEDS)
-- ---------------------------------------------------------------------

-- Reserva 1: Pendiente recién creada
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`)
VALUES (
    1,
    'Carlos Mendoza',
    'carlos.mendoza@example.com',
    'Apartamento Centro Histórico',
    '2026-11-01',
    '2026-11-05',
    'pendiente',
    320.50,
    'Llegará tarde, sobre las 21:00.',
    '2026-10-10 10:15:00'
);

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`)
VALUES (
    1,
    'creada',
    'Reserva registrada en el sistema con estado pendiente.',
    '2026-10-10 10:15:00'
);

-- Reserva 2: Confirmada (pasó de pendiente a confirmada)
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`)
VALUES (
    2,
    'Lucía Gómez',
    'lucia.gomez@example.com',
    'Villa Sol & Playa',
    '2026-11-10',
    '2026-11-17',
    'confirmada',
    950.00,
    'Requiere cuna de bebé.',
    '2026-10-11 12:00:00'
);

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`)
VALUES 
(
    2,
    'creada',
    'Reserva registrada en el sistema con estado pendiente.',
    '2026-10-11 12:00:00'
),
(
    2,
    'confirmada',
    'Reserva confirmada tras validación de disponibilidad/pago.',
    '2026-10-11 14:30:00'
);

-- Reserva 3: Cancelada
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`)
VALUES (
    3,
    'Marcos Alonso',
    'marcos.alonso@example.com',
    'Cabaña Rústica Los Pinos',
    '2026-12-01',
    '2026-12-04',
    'cancelada',
    240.00,
    NULL,
    '2026-10-12 09:20:00'
);

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`)
VALUES 
(
    3,
    'creada',
    'Reserva registrada en el sistema con estado pendiente.',
    '2026-10-12 09:20:00'
),
(
    3,
    'cancelada',
    'El huésped solicitó la cancelación por cambio de planes de viaje.',
    '2026-10-13 18:05:00'
);

-- Reserva 4: Confirmada con mismo nombre de huésped para probar búsquedas
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`)
VALUES (
    4,
    'Carlos Mendoza',
    'carlos.mendoza@example.com',
    'Estudio Loft Moderno',
    '2026-12-20',
    '2026-12-26',
    'confirmada',
    510.00,
    'Cliente recurrente.',
    '2026-10-14 16:45:00'
);

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`)
VALUES 
(
    4,
    'creada',
    'Reserva registrada en el sistema con estado pendiente.',
    '2026-10-14 16:45:00'
),
(
    4,
    'confirmada',
    'Reserva confirmada automáticamente.',
    '2026-10-14 16:46:10'
);