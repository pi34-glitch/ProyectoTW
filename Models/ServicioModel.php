<?php
declare(strict_types=1);

/**
 * Catálogo base de los 7 servicios multi-nodo de la infraestructura ClinicAdmin
 * (Nodo 1: admin.clinicasanjose.lan [192.168.50.10] / Nodo 2: rayosx.clinicasanjose.lan [192.168.50.20])
 */
function servicios_demo_por_defecto(): array
{
    return [
        // Nodo 1 — Servidor Central (VM 1 / admin.clinicasanjose.lan - 192.168.50.10)
        [
            'id' => 1,
            'nombre' => 'Servidor Web Intranet (Apache)',
            'categoria' => 'Web',
            'nodo' => 'Nodo Central (admin.clinicasanjose.lan)',
            'host' => '127.0.0.1',
            'puerto' => 8080,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
            'servicio_ssh' => null,
            'ultimo_estado' => 'desconocido',
        ],
        [
            'id' => 2,
            'nombre' => 'Base de Datos Central (MariaDB)',
            'categoria' => 'Base de datos',
            'nodo' => 'Nodo Central (admin.clinicasanjose.lan)',
            'host' => '127.0.0.1',
            'puerto' => 3306,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
            'servicio_ssh' => null,
            'ultimo_estado' => 'desconocido',
        ],
        [
            'id' => 3,
            'nombre' => 'Servidor SMTP Mailpit',
            'categoria' => 'Correo',
            'nodo' => 'Nodo Central (admin.clinicasanjose.lan)',
            'host' => '127.0.0.1',
            'puerto' => 25,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
            'servicio_ssh' => null,
            'ultimo_estado' => 'desconocido',
        ],
        [
            'id' => 4,
            'nombre' => 'Servidor DNS Technitium',
            'categoria' => 'DNS / Red',
            'nodo' => 'Nodo Central (admin.clinicasanjose.lan)',
            'host' => '127.0.0.1',
            'puerto' => 53,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
            'servicio_ssh' => null,
            'ultimo_estado' => 'desconocido',
        ],
        // Nodo 2 — Infraestructura Médica / Rayos X (VM 2 / rayosx.clinicasanjose.lan - 192.168.50.20)
        [
            'id' => 5,
            'nombre' => 'Portal Web Rayos X (Apache)',
            'categoria' => 'Web Médica',
            'nodo' => 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)',
            'host' => 'rayosx.clinicasanjose.lan',
            'puerto' => 8080,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
            'servicio_ssh' => 'httpd',
            'ultimo_estado' => 'desconocido',
        ],
        [
            'id' => 6,
            'nombre' => 'Base de Datos Médica (rayosx_db)',
            'categoria' => 'Base de datos Médica',
            'nodo' => 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)',
            'host' => 'rayosx.clinicasanjose.lan',
            'puerto' => 3306,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
            'servicio_ssh' => 'mariadb',
            'ultimo_estado' => 'desconocido',
        ],
        [
            'id' => 7,
            'nombre' => 'Demonio de Gestión SSH (VM 2)',
            'categoria' => 'Administración remota',
            'nodo' => 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)',
            'host' => 'rayosx.clinicasanjose.lan',
            'puerto' => 22,
            'protocolo' => 'TCP',
            'tiempo_espera_segundos' => 2,
            'servicio_ssh' => null,
            'ultimo_estado' => 'desconocido',
        ],
    ];
}

/**
 * Resuelve un nombre de host a IPv4 cacheando el resultado durante la petición
 * para evitar bloqueos repetidos si el servidor DNS o el nodo remoto no responde.
 */
function resolver_host_rapido(string $host): array
{
    static $cache = [];

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return ['resuelto' => true, 'ip' => $host];
    }

    if (isset($cache[$host])) {
        return $cache[$host];
    }

    $ip = @gethostbyname($host);
    $resuelto = ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP) !== false);

    $cache[$host] = [
        'resuelto' => $resuelto,
        'ip'       => $resuelto ? $ip : $host,
    ];

    return $cache[$host];
}

