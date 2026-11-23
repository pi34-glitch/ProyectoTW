<?php
declare(strict_types=1);

function registrar_lote_monitoreos(?PDO $pdo, array $items): void
{
    if (!($pdo instanceof PDO) || $items === []) {
        return;
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO monitoreos (servicio_id, estado, tiempo_respuesta_ms, detalle)
             VALUES (:servicio_id, :estado, :tiempo_ms, :detalle)'
        );

        foreach ($items as $item) {
            $servicioId = (int) ($item['id'] ?? 0);
            if ($servicioId <= 0) {
                continue;
            }

            $estado = (string) ($item['estado'] ?? 'error');
            if (!in_array($estado, ['activo', 'caido', 'error'], true)) {
                $estado = 'error';
            }

            $stmt->execute([
                'servicio_id' => $servicioId,
                'estado' => $estado,
                'tiempo_ms' => $item['tiempo_ms'] !== null ? (int) $item['tiempo_ms'] : null,
                'detalle' => substr((string) ($item['detalle'] ?? ''), 0, 500),
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

function construir_filtro_fecha(string $columna, array $filtros, array &$params): string
{
    $condiciones = [];

    if ($filtros['fecha_desde'] !== '') {
        $condiciones[] = "$columna >= :fecha_desde";
        $params['fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
    }

    if ($filtros['fecha_hasta'] !== '') {
        $condiciones[] = "$columna <= :fecha_hasta";
        $params['fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
    }

    return $condiciones !== [] ? ' AND ' . implode(' AND ', $condiciones) : '';
}

function consultar_monitoreos(PDO $pdo, array $filtros, int $limite): array
{
    $params = [];
    $sql = 'SELECT m.id, m.verificado_en AS fecha, s.nombre AS origen,
                   CONCAT(s.host, ":", s.puerto) AS destino,
                   m.estado, m.tiempo_respuesta_ms, m.detalle
            FROM monitoreos m
            INNER JOIN servicios s ON s.id = m.servicio_id
            WHERE 1=1';

    $sql .= construir_filtro_fecha('m.verificado_en', $filtros, $params);

    if ($filtros['estado'] !== '') {
        $sql .= ' AND m.estado = :estado';
        $params['estado'] = $filtros['estado'];
    }

    if ($filtros['buscar'] !== '') {
        $sql .= ' AND (s.nombre LIKE :buscar OR m.detalle LIKE :buscar_det OR s.host LIKE :buscar_host)';
        $term = '%' . $filtros['buscar'] . '%';
        $params['buscar'] = $term;
        $params['buscar_det'] = $term;
        $params['buscar_host'] = $term;
    }

    $sql .= ' ORDER BY m.verificado_en DESC, m.id DESC LIMIT ' . max(1, $limite);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $eventos = [];
    foreach ($filas as $f) {
        $latencia = $f['tiempo_respuesta_ms'] !== null ? ' (' . (int) $f['tiempo_respuesta_ms'] . ' ms)' : '';
        $eventos[] = [
            'tipo' => 'monitoreo',
            'tipo_label' => 'Monitoreo',
            'fecha' => (string) $f['fecha'],
            'origen' => (string) $f['origen'],
            'destino' => (string) $f['destino'],
            'detalle' => (string) ($f['detalle'] ?? '') . $latencia,
            'estado' => (string) $f['estado'],
        ];
    }

    return $eventos;
}

function consultar_incidentes(PDO $pdo, array $filtros, int $limite): array
{
    $params = [];
    $sql = 'SELECT i.id, i.detectado_en AS fecha,
                   COALESCE(s.nombre, "Sistema general") AS origen,
                   CONCAT("Prioridad: ", i.prioridad) AS destino,
                   i.estado, i.titulo, i.descripcion
            FROM incidentes i
            LEFT JOIN servicios s ON s.id = i.servicio_id
            WHERE 1=1';

    $sql .= construir_filtro_fecha('i.detectado_en', $filtros, $params);

    if ($filtros['estado'] !== '') {
        $sql .= ' AND i.estado = :estado';
        $params['estado'] = $filtros['estado'];
    }

    if ($filtros['buscar'] !== '') {
        $sql .= ' AND (i.titulo LIKE :buscar OR i.descripcion LIKE :buscar_desc)';
        $term = '%' . $filtros['buscar'] . '%';
        $params['buscar'] = $term;
        $params['buscar_desc'] = $term;
    }

    $sql .= ' ORDER BY i.detectado_en DESC, i.id DESC LIMIT ' . max(1, $limite);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $eventos = [];
    foreach ($filas as $f) {
        $desc = trim((string) ($f['descripcion'] ?? ''));
        $detalle = $desc !== '' ? $f['titulo'] . ' — ' . $desc : (string) $f['titulo'];
        $eventos[] = [
            'tipo' => 'incidente',
            'tipo_label' => 'Incidente',
            'fecha' => (string) $f['fecha'],
            'origen' => (string) $f['origen'],
            'destino' => (string) $f['destino'],
            'detalle' => $detalle,
            'estado' => (string) $f['estado'],
        ];
    }

    return $eventos;
}

function consultar_alertas(PDO $pdo, array $filtros, int $limite): array
{
    $params = [];
    $sql = 'SELECT a.id, a.creado_en AS fecha,
                   COALESCE(s.nombre, "Alerta") AS origen,
                   a.destinatario AS destino,
                   a.estado_envio AS estado, a.asunto, a.error_envio
            FROM alertas a
            LEFT JOIN servicios s ON s.id = a.servicio_id
            WHERE 1=1';

    $sql .= construir_filtro_fecha('a.creado_en', $filtros, $params);

    if ($filtros['estado'] !== '') {
        $sql .= ' AND a.estado_envio = :estado';
        $params['estado'] = $filtros['estado'];
    }

    if ($filtros['buscar'] !== '') {
        $sql .= ' AND (a.asunto LIKE :buscar OR a.destinatario LIKE :buscar_dest)';
        $term = '%' . $filtros['buscar'] . '%';
        $params['buscar'] = $term;
        $params['buscar_dest'] = $term;
    }

    $sql .= ' ORDER BY a.creado_en DESC, a.id DESC LIMIT ' . max(1, $limite);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $eventos = [];
    foreach ($filas as $f) {
        $error = trim((string) ($f['error_envio'] ?? ''));
        $detalle = $error !== '' ? $f['asunto'] . ' (' . $error . ')' : (string) $f['asunto'];
        $eventos[] = [
            'tipo' => 'alerta',
            'tipo_label' => 'Alerta',
            'fecha' => (string) $f['fecha'],
            'origen' => (string) $f['origen'],
            'destino' => (string) $f['destino'],
            'detalle' => $detalle,
            'estado' => (string) $f['estado'],
        ];
    }

    return $eventos;
}

function consultar_comandos(PDO $pdo, array $filtros, int $limite): array
{
    $params = [];
    $sql = 'SELECT c.id, c.ejecutado_en AS fecha,
                   COALESCE(u.username, "Sistema") AS origen,
                   c.host_destino AS destino,
                   c.resultado AS estado, c.accion, c.comando_ejecutado
            FROM comandos_ejecutados c
            LEFT JOIN usuarios u ON u.id = c.usuario_id
            WHERE 1=1';

    $sql .= construir_filtro_fecha('c.ejecutado_en', $filtros, $params);

    if ($filtros['estado'] !== '') {
        $sql .= ' AND c.resultado = :estado';
        $params['estado'] = $filtros['estado'];
    }

    if ($filtros['buscar'] !== '') {
        $sql .= ' AND (c.accion LIKE :buscar OR c.comando_ejecutado LIKE :buscar_cmd OR c.host_destino LIKE :buscar_host)';
        $term = '%' . $filtros['buscar'] . '%';
        $params['buscar'] = $term;
        $params['buscar_cmd'] = $term;
        $params['buscar_host'] = $term;
    }

    $sql .= ' ORDER BY c.ejecutado_en DESC, c.id DESC LIMIT ' . max(1, $limite);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $eventos = [];
    foreach ($filas as $f) {
        $eventos[] = [
            'tipo' => 'comando',
            'tipo_label' => 'Comando SSH',
            'fecha' => (string) $f['fecha'],
            'origen' => (string) $f['origen'],
            'destino' => (string) $f['destino'],
            'detalle' => $f['accion'] . ': ' . $f['comando_ejecutado'],
            'estado' => (string) $f['estado'],
        ];
    }

    return $eventos;
}

function obtener_historial_eventos(?PDO $pdo, array $filtrosEntrada = [], int $limite = 100): array
{
    $filtros = [
        'tipo' => (string) ($filtrosEntrada['tipo'] ?? ''),
        'estado' => (string) ($filtrosEntrada['estado'] ?? ''),
        'fecha_desde' => (string) ($filtrosEntrada['fecha_desde'] ?? ''),
        'fecha_hasta' => (string) ($filtrosEntrada['fecha_hasta'] ?? ''),
        'buscar' => trim((string) ($filtrosEntrada['buscar'] ?? '')),
    ];

    $resumen = [
        'total' => 0,
        'monitoreo' => 0,
        'incidente' => 0,
        'alerta' => 0,
        'comando' => 0,
    ];

    if (!($pdo instanceof PDO)) {
        return [
            'eventos' => [],
            'resumen' => $resumen,
            'bd_disponible' => false,
        ];
    }

    $fuentes = [
        'monitoreo' => 'consultar_monitoreos',
        'incidente' => 'consultar_incidentes',
        'alerta' => 'consultar_alertas',
        'comando' => 'consultar_comandos',
    ];

    $eventos = [];
    foreach ($fuentes as $tipo => $funcion) {
        if ($filtros['tipo'] !== '' && $filtros['tipo'] !== $tipo) {
            continue;
        }
        try {
            $parcial = $funcion($pdo, $filtros, $limite);
            $resumen[$tipo] = count($parcial);
            $eventos = array_merge($eventos, $parcial);
        } catch (Throwable $e) {
        }
    }

    usort($eventos, static function (array $a, array $b): int {
        return strcmp($b['fecha'], $a['fecha']);
    });

    if (count($eventos) > $limite) {
        $eventos = array_slice($eventos, 0, $limite);
    }

    $resumen['total'] = count($eventos);

    return [
        'eventos' => $eventos,
        'resumen' => $resumen,
        'bd_disponible' => true,
    ];
}
