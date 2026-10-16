-- Migración: recibos y estado de cuenta
-- Ejecutar UNA sola vez en phpMyAdmin sobre la base isp_clientes

ALTER TABLE pagos
  ADD COLUMN numero_recibo VARCHAR(20) NULL AFTER id,
  ADD COLUMN metodo_pago ENUM('efectivo','transferencia','deposito','tarjeta') NOT NULL DEFAULT 'efectivo' AFTER monto,
  ADD COLUMN periodo DATE NULL AFTER mes_correspondiente,
  ADD COLUMN registrado_por INT(11) NULL AFTER estado,
  ADD UNIQUE KEY numero_recibo (numero_recibo),
  ADD KEY periodo (periodo),
  ADD CONSTRAINT pagos_ibfk_2 FOREIGN KEY (registrado_por) REFERENCES usuarios (id) ON DELETE SET NULL;

-- Convierte el texto "Agosto 2026" (o "August 2026") a la fecha 2026-08-01
UPDATE pagos
SET periodo = STR_TO_DATE(
    CONCAT(
        SUBSTRING_INDEX(TRIM(mes_correspondiente), ' ', -1), '-',
        MOD(FIELD(LOWER(SUBSTRING_INDEX(TRIM(mes_correspondiente), ' ', 1)),
            'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre',
            'january','february','march','april','may','june','july','august','september','october','november','december') - 1, 12) + 1,
        '-01'),
    '%Y-%c-%d')
WHERE periodo IS NULL
  AND FIELD(LOWER(SUBSTRING_INDEX(TRIM(mes_correspondiente), ' ', 1)),
            'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre',
            'january','february','march','april','may','june','july','august','september','october','november','december') > 0;

-- Número de recibo a los pagos ya existentes
UPDATE pagos SET numero_recibo = CONCAT('REC-', LPAD(id, 6, '0'))
WHERE numero_recibo IS NULL AND estado = 'pagado';
