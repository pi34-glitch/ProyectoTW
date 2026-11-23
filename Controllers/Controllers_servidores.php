<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ServicioModel.php';
require_once __DIR__ . '/../Config/auth.php';

if (!is_authenticated()) {
    header('Location: ../login.php');
    exit;
}

$csrfToken     = get_csrf_token();
$nombreUsuario = is_string($_SESSION['usuario_nombre'] ?? null) && trim($_SESSION['usuario_nombre']) !== ''
    ? trim($_SESSION['usuario_nombre'])
    : 'Bianca Morales';
$tituloPagina  = 'Estado de Servidores y Nodos | ClinicAdmin';
$cssExtra      = ['../assets/css/dashboard.css', '../assets/css/servidores.css'];
$paginaActiva  = 'servidores';
$baseUrl       = '../';

$pdo = null;
$rutaDatabase = __DIR__ . '/../Config/database.php';

if (is_file($rutaDatabase)) {
    require_once $rutaDatabase;
    if (function_exists('get_db_connection')) {
        try {
            $pdo = get_db_connection();
        } catch (Throwable $e) {
            $pdo = null;
        }
    }
}

// Endpoint AJAX controlado para Control Remoto SSH (VM 2) y Alerta de Prueba SMTP (Mailpit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_ajax'])) {
    header('Content-Type: application/json; charset=UTF-8');

    $tokenRecibido = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    if (!verify_csrf_token($tokenRecibido)) {
        http_response_code(403);
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'Token CSRF inválido o sesión expirada. Recarga la página.',
        ]);
        exit;
    }

    $accionAjax = (string) $_POST['accion_ajax'];

    if ($accionAjax === 'control_ssh') {
        $servicio = isset($_POST['servicio']) && is_string($_POST['servicio']) ? trim($_POST['servicio']) : '';
        $accion   = isset($_POST['accion']) && is_string($_POST['accion']) ? trim($_POST['accion']) : '';

        $resultado = ejecutar_accion_ssh_vm2($pdo, $servicio, $accion, $nombreUsuario);
        $resultado['evento_fecha'] = date('d/m/Y H:i:s');
        echo json_encode($resultado);
        exit;
    }

    if ($accionAjax === 'alerta_prueba') {
        $resultado = enviar_alerta_prueba_smtp($pdo, $nombreUsuario);
        $resultado['evento_fecha'] = date('d/m/Y H:i:s');
        echo json_encode($resultado);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Acción no reconocida.',
    ]);
    exit;
}

$datosServidores    = obtener_estado_servidores($pdo);
$servidores         = $datosServidores['items'];
$servidoresPorNodo  = $datosServidores['por_nodo'];
$resumenServidores  = $datosServidores['resumen'];
$modoDemoServidores = $datosServidores['modo_demo'];
$dnsActivo          = verificar_resolucion_dns_activa();
$telemetriaVm2      = obtener_telemetria_vm2();
$saludRayosxDb      = verificar_integridad_rayosx_db();
$bitacoraReciente   = obtener_bitacora_reciente($pdo, 12);
$fechaConsulta      = date('d/m/Y H:i:s');

require __DIR__ . '/../Views/servidores_estado.php';
