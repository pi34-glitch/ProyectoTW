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
$tituloPagina  = 'Historial y Trazabilidad | ClinicAdmin';
$cssExtra      = ['../assets/css/dashboard.css', '../assets/css/historial.css'];

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
    'estado' => (string) ($_GET['estado'] ?? ''),
    'fecha_desde' => (string) ($_GET['fecha_desde'] ?? ''),
    'fecha_hasta' => (string) ($_GET['fecha_hasta'] ?? ''),
    'buscar' => trim((string) ($_GET['buscar'] ?? '')),
];

$resultadoHistorial = obtener_historial_eventos($pdo, $filtros, 100);
$eventos = $resultadoHistorial['eventos'];
$resumenHistorial = $resultadoHistorial['resumen'];
$bdDisponible = $resultadoHistorial['bd_disponible'];

require __DIR__ . '/../Views/historial_lista.php';
