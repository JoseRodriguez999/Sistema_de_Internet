<?php
require __DIR__ . '/config.php';
requireLogin();

$planes = $pdo->query("SELECT * FROM planes ORDER BY precio")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Planes de Internet</h3>
    <a href="planes_form.php" class="btn btn-isp">+ Nuevo plan</a>
</div>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Velocidad</th>
            <th>Precio</th>
            <th>Tipo de servicio</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($planes as $p): ?>
        <tr>
            <td><?= e($p['nombre']) ?></td>
            <td><?= e($p['velocidad']) ?></td>
            <td>Q <?= number_format($p['precio'], 2) ?></td>
            <td><?= e($p['tipo_servicio']) ?></td>
            <td>
                <span class="badge bg-<?= $p['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                    <?= e(ucfirst($p['estado'])) ?>
                </span>
            </td>
            <td>
                <a href="planes_form.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$planes): ?>
        <tr><td colspan="6" class="text-center text-muted">No hay planes registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