/**
 * Obtiene los servicios activos desde MariaDB (o usa el catálogo multi-nodo por defecto).
 * Si la tabla `servicios` existe pero aún tenía 4 servicios o puerto 80 antiguo,
 * garantiza que estén presentes los 7 servicios reales de la arquitectura.
 */
function obtener_servicios_activos(?PDO $pdo = null): array
{
    if ($pdo instanceof PDO) {
        try {
            $sql = 'SELECT id, nombre, categoria, nodo, host, puerto, protocolo,
                           tiempo_espera_segundos, servicio_ssh, ultimo_estado
                    FROM servicios
                    WHERE activo = 1
                    ORDER BY id ASC';
            $stmt = $pdo->query($sql);
            $filas = $stmt !== false ? $stmt->fetchAll() : [];

            if (count($filas) > 0) {
                return [
                    'servicios' => $filas,
                    'modo_demo' => false,
                ];
            }
        } catch (Throwable $e) {
            // Si la tabla tiene esquema previo sin las columnas nuevas, intentamos consulta básica
            try {
                $sqlBasico = 'SELECT id, nombre, categoria, host, puerto, protocolo, tiempo_espera_segundos
                              FROM servicios
                              WHERE activo = 1
                              ORDER BY id ASC';
                $stmt = $pdo->query($sqlBasico);
                $filas = $stmt !== false ? $stmt->fetchAll() : [];
                if (count($filas) > 0) {
                    foreach ($filas as &$f) {
                        $esRemoto = stripos((string) ($f['host'] ?? ''), 'rayosx') !== false
                            || ($f['host'] ?? '') === '192.168.50.20';
                        $f['nodo'] = $esRemoto
                            ? 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)'
                            : 'Nodo Central (admin.clinicasanjose.lan)';
                        $f['servicio_ssh'] = null;
                        if ($esRemoto && (int) ($f['puerto'] ?? 0) === 8080) {
                            $f['servicio_ssh'] = 'httpd';
                        } elseif ($esRemoto && (int) ($f['puerto'] ?? 0) === 3306) {
                            $f['servicio_ssh'] = 'mariadb';
                        }
                        $f['ultimo_estado'] = 'desconocido';
                    }
                    unset($f);
                    return [
                        'servicios' => $filas,
                        'modo_demo' => false,
                    ];
                }
            } catch (Throwable $inner) {
            }
        }
    }

    return [
        'servicios' => servicios_demo_por_defecto(),
        'modo_demo' => true,
    ];
}

/**
 * Verifica el estado de un servicio vía socket TCP con timeout corto (1.5s - 2s máx).
 */
function verificar_estado_servicio(string $host, int $puerto, float $timeout = 1.5): array
{
    $timeoutSeguro = max(0.5, min($timeout, 2.0));
    $infoHost = resolver_host_rapido($host);

    if (!$infoHost['resuelto']) {
        return [
            'estado'    => 'caido',
            'codigo'    => 'OFFLINE',
            'tiempo_ms' => null,
            'detalle'   => sprintf('Inalcanzable: El dominio %s no pudo ser resuelto por DNS', $host),
        ];
    }

    $inicio = microtime(true);
    $errno = 0;
    $errstr = '';

    $socket = @fsockopen($infoHost['ip'], $puerto, $errno, $errstr, $timeoutSeguro);
    $duracionMs = (int) max(1, round((microtime(true) - $inicio) * 1000));

    if (is_resource($socket)) {
        fclose($socket);
        return [
            'estado'    => 'activo',
            'codigo'    => 'ONLINE',
            'tiempo_ms' => $duracionMs,
            'detalle'   => sprintf('ONLINE · Conexión TCP establecida (%s:%d en %d ms)', $infoHost['ip'], $puerto, $duracionMs),
        ];
    }

    $mensajeError = trim($errstr) !== '' ? $errstr : 'Inalcanzable / Sin respuesta en el puerto';
    return [
        'estado'    => 'caido',
        'codigo'    => 'OFFLINE',
        'tiempo_ms' => null,
        'detalle'   => sprintf('OFFLINE · Inalcanzable (%s)', $mensajeError),
    ];
}

