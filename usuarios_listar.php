<?php
require __DIR__ . '/config.php';
requireRole(['administrador', 'superusuario']);

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Usuarios y Roles</h3>
    <a href="usuarios_form.php" class="btn btn-isp">+ Nuevo usuario</a>
</div>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= e($u['nombre']) ?></td>
            <td><?= e($u['correo']) ?></td>
            <td><?= e(ucfirst($u['rol'])) ?></td>
            <td>
                <span class="badge bg-<?= $u['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                    <?= e(ucfirst($u['estado'])) ?>
                </span>
            </td>
            <td>
                <?php if ($u['rol'] === 'superusuario'): ?>
                    <span class="text-muted small">Cuenta protegida</span>
                <?php else: ?>
                    <a href="usuarios_form.php?id=<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                    <a href="usuarios_eliminar.php?id=<?= (int) $u['id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('¿Desactivar este usuario?');">
                       <?= $u['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?>
                    </a>
                    <?php if (esSuperusuarioSesion()): ?>
                        <a href="usuarios_eliminar.php?id=<?= (int) $u['id'] ?>&accion=eliminar"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('ELIMINAR DEFINITIVAMENTE a este usuario. Esta acción no se puede deshacer. ¿Continuar?');">Eliminar</a>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$usuarios): ?>
        <tr><td colspan="5" class="text-center text-muted">No hay usuarios registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
