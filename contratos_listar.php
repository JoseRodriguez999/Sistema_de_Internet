<?php
require __DIR__ . '/config.php';
requireLogin();

$contratos = $pdo->query("
    SELECT co.*, c.nombre AS cliente_nombre, p.nombre AS plan_nombre
    FROM contratos co
    JOIN clientes c ON c.id = co.cliente_id
    JOIN planes p ON p.id = co.plan_id
    ORDER BY co.fecha_inicio DESC
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contratos</h3>
    <a href="contratos_form.php" class="btn btn-isp">+ Nuevo contrato</a>
</div>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Plan</th>
            <th>Fecha de inicio</th>
            <th>Estado</th>
            <th>Estado de cuenta</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($contratos as $c): ?>
        <tr>
            <td><?= e($c['cliente_nombre']) ?></td>
            <td><?= e($c['plan_nombre']) ?></td>
            <td><?= e($c['fecha_inicio']) ?></td>
            <td>
                <span class="badge bg-<?= $c['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                    <?= e(ucfirst($c['estado'])) ?>
                </span>
            </td>
            <td>
                <?php $estadoCuenta = calcularEstadoCuenta($pdo, $c['id']); ?>
                <span class="badge bg-<?= $estadoCuenta === 'Al día' ? 'success' : ($estadoCuenta === 'Pendiente' ? 'warning' : 'danger') ?>">
                    <?= e($estadoCuenta) ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$contratos): ?>
        <tr><td colspan="5" class="text-center text-muted">No hay contratos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