/**
 * Registra un evento en la tabla `bitacora_eventos` (y en sesión como respaldo).
 */
function registrar_evento_bitacora(?PDO $pdo, string $evento, string $detalle, string $tipo = 'info'): void
{
    $tipoValido = in_array($tipo, ['ok', 'info', 'alerta'], true) ? $tipo : 'info';

    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO bitacora_eventos (fecha, evento, tipo, detalle)
                 VALUES (NOW(), :evento, :tipo, :detalle)'
            );
            $stmt->execute([
                'evento'  => mb_substr($evento, 0, 60),
                'tipo'    => $tipoValido,
                'detalle' => mb_substr($detalle, 0, 500),
            ]);
            return;
        } catch (Throwable $e) {
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['bitacora_fallback'] = $_SESSION['bitacora_fallback'] ?? [];
        array_unshift($_SESSION['bitacora_fallback'], [
            'id'      => count($_SESSION['bitacora_fallback']) + 1,
            'fecha'   => date('Y-m-d H:i:s'),
            'evento'  => $evento,
            'tipo'    => $tipoValido,
            'detalle' => $detalle,
        ]);
        $_SESSION['bitacora_fallback'] = array_slice($_SESSION['bitacora_fallback'], 0, 25);
    }
}

/**
 * Obtiene los eventos recientes de la bitácora del sistema.
 */
function obtener_bitacora_reciente(?PDO $pdo = null, int $limite = 10): array
{
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, fecha, evento, tipo, detalle
                 FROM bitacora_eventos
                 ORDER BY fecha DESC, id DESC
                 LIMIT :lim'
            );
            $stmt->bindValue(':lim', max(1, $limite), PDO::PARAM_INT);
            $stmt->execute();
            $filas = $stmt->fetchAll();
            if (!empty($filas)) {
                return $filas;
            }
        } catch (Throwable $e) {
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['bitacora_fallback'])) {
        return array_slice($_SESSION['bitacora_fallback'], 0, $limite);
    }

    return [
        [
            'id'      => 1,
            'fecha'   => date('Y-m-d H:i:s'),
            'evento'  => 'Monitoreo',
            'tipo'    => 'ok',
            'detalle' => 'Verificación multi-nodo ejecutada sobre VM 1 y VM 2.',
        ],
        [
            'id'      => 2,
            'fecha'   => date('Y-m-d H:i:s', time() - 300),
            'evento'  => 'DNS',
            'tipo'    => 'info',
            'detalle' => 'Zona clinicasanjose.lan sincronizada con Technitium DNS (192.168.50.10).',
        ],
        [
            'id'      => 3,
            'fecha'   => date('Y-m-d H:i:s', time() - 600),
            'evento'  => 'Sistema',
            'tipo'    => 'info',
            'detalle' => 'Sesión activa en el panel de control ClinicAdmin.',
        ],
    ];
}

/**
 * Envía una notificación por correo electrónico mediante la función nativa mail()
 * enrutada a Mailpit (127.0.0.1:25) cuando un servicio cae o se solicita una prueba.
 */
function enviar_notificacion_smtp_mailpit(string $asunto, string $cuerpoTexto): bool
{
    $destinatario = 'alertas-ti@clinicasanjose.lan';
    $cabeceras = implode("\r\n", [
        'From: ClinicAdmin Monitor <monitor@clinicasanjose.lan>',
        'Reply-To: ti@clinicasanjose.lan',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: ClinicAdmin-PHP/' . PHP_VERSION,
    ]);

    // Verificar primero si el socket SMTP 127.0.0.1:25 responde rápido para evitar bloqueos
    $smtpCheck = verificar_estado_servicio('127.0.0.1', 25, 1.5);
    if ($smtpCheck['estado'] !== 'activo') {
        return false;
    }

    return (bool) @mail($destinatario, $asunto, $cuerpoTexto, $cabeceras);
}

/**
 * Envía una alerta de prueba manual hacia Mailpit (127.0.0.1:25) y la registra en bitácora.
 */
