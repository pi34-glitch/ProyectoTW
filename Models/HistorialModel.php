<?php
declare(strict_types=1);

function obtener_eventos_distintos(?PDO $pdo = null): array
{
    if (!($pdo instanceof PDO)) {
        return [];
    }

    try {
        $stmt = $pdo->query('SELECT DISTINCT evento FROM bitacora_eventos ORDER BY evento ASC');
        if ($stmt === false) {
            return [];
        }
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $ev = trim((string) ($fila['evento'] ?? ''));
            if ($ev !== '') {
                $lista[] = $ev;
            }
        }
        return $lista;
    } catch (Throwable $e) {
        return [];
    }
}

function obtener_historial_bitacora(?PDO $pdo = null, array $filtrosEntrada = [], int $limite = 100): array
{
    $filtros = [
        'tipo' => in_array((string) ($filtrosEntrada['tipo'] ?? ''), ['ok', 'info', 'alerta'], true)
            ? (string) $filtrosEntrada['tipo']
            : '',
        'evento' => trim((string) ($filtrosEntrada['evento'] ?? '')),
        'fecha_desde' => trim((string) ($filtrosEntrada['fecha_desde'] ?? '')),
        'fecha_hasta' => trim((string) ($filtrosEntrada['fecha_hasta'] ?? '')),
        'buscar' => trim((string) ($filtrosEntrada['buscar'] ?? '')),
    ];

    $resumen = [
        'total' => 0,
        'ok' => 0,
        'info' => 0,
        'alerta' => 0,
    ];

    if (!($pdo instanceof PDO)) {
        return [
            'registros' => [],
            'resumen' => $resumen,
            'eventos_disponibles' => [],
            'bd_disponible' => false,
        ];
    }

    try {
        $params = [];
        $sql = 'SELECT id, fecha, evento, tipo, detalle FROM bitacora_eventos WHERE 1=1';

        if ($filtros['tipo'] !== '') {
            $sql .= ' AND tipo = :tipo';
            $params['tipo'] = $filtros['tipo'];
        }

        if ($filtros['evento'] !== '') {
            $sql .= ' AND evento = :evento';
            $params['evento'] = $filtros['evento'];
        }

        if ($filtros['fecha_desde'] !== '') {
            $sql .= ' AND fecha >= :fecha_desde';
            $params['fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }

        if ($filtros['fecha_hasta'] !== '') {
            $sql .= ' AND fecha <= :fecha_hasta';
            $params['fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        if ($filtros['buscar'] !== '') {
            $sql .= ' AND (detalle LIKE :buscar_det OR evento LIKE :buscar_ev)';
            $term = '%' . $filtros['buscar'] . '%';
            $params['buscar_det'] = $term;
            $params['buscar_ev'] = $term;
        }

        $sql .= ' ORDER BY fecha DESC, id DESC LIMIT ' . max(1, $limite);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll();

        foreach ($registros as $reg) {
            $tipo = (string) ($reg['tipo'] ?? 'info');
            if (isset($resumen[$tipo])) {
                $resumen[$tipo]++;
            }
        }
        $resumen['total'] = count($registros);

        return [
            'registros' => $registros,
            'resumen' => $resumen,
            'eventos_disponibles' => obtener_eventos_distintos($pdo),
            'bd_disponible' => true,
        ];
    } catch (Throwable $e) {
        return [
            'registros' => [],
            'resumen' => $resumen,
            'eventos_disponibles' => [],
            'bd_disponible' => false,
        ];
    }
}
