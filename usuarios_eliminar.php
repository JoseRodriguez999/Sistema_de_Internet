<?php
require __DIR__ . '/config.php';
requireRole(['administrador', 'superusuario']);

$id     = (int) ($_GET['id'] ?? 0);
$accion = $_GET['accion'] ?? 'estado';

// La cuenta de superusuario no puede ser desactivada ni eliminada por nadie
if (usuarioEsSuperusuario($pdo, $id)) {
    setFlash('error', 'La cuenta de superusuario no puede ser modificada ni eliminada.');
    header('Location: usuarios_listar.php');
    exit;
}

// ---- Eliminación definitiva de un usuario (solo superusuario) ----
if ($accion === 'eliminar') {
    if (!esSuperusuarioSesion()) {
        setFlash('error', 'Solo el superusuario puede eliminar usuarios de forma definitiva.');
        header('Location: usuarios_listar.php');
        exit;
    }
    if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
        setFlash('error', 'No puede eliminar su propia cuenta.');
        header('Location: usuarios_listar.php');
        exit;
    }
    $stmt = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $nombreUsuario = $stmt->fetchColumn();
    if ($nombreUsuario === false) {
        setFlash('error', 'Usuario no encontrado.');
    } else {
        $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        setFlash('ok', 'Usuario «' . $nombreUsuario . '» eliminado definitivamente.');
    }
    header('Location: usuarios_listar.php');
    exit;
}

$stmt = $pdo->prepare('SELECT estado FROM usuarios WHERE id = ?');
$stmt->execute([$id]);
$estadoActual = $stmt->fetchColumn();

if ($estadoActual) {
    $nuevoEstado = $estadoActual === 'activo' ? 'inactivo' : 'activo';
    $stmt = $pdo->prepare('UPDATE usuarios SET estado = ? WHERE id = ?');
    $stmt->execute([$nuevoEstado, $id]);
    setFlash('ok', 'Estado del usuario actualizado.');
} else {
    setFlash('error', 'Usuario no encontrado.');
}

header('Location: usuarios_listar.php');
exit;