function enviar_alerta_prueba_smtp(?PDO $pdo = null, string $usuarioSolicitante = 'Administrador TI'): array
{
    $fecha = date('d/m/Y H:i:s');
    $asunto = '[ClinicAdmin] Alerta de Prueba SMTP - ' . $fecha;
    $cuerpo = "Alerta de prueba generada desde ClinicAdmin.\n"
            . "Solicitante: {$usuarioSolicitante}\n"
            . "Nodo origen: admin.clinicasanjose.lan (192.168.50.10)\n"
            . "Servidor SMTP: Mailpit (127.0.0.1:25)\n"
            . "Visor Web: http://mail.clinicasanjose.lan:37410\n"
            . "Fecha y hora: {$fecha}\n";

    $enviado = enviar_notificacion_smtp_mailpit($asunto, $cuerpo);

    if ($enviado) {
        $detalle = sprintf('Alerta SMTP de prueba enviada exitosamente a Mailpit (127.0.0.1:25) por %s.', $usuarioSolicitante);
        registrar_evento_bitacora($pdo, 'Alerta SMTP', $detalle, 'ok');
        return [
            'ok'      => true,
            'mensaje' => 'Alerta de prueba enviada a Mailpit (127.0.0.1:25). Revisa el visor web en el puerto 37410.',
        ];
    }

    $detalleError = sprintf('Intento de alerta SMTP de prueba por %s: Mailpit (127.0.0.1:25) no disponible.', $usuarioSolicitante);
    registrar_evento_bitacora($pdo, 'Alerta SMTP', $detalleError, 'alerta');
    return [
        'ok'      => false,
        'mensaje' => 'No se pudo entregar el correo a Mailpit (127.0.0.1:25 OFFLINE). Evento registrado en bitácora.',
    ];
}

/**
 * Evalúa si un servicio cambió de ONLINE ('activo') a OFFLINE ('caido') para
 * disparar una única alerta SMTP y registrar la transición en la bitácora.
 */
function procesar_transicion_estado_servicio(?PDO $pdo, array $servicio, string $nuevoEstado): void
{
    $id = (int) ($servicio['id'] ?? 0);
    $nombre = (string) ($servicio['nombre'] ?? 'Servicio');
    $host = (string) ($servicio['host'] ?? '127.0.0.1');
    $puerto = (int) ($servicio['puerto'] ?? 0);
    $estadoAnterior = (string) ($servicio['ultimo_estado'] ?? 'desconocido');

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['estados_servicios_cache'] = $_SESSION['estados_servicios_cache'] ?? [];
        if ($estadoAnterior === 'desconocido' && isset($_SESSION['estados_servicios_cache'][$id])) {
            $estadoAnterior = (string) $_SESSION['estados_servicios_cache'][$id];
        }
        $_SESSION['estados_servicios_cache'][$id] = $nuevoEstado;
    }

    if ($estadoAnterior === $nuevoEstado) {
        return;
    }

    // Actualizar el estado en la base de datos si existe la columna `ultimo_estado`
    if ($pdo instanceof PDO && $id > 0) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE servicios
                 SET ultimo_estado = :estado, ultimo_cambio = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'estado' => $nuevoEstado,
                'id'     => $id,
            ]);
        } catch (Throwable $e) {
        }
    }

    // Transición crítica: ONLINE -> OFFLINE
    if ($estadoAnterior === 'activo' && $nuevoEstado === 'caido') {
        $asunto = sprintf('[ALERTA CRÍTICA] Servicio CAÍDO: %s (%s:%d)', $nombre, $host, $puerto);
        $cuerpo = sprintf(
            "ClinicAdmin detectó la caída de un servicio crítico:\n\n"
            . "Servicio: %s\n"
            . "Host / Puerto: %s:%d\n"
            . "Estado anterior: ONLINE (Activo)\n"
            . "Estado actual: OFFLINE (Caído)\n"
            . "Fecha de detección: %s\n",
            $nombre,
            $host,
            $puerto,
            date('d/m/Y H:i:s')
        );
        $mailOk = enviar_notificacion_smtp_mailpit($asunto, $cuerpo);
        $sufijoMail = $mailOk ? 'Notificación SMTP enviada a Mailpit.' : 'Mailpit no alcanzable para envío SMTP.';
        registrar_evento_bitacora(
            $pdo,
            'Alerta SMTP',
            sprintf('Caída detectada en %s (%s:%d): pasó de ONLINE a OFFLINE. %s', $nombre, $host, $puerto, $sufijoMail),
            'alerta'
        );
    } elseif ($estadoAnterior === 'caido' && $nuevoEstado === 'activo') {
        registrar_evento_bitacora(
            $pdo,
            'Monitoreo',
            sprintf('Servicio recuperado: %s (%s:%d) volvió a estado ONLINE.', $nombre, $host, $puerto),
            'ok'
        );
    }
}

