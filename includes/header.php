<?php
// Este archivo asume que config.php ya fue incluido antes.
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiche-Net - Control de Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if (isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg navbar-isp mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Quiché-Net - Control de Clientes</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="clientes_listar.php">Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="planes_listar.php">Planes</a></li>
                <li class="nav-item"><a class="nav-link" href="contratos_listar.php">Contratos</a></li>
                <li class="nav-item"><a class="nav-link" href="pagos_listar.php">Recibos</a></li>
                <li class="nav-item"><a class="nav-link" href="reportes.php">Reportes</a></li>
                <?php if (($_SESSION['usuario_rol'] ?? '') === 'administrador'): ?>
                    <li class="nav-item"><a class="nav-link" href="usuarios_listar.php">Usuarios</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text text-white me-3">
                <?= e($_SESSION['usuario_nombre'] ?? '') ?> (<?= e($_SESSION['usuario_rol'] ?? '') ?>)
            </span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">Cerrar sesión</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<div class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'error' ? 'danger' : 'success' ?>">
            <?= e($flash['mensaje']) ?>
        </div>
    <?php endif; ?>
</div>

<div class="container mb-5">
