-- =====================================================================
-- SGRSI - Sistema de Gestión de Recursos y Soporte de Informática
-- PixelMind - Segunda entrega - Programación Full Stack
-- DDL: Creación de la base de datos (esquema normalizado a 3FN)
-- Motor: MySQL/MariaDB (XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sgrsi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sgrsi;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS intervenciones_ticket;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS solicitudes_servicio;
DROP TABLE IF EXISTS prestamos;
DROP TABLE IF EXISTS asignaciones;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS usuarios;

DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS tipos_equipo;
DROP TABLE IF EXISTS estados_equipo;
DROP TABLE IF EXISTS estados_ticket;
DROP TABLE IF EXISTS estados_prestamo;
DROP TABLE IF EXISTS tipos_solicitud;
DROP TABLE IF EXISTS estados_solicitud;
DROP TABLE IF EXISTS prioridades;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- TABLAS CATÁLOGO
-- Almacenar los estados y tipos en tablas propias (en lugar de texto
-- repetido en cada fila) elimina dependencias transitivas y garantiza
-- la Tercera Forma Normal (3FN): todo atributo no clave depende
-- directamente de la clave primaria de su tabla.
-- ---------------------------------------------------------------------

CREATE TABLE roles (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE tipos_equipo (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE estados_equipo (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE prioridades (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE estados_ticket (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE estados_prestamo (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE tipos_solicitud (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE estados_solicitud (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- USUARIOS
-- La contraseña NUNCA se guarda en texto plano: se almacena el hash
-- bcrypt generado con password_hash() de PHP.
-- ---------------------------------------------------------------------

CREATE TABLE usuarios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(80)  NOT NULL,
    apellido      VARCHAR(80)  NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id        INT UNSIGNED NOT NULL,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_creacion DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL,
    CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id)
        REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EQUIPOS (inventario tecnológico)
-- ---------------------------------------------------------------------

CREATE TABLE equipos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(30)  NOT NULL UNIQUE COMMENT 'Código de inventario, ej. INV-0001',
    nombre           VARCHAR(100) NOT NULL,
    tipo_id          INT UNSIGNED NOT NULL,
    marca            VARCHAR(60)  NOT NULL,
    modelo           VARCHAR(80)  NOT NULL,
    numero_serie     VARCHAR(100) NOT NULL UNIQUE,
    estado_id        INT UNSIGNED NOT NULL,
    ubicacion        VARCHAR(100) NOT NULL,
    fecha_adquisicion DATE       NULL,
    observaciones    TEXT NULL,
    fecha_creacion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipo_tipo   FOREIGN KEY (tipo_id)   REFERENCES tipos_equipo(id)   ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_equipo_estado FOREIGN KEY (estado_id) REFERENCES estados_equipo(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_equipos_estado (estado_id),
    INDEX idx_equipos_codigo (codigo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ASIGNACIONES: trazabilidad de equipos (quién tuvo qué equipo y cuándo)
-- Requerimiento B de la letra del proyecto.
-- ---------------------------------------------------------------------

CREATE TABLE asignaciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipo_id       INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL COMMENT 'Usuario asignado/responsable',
    registrado_por  INT UNSIGNED NOT NULL COMMENT 'Técnico o admin que registra el movimiento',
    fecha_asignacion DATE       NOT NULL,
    fecha_devolucion DATE NULL,
    observaciones   VARCHAR(255) NULL,
    CONSTRAINT fk_asig_equipo   FOREIGN KEY (equipo_id)      REFERENCES equipos(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asig_usuario  FOREIGN KEY (usuario_id)     REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asig_registro FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_asig_equipo (equipo_id),
    INDEX idx_asig_usuario (usuario_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRÉSTAMOS de equipos
-- ---------------------------------------------------------------------

CREATE TABLE prestamos (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipo_id                INT UNSIGNED NOT NULL,
    usuario_solicitante_id   INT UNSIGNED NOT NULL,
    usuario_registra_id      INT UNSIGNED NOT NULL COMMENT 'Técnico/admin que gestiona',
    fecha_prestamo           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_esperada DATE   NOT NULL,
    fecha_devolucion_real    DATETIME NULL,
    estado_id                INT UNSIGNED NOT NULL,
    observaciones            VARCHAR(255) NULL,
    CONSTRAINT fk_prest_equipo    FOREIGN KEY (equipo_id)              REFERENCES equipos(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prest_solicita  FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prest_registra  FOREIGN KEY (usuario_registra_id)    REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prest_estado    FOREIGN KEY (estado_id)              REFERENCES estados_prestamo(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_prestamos_estado (estado_id),
    INDEX idx_prestamos_equipo (equipo_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TICKETS (mesa de ayuda / incidencias técnicas)
-- ---------------------------------------------------------------------

CREATE TABLE tickets (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo                 VARCHAR(150) NOT NULL,
    descripcion            TEXT NOT NULL,
    usuario_solicitante_id INT UNSIGNED NOT NULL,
    tecnico_asignado_id    INT UNSIGNED NULL,
    equipo_id              INT UNSIGNED NULL,
    prioridad_id           INT UNSIGNED NOT NULL,
    estado_id              INT UNSIGNED NOT NULL,
    fecha_creacion         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion       DATETIME NULL,
    eliminado              TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Borrado lógico (preserva trazabilidad)',
    CONSTRAINT fk_tick_solicitante FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tick_tecnico     FOREIGN KEY (tecnico_asignado_id)    REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tick_equipo      FOREIGN KEY (equipo_id)              REFERENCES equipos(id)  ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tick_prioridad   FOREIGN KEY (prioridad_id)           REFERENCES prioridades(id)    ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tick_estado      FOREIGN KEY (estado_id)              REFERENCES estados_ticket(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_tickets_estado (estado_id),
    INDEX idx_tickets_solicitante (usuario_solicitante_id),
    INDEX idx_tickets_tecnico (tecnico_asignado_id)
) ENGINE=InnoDB;

-- Intervenciones/diagnósticos por ticket:
-- base de conocimiento para futuros diagnósticos (requerimiento E).
CREATE TABLE intervenciones_ticket (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT UNSIGNED NOT NULL,
    tecnico_id  INT UNSIGNED NOT NULL,
    diagnostico TEXT NOT NULL,
    es_resolucion TINYINT(1) NOT NULL DEFAULT 0,
    fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_int_ticket  FOREIGN KEY (ticket_id)  REFERENCES tickets(id)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_int_tecnico FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_intervenciones_ticket (ticket_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SOLICITUDES DE SERVICIO
-- ---------------------------------------------------------------------

CREATE TABLE solicitudes_servicio (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_solicitante_id INT UNSIGNED NOT NULL,
    tipo_id                INT UNSIGNED NOT NULL,
    titulo                 VARCHAR(150) NOT NULL,
    descripcion            TEXT NOT NULL,
    laboratorio            VARCHAR(80) NULL,
    fecha_necesidad        DATE NOT NULL,
    estado_id              INT UNSIGNED NOT NULL,
    respuesta              TEXT NULL COMMENT 'Nota del técnico/coordinador',
    atendida_por           INT UNSIGNED NULL,
    fecha_creacion         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre           DATETIME NULL,
    eliminado              TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Borrado lógico (preserva trazabilidad)',
    CONSTRAINT fk_sol_solicitante FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id)         ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sol_tipo        FOREIGN KEY (tipo_id)                 REFERENCES tipos_solicitud(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sol_estado      FOREIGN KEY (estado_id)               REFERENCES estados_solicitud(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sol_atendida    FOREIGN KEY (atendida_por)            REFERENCES usuarios(id)          ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_solicitudes_estado (estado_id),
    INDEX idx_solicitudes_solicitante (usuario_solicitante_id)
) ENGINE=InnoDB;
