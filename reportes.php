<?php
require __DIR__ . '/config.php';
requireLogin();

$clientesActivos = $pdo->query("SELECT * FROM clientes WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
$clientesSuspendidos = $pdo->query("SELECT * FROM clientes WHERE estado = 'suspendido' ORDER BY nombre")->fetchAll();

$clientesMorosos = [];
foreach ($pdo->query("SELECT * FROM clientes WHERE estado != 'inactivo' ORDER BY nombre")->fetchAll() as $c) {
    if (estadoCuentaCliente($pdo, (int) $c['id'])['estado'] === 'Moroso') $clientesMorosos[] = $c;
}

$esAdmin = esAdmin();   // incluye administrador y superusuario

$ingresosPorMes = [];
if ($esAdmin) {
    $ingresosPorMes = $pdo->query("
        SELECT DATE_FORMAT(fecha_pago, '%Y-%m-01') AS mes,
               SUM(monto) AS total,
               COUNT(*) AS cantidad
        FROM pagos
        WHERE estado = 'pagado'
        GROUP BY mes
        ORDER BY mes DESC
    ")->fetchAll();

    // Detalle de pagos por mes de ingreso
    $detalleIngresos = [];
    $detalle = $pdo->query("
        SELECT pg.fecha_pago, pg.monto, pg.mes_correspondiente, c.nombre
        FROM pagos pg
        JOIN contratos co ON co.id = pg.contrato_id
        JOIN clientes c ON c.id = co.cliente_id
        WHERE pg.estado = 'pagado'
        ORDER BY pg.fecha_pago, c.nombre
    ")->fetchAll();

    foreach ($detalle as $d) {
        $detalleIngresos[substr($d['fecha_pago'], 0, 7) . '-01'][] = $d;
    }
}

require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Reportes Administrativos</h3>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#activos">Clientes activos</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#suspendidos">Suspendidos</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#morosos">Morosos</button></li>
<?php if ($esAdmin): ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ingresos">Ingresos mensuales</button></li>
<?php endif; ?></ul>

<div class="tab-content bg-white p-3 rounded shadow-sm">

    <div class="tab-pane fade show active" id="activos">
        <table class="table table-bordered">
            <thead><tr><th>Nombre</th><th>DPI</th><th>Teléfono</th></tr></thead>
            <tbody>
                <?php foreach ($clientesActivos as $c): ?>
                <tr><td><?= e($c['nombre']) ?></td><td><?= e($c['dpi']) ?></td><td><?= e($c['telefono']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$clientesActivos): ?><tr><td colspan="3" class="text-muted text-center">Sin registros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="tab-pane fade" id="suspendidos">
        <table class="table table-bordered">
            <thead><tr><th>Nombre</th><th>DPI</th><th>Teléfono</th></tr></thead>
            <tbody>
                <?php foreach ($clientesSuspendidos as $c): ?>
                <tr><td><?= e($c['nombre']) ?></td><td><?= e($c['dpi']) ?></td><td><?= e($c['telefono']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$clientesSuspendidos): ?><tr><td colspan="3" class="text-muted text-center">Sin registros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="tab-pane fade" id="morosos">
        <table class="table table-bordered">
            <thead><tr><th>Nombre</th><th>DPI</th><th>Teléfono</th></tr></thead>
            <tbody>
                <?php foreach ($clientesMorosos as $c): ?>
                <tr><td><?= e($c['nombre']) ?></td><td><?= e($c['dpi']) ?></td><td><?= e($c['telefono']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$clientesMorosos): ?><tr><td colspan="3" class="text-muted text-center">Sin registros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

<?php if ($esAdmin): ?>
<div class="tab-pane fade" id="ingresos">
    <table class="table table-bordered">
        <thead><tr><th>Mes de ingreso</th><th>Pagos recibidos</th><th>Total ingresado</th><th>Detalle</th></tr></thead>
        <tbody>
            <?php foreach ($ingresosPorMes as $i): $idDet = 'det-' . substr($i['mes'], 0, 7); ?>
            <tr>
                <td><?= e(mesTexto($i['mes'])) ?></td>
                <td><?= (int) $i['cantidad'] ?></td>
                <td>Q <?= number_format($i['total'], 2) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#<?= $idDet ?>">Ver detalle</button>
                </td>
            </tr>
            <tr class="collapse" id="<?= $idDet ?>">
                <td colspan="4" class="bg-light">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Cliente</th><th>Mes pagado</th><th>Fecha de pago</th><th class="text-end">Monto</th></tr></thead>
                        <tbody>
                            <?php foreach ($detalleIngresos[$i['mes']] ?? [] as $d): ?>
                            <tr>
                                <td><?= e($d['nombre']) ?></td>
                                <td><?= e($d['mes_correspondiente']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($d['fecha_pago']))) ?></td>
                                <td class="text-end">Q <?= number_format($d['monto'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$ingresosPorMes): ?><tr><td colspan="4" class="text-muted text-center">Sin registros.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