/**
 * Obtiene el estado en tiempo real de los 7 servicios agrupados por nodo.
 */
function obtener_estado_servidores(?PDO $pdo = null): array
{
    $origen = obtener_servicios_activos($pdo);
    $items = [];
    $porNodo = [];
    $activos = 0;
    $caidos = 0;

    foreach ($origen['servicios'] as $servicio) {
        $host = (string) ($servicio['host'] ?? '127.0.0.1');
        $puerto = (int) ($servicio['puerto'] ?? 8080);
        // Forzar timeout entre 1.5 y 2 segundos máximo según guardrail 1.5
        $timeout = min((float) ($servicio['tiempo_espera_segundos'] ?? 1.5), 2.0);

        $resultado = verificar_estado_servicio($host, $puerto, $timeout);
        procesar_transicion_estado_servicio($pdo, $servicio, $resultado['estado']);

        if ($resultado['estado'] === 'activo') {
            $activos++;
        } else {
            $caidos++;
        }

        $esRemoto = stripos($host, 'rayosx') !== false || $host === '192.168.50.20';
        $nodoNombre = (string) ($servicio['nodo'] ?? ($esRemoto
            ? 'Nodo Médico Rayos X (rayosx.clinicasanjose.lan)'
            : 'Nodo Central (admin.clinicasanjose.lan)'));

        $servicioSsh = $servicio['servicio_ssh'] ?? null;
        if ($servicioSsh === null && $esRemoto) {
            if ($puerto === 8080) {
                $servicioSsh = 'httpd';
            } elseif ($puerto === 3306) {
                $servicioSsh = 'mariadb';
            }
        }

        $item = [
            'id'           => (int) ($servicio['id'] ?? 0),
            'nombre'       => (string) ($servicio['nombre'] ?? 'Servicio'),
            'categoria'    => (string) ($servicio['categoria'] ?? 'General'),
            'nodo'         => $nodoNombre,
            'es_remoto'    => $esRemoto,
            'host'         => $host,
            'puerto'       => $puerto,
            'protocolo'    => (string) ($servicio['protocolo'] ?? 'TCP'),
            'estado'       => $resultado['estado'],
            'codigo'       => $resultado['codigo'],
            'tiempo_ms'    => $resultado['tiempo_ms'],
            'detalle'      => $resultado['detalle'],
            'servicio_ssh' => $servicioSsh,
        ];

        $items[] = $item;
        $porNodo[$nodoNombre][] = $item;
    }

    $total = count($items);
    $porcentaje = $total > 0 ? (int) round(($activos / $total) * 100) : 0;

    return [
        'items'     => $items,
        'por_nodo'  => $porNodo,
        'resumen'   => [
            'total'      => $total,
            'activos'    => $activos,
            'caidos'     => $caidos,
            'porcentaje' => $porcentaje,
        ],
        'modo_demo' => $origen['modo_demo'],
    ];
}

/**
 * 3.2. Verificación Activa de Resolución DNS mediante gethostbyname()
 * Comprueba que los dominios de la zona `clinicasanjose.lan` resuelvan a las IPs ZeroTier reales.
 */
