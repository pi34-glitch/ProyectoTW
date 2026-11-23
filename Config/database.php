<?php
declare(strict_types=1);

const DB_HOST    = '127.0.0.1';
const DB_PORT    = '3306';
const DB_NAME    = 'clinicadmin_db';
const DB_USER    = 'equipo';
const DB_PASS    = 'Clinic2026*';
const DB_CHARSET = 'utf8mb4';

// Configuración Nodo 2 (VM 2 - Base de Datos Médica Rayos X)
const RAYOSX_DB_HOST = 'rayosx.clinicasanjose.lan';
const RAYOSX_DB_IP   = '192.168.50.20';
const RAYOSX_DB_PORT = '3306';
const RAYOSX_DB_NAME = 'rayosx_db';
const RAYOSX_DB_USER = 'equipo';
const RAYOSX_DB_PASS = 'Clinic2026*';

/**
 * Obtiene la conexión PDO hacia MariaDB Local (VM 1).
 * Compatible tanto con el servidor Rocky Linux 9 (`equipo` / `Clinic2026*`)
 * como con el entorno local de QA (`root` / `admin` o sin clave).
 */
function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2,
    ];

    // Intentos de conexión priorizando credenciales de producción (Rocky Linux 9)
    // y respaldo transparente para QA local.
    $candidatos = [
        ['db' => DB_NAME,        'user' => DB_USER, 'pass' => DB_PASS],
        ['db' => 'clinicaadmin', 'user' => DB_USER, 'pass' => DB_PASS],
        ['db' => DB_NAME,        'user' => 'root',  'pass' => 'admin'],
        ['db' => DB_NAME,        'user' => 'root',  'pass' => ''],
        ['db' => 'clinicaadmin', 'user' => 'root',  'pass' => 'admin'],
        ['db' => 'clinicaadmin', 'user' => 'root',  'pass' => ''],
    ];

    $ultimaExcepcion = null;

    foreach ($candidatos as $cred) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            $cred['db'],
            DB_CHARSET
        );

        try {
            $pdo = new PDO($dsn, $cred['user'], $cred['pass'], $options);
            return $pdo;
        } catch (PDOException $e) {
            $ultimaExcepcion = $e;
        }
    }

    throw $ultimaExcepcion ?? new PDOException('No se pudo establecer conexión con MariaDB local.');
}

/**
 * Obtiene una conexión PDO con timeout estricto de 2 segundos hacia `rayosx_db` en VM 2.
 */
function get_rayosx_db_connection(string $hostOverride = ''): PDO
{
    $host = $hostOverride !== '' ? $hostOverride : RAYOSX_DB_HOST;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $host,
        RAYOSX_DB_PORT,
        RAYOSX_DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2,
    ];

    return new PDO($dsn, RAYOSX_DB_USER, RAYOSX_DB_PASS, $options);
}
