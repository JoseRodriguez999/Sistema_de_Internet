<?php
require __DIR__ . '/config.php';
requireLogin();

$pagos = $pdo->query("
    SELECT pg.*, c.nombre AS cliente_nombre, p.nombre AS plan_nombre
    FROM pagos pg
    JOIN contratos co ON co.id = pg.contrato_id
    JOIN clientes c ON c.id = co.cliente_id
    JOIN planes p ON p.id = co.plan_id
    ORDER BY pg.fecha_pago DESC, pg.id DESC
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Recibos</h3>
    <a href="pagos_form.php" class="btn btn-isp">+ Registrar pago</a>
</div>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>No. recibo</th>
            <th>Cliente</th>
            <th>Plan</th>
            <th>Mes correspondiente</th>
            <th>Monto</th>
            <th>Fecha de pago</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pagos as $p): ?>
        <tr>
            <td><?= e($p['numero_recibo'] ?: '—') ?></td>
            <td><?= e($p['cliente_nombre']) ?></td>
            <td><?= e($p['plan_nombre']) ?></td>
            <td><?= e($p['mes_correspondiente']) ?></td>
            <td>Q <?= number_format($p['monto'], 2) ?></td>
            <td><?= e($p['fecha_pago']) ?></td>
            <td>
                <span class="badge bg-<?= $p['estado'] === 'pagado' ? 'success' : ($p['estado'] === 'pendiente' ? 'warning' : 'danger') ?>">
                    <?= e(ucfirst($p['estado'])) ?>
                </span>
            </td>
            <td>
                <?php if ($p['estado'] === 'pagado'): ?>
                    <a href="recibo.php?id=<?= (int) $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Imprimir recibo</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$pagos): ?>
        <tr><td colspan="8" class="text-center text-muted">No hay pagos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