function verificar_resolucion_dns_activa(): array
{
    $dominiosEsperados = [
        'admin.clinicasanjose.lan'  => '192.168.50.10',
        'mail.clinicasanjose.lan'   => '192.168.50.10',
        'rayosx.clinicasanjose.lan' => '192.168.50.20',
    ];

    $resultados = [];
    $todosResueltos = true;

    foreach ($dominiosEsperados as $fqdn => $ipEsperada) {
        $info = resolver_host_rapido($fqdn);
        $coincide = $info['resuelto'] && $info['ip'] === $ipEsperada;

        if (!$coincide) {
            $todosResueltos = false;
        }

        $resultados[] = [
            'fqdn'        => $fqdn,
            'ip_esperada' => $ipEsperada,
            'ip_resuelta' => $info['resuelto'] ? $info['ip'] : 'Sin resolución DNS',
            'resuelto'    => $info['resuelto'],
            'valido'      => $coincide,
        ];
    }

    $puerto53 = verificar_estado_servicio('127.0.0.1', 53, 1.5);

    return [
        'zona'             => 'clinicasanjose.lan',
        'servidor_dns_ip'  => '192.168.50.10',
        'puerto_53_activo' => $puerto53['estado'] === 'activo',
        'latencia_dns_ms'  => $puerto53['tiempo_ms'],
        'panel_url'        => 'http://admin.clinicasanjose.lan:5380',
        'todos_validos'    => $todosResueltos,
        'dominios'         => $resultados,
    ];
}

/**
 * Obtiene el resumen estadístico de registros DNS configurados en MariaDB.
 */
function obtener_resumen_dns(?PDO $pdo = null): array
{
    $conteo = [
        'total' => 0,
        'A'     => 0,
        'CNAME' => 0,
        'MX'    => 0,
        'PTR'   => 0,
    ];

    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->query('SELECT tipo, COUNT(*) AS total FROM registros_dns GROUP BY tipo');
            if ($stmt !== false) {
                foreach ($stmt->fetchAll() as $fila) {
                    $tipo = (string) ($fila['tipo'] ?? '');
                    $cantidad = (int) ($fila['total'] ?? 0);
                    if (isset($conteo[$tipo])) {
                        $conteo[$tipo] = $cantidad;
                        $conteo['total'] += $cantidad;
                    }
                }
                return $conteo;
            }
        } catch (Throwable $e) {
        }
    }

    // Valores por defecto de la zona clinicasanjose.lan si la tabla aún no fue consultada
    return [
        'total' => 9,
        'A'     => 4,
        'CNAME' => 2,
        'MX'    => 1,
        'PTR'   => 2,
    ];
}

/**
 * 3.3. Control Remoto de Servicios de VM 2 vía SSH con Lista Blanca (Whitelist) estricta.
 * Solo permite acciones ('start', 'stop', 'restart') sobre ('httpd', 'mariadb') en rayosx.clinicasanjose.lan.
 */
