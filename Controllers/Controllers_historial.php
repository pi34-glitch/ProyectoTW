<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/HistorialModel.php';
require_once __DIR__ . '/../Config/auth.php';

if (!is_authenticated()) {
    header('Location: ../login.php');
    exit;
}

$csrfToken     = get_csrf_token();
$nombreUsuario = is_string($_SESSION['usuario_nombre'] ?? null) ? $_SESSION['usuario_nombre'] : 'Administrador TI';
$paginaActiva  = 'historial';
$baseUrl       = '../';
$tituloPagina  = 'Historial y Bitácora | ClinicAdmin';

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

$filtros = [
    'tipo' => (string) ($_GET['tipo'] ?? ''),
    'evento' => trim((string) ($_GET['evento'] ?? '')),
    'fecha_desde' => trim((string) ($_GET['fecha_desde'] ?? '')),
    'fecha_hasta' => trim((string) ($_GET['fecha_hasta'] ?? '')),
    'buscar' => trim((string) ($_GET['buscar'] ?? '')),
];

$resultado = obtener_historial_bitacora($pdo, $filtros, 100);
$registros = $resultado['registros'];
$resumenHistorial = $resultado['resumen'];
$eventosDisponibles = $resultado['eventos_disponibles'];
$bdDisponible = $resultado['bd_disponible'];

require __DIR__ . '/../Views/historial_lista.php';
