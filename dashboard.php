<?php
declare(strict_types=1);

require_once __DIR__ . '/Config/auth.php';
require_once __DIR__ . '/Models/ServicioModel.php';

// Protección: sin sesión válida se redirige a login.php y no se ejecuta nada más.
require_auth();

// Evita que el navegador muestre una copia en caché del panel tras cerrar sesión.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$pdo = null;
$rutaDatabase = __DIR__ . '/Config/database.php';
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

$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
$nombreUsuario = is_string($_SESSION['usuario_nombre'] ?? null) && trim($_SESSION['usuario_nombre']) !== ''
    ? trim($_SESSION['usuario_nombre'])
    : 'Bianca Morales';
$csrfToken = get_csrf_token();

// Obtener primer nombre para el saludo personalizado del banner
$partesNombre = preg_split('/\s+/', $nombreUsuario) ?: [];
$primerNombre = $partesNombre[0] ?? 'Administrador';
$saludo = (stripos($primerNombre, 'bianca') !== false || substr(strtolower($primerNombre), -1) === 'a')
    ? 'Bienvenida'
    : 'Bienvenido';

// Datos en tiempo real para las tarjetas del Dashboard
$datosServidores = obtener_estado_servidores($pdo);
$servidores = $datosServidores['items'];
$resumenServidores = $datosServidores['resumen'];
$resumenDns = obtener_resumen_dns($pdo);
$dnsActivo = verificar_resolucion_dns_activa();
$bitacora = obtener_bitacora_reciente($pdo, 6);
$fechaActualizacion = date('d/m/Y H:i:s');

// Cálculo del gráfico circular SVG (circunferencia r=46 => 2 * pi * 46 = 289.02)
$porcentajeOnline = (int) ($resumenServidores['porcentaje'] ?? 0);
$circunferencia = 289.02;
$offsetDonut = $circunferencia - (($porcentajeOnline / 100) * $circunferencia);
$colorDonut = $porcentajeOnline >= 70 ? '#16a34a' : ($porcentajeOnline >= 40 ? '#d97706' : '#dc2626');
$estadoGlobalTexto = $resumenServidores['caidos'] === 0
    ? 'Operativo'
    : ($resumenServidores['activos'] > 0 ? 'Parcial' : 'Crítico');
$estadoGlobalClase = $resumenServidores['caidos'] === 0
    ? 'ca-status-pill--ok'
    : 'ca-status-pill--down';

// Variables compartidas con Views/header.php y Views/partials/navbar.php
$tituloPagina = 'Panel de Control | ClinicAdmin';
$cssExtra = ['assets/css/dashboard.css'];
$paginaActiva = 'inicio';
$baseUrl = '';

include __DIR__ . '/Views/header.php';
include __DIR__ . '/Views/partials/navbar.php';
?>

