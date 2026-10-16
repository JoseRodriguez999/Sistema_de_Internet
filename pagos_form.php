<?php
require __DIR__ . '/config.php';
requireLogin();

$errores    = [];
$contratoId = $_GET['contrato_id'] ?? '';
$monto      = '';
$fechaPago  = date('Y-m-d');
$periodo    = date('Y-m');          // formato del input type="month"
$metodo     = 'efectivo';
$estado     = 'pagado';
$metodos    = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito', 'tarjeta' => 'Tarjeta'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contratoId = $_POST['contrato_id'] ?? '';
    $monto      = trim($_POST['monto'] ?? '');
    $fechaPago  = $_POST['fecha_pago'] ?? '';
    $periodo    = $_POST['periodo'] ?? '';
    $metodo     = $_POST['metodo_pago'] ?? 'efectivo';
    $estado     = $_POST['estado'] ?? 'pagado';

    if ($contratoId === '' || $monto === '' || $fechaPago === '' || $periodo === '') {
        $errores[] = 'Todos los campos son obligatorios.';
    }
    if ($monto !== '' && (!is_numeric($monto) || $monto <= 0)) {
        $errores[] = 'El monto debe ser un valor numérico mayor a cero.';
    }
    if ($periodo !== '' && !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
        $errores[] = 'Mes correspondiente inválido.';
    }
    if (!isset($metodos[$metodo])) {
        $errores[] = 'Método de pago inválido.';
    }

    $periodoFecha = $periodo . '-01';

    // Evitar pagar dos veces el mismo mes
    if (!$errores && $estado === 'pagado') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE contrato_id = ? AND periodo = ? AND estado = 'pagado'");
        $stmt->execute([$contratoId, $periodoFecha]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'Ese mes ya fue pagado para este contrato.';
        }
    }

    if (!$errores) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO pagos (contrato_id, monto, metodo_pago, fecha_pago, mes_correspondiente, periodo, estado, registrado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$contratoId, $monto, $metodo, $fechaPago, mesTexto($periodoFecha),
                        $periodoFecha, $estado, $_SESSION['usuario_id']]);
        $pagoId = (int) $pdo->lastInsertId();

        if ($estado === 'pagado') {
            $pdo->prepare('UPDATE pagos SET numero_recibo = ? WHERE id = ?')
                ->execute(['REC-' . str_pad((string) $pagoId, 6, '0', STR_PAD_LEFT), $pagoId]);
        }
        $pdo->commit();

        if ($estado === 'pagado') {
            setFlash('ok', 'Pago registrado correctamente.');
            header('Location: recibo.php?id=' . $pagoId);
        } else {
            setFlash('ok', 'Registro guardado. Estado de cuenta actualizado.');
            header('Location: pagos_listar.php');
        }
        exit;
    }
}

$contratos = $pdo->query("
    SELECT co.id, c.nombre AS cliente_nombre, p.nombre AS plan_nombre, p.precio
    FROM contratos co
    JOIN clientes c ON c.id = co.cliente_id
    JOIN planes p ON p.id = co.plan_id
    WHERE co.estado = 'activo' AND c.estado <> 'inactivo'
    ORDER BY c.nombre
")->fetchAll();

// Precio sugerido si viene preseleccionado
if ($monto === '' && $contratoId !== '') {
    foreach ($contratos as $c) {
        if ($c['id'] == $contratoId) $monto = $c['precio'];
    }
}

require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Registrar pago</h3>

<?php if ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach ($errores as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" class="bg-white p-4 rounded shadow-sm" style="max-width: 500px;">
    <div class="mb-3">
        <label class="form-label">Contrato (cliente - plan)</label>
        <select name="contrato_id" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($contratos as $c): ?>
                <option value="<?= (int) $c['id'] ?>" data-precio="<?= e($c['precio']) ?>" <?= $contratoId == $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['cliente_nombre']) ?> - <?= e($c['plan_nombre']) ?> (Q<?= number_format($c['precio'], 2) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Mes correspondiente</label>
        <input type="month" name="periodo" class="form-control" value="<?= e($periodo) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Monto (Q)</label>
        <input type="text" name="monto" id="monto" class="form-control" value="<?= e($monto) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Método de pago</label>
        <select name="metodo_pago" class="form-select">
            <?php foreach ($metodos as $k => $v): ?>
                <option value="<?= $k ?>" <?= $metodo === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Fecha de pago</label>
        <input type="date" name="fecha_pago" class="form-control" value="<?= e($fechaPago) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Estado del pago</label>
        <select name="estado" class="form-select">
            <option value="pagado" <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado (genera recibo)</option>
            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="vencido" <?= $estado === 'vencido' ? 'selected' : '' ?>>Vencido</option>
        </select>
    </div>
    <button type="submit" class="btn btn-isp">Guardar pago</button>
    <a href="pagos_listar.php" class="btn btn-outline-secondary">Cancelar</a>
</form>

<script>
document.querySelector('select[name="contrato_id"]').addEventListener('change', function () {
    const precio = this.options[this.selectedIndex].dataset.precio;
    if (precio) document.getElementById('monto').value = precio;
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
