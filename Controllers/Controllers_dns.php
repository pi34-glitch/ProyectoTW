<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/auth.php';
require_once __DIR__ . '/../Models/ServicioModel.php';

if (!is_authenticated()) {
    header('Location: ../login.php');
    exit;
}

$csrfToken     = get_csrf_token();
$nombreUsuario = is_string($_SESSION['usuario_nombre'] ?? null) && trim($_SESSION['usuario_nombre']) !== ''
    ? trim($_SESSION['usuario_nombre'])
    : 'Bianca Morales';
$tituloPagina  = 'Registros DNS (clinicasanjose.lan) | ClinicAdmin';
$cssExtra      = ['../assets/css/dashboard.css', '../assets/css/servidores.css'];
$paginaActiva  = 'dns';
$baseUrl       = '../';

$pdo = null;
$errores = [];
$mensajeExito = null;
$editando = null;

try {
    $pdo = get_db_connection();
} catch (Throwable $e) {
    $errores[] = 'No se pudo conectar con MariaDB local.';
}

function validar_registro(array $in): array
{
    $e = [];
    $nombre = trim((string) ($in['nombre'] ?? ''));
    $tipo   = (string) ($in['tipo'] ?? '');
    $valor  = trim((string) ($in['valor'] ?? ''));
    $prio   = ($in['prioridad'] ?? '') === '' ? null : (int) $in['prioridad'];
    $ttl    = (int) ($in['ttl'] ?? 3600);
    $estado = (string) ($in['estado'] ?? 'activo');

    if (!in_array($tipo, ['A', 'CNAME', 'MX', 'PTR'], true)) {
        $e[] = 'Tipo de registro inválido (permitidos: A, CNAME, MX, PTR).';
    }
    if ($tipo === 'PTR') {
        if (!preg_match('/^([0-9]{1,3}(\.[0-9]{1,3}){0,3}(\.in-addr\.arpa\.?)?|[a-z0-9.-]+)$/i', $nombre)) {
            $e[] = 'Para un registro PTR use una IP o formato inverso (ej. 20.50.168.192.in-addr.arpa o 192.168.50.20).';
        }
    } elseif (!preg_match('/^(@|[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)$/i', $nombre)) {
        $e[] = 'Nombre inválido (use @ o un subdominio como admin, mail, rayosx o laboratorio).';
    }
    if ($tipo === 'A' && !filter_var($valor, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $e[] = 'Un registro A necesita una dirección IPv4 válida (ej. 192.168.50.10).';
    }
    if (in_array($tipo, ['CNAME', 'MX', 'PTR'], true) && substr($valor, -1) !== '.') {
        $e[] = 'Los registros CNAME, MX y PTR deben terminar con punto final (ej. rayosx.clinicasanjose.lan.).';
    }
    if ($tipo === 'MX' && $prio === null) {
        $e[] = 'Un registro MX requiere prioridad numérica (ej. 10).';
    }
    if ($tipo !== 'MX') {
        $prio = null;
    }
    if ($ttl < 60 || $ttl > 86400) {
        $e[] = 'El TTL debe estar entre 60 y 86400 segundos.';
    }
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        $e[] = 'Estado inválido.';
    }

    return [$e, compact('nombre', 'tipo', 'valor', 'prio', 'ttl', 'estado')];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    $tokenPost = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    // Permitir compatibilidad con formularios si envían token CSRF válido
    if ($tokenPost !== null && !verify_csrf_token($tokenPost)) {
        $errores[] = 'La sesión del formulario expiró. Intenta nuevamente.';
    } else {
        $accion = (string) ($_POST['accion'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        if ($accion === 'eliminar' && $id > 0) {
            try {
                $stInfo = $pdo->prepare('SELECT nombre, tipo, valor FROM registros_dns WHERE id = :id');
                $stInfo->execute(['id' => $id]);
                $regEliminado = $stInfo->fetch();

                $pdo->prepare('DELETE FROM registros_dns WHERE id = :id')->execute(['id' => $id]);

                if ($regEliminado) {
                    registrar_evento_bitacora(
                        $pdo,
                        'DNS',
                        sprintf(
                            "Registro '%s' (%s -> %s) eliminado por %s.",
                            (string) $regEliminado['nombre'],
                            (string) $regEliminado['tipo'],
                            (string) $regEliminado['valor'],
                            $nombreUsuario
                        ),
                        'info'
                    );
                }
                header('Location: Controllers_dns.php?ok=eliminado');
                exit;
            } catch (Throwable $ex) {
                $errores[] = 'No se pudo eliminar el registro DNS.';
            }
        }

        if (in_array($accion, ['crear', 'editar'], true)) {
            [$errores, $d] = validar_registro($_POST);
            if (!$errores) {
                try {
                    if ($accion === 'crear') {
                        $sql = 'INSERT INTO registros_dns (nombre, tipo, valor, prioridad, ttl, estado)
                                VALUES (:nombre, :tipo, :valor, :prio, :ttl, :estado)';
                        $pdo->prepare($sql)->execute($d);
                        registrar_evento_bitacora(
                            $pdo,
                            'DNS',
                            sprintf("Registro '%s' (%s -> %s) creado por %s.", $d['nombre'], $d['tipo'], $d['valor'], $nombreUsuario),
                            'info'
                        );
                    } else {
                        $sql = 'UPDATE registros_dns SET nombre=:nombre, tipo=:tipo, valor=:valor,
                                prioridad=:prio, ttl=:ttl, estado=:estado WHERE id=:id';
                        $pdo->prepare($sql)->execute($d + ['id' => $id]);
                        registrar_evento_bitacora(
                            $pdo,
                            'DNS',
                            sprintf("Registro '%s' (%s -> %s) actualizado por %s.", $d['nombre'], $d['tipo'], $d['valor'], $nombreUsuario),
                            'info'
                        );
                    }
                    header('Location: Controllers_dns.php?ok=guardado');
                    exit;
                } catch (PDOException $ex) {
                    $errores[] = ($ex->getCode() === '23000')
                        ? 'Ya existe un registro con ese nombre, tipo y valor.'
                        : 'Error al guardar el registro en la base de datos.';
                }
            }
            $editando = $_POST;
        }
    }
}

if (isset($_GET['ok'])) {
    $mensajeExito = $_GET['ok'] === 'eliminado'
        ? 'El registro DNS fue eliminado correctamente.'
        : 'El registro DNS fue guardado correctamente.';
}

if ($editando === null && isset($_GET['editar']) && $pdo instanceof PDO) {
    try {
        $st = $pdo->prepare('SELECT * FROM registros_dns WHERE id = :id');
        $st->execute(['id' => (int) $_GET['editar']]);
        $fila = $st->fetch();
        $editando = $fila !== false ? $fila : null;
    } catch (Throwable $e) {
    }
}

$filtro = (string) ($_GET['tipo'] ?? '');
$registros = [];

if ($pdo instanceof PDO) {
    try {
        if (in_array($filtro, ['A', 'CNAME', 'MX', 'PTR'], true)) {
            $st = $pdo->prepare('SELECT * FROM registros_dns WHERE tipo = :t ORDER BY tipo, nombre');
            $st->execute(['t' => $filtro]);
        } else {
            $filtro = '';
            $st = $pdo->query('SELECT * FROM registros_dns ORDER BY tipo, nombre');
        }
        $registros = $st !== false ? $st->fetchAll() : [];
    } catch (Throwable $e) {
        $errores[] = 'La tabla registros_dns aún no existe en la base de datos. Ejecuta database/schema.sql.';
    }
}

$dnsActivo = verificar_resolucion_dns_activa();
$resumenDns = obtener_resumen_dns($pdo);

require __DIR__ . '/../Views/dns_lista.php';
