<?php
require __DIR__ . '/config.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$cliente = ['nombre' => '', 'dpi' => '', 'direccion' => '', 'telefono' => '', 'correo' => '', 'estado' => 'activo',
            'tipo_conexion' => '', 'torre' => '', 'ip' => '', 'usuario_servicio' => '', 'password_servicio' => ''];
$errores = [];
$avisoBaja = [];   // clientes dados de baja que coinciden con el que se intenta registrar

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
    $stmt->execute([$id]);
    $existente = $stmt->fetch();
    if (!$existente) {
        setFlash('error', 'Cliente no encontrado.');
        header('Location: clientes_listar.php');
        exit;
    }
    // El operador no puede ver ni editar clientes dados de baja
    if ($existente['estado'] === 'inactivo' && !esAdmin()) {
        setFlash('error', 'Cliente no encontrado.');
        header('Location: clientes_listar.php');
        exit;
    }
    $cliente = $existente;
    $cliente['password_servicio'] = descifrar($existente['password_servicio'] ?? null);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente['nombre']    = trim($_POST['nombre'] ?? '');
    $cliente['dpi']       = trim($_POST['dpi'] ?? '');
    $cliente['direccion'] = trim($_POST['direccion'] ?? '');
    $cliente['telefono']  = trim($_POST['telefono'] ?? '');
    $cliente['correo']    = trim($_POST['correo'] ?? '');
    // La baja/reactivación se hace con su botón; aquí solo activo o suspendido
    $estadoPost = $_POST['estado'] ?? 'activo';
    if ($cliente['estado'] !== 'inactivo') {
        $cliente['estado'] = in_array($estadoPost, ['activo', 'suspendido'], true) ? $estadoPost : 'activo';
    }
    $cliente['tipo_conexion']     = $_POST['tipo_conexion'] ?? '';
    $cliente['torre']             = trim($_POST['torre'] ?? '');
    $cliente['ip']                = trim($_POST['ip'] ?? '');
    $cliente['usuario_servicio']  = trim($_POST['usuario_servicio'] ?? '');
    $cliente['password_servicio'] = $_POST['password_servicio'] ?? '';

    // Campos obligatorios
    // Solo el nombre es obligatorio (DPI y correo son opcionales)
    if ($cliente['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($cliente['correo'] !== '' && !filter_var($cliente['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    if ($cliente['tipo_conexion'] !== '' && !isset(TIPOS_CONEXION[$cliente['tipo_conexion']])) {
        $errores[] = 'Tipo de conexión inválido.';
    }
    if ($cliente['ip'] !== '' && !filter_var($cliente['ip'], FILTER_VALIDATE_IP)) {
        $errores[] = 'La dirección IP no es válida.';
    }

    // CP-CLI-002: Validación de formato de DPI (13 dígitos numéricos)
    if ($cliente['dpi'] !== '' && !preg_match('/^\d{13}$/', $cliente['dpi'])) {
        $errores[] = 'Formato de DPI inválido. Debe contener 13 dígitos numéricos.';
    }

    // Validar que el DPI no esté registrado en otro cliente
    if (!$errores && $cliente['dpi'] !== '') {
        $stmt = $pdo->prepare('SELECT id, nombre, telefono, estado, fecha_baja FROM clientes WHERE dpi = ? AND id <> ?');
        $stmt->execute([$cliente['dpi'], $id ?? 0]);
        if ($dup = $stmt->fetch()) {
            if ($dup['estado'] === 'inactivo') {
                $avisoBaja = [$dup];      // ya estuvo registrado: ofrecer reactivar
                $errores[] = 'Este DPI pertenece a un cliente que fue dado de baja.';
            } else {
                $errores[] = 'Ya existe un cliente registrado con ese DPI.';
            }
        }
    }

    // Cliente nuevo sin coincidencia de DPI: revisar si hay bajas con el mismo nombre o teléfono
    if (!$errores && !$id && empty($_POST['confirmar_nuevo'])) {
        $stmt = $pdo->prepare("
            SELECT id, nombre, telefono, estado, fecha_baja FROM clientes
            WHERE estado = 'inactivo'
              AND (LOWER(TRIM(nombre)) = LOWER(?) OR (? <> '' AND telefono = ?))
            LIMIT 5");
        $stmt->execute([$cliente['nombre'], $cliente['telefono'], $cliente['telefono']]);
        $avisoBaja = $stmt->fetchAll();
        if ($avisoBaja) {
            $errores[] = 'Hay clientes dados de baja con el mismo nombre o teléfono. Revise antes de registrar.';
        }
    }

    if (!$errores) {
        $dpiDb  = $cliente['dpi'] !== '' ? $cliente['dpi'] : null;
        $ipDb   = $cliente['ip'] !== '' ? $cliente['ip'] : null;
        $tipoDb = $cliente['tipo_conexion'] !== '' ? $cliente['tipo_conexion'] : null;
        $torDb  = ($tipoDb === 'antena' && $cliente['torre'] !== '') ? $cliente['torre'] : null;
        $usrDb  = $cliente['usuario_servicio'] !== '' ? $cliente['usuario_servicio'] : null;
        $passDb = cifrar($cliente['password_servicio']);
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE clientes SET nombre=?, dpi=?, direccion=?, telefono=?, correo=?, estado=?,
                 tipo_conexion=?, torre=?, ip=?, usuario_servicio=?, password_servicio=? WHERE id=?'
            );
            $stmt->execute([
                $cliente['nombre'], $dpiDb, $cliente['direccion'],
                $cliente['telefono'], $cliente['correo'], $cliente['estado'],
                $tipoDb, $torDb, $ipDb, $usrDb, $passDb, $id,
            ]);
            setFlash('ok', 'Cliente actualizado correctamente.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO clientes (nombre, dpi, direccion, telefono, correo, estado, tipo_conexion, torre, ip, usuario_servicio, password_servicio)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $cliente['nombre'], $dpiDb, $cliente['direccion'],
                $cliente['telefono'], $cliente['correo'], $cliente['estado'],
                $tipoDb, $torDb, $ipDb, $usrDb, $passDb,
            ]);
            setFlash('ok', 'Cliente registrado correctamente.');
        }
        header('Location: clientes_listar.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4"><?= $id ? 'Editar cliente' : 'Nuevo cliente' ?></h3>

<?php if ($avisoBaja): ?>
    <div class="alert alert-warning" style="max-width: 600px;">
        <strong>Este cliente ya estuvo registrado.</strong>
        <?php foreach ($avisoBaja as $b): ?>
            <div class="d-flex justify-content-between align-items-center border-top mt-2 pt-2">
                <div>
                    <?= e($b['nombre']) ?><?= $b['telefono'] ? ' · Tel. ' . e($b['telefono']) : '' ?>
                    <div class="small text-muted">
                        Dado de baja<?= $b['fecha_baja'] ? ' el ' . e(date('d/m/Y', strtotime($b['fecha_baja']))) : '' ?>
                    </div>
                </div>
                <?php if (esAdmin()): ?>
                    <a href="clientes_eliminar.php?id=<?= (int) $b['id'] ?>&accion=reactivar" class="btn btn-sm btn-success"
                       onclick="return confirm('¿Reactivar a este cliente con todo su historial?');">Reactivar cliente</a>
                <?php else: ?>
                    <span class="small text-muted">Solicite al administrador la reactivación.</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach ($errores as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" class="bg-white p-4 rounded shadow-sm" style="max-width: 600px;">
    <div class="mb-3">
        <label class="form-label">Nombre completo</label>
        <input type="text" name="nombre" class="form-control" value="<?= e($cliente['nombre']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">DPI (13 dígitos) <span class="text-muted small">— opcional</span></label>
        <input type="text" name="dpi" maxlength="13" class="form-control" value="<?= e($cliente['dpi']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Dirección</label>
        <input type="text" name="direccion" class="form-control" value="<?= e($cliente['direccion']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="<?= e($cliente['telefono']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Correo electrónico <span class="text-muted small">— opcional</span></label>
        <input type="email" name="correo" class="form-control" value="<?= e($cliente['correo']) ?>">
    </div>
    <h6 class="mt-4 mb-3 text-secondary">Datos de conexión del servicio</h6>
    <div class="mb-3">
        <label class="form-label">Tipo de conexión</label>
        <select name="tipo_conexion" id="tipoConexion" class="form-select" onchange="mostrarTorre()">
            <option value="">-- Sin definir --</option>
            <?php foreach (TIPOS_CONEXION as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($cliente['tipo_conexion'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3" id="grupoTorre">
        <label class="form-label">Torre / punto de acceso <span class="text-muted small">— opcional</span></label>
        <input type="text" name="torre" class="form-control" placeholder="Ej. Torre Centro" value="<?= e($cliente['torre'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Dirección IP</label>
        <input type="text" name="ip" class="form-control" placeholder="Ej. 192.168.1.50" value="<?= e($cliente['ip'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input type="text" name="usuario_servicio" class="form-control" autocomplete="off" value="<?= e($cliente['usuario_servicio'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <div class="input-group">
            <input type="password" name="password_servicio" id="passServicio" class="form-control" autocomplete="new-password"
                   value="<?= e($cliente['password_servicio'] ?? '') ?>">
            <button type="button" class="btn btn-outline-secondary" onclick="togglePass('passServicio', this)">Ver</button>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
            <option value="activo" <?= $cliente['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
            <option value="suspendido" <?= $cliente['estado'] === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
        </select>
        <?php if ($cliente['estado'] === 'inactivo'): ?>
            <div class="form-text text-danger">Este cliente está dado de baja. Use el botón «Reactivar» para volver a activarlo.</div>
        <?php endif; ?>
    </div>
    <?php if ($avisoBaja && !$id && !in_array('Este DPI pertenece a un cliente que fue dado de baja.', $errores, true)): ?>
        <button type="submit" name="confirmar_nuevo" value="1" class="btn btn-outline-warning">Es otra persona, registrar como nuevo</button>
    <?php else: ?>
    <button type="submit" class="btn btn-isp">Guardar</button>
    <?php endif; ?>
    <a href="clientes_listar.php" class="btn btn-outline-secondary">Cancelar</a>
</form>

<script>
function mostrarTorre() {
    document.getElementById('grupoTorre').style.display =
        document.getElementById('tipoConexion').value === 'antena' ? '' : 'none';
}
mostrarTorre();

function togglePass(id, btn) {
    const i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
    btn.textContent = i.type === 'password' ? 'Ver' : 'Ocultar';
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
