-- Ejecutar UNA vez en phpMyAdmin (base isp_clientes)
-- 1) DPI opcional (se permiten varios clientes sin DPI; si se ingresa, sigue siendo único)
ALTER TABLE clientes MODIFY dpi VARCHAR(13) NULL;
UPDATE clientes SET dpi = NULL WHERE dpi = '';

-- 2) Credenciales del servicio por cliente
ALTER TABLE clientes
  ADD COLUMN ip VARCHAR(45) NULL AFTER correo,
  ADD COLUMN usuario_servicio VARCHAR(100) NULL AFTER ip,
  ADD COLUMN password_servicio VARCHAR(255) NULL AFTER usuario_servicio;
