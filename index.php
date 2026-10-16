<?php
require __DIR__ . '/config.php';
requireLogin();

$totalClientes      = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$clientesActivos    = $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado = 'activo'")->fetchColumn();
$clientesSuspendidos = $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado = 'suspendido'")->fetchColumn();
$clientesMorosos = 0;
foreach ($pdo->query("SELECT id FROM clientes WHERE estado != 'inactivo'")->fetchAll(PDO::FETCH_COLUMN) as $cid) {
    if (estadoCuentaCliente($pdo, (int) $cid)['estado'] === 'Moroso') $clientesMorosos++;
}

$ingresosMes = $pdo->query("
    SELECT COALESCE(SUM(monto), 0)
    FROM pagos
    WHERE estado = 'pagado'
      AND MONTH(fecha_pago) = MONTH(CURDATE())
      AND YEAR(fecha_pago) = YEAR(CURDATE())
")->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Panel de Control</h3>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-indicador p-3">
            <div class="fs-6">Clientes totales</div>
            <div class="fs-2 fw-bold"><?= (int) $totalClientes ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-indicador alt p-3">
            <div class="fs-6">Clientes activos</div>
            <div class="fs-2 fw-bold"><?= (int) $clientesActivos ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-indicador warn p-3">
            <div class="fs-6">Suspendidos</div>
            <div class="fs-2 fw-bold"><?= (int) $clientesSuspendidos ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-indicador danger p-3">
            <div class="fs-6">Morosos</div>
            <div class="fs-2 fw-bold"><?= (int) $clientesMorosos ?></div>
        </div>
    </div>
</div>


<div class="card p-3 mb-4">
    <div class="fs-6 text-muted">Ingresos del mes actual</div>
    <div class="fs-3 fw-bold text-success">Q <?= number_format((float) $ingresosMes, 2) ?></div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <a href="clientes_listar.php" class="btn btn-isp w-100">Gestionar Clientes</a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="contratos_listar.php" class="btn btn-isp w-100">Gestionar Contratos</a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="reportes.php" class="btn btn-isp w-100">Ver Reportes</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
