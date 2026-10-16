<?php
require __DIR__ . '/config.php';
requireRole('administrador');

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$usuario = ['nombre' => '', 'correo' => '', 'rol' => 'operador'];
$errores = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $existente = $stmt->fetch();
    if (!$existente) {
        setFlash('error', 'Usuario no encontrado.');
        header('Location: usuarios_listar.php');
        exit;
    }
    $usuario = $existente;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario['nombre'] = trim($_POST['nombre'] ?? '');
    $usuario['correo'] = trim($_POST['correo'] ?? '');
    $usuario['rol']    = $_POST['rol'] ?? 'operador';
    $password = $_POST['password'] ?? '';

    if ($usuario['nombre'] === '' || $usuario['correo'] === '') {
        $errores[] = 'Nombre y correo son obligatorios.';
    }
    if (!$id && $password === '') {
        $errores[] = 'La contraseña es obligatoria para un usuario nuevo.';
    }

    // Validación de correo duplicado (evita el Fatal error de MySQL)
    if (!$errores) {
        $stmtDup = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? AND id != ?');
        $stmtDup->execute([$usuario['correo'], $id ?: 0]);
        if ($stmtDup->fetch()) {
            $errores[] = 'Ya existe un usuario registrado con ese correo.';
        }
    }

    if (!$errores) {
        try {
            if ($id) {
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE usuarios SET nombre=?, correo=?, rol=?, password=? WHERE id=?');
                    $stmt->execute([$usuario['nombre'], $usuario['correo'], $usuario['rol'], password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE usuarios SET nombre=?, correo=?, rol=? WHERE id=?');
                    $stmt->execute([$usuario['nombre'], $usuario['correo'], $usuario['rol'], $id]);
                }
                setFlash('ok', 'Usuario actualizado correctamente.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)');
                $stmt->execute([$usuario['nombre'], $usuario['correo'], password_hash($password, PASSWORD_DEFAULT), $usuario['rol']]);
                setFlash('ok', 'Usuario creado correctamente.');
            }
            header('Location: usuarios_listar.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe un usuario registrado con ese correo.';
            } else {
                $errores[] = 'Ocurrió un error al guardar el usuario. Intente de nuevo.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4"><?= $id ? 'Editar usuario' : 'Nuevo usuario' ?></h3>

<?php if ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach ($errores as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" class="bg-white p-4 rounded shadow-sm" style="max-width: 500px;">
    <div class="mb-3">
        <label class="form-label">Nombre completo</label>
        <input type="text" name="nombre" class="form-control" value="<?= e($usuario['nombre']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="correo" class="form-control" value="<?= e($usuario['correo']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Rol</label>
        <select name="rol" class="form-select">
            <option value="operador" <?= $usuario['rol'] === 'operador' ? 'selected' : '' ?>>Operador</option>
            <option value="administrador" <?= $usuario['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Contraseña <?= $id ? '(dejar en blanco para no cambiar)' : '' ?></label>
        <input type="password" name="password" class="form-control">
    </div>
    <button type="submit" class="btn btn-isp">Guardar</button>
    <a href="usuarios_listar.php" class="btn btn-outline-secondary">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