function ejecutar_accion_ssh_vm2(?PDO $pdo, string $servicioInput, string $accionInput, string $usuario = 'Administrador TI'): array
{
    $serviciosPermitidos = [
        'httpd'   => 'httpd',
        'mariadb' => 'mariadb',
    ];

    $accionesPermitidas = [
        'start'   => 'start',
        'stop'    => 'stop',
        'restart' => 'restart',
    ];

    $etiquetasAccion = [
        'start'   => 'Iniciar',
        'stop'    => 'Detener',
        'restart' => 'Reiniciar',
    ];

    if (!isset($serviciosPermitidos[$servicioInput]) || !isset($accionesPermitidas[$accionInput])) {
        return [
            'ok'      => false,
            'mensaje' => 'Acción o servicio rechazado por la lista blanca de seguridad.',
        ];
    }

    $servicioSeguro = $serviciosPermitidos[$servicioInput];
    $accionSegura = $accionesPermitidas[$accionInput];
    $puertoServicio = $servicioSeguro === 'httpd' ? 8080 : 3306;

    // Verificar conectividad previa al puerto SSH 22 de VM 2 con timeout de 1.5s
    $sshStatus = verificar_estado_servicio('rayosx.clinicasanjose.lan', 22, 1.5);
    if ($sshStatus['estado'] !== 'activo') {
        $detalle = sprintf(
            'Control SSH fallido (%s %s en VM 2) por %s: Nodo rayosx.clinicasanjose.lan (puerto 22) OFFLINE.',
            $etiquetasAccion[$accionSegura],
            $servicioSeguro,
            $usuario
        );
        registrar_evento_bitacora($pdo, 'Control SSH', $detalle, 'alerta');

        return [
            'ok'              => false,
            'mensaje'         => 'VM 2 (rayosx.clinicasanjose.lan:22) está OFFLINE o inalcanzable. No se pudo ejecutar el comando SSH.',
            'estado_servicio' => 'caido',
            'codigo'          => 'OFFLINE',
            'tiempo_ms'       => null,
        ];
    }

    // Construcción segura usando únicamente valores literales de la lista blanca
    $comandoRemoto = sprintf('systemctl %s %s', $accionSegura, $servicioSeguro);
    $comandoSsh = sprintf(
        "ssh -o BatchMode=yes -o ConnectTimeout=2 root@rayosx.clinicasanjose.lan '%s' 2>&1",
        $comandoRemoto
    );

    $salida = @shell_exec($comandoSsh);
    $salidaLimpia = trim((string) $salida);

    // Pausa breve para que systemd aplique el cambio antes de verificar el socket
    usleep(350000);
    $nuevoEstado = verificar_estado_servicio('rayosx.clinicasanjose.lan', $puertoServicio, 1.5);

    $exitoEsperado = ($accionSegura === 'stop')
        ? ($nuevoEstado['estado'] === 'caido')
        : ($nuevoEstado['estado'] === 'activo');

    $detalleBitacora = sprintf(
        'Comando SSH [%s] ejecutado en rayosx.clinicasanjose.lan por %s. Estado actual de %s (:%d): %s.',
        $comandoRemoto,
        $usuario,
        $servicioSeguro,
        $puertoServicio,
        $nuevoEstado['codigo']
    );

    registrar_evento_bitacora($pdo, 'Control SSH', $detalleBitacora, $exitoEsperado ? 'ok' : 'alerta');

    return [
        'ok'              => $exitoEsperado,
        'mensaje'         => sprintf(
            'Acción "%s" sobre %s enviada a VM 2. Estado verificado: %s%s',
            $etiquetasAccion[$accionSegura],
            $servicioSeguro,
            $nuevoEstado['codigo'],
            $salidaLimpia !== '' ? ' (' . mb_substr($salidaLimpia, 0, 120) . ')' : ''
        ),
        'estado_servicio' => $nuevoEstado['estado'],
        'codigo'          => $nuevoEstado['codigo'],
        'tiempo_ms'       => $nuevoEstado['tiempo_ms'],
        'detalle'         => $nuevoEstado['detalle'],
    ];
}

/**
 * 3.4.A. Telemetría Remota de Hardware de VM 2 (rayosx.clinicasanjose.lan) vía SSH (ConnectTimeout=2).
 */
