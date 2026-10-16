<?php
require __DIR__ . '/config.php';
requireRole('administrador');

$id = (int) ($_GET['id'] ?? 0);

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
