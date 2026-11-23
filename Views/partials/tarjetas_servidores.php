<?php
declare(strict_types=1);

$servidores = $servidores ?? [];
$servidoresPorNodo = $servidoresPorNodo ?? [];
$resumenServidores = $resumenServidores ?? ['total' => 0, 'activos' => 0, 'caidos' => 0, 'porcentaje' => 0];
$modoDemoServidores = $modoDemoServidores ?? false;
$fechaConsulta = $fechaConsulta ?? date('d/m/Y H:i:s');
$csrfToken = $csrfToken ?? (function_exists('get_csrf_token') ? get_csrf_token() : '');
$dnsActivo = $dnsActivo ?? (function_exists('verificar_resolucion_dns_activa') ? verificar_resolucion_dns_activa() : []);
$telemetriaVm2 = $telemetriaVm2 ?? (function_exists('obtener_telemetria_vm2') ? obtener_telemetria_vm2() : []);
$saludRayosxDb = $saludRayosxDb ?? (function_exists('verificar_integridad_rayosx_db') ? verificar_integridad_rayosx_db() : []);
$bitacoraReciente = $bitacoraReciente ?? (function_exists('obtener_bitacora_reciente') ? obtener_bitacora_reciente(null, 10) : []);

if (empty($servidoresPorNodo) && !empty($servidores)) {
    foreach ($servidores as $s) {
        $n = (string) ($s['nodo'] ?? 'Nodo Central (admin.clinicasanjose.lan)');
        $servidoresPorNodo[$n][] = $s;
    }
}
?>

