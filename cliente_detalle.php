<?php
require __DIR__ . '/config.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
$stmt->execute([$id]);
$cliente = $stmt->fetch();
if (!$cliente || ($cliente['estado'] === 'inactivo' && !esAdmin())) {
    setFlash('error', 'Cliente no encontrado.');
    header('Location: clientes_listar.php');
    exit;
}

// Contratos del cliente (activos primero)
$stmt = $pdo->prepare("SELECT id FROM contratos WHERE cliente_id = ? ORDER BY estado = 'activo' DESC, fecha_inicio DESC");
$stmt->execute([$id]);
$cuentas = [];
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cid) {
    $cuentas[] = estadoCuentaContrato($pdo, (int) $cid);
}
$resumen = estadoCuentaCliente($pdo, $id);

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Estado de cuenta</h3>
    <div>
        <?php if ($cliente['estado'] === 'inactivo'): ?>
            <a href="clientes_eliminar.php?id=<?= $id ?>&accion=reactivar" class="btn btn-success"
               onclick="return confirm('¿Reactivar a este cliente con todo su historial?');">Reactivar cliente</a>
            <a href="clientes_listar.php?ver=bajas" class="btn btn-outline-secondary">Volver</a>
        <?php else: ?>
            <a href="clientes_form.php?id=<?= $id ?>" class="btn btn-outline-secondary">Editar cliente</a>
            <a href="clientes_listar.php" class="btn btn-outline-secondary">Volver</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($cliente['estado'] === 'inactivo'): ?>
    <div class="alert alert-secondary">
        Cliente <strong>dado de baja</strong><?= $cliente['fecha_baja'] ? ' el ' . e(date('d/m/Y', strtotime($cliente['fecha_baja']))) : '' ?>.
        Solo el administrador puede ver este registro.
    </div>
<?php endif; ?>

<div class="bg-white p-3 rounded shadow-sm mb-4">
    <div class="row">
        <div class="col-md-8">
            <h5 class="mb-1"><?= e($cliente['nombre']) ?></h5>
            <div class="text-muted small">
                DPI: <?= e($cliente['dpi'] ?: '—') ?> · Tel: <?= e($cliente['telefono'] ?: '—') ?> ·
                <?= e($cliente['direccion'] ?: '—') ?>
            </div>
            <span class="badge bg-<?= $cliente['estado'] === 'activo' ? 'success' : ($cliente['estado'] === 'suspendido' ? 'warning' : 'secondary') ?> mt-2">
                <?= $cliente['estado'] === 'inactivo' ? 'Dado de baja' : 'Cliente ' . e($cliente['estado']) ?>
            </span>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="small text-muted">Situación de pagos</div>
            <span id="estadoCuenta" class="badge fs-6 bg-<?= colorEstadoCuenta($resumen['estado']) ?>">
                <?= e($resumen['estado'] === 'Moroso' ? 'Con mora' : $resumen['estado']) ?>
            </span>
            <div class="fs-5 fw-bold mt-1">Saldo: Q <?= number_format($resumen['saldo'], 2) ?></div>
        </div>
    </div>
</div>

<div class="bg-white p-3 rounded shadow-sm mb-4">
    <h6 class="text-secondary mb-3">Datos de conexión del servicio</h6>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="small text-muted">Tipo de conexión</div>
            <div><?= badgeConexion($cliente['tipo_conexion'] ?? null) ?></div>
        </div>
        <div class="col-md-8">
            <?php if (($cliente['tipo_conexion'] ?? '') === 'antena'): ?>
            <div class="small text-muted">Torre / punto de acceso</div>
            <div class="fw-bold"><?= e($cliente['torre'] ?: '—') ?></div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Dirección IP</div>
            <div class="fw-bold"><?= e($cliente['ip'] ?: '—') ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Usuario</div>
            <div class="fw-bold"><?= e($cliente['usuario_servicio'] ?: '—') ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Contraseña</div>
            <?php $passCli = descifrar($cliente['password_servicio'] ?? null); ?>
            <?php if ($passCli !== ''): ?>
            <div class="input-group input-group-sm">
                <input type="password" id="passDetalle" class="form-control" value="<?= e($passCli) ?>" readonly>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('passDetalle', this)">Ver</button>
                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('passDetalle').value); this.textContent='Copiado'">Copiar</button>
            </div>
            <?php else: ?><div class="fw-bold">—</div><?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$cuentas): ?>
    <div class="alert alert-info">Este cliente no tiene contratos registrados.
        <a href="contratos_form.php">Generar contrato</a></div>
<?php endif; ?>

<?php foreach ($cuentas as $ec): $co = $ec['contrato']; ?>
<div class="bg-white p-3 rounded shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <strong><?= e($co['plan_nombre']) ?></strong> (<?= e($co['velocidad']) ?>) —
            Q <?= number_format($co['precio'], 2) ?>/mes · desde <?= e(date('d/m/Y', strtotime($co['fecha_inicio']))) ?>
            <span class="badge bg-<?= $co['estado'] === 'activo' ? 'success' : 'secondary' ?>"><?= e(ucfirst($co['estado'])) ?></span>
        </div>
        <?php if ($co['estado'] === 'activo'): ?>
            <a href="pagos_form.php?contrato_id=<?= (int) $co['id'] ?>" class="btn btn-sm btn-isp">+ Registrar pago</a>
        <?php endif; ?>
    </div>

    <table class="table table-bordered table-sm mb-0">
        <thead>
            <tr>
                <th>Mes</th><th class="text-end">Cargo</th><th class="text-end">Pagado</th>
                <th class="text-end">Saldo</th><th>Estado</th><th>Recibo(s)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ec['meses'] as $m): ?>
            <tr>
                <td><?= e($m['mes']) ?></td>
                <td class="text-end">Q <?= number_format($m['cargo'], 2) ?></td>
                <td class="text-end">Q <?= number_format($m['pagado'], 2) ?></td>
                <td class="text-end">Q <?= number_format($m['saldo'], 2) ?></td>
                <td><span class="badge bg-<?= colorEstadoCuenta($m['estado']) ?>"><?= e($m['estado']) ?></span></td>
                <td>
                    <?php foreach ($m['recibos'] as $r): ?>
                        <a href="recibo.php?id=<?= (int) $r['id'] ?>" target="_blank"><?= e($r['numero_recibo'] ?: '#' . $r['id']) ?></a>
                    <?php endforeach; ?>
                    <?= $m['recibos'] ? '' : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td>Total</td>
                <td class="text-end">Q <?= number_format(array_sum(array_column($ec['meses'], 'cargo')), 2) ?></td>
                <td class="text-end">Q <?= number_format(array_sum(array_column($ec['meses'], 'pagado')), 2) ?></td>
                <td class="text-end">Q <?= number_format($ec['saldo'], 2) ?></td>
                <td colspan="2">
                    <span class="badge bg-<?= colorEstadoCuenta($ec['estado']) ?>"><?= e($ec['estado']) ?></span>
                    <?php if ($ec['vencidos']): ?><span class="small text-danger"><?= (int) $ec['vencidos'] ?> mes(es) vencido(s)</span><?php endif; ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
<?php endforeach; ?>

<script>
function togglePass(id, btn) {
    const i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
    btn.textContent = i.type === 'password' ? 'Ver' : 'Ocultar';
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