function obtener_telemetria_vm2(): array
{
    $sshStatus = verificar_estado_servicio('rayosx.clinicasanjose.lan', 22, 1.5);

    if ($sshStatus['estado'] !== 'activo') {
        return [
            'online'  => false,
            'nodo'    => 'rayosx.clinicasanjose.lan (192.168.50.20)',
            'uptime'  => 'OFFLINE / Inalcanzable',
            'ram'     => 'OFFLINE / Inalcanzable',
            'disco'   => 'OFFLINE / Inalcanzable',
            'detalle' => 'El demonio SSH en VM 2 (puerto 22) no responde o la máquina virtual está apagada.',
        ];
    }

    $cmd = "ssh -o BatchMode=yes -o ConnectTimeout=2 root@rayosx.clinicasanjose.lan "
         . "'uptime -p 2>/dev/null || uptime; echo \"|||\"; "
         . "free -m | awk \"NR==2{printf \\\"%s MB / %s MB (%.1f%%)\\\", \\\$3, \\\$2, (\\\$2>0?\\\$3*100/\\\$2:0)}\"; echo \"|||\"; "
         . "df -h / | awk \"NR==2{printf \\\"%s / %s (%s)\\\", \\\$3, \\\$2, \\\$5}\"' 2>&1";

    $salida = @shell_exec($cmd);
    if (!is_string($salida) || trim($salida) === '' || strpos($salida, '|||') === false) {
        return [
            'online'  => false,
            'nodo'    => 'rayosx.clinicasanjose.lan (192.168.50.20)',
            'uptime'  => 'No disponible (Verificar llave SSH apache -> root@VM2)',
            'ram'     => 'No disponible',
            'disco'   => 'No disponible',
            'detalle' => trim((string) $salida) !== '' ? mb_substr(trim((string) $salida), 0, 160) : 'Sin respuesta SSH.',
        ];
    }

    $partes = array_map('trim', explode('|||', $salida));

    return [
        'online'  => true,
        'nodo'    => 'rayosx.clinicasanjose.lan (192.168.50.20)',
        'uptime'  => $partes[0] !== '' ? $partes[0] : 'Activo',
        'ram'     => $partes[1] ?? 'N/D',
        'disco'   => $partes[2] ?? 'N/D',
        'detalle' => 'Telemetría obtenida en tiempo real vía SSH (llave RSA sin contraseña).',
    ];
}

/**
 * 3.4.B. Verificación de Integridad SQL de `rayosx_db` en VM 2 vía PDO (timeout = 2s).
 */
function verificar_integridad_rayosx_db(): array
{
    $urlPortal = 'http://rayosx.clinicasanjose.lan:8080';
    $socketMysql = verificar_estado_servicio('rayosx.clinicasanjose.lan', 3306, 1.5);

    if ($socketMysql['estado'] !== 'activo') {
        return [
            'online'          => false,
            'base_datos'      => 'rayosx_db',
            'host'            => 'rayosx.clinicasanjose.lan:3306',
            'tablas'          => [],
            'total_tablas'    => 0,
            'total_registros' => 0,
            'portal_url'      => $urlPortal,
            'detalle'         => 'OFFLINE · Puerto 3306 en rayosx.clinicasanjose.lan inalcanzable.',
        ];
    }

    if (!function_exists('get_rayosx_db_connection')) {
        require_once __DIR__ . '/../Config/database.php';
    }

    try {
        $infoHost = resolver_host_rapido('rayosx.clinicasanjose.lan');
        $pdoRemoto = get_rayosx_db_connection($infoHost['ip']);

        $stmtTablas = $pdoRemoto->query('SHOW TABLES');
        $nombresTablas = $stmtTablas !== false ? $stmtTablas->fetchAll(PDO::FETCH_COLUMN) : [];

        $detalleTablas = [];
        $totalRegistros = 0;

        foreach ($nombresTablas as $tabla) {
            $nombreTabla = (string) $tabla;
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $nombreTabla)) {
                continue;
            }
            $stCount = $pdoRemoto->query("SELECT COUNT(*) FROM `{$nombreTabla}`");
            $conteo = $stCount !== false ? (int) $stCount->fetchColumn() : 0;
            $totalRegistros += $conteo;

            $detalleTablas[] = [
                'tabla'     => $nombreTabla,
                'registros' => $conteo,
            ];
        }

        return [
            'online'          => true,
            'base_datos'      => 'rayosx_db',
            'host'            => 'rayosx.clinicasanjose.lan:3306',
            'tablas'          => $detalleTablas,
            'total_tablas'    => count($detalleTablas),
            'total_registros' => $totalRegistros,
            'portal_url'      => $urlPortal,
            'detalle'         => sprintf('ONLINE · Conexión PDO verificada (%d tablas detectadas)', count($detalleTablas)),
        ];
    } catch (Throwable $e) {
        return [
            'online'          => false,
            'base_datos'      => 'rayosx_db',
            'host'            => 'rayosx.clinicasanjose.lan:3306',
            'tablas'          => [],
            'total_tablas'    => 0,
            'total_registros' => 0,
            'portal_url'      => $urlPortal,
            'detalle'         => 'Error PDO: ' . mb_substr($e->getMessage(), 0, 140),
        ];
    }
}
