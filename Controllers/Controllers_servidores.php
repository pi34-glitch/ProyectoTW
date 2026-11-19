<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ServicioModel.php';
require_once __DIR__ . '/../Config/auth.php';
if (!is_authenticated()) {
    header('Location: ../login.php');
    exit;
}
$csrfToken     = get_csrf_token();
$nombreUsuario = is_string($_SESSION['usuario_nombre'] ?? null) ? $_SESSION['usuario_nombre'] : 'Administrador TI';
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

$datosServidores = obtener_estado_servidores($pdo);
$servidores = $datosServidores['items'];
$resumenServidores = $datosServidores['resumen'];
$modoDemoServidores = $datosServidores['modo_demo'];
$fechaConsulta = date('d/m/Y H:i:s');

require __DIR__ . '/../Views/servidores_estado.php';
