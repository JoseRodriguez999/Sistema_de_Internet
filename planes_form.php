<?php
require __DIR__ . '/config.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$plan = ['nombre' => '', 'velocidad' => '', 'precio' => '', 'tipo_servicio' => '', 'estado' => 'activo'];
$errores = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM planes WHERE id = ?');
    $stmt->execute([$id]);
    $existente = $stmt->fetch();
    if (!$existente) {
        setFlash('error', 'Plan no encontrado.');
        header('Location: planes_listar.php');
        exit;
    }
    $plan = $existente;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan['nombre']        = trim($_POST['nombre'] ?? '');
    $plan['velocidad']     = trim($_POST['velocidad'] ?? '');
    $plan['precio']        = trim($_POST['precio'] ?? '');
    $plan['tipo_servicio'] = trim($_POST['tipo_servicio'] ?? '');
    $plan['estado']        = $_POST['estado'] ?? 'activo';

    if ($plan['nombre'] === '' || $plan['velocidad'] === '' || $plan['precio'] === '') {
        $errores[] = 'Nombre, velocidad y precio son obligatorios.';
    }
    if ($plan['precio'] !== '' && !is_numeric($plan['precio'])) {
        $errores[] = 'El precio debe ser un valor numérico.';
    }

    if (!$errores) {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE planes SET nombre=?, velocidad=?, precio=?, tipo_servicio=?, estado=? WHERE id=?'
            );
            $stmt->execute([$plan['nombre'], $plan['velocidad'], $plan['precio'], $plan['tipo_servicio'], $plan['estado'], $id]);
            setFlash('ok', 'Plan actualizado correctamente.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO planes (nombre, velocidad, precio, tipo_servicio, estado) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$plan['nombre'], $plan['velocidad'], $plan['precio'], $plan['tipo_servicio'], $plan['estado']]);
            setFlash('ok', 'Plan registrado correctamente.');
        }
        header('Location: planes_listar.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4"><?= $id ? 'Editar plan' : 'Nuevo plan' ?></h3>

<?php if ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach ($errores as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" class="bg-white p-4 rounded shadow-sm" style="max-width: 500px;">
    <div class="mb-3">
        <label class="form-label">Nombre del plan</label>
        <input type="text" name="nombre" class="form-control" value="<?= e($plan['nombre']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Velocidad</label>
        <input type="text" name="velocidad" class="form-control" placeholder="Ej. 30 Mbps" value="<?= e($plan['velocidad']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Precio mensual (Q)</label>
        <input type="text" name="precio" class="form-control" value="<?= e($plan['precio']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Tipo de servicio</label>
        <input type="text" name="tipo_servicio" class="form-control" placeholder="Residencial / Empresarial" value="<?= e($plan['tipo_servicio']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
            <option value="activo" <?= $plan['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
            <option value="inactivo" <?= $plan['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
        </select>
    </div>
    <button type="submit" class="btn btn-isp">Guardar</button>
    <a href="planes_listar.php" class="btn btn-outline-secondary">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
