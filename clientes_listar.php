<?php
require __DIR__ . '/config.php';
requireLogin();

// ---- Módulo de Búsqueda y Filtrado (RF-06) ----
$nombre = trim($_GET['nombre'] ?? '');
$dpi    = trim($_GET['dpi'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$planId = trim($_GET['plan_id'] ?? '');
$conexion = trim($_GET['conexion'] ?? '');
// Vista de clientes dados de baja: solo administrador
$verBajas = esAdmin() && ($_GET['ver'] ?? '') === 'bajas';
if ($estado === 'inactivo') $estado = '';
$torre    = trim($_GET['torre'] ?? '');

$sql = "
    SELECT c.*, 
           GROUP_CONCAT(DISTINCT p.nombre SEPARATOR ', ') AS planes
    FROM clientes c
    LEFT JOIN contratos co ON co.cliente_id = c.id AND co.estado = 'activo'
    LEFT JOIN planes p ON p.id = co.plan_id
    WHERE " . ($verBajas ? "c.estado = 'inactivo'" : "c.estado <> 'inactivo'") . "
";
$params = [];

if ($nombre !== '') {
    $sql .= " AND c.nombre LIKE ?";
    $params[] = "%$nombre%";
}
if ($dpi !== '') {
    $sql .= " AND c.dpi LIKE ?";
    $params[] = "%$dpi%";
}
if ($estado !== '') {
    $sql .= " AND c.estado = ?";
    $params[] = $estado;
}
if ($planId !== '') {
    $sql .= " AND co.plan_id = ?";
    $params[] = $planId;
}

if ($conexion === 'sin') {
    $sql .= " AND c.tipo_conexion IS NULL";
} elseif (isset(TIPOS_CONEXION[$conexion])) {
    $sql .= " AND c.tipo_conexion = ?";
    $params[] = $conexion;
}
if ($torre !== '') {
    $sql .= " AND c.torre = ?";
    $params[] = $torre;
}

$sql .= " GROUP BY c.id ORDER BY c.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

// Estado de pago de cada cliente (Al día / Pendiente / Moroso)
foreach ($clientes as &$c) {
    $c['cuenta'] = estadoCuentaCliente($pdo, (int) $c['id']);
}
unset($c);

$hayFiltros = ($nombre !== '' || $dpi !== '' || $estado !== '' || $planId !== '' || $conexion !== '' || $torre !== '');

// Contadores por tipo de conexión (clientes no inactivos)
$conteo = ['fibra' => 0, 'antena' => 0, 'sin' => 0];
foreach ($pdo->query("SELECT tipo_conexion, COUNT(*) n FROM clientes WHERE estado <> 'inactivo' GROUP BY tipo_conexion") as $r) {
    $conteo[$r['tipo_conexion'] ?? 'sin'] = (int) $r['n'];
}
$totalBajas = esAdmin() ? (int) $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado = 'inactivo'")->fetchColumn() : 0;
$urlBase = 'clientes_listar.php' . ($verBajas ? '?ver=bajas' : '');
$torres = $pdo->query("SELECT DISTINCT torre FROM clientes WHERE torre IS NOT NULL AND torre <> '' ORDER BY torre")->fetchAll(PDO::FETCH_COLUMN);

$planes = $pdo->query("SELECT * FROM planes ORDER BY nombre")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><?= $verBajas ? 'Clientes dados de baja' : 'Clientes' ?></h3>
    <div class="d-flex align-items-center gap-3">
        <?php if (esAdmin()): ?>
            <?php if ($verBajas): ?>
                <a href="clientes_listar.php" class="small">&larr; Volver a clientes activos</a>
            <?php elseif ($totalBajas): ?>
                <a href="clientes_listar.php?ver=bajas" class="small text-muted">Ver dados de baja (<?= $totalBajas ?>)</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!$verBajas): ?><a href="clientes_form.php" class="btn btn-isp">+ Nuevo cliente</a><?php endif; ?>
    </div>
</div>

<?php if (!$verBajas): ?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="clientes_listar.php?conexion=fibra" class="btn btn-sm btn-outline-primary">Fibra: <strong><?= $conteo['fibra'] ?></strong></a>
    <a href="clientes_listar.php?conexion=antena" class="btn btn-sm btn-outline-warning">Antena: <strong><?= $conteo['antena'] ?></strong></a>
    <a href="clientes_listar.php?conexion=sin" class="btn btn-sm btn-outline-secondary">Sin definir: <strong><?= $conteo['sin'] ?></strong></a>
</div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3 bg-white p-3 rounded shadow-sm">
    <?php if ($verBajas): ?><input type="hidden" name="ver" value="bajas"><?php endif; ?>
    <div class="col-md-3">
        <input type="text" name="nombre" class="form-control" placeholder="Buscar por nombre" value="<?= e($nombre) ?>">
    </div>
    <div class="col-md-2">
        <input type="text" name="dpi" class="form-control" placeholder="Buscar por DPI" value="<?= e($dpi) ?>">
    </div>
    <div class="col-md-2">
        <select name="estado" class="form-select">
            <option value="">-- Estado --</option>
            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
            <option value="suspendido" <?= $estado === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
        </select>
    </div>
    <div class="col-md-2">
        <select name="plan_id" class="form-select">
            <option value="">-- Plan --</option>
            <?php foreach ($planes as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $planId == $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="conexion" class="form-select">
            <option value="">-- Conexión --</option>
            <?php foreach (TIPOS_CONEXION as $k => $v): ?>
                <option value="<?= $k ?>" <?= $conexion === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
            <option value="sin" <?= $conexion === 'sin' ? 'selected' : '' ?>>Sin definir</option>
        </select>
    </div>
    <?php if ($torres): ?>
    <div class="col-md-2">
        <select name="torre" class="form-select">
            <option value="">-- Torre --</option>
            <?php foreach ($torres as $t): ?>
                <option value="<?= e($t) ?>" <?= $torre === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-md-3 d-flex gap-2">
        <button type="submit" id="btnFiltrar" class="btn btn-isp w-100">Filtrar</button>
        <a href="<?= $urlBase ?>" id="btnLimpiar" class="btn btn-outline-secondary w-100">Limpiar</a>
    </div>
    <?php if ($hayFiltros): ?>
    <div class="col-12 small text-muted">Mostrando resultados filtrados (<?= count($clientes) ?>).</div>
    <?php endif; ?>
</form>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>DPI</th>
            <th>Teléfono</th>
            <th>Plan(es)</th>
            <th>Conexión</th>
            <th>Estado</th>
            <th>Pagos</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($clientes as $c): ?>
        <tr class="<?= $verBajas ? 'text-muted' : '' ?>" <?= $verBajas ? 'style="background:#f3f3f3"' : '' ?>>
            <td><a href="cliente_detalle.php?id=<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></a></td>
            <td><?= e($c['dpi'] ?: '—') ?></td>
            <td><?= e($c['telefono']) ?></td>
            <td><?= e($c['planes'] ?: '—') ?></td>
            <td>
                <?= badgeConexion($c['tipo_conexion'] ?? null) ?>
                <?php if (!empty($c['torre'])): ?><div class="small text-muted"><?= e($c['torre']) ?></div><?php endif; ?>
            </td>
            <td>
                <span class="badge bg-<?= $c['estado'] === 'activo' ? 'success' : ($c['estado'] === 'suspendido' ? 'warning' : 'secondary') ?>">
                    <?= $c['estado'] === 'inactivo' ? 'Dado de baja' : e(ucfirst($c['estado'])) ?>
                </span>
                <?php if ($c['estado'] === 'inactivo' && !empty($c['fecha_baja'])): ?>
                    <div class="small"><?= e(date('d/m/Y', strtotime($c['fecha_baja']))) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <span class="badge bg-<?= colorEstadoCuenta($c['cuenta']['estado']) ?>">
                    <?= e($c['cuenta']['estado'] === 'Moroso' ? 'Con mora' : $c['cuenta']['estado']) ?>
                </span>
                <?php if ($c['cuenta']['saldo'] > 0): ?>
                    <div class="small text-muted">Saldo: Q <?= number_format($c['cuenta']['saldo'], 2) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <a href="cliente_detalle.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-isp">Ver</a>
                <?php if ($verBajas): ?>
                    <a href="clientes_eliminar.php?id=<?= (int) $c['id'] ?>&accion=reactivar"
                       class="btn btn-sm btn-success"
                       onclick="return confirm('¿Reactivar a este cliente con todo su historial?');">Reactivar</a>
                <?php else: ?>
                    <a href="clientes_form.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                    <a href="clientes_eliminar.php?id=<?= (int) $c['id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('¿Dar de baja a este cliente? Sus contratos se cerrarán, pero el historial de pagos se conserva.');">Baja</a>
                <?php endif; ?>
                <?php if (esSuperusuarioSesion()): ?>
                    <a href="clientes_eliminar.php?id=<?= (int) $c['id'] ?>&accion=eliminar"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('ELIMINAR DEFINITIVAMENTE a este cliente y TODO su historial (contratos y pagos). Esta acción no se puede deshacer. ¿Continuar?');">Eliminar</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$clientes): ?>
        <tr><td colspan="8" class="text-center text-muted">No se encontraron clientes con esos criterios.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