<main class="dash">
    <!-- Hero Banner Principal (Idéntico a la guía visual) -->
    <section class="ca-hero-banner" aria-label="Bienvenida al panel de control">
        <div class="ca-hero-left">
            <span class="ca-hero-accent" aria-hidden="true"></span>
            <div>
                <h1><?= e($saludo) ?>, <?= e($primerNombre) ?></h1>
                <p>
                    Panel de control de la infraestructura médica<br>
                    Clínica Don Juan
                </p>
            </div>
        </div>

        <!-- Onda ECG central -->
        <div class="ca-hero-ecg" aria-hidden="true">
            <svg width="240" height="84" viewBox="0 0 240 84" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 44H75L88 28L100 64L114 10L127 72L139 34L149 44H240"
                      stroke="#5ce1e6" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" opacity="0.85"/>
                <path d="M0 44H75L88 28L100 64L114 10L127 72L139 34L149 44H240"
                      stroke="#ffffff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" opacity="0.16"/>
            </svg>
        </div>

        <!-- Ilustración arquitectónica del edificio de la clínica a la derecha -->
        <div class="ca-hero-illustration" aria-hidden="true">
            <svg viewBox="0 0 520 170" width="100%" height="100%" preserveAspectRatio="xMaxYMid slice" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="heroFade" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#174f8e" stop-opacity="1"/>
                        <stop offset="28%" stop-color="#2268b2" stop-opacity="0.65"/>
                        <stop offset="100%" stop-color="#87b8e8" stop-opacity="0.15"/>
                    </linearGradient>
                    <linearGradient id="glassWin" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#1b4d82"/>
                        <stop offset="100%" stop-color="#3877b8"/>
                    </linearGradient>
                </defs>
                <!-- Cielo luminoso detrás del edificio -->
                <rect x="60" y="0" width="460" height="170" fill="#8ab9e8" opacity="0.38"/>
                <circle cx="440" cy="36" r="55" fill="#ffffff" opacity="0.22"/>
                <!-- Edificio moderno de la Clínica Don Juan en perspectiva -->
                <g transform="translate(85, 16)">
                    <!-- Ala izquierda en perspectiva -->
                    <polygon points="45,88 185,20 185,146 45,146" fill="#d9e7f7"/>
                    <!-- Fachada central principal -->
                    <polygon points="185,20 315,52 315,146 185,146" fill="#f1f6fc"/>
                    <!-- Ala derecha -->
                    <polygon points="315,38 415,58 415,146 315,146" fill="#dce9f8"/>
                    <!-- Franja superior azul corporativa -->
                    <polygon points="45,88 185,20 185,30 45,95" fill="#2c6eb7"/>
                    <polygon points="185,20 315,52 315,60 185,30" fill="#1d5699"/>
                    <!-- Ventanales de cristal médico -->
                    <polygon points="75,92 115,74 115,134 75,138" fill="url(#glassWin)" opacity="0.88"/>
                    <polygon points="128,67 172,46 172,134 128,134" fill="url(#glassWin)" opacity="0.92"/>
                    <polygon points="198,44 245,55 245,134 198,134" fill="url(#glassWin)" opacity="0.85"/>
                    <polygon points="258,58 298,67 298,134 258,134" fill="url(#glassWin)" opacity="0.85"/>
                    <rect x="332" y="68" width="28" height="64" fill="url(#glassWin)" opacity="0.82"/>
                    <rect x="370" y="74" width="24" height="58" fill="url(#glassWin)" opacity="0.82"/>
                    <!-- Líneas estructurales blancas -->
                    <line x1="45" y1="112" x2="185" y2="68" stroke="#ffffff" stroke-width="2.2"/>
                    <line x1="185" y1="68" x2="315" y2="88" stroke="#ffffff" stroke-width="2.2"/>
                    <!-- Vegetación frontal -->
                    <circle cx="170" cy="144" r="22" fill="#1e5b3a" opacity="0.85"/>
                    <circle cx="195" cy="146" r="18" fill="#277249" opacity="0.85"/>
                    <circle cx="320" cy="145" r="20" fill="#1e5b3a" opacity="0.85"/>
                    <circle cx="48" cy="146" r="16" fill="#277249" opacity="0.8"/>
                </g>
                <!-- Fundido suave con el azul del banner -->
                <rect x="0" y="0" width="260" height="170" fill="url(#heroFade)"/>
            </svg>
        </div>
    </section>

    <!-- Notificación flotante para acciones rápidas (Alerta SMTP / Control SSH) -->
    <div id="caDashToast" class="ca-toast" role="status" aria-live="polite"></div>

    <!-- Grilla Superior de 3 Columnas: Servidores | DNS | Estado del sistema -->
    <section class="ca-grid-3" aria-label="Resumen principal de infraestructura">

        <!-- 1. Tarjeta: Servidores (Monitoreo Multi-Nodo de 7 servicios) -->
        <article class="ca-card" aria-label="Estado de servidores">
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="6" rx="1.5"/>
                        <rect x="3" y="14" width="18" height="6" rx="1.5"/>
                        <circle cx="7" cy="7" r="1" fill="currentColor"/>
                        <circle cx="10" cy="7" r="1" fill="currentColor"/>
                        <circle cx="7" cy="17" r="1" fill="currentColor"/>
                        <circle cx="10" cy="17" r="1" fill="currentColor"/>
                    </svg>
                    <span>Servidores</span>
                </h2>
                <a href="Controllers/Controllers_servidores.php" class="ca-card-link">
                    <span>Ver detalle</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <!-- Selector rápido de Nodo (VM 1 Central / VM 2 Rayos X / Todos) -->
            <div class="ca-node-tabs" role="tablist" aria-label="Filtrar servicios por nodo">
                <button type="button" class="ca-node-tab is-active" data-node-filter="all">Todos (<?= count($servidores) ?>)</button>
                <button type="button" class="ca-node-tab" data-node-filter="local">VM 1 Central (4)</button>
                <button type="button" class="ca-node-tab" data-node-filter="remoto">VM 2 Rayos X (3)</button>
            </div>

            <div class="ca-srv-list">
                <?php foreach ($servidores as $srv): ?>
                    <?php
                    $esActivo = ($srv['estado'] ?? '') === 'activo';
                    $esRemoto = !empty($srv['es_remoto']);
                    $latenciaTexto = ($srv['tiempo_ms'] !== null) ? ((int) $srv['tiempo_ms'] . ' ms') : 'OFFLINE';
                    $puerto = (int) ($srv['puerto'] ?? 0);
                    $servicioSsh = $srv['servicio_ssh'] ?? null;
                    $urlPortalFila = null;
                    if ($esRemoto && $puerto === 8080) {
                        $urlPortalFila = 'http://rayosx.clinicasanjose.lan:8080';
                    } elseif (!$esRemoto && $puerto === 53) {
                        $urlPortalFila = 'http://admin.clinicasanjose.lan:5380';
                    } elseif (!$esRemoto && $puerto === 25) {
                        $urlPortalFila = 'http://mail.clinicasanjose.lan:37410';
                    }
                    ?>
                    <div class="ca-srv-row"
                         data-searchable
                         data-node-type="<?= $esRemoto ? 'remoto' : 'local' ?>"
                         data-dash-service-row="<?= e((string) ($servicioSsh ?? $srv['id'])) ?>">
                        <div class="ca-srv-row-left">
                            <span class="ca-srv-icon" aria-hidden="true">
                                <?php if ($puerto === 8080 || $puerto === 80): ?>
                                    <!-- Icono Web / Apache -->
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18"/>
                                    </svg>
                                <?php elseif ($puerto === 3306): ?>
                                    <!-- Icono Base de Datos MariaDB -->
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                        <ellipse cx="12" cy="5" rx="8" ry="3"/>
                                        <path d="M4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/>
                                        <path d="M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>
                                    </svg>
                                <?php elseif ($puerto === 25): ?>
                                    <!-- Icono Correo SMTP Mailpit -->
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        <polyline points="3 7 12 13 21 7"/>
                                    </svg>
                                <?php elseif ($puerto === 53): ?>
                                    <!-- Icono DNS Technitium -->
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                                    </svg>
                                <?php else: ?>
                                    <!-- Icono SSH / Terminal -->
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                                        <polyline points="8 10 11 13 8 16"/>
                                        <line x1="13" y1="16" x2="17" y2="16"/>
                                    </svg>
                                <?php endif; ?>
                            </span>
                            <div class="ca-srv-info">
                                <div class="ca-srv-name"><?= e((string) $srv['nombre']) ?></div>
                                <div class="ca-srv-sub" data-role="dash-srv-sub">
                                    <?= e((string) $srv['host']) ?>:<?= $puerto ?> &middot; <?= e($latenciaTexto) ?>
                                </div>
                            </div>
                        </div>

                        <div class="ca-srv-actions">
                            <?php if ($urlPortalFila !== null): ?>
                                <a href="<?= e($urlPortalFila) ?>"
                                   target="_blank"
                                   rel="noopener"
                                   class="ca-dash-portal-link"
                                   title="Abrir portal interno (HU-07)">
                                    Portal &nearr;
                                </a>
                            <?php endif; ?>

                            <?php if ($servicioSsh !== null && in_array($servicioSsh, ['httpd', 'mariadb'], true)): ?>
                                <button type="button"
                                        class="ca-dash-ssh-btn"
                                        data-dash-ssh-service="<?= e((string) $servicioSsh) ?>"
                                        data-dash-ssh-host="<?= e((string) $srv['host']) ?>:<?= $puerto ?>"
                                        title="Reiniciar <?= e((string) $servicioSsh) ?> remotamente vía SSH (HU-06)">
                                    &#x21bb; SSH
                                </button>
                            <?php endif; ?>

                            <span class="ca-status-pill <?= $esActivo ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>" data-role="dash-srv-badge">
                                <span class="ca-status-pill-dot" aria-hidden="true"></span>
                                <span data-role="dash-srv-badge-text"><?= $esActivo ? 'Activo' : 'Caído' ?></span>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <!-- 2. Tarjeta: DNS (Technitium DNS + Verificación Activa gethostbyname) -->
        <article class="ca-card" aria-label="Resumen de DNS">
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18"/>
                    </svg>
                    <span>DNS</span>
                </h2>
                <a href="Controllers/Controllers_dns.php" class="ca-card-link">
                    <span>Administrar</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="ca-dns-center">
                <div class="ca-dns-globe-wrap" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18"/>
                    </svg>
                    <span class="ca-dns-check">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </span>
                </div>

                <div class="ca-dns-zone-title">Zona: <?= e($dnsActivo['zona']) ?></div>
                <span class="ca-dns-count-pill">
                    <?= (int) $resumenDns['total'] ?> registros configurados
                </span>

                <!-- Verificación Activa gethostbyname() requerida en 3.2 -->
                <div class="ca-dns-active-checks" title="Verificación activa mediante gethostbyname()">
                    <?php foreach ($dnsActivo['dominios'] as $dom): ?>
                        <div class="ca-dns-check-item">
                            <span class="ca-dns-check-fqdn"><?= e($dom['fqdn']) ?></span>
                            <span class="ca-dns-check-ip <?= $dom['valido'] ? 'is-ok' : 'is-fail' ?>">
                                &rarr; <?= e($dom['ip_resuelta']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top:4px; text-align:right;">
                        <a href="<?= e($dnsActivo['panel_url']) ?>" target="_blank" rel="noopener" class="ca-card-link" style="font-size:0.75rem; padding:2px 4px;">
                            Technitium DNS (:5380) &nearr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="ca-dns-counters">
                <div class="ca-dns-counter-col">
                    <span class="ca-dns-counter-type">A</span>
                    <span class="ca-dns-counter-num"><?= (int) ($resumenDns['A'] ?? 0) ?></span>
                </div>
                <div class="ca-dns-counter-col">
                    <span class="ca-dns-counter-type">CNAME</span>
                    <span class="ca-dns-counter-num"><?= (int) ($resumenDns['CNAME'] ?? 0) ?></span>
                </div>
                <div class="ca-dns-counter-col">
                    <span class="ca-dns-counter-type">MX</span>
                    <span class="ca-dns-counter-num"><?= (int) ($resumenDns['MX'] ?? 0) ?></span>
                </div>
                <div class="ca-dns-counter-col">
                    <span class="ca-dns-counter-type">PTR</span>
                    <span class="ca-dns-counter-num"><?= (int) ($resumenDns['PTR'] ?? 0) ?></span>
                </div>
            </div>
        </article>

        <!-- 3. Tarjeta: Estado del sistema (Gráfico Donut SVG) -->
        <article class="ca-card" aria-label="Estado general del sistema">
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    <span>Estado del sistema</span>
                </h2>
                <span class="ca-status-pill <?= $estadoGlobalClase ?>">
                    <?= e($estadoGlobalTexto) ?>
                </span>
            </div>

            <div class="ca-sys-body">
                <div class="ca-donut-col">
                    <div class="ca-donut-wrap">
                        <svg width="124" height="124" viewBox="0 0 110 110" aria-hidden="true">
                            <circle cx="55" cy="55" r="46" fill="none" stroke="#e6eef8" stroke-width="11"/>
                            <circle cx="55" cy="55" r="46" fill="none"
                                    stroke="<?= e($colorDonut) ?>"
                                    stroke-width="11"
                                    stroke-linecap="round"
                                    stroke-dasharray="<?= e((string) $circunferencia) ?>"
                                    stroke-dashoffset="<?= e((string) round($offsetDonut, 2)) ?>"
                                    transform="rotate(-90 55 55)"/>
                        </svg>
                        <span class="ca-donut-pct"><?= $porcentajeOnline ?>%</span>
                    </div>
                    <span class="ca-donut-label">Servicios en línea</span>
                </div>

                <div class="ca-sys-metrics">
                    <div class="ca-sys-metric">
                        <span class="ca-sys-metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="6" rx="1.5"/>
                                <rect x="3" y="14" width="18" height="6" rx="1.5"/>
                            </svg>
                        </span>
                        <div>
                            <span class="ca-sys-metric-label">Servidores</span>
                            <span class="ca-sys-metric-val">
                                <?= (int) $resumenServidores['activos'] ?> / <?= (int) $resumenServidores['total'] ?> activos
                            </span>
                        </div>
                    </div>

                    <div class="ca-sys-metric">
                        <span class="ca-sys-metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M3.6 9h16.8M3.6 15h16.8"/>
                            </svg>
                        </span>
                        <div>
                            <span class="ca-sys-metric-label">DNS</span>
                            <span class="ca-sys-metric-val"><?= (int) $resumenDns['total'] ?> registros</span>
                        </div>
                    </div>

                    <div class="ca-sys-metric">
                        <span class="ca-sys-metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/>
                                <polyline points="12 7 12 12 15 15"/>
                            </svg>
                        </span>
                        <div>
                            <span class="ca-sys-metric-label">Última actualización</span>
                            <span class="ca-sys-metric-val"><?= e($fechaActualizacion) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <!-- Grilla Inferior: Actividad reciente (Bitácora + Alertas Mailpit) y Tarjeta ClinicAdmin -->
    <section class="ca-grid-bottom" aria-label="Actividad reciente e información del sistema">

        <!-- Actividad Reciente y Alertas SMTP -->
        <article class="ca-card" aria-label="Actividad reciente del sistema">
            <div class="ca-card-header" style="flex-wrap:wrap;">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span>Actividad reciente</span>
                </h2>

                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <button type="button" id="btnAlertaPruebaDash" class="ca-btn-sm ca-btn-sm--primary">
                        &#9993; Enviar Alerta de Prueba
                    </button>
                    <a href="http://mail.clinicasanjose.lan:37410" target="_blank" rel="noopener" class="ca-btn-sm">
                        Visor Mailpit (:37410) &nearr;
                    </a>
                    <a href="Controllers/Controllers_historial.php" class="ca-card-link">
                        <span>Ver historial</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>

            <div class="ca-table-wrap">
                <table class="ca-activity-table" id="tablaActividadDash">
                    <thead>
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Evento</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bitacora as $ev): ?>
                            <?php
                            $tipoEv = (string) ($ev['tipo'] ?? 'info');
                            $claseDot = $tipoEv === 'ok'
                                ? 'ca-event-dot--ok'
                                : ($tipoEv === 'alerta' ? 'ca-event-dot--alerta' : 'ca-event-dot--info');
                            $fechaEv = isset($ev['fecha']) ? date('d/m/Y H:i:s', strtotime((string) $ev['fecha']) ?: time()) : $fechaActualizacion;
                            ?>
                            <tr data-searchable>
                                <td>
                                    <span class="ca-event-time">
                                        <span class="ca-event-dot <?= $claseDot ?>" aria-hidden="true"></span>
                                        <span><?= e($fechaEv) ?></span>
                                    </span>
                                </td>
                                <td><strong><?= e((string) ($ev['evento'] ?? 'Sistema')) ?></strong></td>
                                <td><?= e((string) ($ev['detalle'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <!-- Tarjeta Escudo ClinicAdmin (Esquina inferior derecha de la imagen guía) -->
        <aside class="ca-shield-card" aria-label="Acerca de ClinicAdmin">
            <div class="ca-shield-icon" aria-hidden="true">
                <svg viewBox="0 0 64 64" width="60" height="60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="shieldGrad" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#154c91"/>
                        </linearGradient>
                    </defs>
                    <path d="M32 6L10 15V31C10 45.8 19.4 58.6 32 62C44.6 58.6 54 45.8 54 31V15L32 6Z" fill="url(#shieldGrad)"/>
                    <rect x="23" y="29" width="18" height="14" rx="3" fill="#ffffff"/>
                    <path d="M27 29V24C27 21.2 29.2 19 32 19C34.8 19 37 21.2 37 24V29" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round"/>
                    <circle cx="32" cy="35" r="2" fill="#154c91"/>
                </svg>
            </div>
            <h3 class="ca-shield-title">ClinicAdmin</h3>
            <p class="ca-shield-desc">
                Gestión y monitoreo eficiente para una clínica más conectada.
            </p>
            <div style="display:flex; flex-direction:column; gap:8px; width:100%; align-items:center;">
                <a href="http://rayosx.clinicasanjose.lan:8080" target="_blank" rel="noopener" class="ca-btn-sm" style="width:100%; justify-content:center;">
                    Portal Médico Rayos X (:8080) &nearr;
                </a>
                <button type="button" class="ca-shield-btn" data-open-modal="caModalAcerca" style="width:100%;">
                    <span>Acerca del sistema</span>
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </aside>

    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = <?= json_encode($csrfToken) ?>;
    var toast = document.getElementById('caDashToast');

    function mostrarToastDash(ok, msg) {
        if (!toast) return;
        toast.className = 'ca-toast is-visible ' + (ok ? 'ca-toast--ok' : 'ca-toast--error');
        toast.textContent = msg;
    }

    function agregarFilaActividadDash(fecha, evento, detalle, ok) {
        var tbody = document.querySelector('#tablaActividadDash tbody');
        if (!tbody) return;
        var tr = document.createElement('tr');
        var dotClass = ok ? 'ca-event-dot--ok' : 'ca-event-dot--alerta';
        tr.innerHTML = '<td><span class="ca-event-time"><span class="ca-event-dot ' + dotClass + '"></span><span>' +
            fecha + '</span></span></td><td><strong>' + evento + '</strong></td><td>' + detalle + '</td>';
        tbody.insertBefore(tr, tbody.firstChild);
    }

    // Filtro rápido por Nodo en la tarjeta Servidores del Dashboard
    var tabs = document.querySelectorAll('[data-node-filter]');
    var rows = document.querySelectorAll('.ca-srv-row[data-node-type]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('is-active'); });
            tab.classList.add('is-active');
            var filtro = tab.getAttribute('data-node-filter');

            rows.forEach(function (row) {
                var tipo = row.getAttribute('data-node-type');
                row.style.display = (filtro === 'all' || filtro === tipo) ? '' : 'none';
            });
        });
    });

    // HU-06: Reinicio remoto SSH directo desde un botón en el Dashboard
    document.querySelectorAll('[data-dash-ssh-service]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var servicio = btn.getAttribute('data-dash-ssh-service');
            var hostPort = btn.getAttribute('data-dash-ssh-host') || '';
            var row = document.querySelector('[data-dash-service-row="' + servicio + '"]');

            btn.disabled = true;
            var textoOriginal = btn.innerHTML;
            btn.textContent = 'SSH...';
            mostrarToastDash(true, 'Ejecutando reinicio remoto SSH (systemctl restart ' + servicio + ') en VM 2...');

            var fd = new FormData();
            fd.append('accion_ajax', 'control_ssh');
            fd.append('servicio', servicio);
            fd.append('accion', 'restart');
            fd.append('csrf_token', csrfToken);

            fetch('Controllers/Controllers_servidores.php', {
                method: 'POST',
                body: fd
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
                mostrarToastDash(Boolean(data.ok), data.mensaje || 'Comando SSH procesado.');

                if (row && data.estado_servicio) {
                    var esActivo = data.estado_servicio === 'activo';
                    var badge = row.querySelector('[data-role="dash-srv-badge"]');
                    var badgeText = row.querySelector('[data-role="dash-srv-badge-text"]');
                    var subEl = row.querySelector('[data-role="dash-srv-sub"]');

                    if (badge) {
                        badge.classList.remove('ca-status-pill--ok', 'ca-status-pill--down');
                        badge.classList.add(esActivo ? 'ca-status-pill--ok' : 'ca-status-pill--down');
                    }
                    if (badgeText) {
                        badgeText.textContent = esActivo ? 'Activo' : 'Caído';
                    }
                    if (subEl && hostPort !== '') {
                        var lat = (data.tiempo_ms !== null && data.tiempo_ms !== undefined)
                            ? (data.tiempo_ms + ' ms')
                            : 'OFFLINE';
                        subEl.textContent = hostPort + ' · ' + lat;
                    }
                }

                if (data.evento_fecha) {
                    agregarFilaActividadDash(data.evento_fecha, 'Control SSH', data.mensaje || '', Boolean(data.ok));
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
                mostrarToastDash(false, 'No se pudo completar la petición SSH desde el Dashboard.');
            });
        });
    });

    // HU-05: Botón Enviar Alerta de Prueba SMTP desde el Dashboard
    var btnAlerta = document.getElementById('btnAlertaPruebaDash');
    if (btnAlerta) {
        btnAlerta.addEventListener('click', function () {
            btnAlerta.disabled = true;
            var textoOriginal = btnAlerta.textContent;
            btnAlerta.textContent = 'Enviando a Mailpit...';

            var formData = new FormData();
            formData.append('accion_ajax', 'alerta_prueba');
            formData.append('csrf_token', csrfToken);

            fetch('Controllers/Controllers_servidores.php', {
                method: 'POST',
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btnAlerta.disabled = false;
                btnAlerta.textContent = textoOriginal;
                mostrarToastDash(Boolean(data.ok), data.mensaje || 'Operación completada.');
                if (data.evento_fecha) {
                    agregarFilaActividadDash(data.evento_fecha, 'Alerta SMTP', data.mensaje || '', Boolean(data.ok));
                }
            })
            .catch(function () {
                btnAlerta.disabled = false;
                btnAlerta.textContent = textoOriginal;
            });
        });
    }
});
</script>

<?php include __DIR__ . '/Views/footer.php'; ?>