<section class="servidores-modulo" aria-label="Estado de servidores y servicios multi-nodo">

    <!-- Banner Principal de la Vista Servidores (Misma estética del Dashboard) -->
    <div class="ca-hero-banner">
        <div class="ca-hero-left">
            <span class="ca-hero-accent" aria-hidden="true"></span>
            <div>
                <h1>Monitoreo Multi-Nodo de Servidores</h1>
                <p>
                    Verificación TCP (<code>fsockopen</code>), Control Remoto SSH y Telemetría Distribuida<br>
                    VM 1 (<code>192.168.50.10</code>) &middot; VM 2 Rayos X (<code>192.168.50.20</code>)
                </p>
            </div>
        </div>

        <div class="ca-hero-ecg" aria-hidden="true">
            <svg width="220" height="80" viewBox="0 0 240 84" fill="none">
                <path d="M0 44H75L88 28L100 64L114 10L127 72L139 34L149 44H240"
                      stroke="#5ce1e6" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" opacity="0.85"/>
            </svg>
        </div>

        <div class="ca-hero-badges">
            <span class="ca-status-pill <?= ((int) $resumenServidores['caidos'] === 0) ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                <span class="ca-status-pill-dot"></span>
                <span><?= (int) $resumenServidores['activos'] ?> / <?= (int) $resumenServidores['total'] ?> Servicios ONLINE</span>
            </span>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px;">
                <a href="?actualizar=1" class="btn-actualizar">
                    &#x21bb; Actualizar estado
                </a>
                <button type="button" id="btnAlertaPruebaSrv" class="btn-actualizar" style="background:rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.35);">
                    &#9993; Enviar Alerta de Prueba
                </button>
            </div>
        </div>
    </div>

    <!-- Aviso de retroalimentación AJAX (SSH / Alerta SMTP) -->
    <div id="caSrvToast" class="ca-toast" role="status" aria-live="polite"></div>

    <?php if ($modoDemoServidores): ?>
        <div class="aviso-demo" role="status">
            <strong>Catálogo Multi-Nodo por Defecto:</strong> Se están monitoreando en vivo los 7 servicios de la arquitectura (VM 1 y VM 2) mediante <code>fsockopen</code> con timeout de 1.5s.
        </div>
    <?php endif; ?>

    <!-- KPIs de Resumen -->
    <div class="resumen-kpi">
        <div class="kpi-box">
            <div>
                <span class="kpi-label">Total Servicios</span>
                <div class="kpi-valor" id="kpiTotal"><?= (int) $resumenServidores['total'] ?></div>
            </div>
            <span class="ca-srv-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="6" rx="1.5"/>
                    <rect x="3" y="14" width="18" height="6" rx="1.5"/>
                </svg>
            </span>
        </div>

        <div class="kpi-box">
            <div>
                <span class="kpi-label">En Línea (ONLINE)</span>
                <div class="kpi-valor kpi-valor--ok" id="kpiActivos"><?= (int) $resumenServidores['activos'] ?></div>
            </div>
            <span class="ca-srv-icon" style="background:var(--ca-ok-bg); color:var(--ca-ok-text);">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </span>
        </div>

        <div class="kpi-box">
            <div>
                <span class="kpi-label">Caídos (OFFLINE)</span>
                <div class="kpi-valor kpi-valor--down" id="kpiCaidos"><?= (int) $resumenServidores['caidos'] ?></div>
            </div>
            <span class="ca-srv-icon" style="background:var(--ca-down-bg); color:var(--ca-down-text);">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="9"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </span>
        </div>

        <div class="kpi-box">
            <div>
                <span class="kpi-label">Última Comprobación</span>
                <div class="kpi-valor" style="font-size:1.1rem; margin-top:8px;"><?= htmlspecialchars((string) $fechaConsulta, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <span class="ca-srv-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"/>
                    <polyline points="12 7 12 12 15 15"/>
                </svg>
            </span>
        </div>
    </div>

    <!-- Listado de los 7 Servicios Agrupados por Nodo (VM 1 y VM 2) -->
    <?php foreach ($servidoresPorNodo as $nombreNodo => $listaServicios): ?>
        <?php
        $esNodoRemoto = stripos($nombreNodo, 'rayosx') !== false || stripos($nombreNodo, 'Médico') !== false;
        ?>
        <section class="nodo-section" aria-label="<?= htmlspecialchars($nombreNodo, ENT_QUOTES, 'UTF-8') ?>">
            <div class="nodo-section-header">
                <div class="nodo-section-title">
                    <span class="ca-srv-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="6" rx="1.5"/>
                            <rect x="3" y="14" width="18" height="6" rx="1.5"/>
                        </svg>
                    </span>
                    <div>
                        <div><?= htmlspecialchars($nombreNodo, ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:0.8rem; font-weight:500; color:var(--ca-text-sub);">
                            <?= $esNodoRemoto
                                ? 'IP ZeroTier: 192.168.50.20 · Control remoto vía SSH (root@rayosx.clinicasanjose.lan)'
                                : 'IP ZeroTier: 192.168.50.10 · Servicios locales en el servidor central' ?>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <?php if ($esNodoRemoto): ?>
                        <a href="http://rayosx.clinicasanjose.lan:8080" target="_blank" rel="noopener" class="ca-btn-sm">
                            Abrir Portal Rayos X (:8080) &nearr;
                        </a>
                    <?php else: ?>
                        <a href="http://admin.clinicasanjose.lan:5380" target="_blank" rel="noopener" class="ca-btn-sm">
                            Technitium DNS (:5380) &nearr;
                        </a>
                        <a href="http://mail.clinicasanjose.lan:37410" target="_blank" rel="noopener" class="ca-btn-sm">
                            Visor Mailpit (:37410) &nearr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="servidores-grid">
                <?php foreach ($listaServicios as $srv): ?>
                    <?php
                    $esActivo = ($srv['estado'] ?? '') === 'activo';
                    $claseCard = $esActivo ? 'servicio-card--activo' : 'servicio-card--caido';
                    $claseBadge = $esActivo ? 'estado-badge--activo' : 'estado-badge--caido';
                    $textoEstado = $esActivo ? 'ONLINE · Activo' : 'OFFLINE · Caído';
                    $latencia = isset($srv['tiempo_ms']) && $srv['tiempo_ms'] !== null
                        ? ((int) $srv['tiempo_ms']) . ' ms'
                        : 'Inalcanzable';
                    $servicioSsh = $srv['servicio_ssh'] ?? null;
                    ?>
                    <article class="servicio-card <?= $claseCard ?>"
                             data-searchable
                             data-service-card="<?= htmlspecialchars((string) ($servicioSsh ?? $srv['id']), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="servicio-card-top">
                            <div>
                                <span class="servicio-categoria">
                                    <?= htmlspecialchars((string) $srv['categoria'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <h2 class="servicio-nombre">
                                    <?= htmlspecialchars((string) $srv['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>
                            </div>

                            <span class="estado-badge <?= $claseBadge ?>" data-role="badge">
                                <span class="estado-dot" aria-hidden="true"></span>
                                <span data-role="badge-text"><?= $textoEstado ?></span>
                            </span>
                        </div>

                        <div class="servicio-meta">
                            <div class="meta-item">
                                <span class="meta-label">Host y Puerto</span>
                                <span class="meta-valor">
                                    <?= htmlspecialchars((string) $srv['host'], ENT_QUOTES, 'UTF-8') ?>:<?= (int) $srv['puerto'] ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Protocolo</span>
                                <span class="meta-valor">
                                    <?= htmlspecialchars((string) $srv['protocolo'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Latencia (ms)</span>
                                <span class="meta-valor" data-role="latencia"><?= htmlspecialchars($latencia, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">ID Servicio</span>
                                <span class="meta-valor">#<?= (int) $srv['id'] ?></span>
                            </div>
                        </div>

                        <div class="servicio-detalle" data-role="detalle">
                            <?= htmlspecialchars((string) $srv['detalle'], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <?php if ($servicioSsh !== null && in_array($servicioSsh, ['httpd', 'mariadb'], true)): ?>
                            <div style="border-top:1px dashed #d5e2f2; padding-top:10px;">
                                <span class="meta-label" style="display:block; margin-bottom:4px;">
                                    Control Remoto SSH (<code>systemctl &lt;acción&gt; <?= htmlspecialchars($servicioSsh, ENT_QUOTES, 'UTF-8') ?></code>)
                                </span>
                                <div class="ca-ssh-controls">
                                    <button type="button"
                                            class="ca-ssh-btn ca-ssh-btn--start"
                                            data-ssh-service="<?= htmlspecialchars($servicioSsh, ENT_QUOTES, 'UTF-8') ?>"
                                            data-ssh-action="start">
                                        &#9654; Iniciar
                                    </button>
                                    <button type="button"
                                            class="ca-ssh-btn ca-ssh-btn--restart"
                                            data-ssh-service="<?= htmlspecialchars($servicioSsh, ENT_QUOTES, 'UTF-8') ?>"
                                            data-ssh-action="restart">
                                        &#x21bb; Reiniciar
                                    </button>
                                    <button type="button"
                                            class="ca-ssh-btn ca-ssh-btn--stop"
                                            data-ssh-service="<?= htmlspecialchars($servicioSsh, ENT_QUOTES, 'UTF-8') ?>"
                                            data-ssh-action="stop">
                                        &#9632; Detener
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <!-- 3 Paneles Operativos: Telemetría VM 2 | Integridad SQL rayosx_db | Resolución Activa DNS -->
    <section class="ca-ops-grid" aria-label="Telemetría remota, integridad SQL y verificación DNS">

        <!-- 3.4.A. Telemetría Remota de Hardware VM 2 (SSH) -->
        <article class="ca-card" data-searchable>
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="4" width="16" height="16" rx="2"/>
                        <rect x="9" y="9" width="6" height="6"/>
                        <line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/>
                        <line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/>
                    </svg>
                    <span>Telemetría Hardware VM 2 (SSH)</span>
                </h2>
                <span class="ca-status-pill <?= !empty($telemetriaVm2['online']) ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                    <span class="ca-status-pill-dot"></span>
                    <span><?= !empty($telemetriaVm2['online']) ? 'SSH ONLINE' : 'OFFLINE' ?></span>
                </span>
            </div>

            <div class="ca-meta-grid" style="margin-bottom:12px;">
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Nodo Remoto</span>
                    <span class="ca-meta-value"><?= htmlspecialchars((string) ($telemetriaVm2['nodo'] ?? 'rayosx.clinicasanjose.lan'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Tiempo Encendido (Uptime)</span>
                    <span class="ca-meta-value"><?= htmlspecialchars((string) ($telemetriaVm2['uptime'] ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Uso de Memoria RAM</span>
                    <span class="ca-meta-value"><?= htmlspecialchars((string) ($telemetriaVm2['ram'] ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Espacio en Disco (/)</span>
                    <span class="ca-meta-value"><?= htmlspecialchars((string) ($telemetriaVm2['disco'] ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="servicio-detalle">
                <?= htmlspecialchars((string) ($telemetriaVm2['detalle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </article>

        <!-- 3.4.B. Salud e Integridad SQL de rayosx_db (PDO hacia VM 2) -->
        <article class="ca-card" data-searchable>
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <ellipse cx="12" cy="5" rx="8" ry="3"/>
                        <path d="M4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/>
                        <path d="M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>
                    </svg>
                    <span>Integridad SQL <code>rayosx_db</code></span>
                </h2>
                <span class="ca-status-pill <?= !empty($saludRayosxDb['online']) ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                    <span class="ca-status-pill-dot"></span>
                    <span><?= !empty($saludRayosxDb['online']) ? 'SQL ONLINE' : 'OFFLINE' ?></span>
                </span>
            </div>

            <div class="ca-meta-grid" style="margin-bottom:12px;">
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Servidor / Base de Datos</span>
                    <span class="ca-meta-value"><?= htmlspecialchars((string) ($saludRayosxDb['host'] ?? 'rayosx.clinicasanjose.lan:3306'), ENT_QUOTES, 'UTF-8') ?> (<code>rayosx_db</code>)</span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Tablas / Registros Totales</span>
                    <span class="ca-meta-value">
                        <?= (int) ($saludRayosxDb['total_tablas'] ?? 0) ?> tablas &middot;
                        <?= (int) ($saludRayosxDb['total_registros'] ?? 0) ?> registros
                    </span>
                </div>
            </div>

            <?php if (!empty($saludRayosxDb['tablas'])): ?>
                <div class="ca-table-wrap" style="margin-bottom:12px;">
                    <table class="ca-activity-table">
                        <thead>
                            <tr>
                                <th>Tabla en <code>rayosx_db</code></th>
                                <th>Registros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($saludRayosxDb['tablas'] as $tb): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars((string) $tb['tabla'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td><strong><?= (int) $tb['registros'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="servicio-detalle" style="margin-bottom:12px;">
                    <?= htmlspecialchars((string) ($saludRayosxDb['detalle'] ?? 'Sin conexión a rayosx_db.'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div style="margin-top:auto; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <a href="http://rayosx.clinicasanjose.lan:8080" target="_blank" rel="noopener" class="ca-btn-sm ca-btn-sm--primary">
                    Abrir Portal Web Rayos X (:8080) &nearr;
                </a>
            </div>
        </article>

        <!-- 3.2. Verificación Activa de Resolución DNS (gethostbyname) -->
        <article class="ca-card" data-searchable>
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18"/>
                    </svg>
                    <span>Resolución Activa DNS</span>
                </h2>
                <span class="ca-status-pill <?= !empty($dnsActivo['todos_validos']) ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                    <span class="ca-status-pill-dot"></span>
                    <span><?= !empty($dnsActivo['todos_validos']) ? 'Resolución OK' : 'Verificar DNS' ?></span>
                </span>
            </div>

            <div class="ca-table-wrap" style="margin-bottom:12px;">
                <table class="ca-activity-table">
                    <thead>
                        <tr>
                            <th>Dominio (FQDN)</th>
                            <th>IP Esperada</th>
                            <th><code>gethostbyname()</code></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($dnsActivo['dominios'] ?? []) as $dom): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $dom['fqdn'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><code><?= htmlspecialchars((string) $dom['ip_esperada'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td>
                                    <span class="ca-pill-badge <?= !empty($dom['valido']) ? 'ca-pill-badge--ok' : 'ca-pill-badge--down' ?>">
                                        <?= htmlspecialchars((string) $dom['ip_resuelta'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:auto; display:flex; gap:8px; flex-wrap:wrap;">
                <a href="http://admin.clinicasanjose.lan:5380" target="_blank" rel="noopener" class="ca-btn-sm ca-btn-sm--primary">
                    Panel Technitium DNS (:5380) &nearr;
                </a>
                <a href="Controllers_dns.php" class="ca-btn-sm">
                    Gestionar Registros DNS &rarr;
                </a>
            </div>
        </article>
    </section>

    <!-- Bitácora del Sistema y Alertas Automáticas SMTP -->
    <section class="ca-card" id="bitacora" aria-label="Bitácora de eventos y alertas">
        <div class="ca-card-header" style="flex-wrap:wrap;">
            <h2 class="ca-card-title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>Bitácora de Monitoreo, Control SSH y Alertas SMTP</span>
            </h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="http://mail.clinicasanjose.lan:37410" target="_blank" rel="noopener" class="ca-btn-sm">
                    Abrir Visor Web Mailpit (:37410) &nearr;
                </a>
            </div>
        </div>

        <div class="ca-table-wrap">
            <table class="ca-activity-table" id="tablaBitacoraSrv">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Evento</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bitacoraReciente as $ev): ?>
                        <?php
                        $tipoEv = (string) ($ev['tipo'] ?? 'info');
                        $claseDot = $tipoEv === 'ok'
                            ? 'ca-event-dot--ok'
                            : ($tipoEv === 'alerta' ? 'ca-event-dot--alerta' : 'ca-event-dot--info');
                        $fechaEv = isset($ev['fecha']) ? date('d/m/Y H:i:s', strtotime((string) $ev['fecha']) ?: time()) : $fechaConsulta;
                        ?>
                        <tr data-searchable>
                            <td>
                                <span class="ca-event-time">
                                    <span class="ca-event-dot <?= $claseDot ?>"></span>
                                    <span><?= htmlspecialchars($fechaEv, ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars((string) ($ev['evento'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars((string) ($ev['detalle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = <?= json_encode($csrfToken) ?>;
    var toast = document.getElementById('caSrvToast');

    function mostrarToast(ok, msg) {
        if (!toast) return;
        toast.className = 'ca-toast is-visible ' + (ok ? 'ca-toast--ok' : 'ca-toast--error');
        toast.textContent = msg;
    }

    function insertarFilaBitacora(fecha, evento, detalle, ok) {
        var tbody = document.querySelector('#tablaBitacoraSrv tbody');
        if (!tbody) return;
        var tr = document.createElement('tr');
        var dotClass = ok ? 'ca-event-dot--ok' : 'ca-event-dot--alerta';
        tr.innerHTML = '<td><span class="ca-event-time"><span class="ca-event-dot ' + dotClass + '"></span><span>' +
            fecha + '</span></span></td><td><strong>' + evento + '</strong></td><td>' + detalle + '</td>';
        tbody.insertBefore(tr, tbody.firstChild);
    }

    // Control Remoto SSH Asíncrono (Iniciar, Reiniciar, Detener httpd / mariadb en VM 2)
    document.querySelectorAll('[data-ssh-service]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var servicio = btn.getAttribute('data-ssh-service');
            var accion = btn.getAttribute('data-ssh-action');
            var card = document.querySelector('[data-service-card="' + servicio + '"]');
            var groupBtns = card ? card.querySelectorAll('[data-ssh-service]') : [btn];

            groupBtns.forEach(function (b) { b.disabled = true; });
            mostrarToast(true, 'Ejecutando comando SSH remoto (systemctl ' + accion + ' ' + servicio + ') en rayosx.clinicasanjose.lan...');

            var fd = new FormData();
            fd.append('accion_ajax', 'control_ssh');
            fd.append('servicio', servicio);
            fd.append('accion', accion);
            fd.append('csrf_token', csrfToken);

            fetch('Controllers_servidores.php', {
                method: 'POST',
                body: fd
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                groupBtns.forEach(function (b) { b.disabled = false; });
                mostrarToast(Boolean(data.ok), data.mensaje || 'Respuesta recibida.');

                if (card && data.estado_servicio) {
                    var esActivo = data.estado_servicio === 'activo';
                    card.classList.remove('servicio-card--activo', 'servicio-card--caido');
                    card.classList.add(esActivo ? 'servicio-card--activo' : 'servicio-card--caido');

                    var badge = card.querySelector('[data-role="badge"]');
                    var badgeText = card.querySelector('[data-role="badge-text"]');
                    var latenciaEl = card.querySelector('[data-role="latencia"]');
                    var detalleEl = card.querySelector('[data-role="detalle"]');

                    if (badge) {
                        badge.classList.remove('estado-badge--activo', 'estado-badge--caido');
                        badge.classList.add(esActivo ? 'estado-badge--activo' : 'estado-badge--caido');
                    }
                    if (badgeText) {
                        badgeText.textContent = esActivo ? 'ONLINE · Activo' : 'OFFLINE · Caído';
                    }
                    if (latenciaEl) {
                        latenciaEl.textContent = (data.tiempo_ms !== null && data.tiempo_ms !== undefined)
                            ? (data.tiempo_ms + ' ms')
                            : 'Inalcanzable';
                    }
                    if (detalleEl && data.detalle) {
                        detalleEl.textContent = data.detalle;
                    }
                }

                if (data.evento_fecha) {
                    insertarFilaBitacora(data.evento_fecha, 'Control SSH', data.mensaje || '', Boolean(data.ok));
                }
            })
            .catch(function () {
                groupBtns.forEach(function (b) { b.disabled = false; });
                mostrarToast(false, 'Error de red al comunicarse con el controlador SSH.');
            });
        });
    });

    // Botón Enviar Alerta de Prueba SMTP
    var btnAlerta = document.getElementById('btnAlertaPruebaSrv');
    if (btnAlerta) {
        btnAlerta.addEventListener('click', function () {
            btnAlerta.disabled = true;
            var textoOriginal = btnAlerta.textContent;
            btnAlerta.textContent = 'Enviando a Mailpit...';

            var fd = new FormData();
            fd.append('accion_ajax', 'alerta_prueba');
            fd.append('csrf_token', csrfToken);

            fetch('Controllers_servidores.php', {
                method: 'POST',
                body: fd
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btnAlerta.disabled = false;
                btnAlerta.textContent = textoOriginal;
                mostrarToast(Boolean(data.ok), data.mensaje || 'Operación completada.');
                if (data.evento_fecha) {
                    insertarFilaBitacora(data.evento_fecha, 'Alerta SMTP', data.mensaje || '', Boolean(data.ok));
                }
            })
            .catch(function () {
                btnAlerta.disabled = false;
                btnAlerta.textContent = textoOriginal;
                mostrarToast(false, 'No se pudo enviar la solicitud de alerta de prueba.');
            });
        });
    }
});
</script>
