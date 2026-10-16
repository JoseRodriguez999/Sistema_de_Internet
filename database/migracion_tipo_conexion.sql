-- Ejecutar UNA vez en phpMyAdmin
USE isp_clientes;

ALTER TABLE clientes
  ADD COLUMN tipo_conexion ENUM('fibra','antena') NULL AFTER correo,
  ADD COLUMN torre VARCHAR(100) NULL AFTER tipo_conexion,
  ADD KEY idx_tipo_conexion (tipo_conexion);
