-- =========================================================
-- Sistema de Control y Gestión de Clientes - ISP
-- Script de creación de base de datos y datos de ejemplo
-- =========================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS isp_clientes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE isp_clientes;

-- ---------------------------------------------------------
-- Módulo 2: Usuarios y Roles
-- ---------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'operador') NOT NULL DEFAULT 'operador',
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- Módulo 3: Clientes
-- ---------------------------------------------------------
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    dpi VARCHAR(13) NOT NULL UNIQUE,
    direccion VARCHAR(200),
    telefono VARCHAR(20),
    correo VARCHAR(100),
    estado ENUM('activo', 'suspendido', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- Módulo 4: Planes de Internet
-- ---------------------------------------------------------
CREATE TABLE planes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    velocidad VARCHAR(50) NOT NULL,
    precio DECIMAL(8,2) NOT NULL,
    tipo_servicio VARCHAR(50) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
);

-- ---------------------------------------------------------
-- Módulo 5: Contratos
-- ---------------------------------------------------------
CREATE TABLE contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    plan_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    estado ENUM('activo', 'cancelado') NOT NULL DEFAULT 'activo',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES planes(id)
);

-- ---------------------------------------------------------
-- Módulo 6: Facturación y Pagos
-- ---------------------------------------------------------
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    monto DECIMAL(8,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    mes_correspondiente VARCHAR(20) NOT NULL,
    estado ENUM('pagado', 'pendiente', 'vencido') NOT NULL DEFAULT 'pagado',
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE
);

-- =========================================================
-- Datos de ejemplo
-- =========================================================

-- Usuarios (contraseñas: admin123 / operador123)
INSERT INTO usuarios (nombre, correo, password, rol) VALUES
('Administrador General', 'admin@ispguate.com', '$2y$10$Brce9urTZyhXpl8M5BKrEue/0jUeNUWODqoeW7BLquRMDWtnNdJ4W', 'administrador'),
('Operador Uno', 'operador@ispguate.com', '$2y$10$3PWobyl47TvpPYmtvSuuaONKxgDjnTP8j0FhuPJN0wH8qCdHd5Rj6', 'operador');

-- Planes de internet
INSERT INTO planes (nombre, velocidad, precio, tipo_servicio) VALUES
('Plan Básico', '10 Mbps', 150.00, 'Residencial'),
('Plan Intermedio', '30 Mbps', 250.00, 'Residencial'),
('Plan Empresarial', '100 Mbps', 600.00, 'Empresarial');

-- Clientes de ejemplo
INSERT INTO clientes (nombre, dpi, direccion, telefono, correo, estado) VALUES
('Ana López', '1234567890101', 'Zona 1, Santa Cruz del Quiché', '5555-1234', 'ana.lopez@correo.com', 'activo'),
('Carlos Méndez', '2345678901012', 'Zona 3, Santa Cruz del Quiché', '5555-5678', 'carlos.mendez@correo.com', 'activo'),
('María Tzoc', '3456789010123', 'Cantón Simajhuleu, Quiché', '5555-9012', 'maria.tzoc@correo.com', 'suspendido');

-- Contratos
INSERT INTO contratos (cliente_id, plan_id, fecha_inicio, estado) VALUES
(1, 2, '2026-06-01', 'activo'),
(2, 1, '2026-07-01', 'activo'),
(3, 3, '2026-05-01', 'activo');

-- Pagos
INSERT INTO pagos (contrato_id, monto, fecha_pago, mes_correspondiente, estado) VALUES
(1, 250.00, '2026-08-05', 'Agosto 2026', 'pagado'),
(2, 150.00, '2026-08-03', 'Agosto 2026', 'pagado'),
(3, 600.00, '2026-07-10', 'Julio 2026', 'vencido');
