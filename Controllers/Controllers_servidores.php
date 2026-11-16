<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ServicioModel.php';

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
