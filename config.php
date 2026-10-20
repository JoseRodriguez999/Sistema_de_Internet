<?php
/**
 * Configuración general del sistema:
 * conexión a la base de datos, sesión y funciones de apoyo (auth, escape, estado de cuenta).
 */

// ---- Conexión a la base de datos ----
$DB_HOST = 'localhost';
$DB_NAME = 'isp_clientes';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . htmlspecialchars($e->getMessage()));
}

// ---- Sesión ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Helpers de autenticación ----
function isLoggedIn(): bool
{
    return isset($_SESSION['usuario_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Restringe el acceso según rol. Ejemplo: requireRole('administrador');
 * @param string|array $roles
 */
function requireRole($roles): void
{
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['usuario_rol'], $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/includes/header.php';
        echo '<div class="alert alert-danger">No tiene permisos para acceder a esta sección.</div>';
        require __DIR__ . '/includes/footer.php';
        exit;
    }
}

// ---- Helper de escape para salidas HTML ----
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// ---- Mensajes flash (éxito / error) entre redirecciones ----
function setFlash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Estado de cuenta de un contrato (RF-05): 'Al día', 'Pendiente' o 'Moroso'.
 * Se calcula mes a mes desde la fecha de inicio (ver estadoCuentaContrato).
 */
function calcularEstadoCuenta(PDO $pdo, int $contratoId): string
{
    return estadoCuentaContrato($pdo, $contratoId)['estado'];
}

// ============================================================
//  Datos de la empresa (encabezado del recibo)
// ============================================================
const EMPRESA_NOMBRE    = 'Quiché-Net';
const EMPRESA_DIRECCION = 'Santa Cruz del Quiché, Quiché';
const EMPRESA_TELEFONO  = '4281-7498, 7736-0199';
const EMPRESA_CORREO    = 'info@ispguate.com';

const MESES_ES = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

/** '2026-08-01' -> 'Agosto 2026' */
function mesTexto(string $fecha): string
{
    $t = strtotime($fecha);
    return MESES_ES[(int) date('n', $t)] . ' ' . date('Y', $t);
}

/**
 * Estado de cuenta detallado de un contrato, mes por mes,
 * desde el mes de inicio del contrato hasta el mes actual.
 */
function estadoCuentaContrato(PDO $pdo, int $contratoId): array
{
    $stmt = $pdo->prepare("
        SELECT co.*, p.nombre AS plan_nombre, p.precio, p.velocidad
        FROM contratos co JOIN planes p ON p.id = co.plan_id
        WHERE co.id = ?");
    $stmt->execute([$contratoId]);
    $contrato = $stmt->fetch();
    if (!$contrato) {
        return ['contrato' => null, 'meses' => [], 'saldo' => 0, 'vencidos' => 0, 'estado' => 'Sin contrato'];
    }

    // Pagos efectivos agrupados por periodo
    $stmt = $pdo->prepare("
        SELECT id, numero_recibo, monto, periodo
        FROM pagos
        WHERE contrato_id = ? AND estado = 'pagado' AND periodo IS NOT NULL");
    $stmt->execute([$contratoId]);
    $pagosPorMes = [];
    foreach ($stmt->fetchAll() as $pg) {
        $k = substr($pg['periodo'], 0, 7);
        $pagosPorMes[$k]['total']    = ($pagosPorMes[$k]['total'] ?? 0) + (float) $pg['monto'];
        $pagosPorMes[$k]['recibos'][] = $pg;
    }

    $precio    = (float) $contrato['precio'];
    $mesActual = date('Y-m');
    $cursor    = new DateTime(date('Y-m-01', strtotime($contrato['fecha_inicio'])));
    $fin       = new DateTime(!empty($contrato['fecha_fin']) ? date('Y-m-01', strtotime($contrato['fecha_fin'])) : date('Y-m-01'));

    $meses = [];
    $saldo = 0;
    $vencidos = 0;
    $pendienteActual = false;

    while ($cursor <= $fin) {
        $k       = $cursor->format('Y-m');
        $pagado  = $pagosPorMes[$k]['total'] ?? 0;
        $debe    = max($precio - $pagado, 0);

        if ($debe <= 0) {
            $estado = 'Pagado';
        } elseif ($k === $mesActual) {
            $estado = 'Pendiente';
            $pendienteActual = true;
        } else {
            $estado = 'Vencido';
            $vencidos++;
        }

        $saldo += $debe;
        $meses[] = [
            'periodo' => $cursor->format('Y-m-01'),
            'mes'     => mesTexto($cursor->format('Y-m-01')),
            'cargo'   => $precio,
            'pagado'  => $pagado,
            'saldo'   => $debe,
            'estado'  => $estado,
            'recibos' => $pagosPorMes[$k]['recibos'] ?? [],
        ];
        $cursor->modify('+1 month');
    }

    $estadoGeneral = $vencidos > 0 ? 'Moroso' : ($pendienteActual ? 'Pendiente' : 'Al día');

    return [
        'contrato' => $contrato,
        'meses'    => $meses,
        'saldo'    => $saldo,
        'vencidos' => $vencidos,
        'estado'   => $estadoGeneral,
    ];
}

/**
 * Estado general de un cliente según todos sus contratos activos.
 * Devuelve 'Al día', 'Pendiente', 'Moroso' o 'Sin contrato'.
 */
function estadoCuentaCliente(PDO $pdo, int $clienteId): array
{
    $stmt = $pdo->prepare("SELECT id FROM contratos WHERE cliente_id = ? AND estado = 'activo'");
    $stmt->execute([$clienteId]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$ids) {
        return ['estado' => 'Sin contrato', 'saldo' => 0];
    }
    $prioridad = ['Al día' => 0, 'Pendiente' => 1, 'Moroso' => 2];
    $peor = 'Al día';
    $saldo = 0;
    foreach ($ids as $cid) {
        $ec = estadoCuentaContrato($pdo, (int) $cid);
        $saldo += $ec['saldo'];
        if ($prioridad[$ec['estado']] > $prioridad[$peor]) {
            $peor = $ec['estado'];
        }
    }
    return ['estado' => $peor, 'saldo' => $saldo];
}

/** Color de badge Bootstrap según el estado de cuenta */
function colorEstadoCuenta(string $estado): string
{
    return match ($estado) {
        'Al día', 'Pagado' => 'success',
        'Pendiente'        => 'warning',
        'Moroso', 'Vencido'=> 'danger',
        default            => 'secondary',
    };
}

// ---- Monto en letras (para el recibo) ----
function numeroEnLetras(int $n): string
{
    if ($n === 0) return 'cero';

    $hasta29 = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
        'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis',
        'veintisiete', 'veintiocho', 'veintinueve'];
    $decenas  = [3 => 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    $centenas = [1 => 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
        'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    $menorMil = function (int $x) use ($hasta29, $decenas, $centenas): string {
        if ($x === 100) return 'cien';
        $partes = [];
        if ($x >= 100) $partes[] = $centenas[intdiv($x, 100)];
        $r = $x % 100;
        if ($r > 0) {
            $partes[] = $r < 30 ? $hasta29[$r]
                : $decenas[intdiv($r, 10)] . ($r % 10 ? ' y ' . $hasta29[$r % 10] : '');
        }
        return implode(' ', $partes);
    };

    $partes = [];
    $millones = intdiv($n, 1000000);
    $miles    = intdiv($n % 1000000, 1000);
    $resto    = $n % 1000;

    if ($millones === 1) $partes[] = 'un millón';
    elseif ($millones > 1) $partes[] = preg_replace('/uno$/', 'ún', $menorMil($millones)) . ' millones';

    if ($miles === 1) $partes[] = 'mil';
    elseif ($miles > 1) $partes[] = preg_replace('/uno$/', 'ún', $menorMil($miles)) . ' mil';

    if ($resto > 0) $partes[] = $menorMil($resto);

    return implode(' ', $partes);
}

function montoEnLetras(float $monto): string
{
    $entero    = (int) floor($monto);
    $centavos  = (int) round(($monto - $entero) * 100);
    $letras    = $entero === 1 ? 'un quetzal' : preg_replace('/uno$/', 'un', numeroEnLetras($entero)) . ' quetzales';
    return ucfirst($letras) . ' con ' . sprintf('%02d', $centavos) . '/100';
}

// ============================================================
//  Cifrado de la contraseña del servicio (IP / usuario del cliente)
//  Cambie esta clave UNA sola vez antes de registrar datos.
// ============================================================
const CLAVE_CIFRADO = 'Quiche-Net-2026-cambie-esta-clave';

function cifrar(?string $texto): ?string
{
    if ($texto === null || $texto === '') return null;
    $iv = random_bytes(16);
    $c = openssl_encrypt($texto, 'AES-256-CBC', hash('sha256', CLAVE_CIFRADO, true), OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $c);
}

function descifrar(?string $dato): string
{
    if ($dato === null || $dato === '') return '';
    $raw = base64_decode($dato, true);
    if ($raw === false || strlen($raw) < 17) return '';
    $r = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', hash('sha256', CLAVE_CIFRADO, true), OPENSSL_RAW_DATA, substr($raw, 0, 16));
    return $r === false ? '' : $r;
}

// ============================================================
//  Tipo de conexión del cliente
// ============================================================
const TIPOS_CONEXION = ['fibra' => 'Fibra óptica', 'antena' => 'Antena'];

/** Etiqueta de color para el tipo de conexión */
function badgeConexion(?string $tipo): string
{
    return match ($tipo) {
        'fibra'  => '<span class="badge bg-primary">Fibra</span>',
        'antena' => '<span class="badge" style="background:#c9862c">Antena</span>',
        default  => '<span class="badge bg-secondary">Sin definir</span>',
    };
}

// ============================================================
//  Roles
// ============================================================
function esAdmin(): bool
{
    // El superusuario tiene todas las funciones del administrador
    return in_array($_SESSION['usuario_rol'] ?? '', ['administrador', 'superusuario'], true);
}

function esSuperusuarioSesion(): bool
{
    return ($_SESSION['usuario_rol'] ?? '') === 'superusuario';
}

/** Devuelve true si el usuario indicado (por id) es superusuario (cuenta protegida). */
function usuarioEsSuperusuario(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetchColumn() === 'superusuario';
}