<?php
require __DIR__ . '/config.php';
requireLogin();

$errores = [];
$clienteId = '';
$planId = '';
$fechaInicio = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clienteId   = $_POST['cliente_id'] ?? '';
    $planId      = $_POST['plan_id'] ?? '';
    $fechaInicio = $_POST['fecha_inicio'] ?? '';

    if ($clienteId === '' || $planId === '' || $fechaInicio === '') {
        $errores[] = 'Debe seleccionar cliente, plan y fecha de inicio.';
    }

    if (!$errores) {
        $stmt = $pdo->prepare(
            'INSERT INTO contratos (cliente_id, plan_id, fecha_inicio, estado) VALUES (?, ?, ?, "activo")'
        );
        $stmt->execute([$clienteId, $planId, $fechaInicio]);
        setFlash('ok', 'Contrato generado correctamente.');
        header('Location: contratos_listar.php');
        exit;
    }
}

$clientes = $pdo->query("SELECT * FROM clientes WHERE estado != 'inactivo' ORDER BY nombre")->fetchAll();
$planes   = $pdo->query("SELECT * FROM planes WHERE estado = 'activo' ORDER BY nombre")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Nuevo contrato</h3>

<?php if ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach ($errores as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" class="bg-white p-4 rounded shadow-sm" style="max-width: 500px;">
    <div class="mb-3">
        <label class="form-label">Cliente</label>
        <select name="cliente_id" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $clienteId == $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nombre']) ?> (<?= e($c['dpi']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Plan de internet</label>
        <select name="plan_id" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($planes as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $planId == $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['nombre']) ?> - Q<?= number_format($p['precio'], 2) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Fecha de inicio</label>
        <input type="date" name="fecha_inicio" class="form-control" value="<?= e($fechaInicio) ?>" required>
    </div>
    <button type="submit" class="btn btn-isp">Generar contrato</button>
    <a href="contratos_listar.php" class="btn btn-outline-secondary">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
