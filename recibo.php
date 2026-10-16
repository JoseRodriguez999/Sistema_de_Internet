<?php
require __DIR__ . '/config.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT pg.*, c.nombre AS cliente_nombre, c.dpi, c.direccion, c.telefono,
           p.nombre AS plan_nombre, p.velocidad, p.tipo_servicio,
           u.nombre AS usuario_nombre, co.id AS contrato_id, c.id AS cliente_id
    FROM pagos pg
    JOIN contratos co ON co.id = pg.contrato_id
    JOIN clientes c   ON c.id = co.cliente_id
    JOIN planes p     ON p.id = co.plan_id
    LEFT JOIN usuarios u ON u.id = pg.registrado_por
    WHERE pg.id = ? AND pg.estado = 'pagado'");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    setFlash('error', 'Recibo no encontrado.');
    header('Location: pagos_listar.php');
    exit;
}

$cuenta  = estadoCuentaContrato($pdo, (int) $r['contrato_id']);
$metodos = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito', 'tarjeta' => 'Tarjeta'];
$flash   = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo <?= e($r['numero_recibo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .recibo { max-width: 800px; margin: 20px auto; background: #fff; padding: 35px; border: 1px solid #ddd; }
        .recibo-head { border-bottom: 3px solid var(--color-primario); padding-bottom: 12px; margin-bottom: 20px; }
        .recibo-num { background: var(--color-primario); color: #fff; padding: 8px 14px; border-radius: 6px; text-align: center; }
        .recibo h6 { color: var(--color-primario); text-transform: uppercase; font-size: .8rem; letter-spacing: .5px; margin-top: 18px; }
        .monto-total { font-size: 1.6rem; font-weight: 700; color: var(--color-primario); }
        .firma { border-top: 1px solid #333; width: 220px; margin: 60px auto 0; text-align: center; padding-top: 4px; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .recibo { border: none; margin: 0; max-width: 100%; padding: 0; }
            .recibo-num, .table thead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: letter; margin: 15mm; }
        }
    </style>
</head>
<body>

<div class="no-print text-center mt-3">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'error' ? 'danger' : 'success' ?> d-inline-block"><?= e($flash['mensaje']) ?></div><br>
    <?php endif; ?>
    <button onclick="window.print()" id="btnImprimir" class="btn btn-isp">Imprimir recibo</button>
    <a href="pagos_listar.php" class="btn btn-outline-secondary">Volver a recibos</a>
    <a href="cliente_detalle.php?id=<?= (int) $r['cliente_id'] ?>" class="btn btn-outline-secondary">Ver estado de cuenta</a>
</div>

<div class="recibo">
    <div class="recibo-head d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0"><?= e(EMPRESA_NOMBRE) ?></h4>
            <div class="small text-muted">
                <?= e(EMPRESA_DIRECCION) ?><br>
                Tel. <?= e(EMPRESA_TELEFONO) ?>  <!--· <?= e(EMPRESA_CORREO) ?>-->
            </div>
        </div>
        <div class="recibo-num">
            <div class="small">RECIBO DE PAGO</div>
            <div class="fw-bold fs-5"><?= e($r['numero_recibo']) ?></div>
        </div>
    </div>

    <div class="d-flex justify-content-between small">
        <div>Fecha de pago: <strong><?= e(date('d/m/Y', strtotime($r['fecha_pago']))) ?></strong></div>
        <div>Emitido: <?= date('d/m/Y H:i') ?></div>
    </div>

    <h6>Datos del cliente</h6>
    <table class="table table-sm table-borderless mb-0">
        <tr><td width="25%">Nombre:</td><td><strong><?= e($r['cliente_nombre']) ?></strong></td></tr>
        <tr><td>DPI:</td><td><?= e($r['dpi'] ?: '—') ?></td></tr>
        <tr><td>Dirección:</td><td><?= e($r['direccion'] ?: '—') ?></td></tr>
        <tr><td>Teléfono:</td><td><?= e($r['telefono'] ?: '—') ?></td></tr>
    </table>

    <h6>Detalle del pago</h6>
    <table class="table table-bordered">
        <thead><tr><th>Descripción</th><th>Periodo</th><th>Método</th><th class="text-end">Monto</th></tr></thead>
        <tbody>
            <tr>
                <td>Servicio de internet — <?= e($r['plan_nombre']) ?> (<?= e($r['velocidad']) ?>, <?= e($r['tipo_servicio']) ?>)</td>
                <td><?= e($r['mes_correspondiente']) ?></td>
                <td><?= e($metodos[$r['metodo_pago']] ?? $r['metodo_pago']) ?></td>
                <td class="text-end">Q <?= number_format($r['monto'], 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-end">
        <div class="small">
            <em><?= e(montoEnLetras((float) $r['monto'])) ?></em><br>
            Saldo pendiente del contrato: <strong>Q <?= number_format($cuenta['saldo'], 2) ?></strong>
        </div>
        <div class="text-end">
            <div class="small text-muted">Total pagado</div>
            <div class="monto-total">Q <?= number_format($r['monto'], 2) ?></div>
        </div>
    </div>

    <div class="firma small">Firma y sello</div>

    <div class="text-center small text-muted mt-4">
        Registrado por: <?= e($r['usuario_nombre'] ?: '—') ?> · Gracias por su pago.
    </div>
</div>

</body>
</html>
