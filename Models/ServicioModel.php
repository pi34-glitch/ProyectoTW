<?php
declare(strict_types=1);

function servicios_demo_por_defecto(): array
{
    return [
        [
            'id' => 1,
            'nombre' => 'Servidor web (Apache)',
            'categoria' => 'Web',
            'host' => '127.0.0.1',
            'puerto' => 80,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
        ],
        [
            'id' => 2,
            'nombre' => 'Base de datos MySQL',
            'categoria' => 'Base de datos',
            'host' => '127.0.0.1',
            'puerto' => 3306,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
        ],
        [
            'id' => 3,
            'nombre' => 'Servidor SSH',
            'categoria' => 'Administración remota',
            'host' => '127.0.0.1',
            'puerto' => 22,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
        ],
        [
            'id' => 4,
            'nombre' => 'Servidor SMTP (Correo)',
            'categoria' => 'Correo',
            'host' => '127.0.0.1',
            'puerto' => 25,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
        ],
    ];
}

function obtener_servicios_activos(?PDO $pdo = null): array
{
    if ($pdo instanceof PDO) {
        try {
            $sql = 'SELECT id, nombre, categoria, host, puerto, protocolo, tiempo_espera_segundos
                    FROM servicios
                    WHERE activo = 1
                    ORDER BY id ASC';
            $stmt = $pdo->query($sql);
            $filas = $stmt !== false ? $stmt->fetchAll() : [];

            return [
                'servicios' => $filas,
                'modo_demo' => false,
            ];
        } catch (Throwable $e) {
        }
    }

    return [
        'servicios' => servicios_demo_por_defecto(),
        'modo_demo' => true,
    ];
}

function verificar_estado_servicio(string $host, int $puerto, int $timeout = 2): array
{
    $inicio = microtime(true);
    $errno = 0;
    $errstr = '';

    $socket = @fsockopen($host, $puerto, $errno, $errstr, max(1, $timeout));
    $duracionMs = (int) round((microtime(true) - $inicio) * 1000);

    if (is_resource($socket)) {
        fclose($socket);
        return [
            'estado' => 'activo',
            'tiempo_ms' => $duracionMs,
            'detalle' => 'Conexión establecida correctamente',
        ];
    }

    $mensajeError = trim($errstr) !== '' ? $errstr : 'Sin respuesta en el puerto';
    return [
        'estado' => 'caido',
        'tiempo_ms' => null,
        'detalle' => sprintf('Error %d: %s', $errno, $mensajeError),
    ];
}

function obtener_estado_servidores(?PDO $pdo = null): array
{
    $origen = obtener_servicios_activos($pdo);
    $items = [];
    $activos = 0;
    $caidos = 0;

    foreach ($origen['servicios'] as $servicio) {
        $host = (string) ($servicio['host'] ?? '127.0.0.1');
        $puerto = (int) ($servicio['puerto'] ?? 80);
        $timeout = min((int) ($servicio['tiempo_espera_segundos'] ?? 2), 3);

        $resultado = verificar_estado_servicio($host, $puerto, $timeout);

        if ($resultado['estado'] === 'activo') {
            $activos++;
        } else {
            $caidos++;
        }

        $items[] = [
            'id' => (int) ($servicio['id'] ?? 0),
            'nombre' => (string) ($servicio['nombre'] ?? 'Servicio'),
            'categoria' => (string) ($servicio['categoria'] ?? 'General'),
            'host' => $host,
            'puerto' => $puerto,
            'protocolo' => (string) ($servicio['protocolo'] ?? 'TCP'),
            'estado' => $resultado['estado'],
            'tiempo_ms' => $resultado['tiempo_ms'],
            'detalle' => $resultado['detalle'],
        ];
    }

    return [
        'items' => $items,
        'resumen' => [
            'total' => count($items),
            'activos' => $activos,
            'caidos' => $caidos,
        ],
        'modo_demo' => $origen['modo_demo'],
    ];
}
