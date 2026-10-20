<?php
require __DIR__ . '/config.php';

// Si ya inició sesión, lo mandamos directo al dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errores = [];
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    // CP-LOGIN-003: Validación de campos vacíos
    if ($correo === '' || $password === '') {
        $errores[] = 'Debe llenar los campos requeridos.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = ? AND estado = "activo"');
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        // CP-LOGIN-001 / CP-LOGIN-002: validar credenciales
        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol']    = $usuario['rol'];
            header('Location: index.php');
            exit;
        } else {
            $errores[] = 'Credenciales inválidas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Sistema ISP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-box">
    <h3 class="mb-4 text-center">Sistema ISP</h3>
    <p class="text-center text-muted">Control y Gestión de Clientes</p>

    <?php if ($errores): ?>
        <div class="alert alert-danger">
            <?php foreach ($errores as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="correo" class="form-control <?= (in_array('Debe llenar los campos requeridos.', $errores) && $correo === '') ? 'is-invalid-field' : '' ?>" value="<?= e($correo) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control <?= (in_array('Debe llenar los campos requeridos.', $errores)) ? 'is-invalid-field' : '' ?>">
        </div>
        <button type="submit" class="btn btn-isp w-100">Entrar</button>
    </form>

   
</div>
</body>
</html>
