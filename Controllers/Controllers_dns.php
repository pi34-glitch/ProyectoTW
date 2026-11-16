<?php

require_once __DIR__ . '/../Config/database.php';
/*require_once __DIR__ . '/../Config/auth.php'; 
start_secure_session();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}*/

$pdo = get_db_connection();
$errores = [];
$editando = null;

function validar_registro(array $in): array
{
    $e = [];
    $nombre = trim((string)($in['nombre'] ?? ''));
    $tipo   = (string)($in['tipo'] ?? '');
    $valor  = trim((string)($in['valor'] ?? ''));
    $prio   = ($in['prioridad'] ?? '') === '' ? null : (int)$in['prioridad'];
    $ttl    = (int)($in['ttl'] ?? 3600);
    $estado = (string)($in['estado'] ?? 'activo');

    if (!preg_match('/^(@|[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)$/i', $nombre)) {
        $e[] = 'Nombre inválido (use @ o un subdominio como rayosx).';
    }
    if (!in_array($tipo, ['A', 'CNAME', 'MX'], true)) {
        $e[] = 'Tipo inválido.';
    }
    if ($tipo === 'A' && !filter_var($valor, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $e[] = 'Un registro A necesita una IPv4 válida.';
    }
    if (in_array($tipo, ['CNAME', 'MX'], true) && substr($valor, -1) !== '.') {
        $e[] = 'CNAME y MX deben terminar con punto final (ej. mail.clinicasanjose.local.).';
    }
    if ($tipo === 'MX' && $prio === null) {
        $e[] = 'Un registro MX necesita prioridad.';
    }
    if ($tipo !== 'MX') {
        $prio = null;
    }
    if ($ttl < 60 || $ttl > 86400) {
        $e[] = 'TTL debe estar entre 60 y 86400.';
    }
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        $e[] = 'Estado inválido.';
    }
    return [$e, compact('nombre', 'tipo', 'valor', 'prio', 'ttl', 'estado')];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string)($_POST['accion'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($accion === 'eliminar' && $id > 0) {
        $pdo->prepare('DELETE FROM registros_dns WHERE id = :id')->execute(['id' => $id]);
       header('Location: Controllers_dns.php');
exit;
    }

    if (in_array($accion, ['crear', 'editar'], true)) {
        [$errores, $d] = validar_registro($_POST);
        if (!$errores) {
            try {
                if ($accion === 'crear') {
                    $sql = 'INSERT INTO registros_dns (nombre, tipo, valor, prioridad, ttl, estado)
                            VALUES (:nombre, :tipo, :valor, :prio, :ttl, :estado)';
                    $pdo->prepare($sql)->execute($d);
                } else {
                    $sql = 'UPDATE registros_dns SET nombre=:nombre, tipo=:tipo, valor=:valor,
                            prioridad=:prio, ttl=:ttl, estado=:estado WHERE id=:id';
                    $pdo->prepare($sql)->execute($d + ['id' => $id]);
                }
               header('Location: Controllers_dns.php');
                exit;
                
            } catch (PDOException $ex) {
                $errores[] = ($ex->getCode() === '23000')
                    ? 'Ya existe un registro con ese nombre, tipo y valor.'
                    : 'Error al guardar el registro.';
            }
        }
        $editando = $_POST;
    }
}

if ($editando === null && isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM registros_dns WHERE id = :id');
    $st->execute(['id' => (int)$_GET['editar']]);
    $fila = $st->fetch();
    $editando = $fila !== false ? $fila : null;
}

$filtro = (string)($_GET['tipo'] ?? '');
if (in_array($filtro, ['A', 'CNAME', 'MX'], true)) {
    $st = $pdo->prepare('SELECT * FROM registros_dns WHERE tipo = :t ORDER BY tipo, nombre');
    $st->execute(['t' => $filtro]);
} else {
    $filtro = '';
    $st = $pdo->query('SELECT * FROM registros_dns ORDER BY tipo, nombre');
}
$registros = $st->fetchAll();

require __DIR__ . '/../Views/dns_lista.php';