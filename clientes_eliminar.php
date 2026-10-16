<?php
/**
 * Baja y reactivación de clientes (no se eliminan registros, se conserva el historial).
 *   ?id=X                     -> dar de baja (administrador y operador)
 *   ?id=X&accion=reactivar    -> reactivar (solo administrador)
 */
require __DIR__ . '/config.php';
requireLogin();

$id     = (int) ($_GET['id'] ?? 0);
$accion = $_GET['accion'] ?? 'baja';

$stmt = $pdo->prepare('SELECT id, nombre, estado FROM clientes WHERE id = ?');
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    setFlash('error', 'Cliente no encontrado.');
    header('Location: clientes_listar.php');
    exit;
}

if ($accion === 'reactivar') {
    if (!esAdmin()) {
        setFlash('error', 'Solo el administrador puede reactivar clientes.');
        header('Location: clientes_listar.php');
        exit;
    }
    if ($cliente['estado'] !== 'inactivo') {
        setFlash('error', 'El cliente ya está activo.');
        header('Location: cliente_detalle.php?id=' . $id);
        exit;
    }
    $pdo->prepare("UPDATE clientes SET estado = 'activo', fecha_baja = NULL WHERE id = ?")->execute([$id]);
    setFlash('ok', 'Cliente «' . $cliente['nombre'] . '» reactivado. Su historial se conservó; asígnele un contrato para volver a facturar.');
    header('Location: cliente_detalle.php?id=' . $id);
    exit;
}

// ---- Dar de baja ----
if ($cliente['estado'] === 'inactivo') {
    setFlash('error', 'El cliente ya estaba dado de baja.');
    header('Location: clientes_listar.php');
    exit;
}

$pdo->beginTransaction();
$pdo->prepare("UPDATE clientes SET estado = 'inactivo', fecha_baja = NOW() WHERE id = ?")->execute([$id]);
// Se cierran sus contratos activos para que no se generen cobros mientras está de baja
$pdo->prepare("UPDATE contratos SET estado = 'cancelado', fecha_fin = CURDATE() WHERE cliente_id = ? AND estado = 'activo'")
    ->execute([$id]);
$pdo->commit();

setFlash('ok', 'Cliente «' . $cliente['nombre'] . '» dado de baja. Su historial de pagos se conserva.');
header('Location: clientes_listar.php');
exit;
