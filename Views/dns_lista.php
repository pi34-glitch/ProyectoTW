<?php
declare(strict_types=1);

$tituloPagina = $tituloPagina ?? 'Registros DNS (clinicasanjose.lan) | ClinicAdmin';
$cssExtra = ['../assets/css/dashboard.css', '../assets/css/servidores.css'];
$paginaActiva = 'dns';
$baseUrl = $baseUrl ?? '../';
$csrfToken = $csrfToken ?? (function_exists('get_csrf_token') ? get_csrf_token() : '');
$dnsActivo = $dnsActivo ?? (function_exists('verificar_resolucion_dns_activa') ? verificar_resolucion_dns_activa() : []);
$resumenDns = $resumenDns ?? ['total' => count($registros ?? []), 'A' => 0, 'CNAME' => 0, 'MX' => 0, 'PTR' => 0];
$eForm = $editando ?? [];

include __DIR__ . '/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<main class="dash">
    <!-- Hero Banner de la Página DNS (Misma estética del Dashboard) -->
    <section class="ca-hero-banner" aria-label="Administración de zona DNS">
        <div class="ca-hero-left">
            <span class="ca-hero-accent" aria-hidden="true"></span>
            <div>
                <h1>Administración DNS &middot; <code>clinicasanjose.lan</code></h1>
                <p>
                    Servidor Primario Technitium DNS (<code>192.168.50.10:53</code>) y Verificación Activa<br>
                    Gestión de registros A, CNAME y MX de la red médica ZeroTier
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
            <span class="ca-status-pill <?= !empty($dnsActivo['puerto_53_activo']) ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                <span class="ca-status-pill-dot"></span>
                <span>Puerto 53 TCP: <?= !empty($dnsActivo['puerto_53_activo']) ? 'ONLINE' : 'OFFLINE' ?></span>
            </span>
            <a href="http://admin.clinicasanjose.lan:5380" target="_blank" rel="noopener" class="btn-actualizar" style="margin-top:6px;">
                Abrir Panel Technitium DNS (:5380) &nearr;
            </a>
        </div>
    </section>

    <!-- Mensajes de éxito o error -->
    <?php if (!empty($mensajeExito)): ?>
        <div class="ca-toast ca-toast--ok is-visible" role="status">
            <?= htmlspecialchars((string) $mensajeExito, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php foreach (($errores ?? []) as $err): ?>
        <div class="ca-toast ca-toast--error is-visible" role="alert">
            <?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endforeach; ?>

    <!-- KPIs de Registros DNS + Verificación Activa gethostbyname() -->
    <section class="ca-ops-grid" aria-label="Resumen de Zona y Verificación Activa DNS">
        <!-- Resumen de Registros en Base de Datos -->
        <article class="ca-card">
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18"/>
                    </svg>
                    <span>Zona Interna: <code>clinicasanjose.lan</code></span>
                </h2>
                <span class="ca-status-pill ca-status-pill--ok">
                    <?= (int) ($resumenDns['total'] ?? 0) ?> registros
                </span>
            </div>

            <div class="resumen-kpi" style="margin-top:6px;">
                <div class="kpi-box">
                    <div>
                        <span class="kpi-label">Registros A (IPv4)</span>
                        <div class="kpi-valor"><?= (int) ($resumenDns['A'] ?? 0) ?></div>
                    </div>
                    <span class="ca-pill-badge ca-pill-badge--blue">Host &rarr; IP</span>
                </div>
                <div class="kpi-box">
                    <div>
                        <span class="kpi-label">Registros CNAME</span>
                        <div class="kpi-valor"><?= (int) ($resumenDns['CNAME'] ?? 0) ?></div>
                    </div>
                    <span class="ca-pill-badge ca-pill-badge--blue">Alias</span>
                </div>
                <div class="kpi-box">
                    <div>
                        <span class="kpi-label">Registros MX</span>
                        <div class="kpi-valor"><?= (int) ($resumenDns['MX'] ?? 0) ?></div>
                    </div>
                    <span class="ca-pill-badge ca-pill-badge--blue">Correo</span>
                </div>
                <div class="kpi-box">
                    <div>
                        <span class="kpi-label">Registros PTR</span>
                        <div class="kpi-valor"><?= (int) ($resumenDns['PTR'] ?? 0) ?></div>
                    </div>
                    <span class="ca-pill-badge ca-pill-badge--blue">IP &rarr; Host</span>
                </div>
            </div>
        </article>

        <!-- Verificación Activa de Resolución DNS (gethostbyname) requerida en 3.2 -->
        <article class="ca-card">
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    <span>Verificación Activa de Resolución (<code>gethostbyname</code>)</span>
                </h2>
                <span class="ca-status-pill <?= !empty($dnsActivo['todos_validos']) ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                    <span class="ca-status-pill-dot"></span>
                    <span><?= !empty($dnsActivo['todos_validos']) ? 'Resolución Verificada' : 'Resolución Pendiente' ?></span>
                </span>
            </div>

            <div class="ca-table-wrap">
                <table class="ca-activity-table">
                    <thead>
                        <tr>
                            <th>Dominio Configurado</th>
                            <th>IP ZeroTier Esperada</th>
                            <th>Resultado <code>gethostbyname()</code></th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($dnsActivo['dominios'] ?? []) as $dom): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $dom['fqdn'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><code><?= htmlspecialchars((string) $dom['ip_esperada'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><code><?= htmlspecialchars((string) $dom['ip_resuelta'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td>
                                    <span class="ca-status-pill <?= !empty($dom['valido']) ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                                        <span class="ca-status-pill-dot"></span>
                                        <span><?= !empty($dom['valido']) ? 'OK' : 'No coincide' ?></span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <!-- Grilla: Tabla de Registros DNS y Formulario de Alta / Edición -->
    <section class="ca-grid-bottom" style="grid-template-columns: 1.55fr 1fr; align-items:start;">

        <!-- Listado de Registros DNS -->
        <article class="ca-card">
            <div class="ca-card-header" style="flex-wrap:wrap;">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    <span>Registros Configurados en la Zona</span>
                </h2>

                <form method="get" style="display:inline-flex; align-items:center; gap:8px; margin:0; padding:0; border:0; background:transparent;">
                    <label for="filtroTipoDns" style="font-size:0.84rem; font-weight:700; color:var(--ca-text-sub);">Filtrar por tipo:</label>
                    <select id="filtroTipoDns" name="tipo" onchange="this.form.submit()"
                            style="min-height:36px; padding:6px 12px; border-radius:9px; border:1px solid #cbd8e8; font-weight:600; color:var(--ca-text-main); background:#f8fafd;">
                        <option value="">Todos (<?= (int) ($resumenDns['total'] ?? 0) ?>)</option>
                        <?php foreach (['A', 'CNAME', 'MX', 'PTR'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($filtro ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="ca-table-wrap">
                <table class="ca-activity-table">
                    <thead>
                        <tr>
                            <th>Nombre (Host)</th>
                            <th>Tipo</th>
                            <th>Valor / Destino</th>
                            <th>Prioridad</th>
                            <th>TTL</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:22px; color:var(--ca-text-sub);">
                                    No se encontraron registros DNS con el filtro seleccionado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registros as $r): ?>
                                <?php $esActivo = ($r['estado'] ?? 'activo') === 'activo'; ?>
                                <tr data-searchable>
                                    <td><strong><?= htmlspecialchars((string) $r['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>
                                        <span class="ca-pill-badge ca-pill-badge--blue">
                                            <?= htmlspecialchars((string) $r['tipo'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars((string) $r['valor'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td><?= htmlspecialchars((string) ($r['prioridad'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $r['ttl'] ?>s</td>
                                    <td>
                                        <span class="ca-status-pill <?= $esActivo ? 'ca-status-pill--ok' : 'ca-status-pill--down' ?>">
                                            <span class="ca-status-pill-dot"></span>
                                            <span><?= $esActivo ? 'Activo' : 'Inactivo' ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:inline-flex; align-items:center; gap:6px;">
                                            <a href="?editar=<?= (int) $r['id'] ?>" class="ca-btn-sm">Editar</a>
                                            <form method="post" style="display:inline; margin:0; padding:0; border:0; background:transparent;"
                                                  onsubmit="return confirm('¿Eliminar este registro DNS?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                <button type="submit" class="ca-ssh-btn ca-ssh-btn--stop" style="min-height:30px; padding:5px 10px;">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <!-- Formulario para Crear o Editar Registro DNS -->
        <aside class="ca-card" aria-label="Formulario de registro DNS">
            <div class="ca-card-header">
                <h2 class="ca-card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span><?= !empty($eForm['id']) ? 'Editar Registro DNS' : 'Nuevo Registro DNS' ?></span>
                </h2>
                <?php if (!empty($eForm['id'])): ?>
                    <a href="Controllers_dns.php" class="ca-card-link">Cancelar</a>
                <?php endif; ?>
            </div>

            <form method="post" style="display:flex; flex-direction:column; gap:12px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="accion" value="<?= !empty($eForm['id']) ? 'editar' : 'crear' ?>">
                <input type="hidden" name="id" value="<?= (int) ($eForm['id'] ?? 0) ?>">

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--ca-text-sub); margin-bottom:4px;">
                        Nombre / Subdominio o IP Inversa (<code>@</code>, <code>rayosx</code>, <code>laboratorio</code>, <code>20.50.168.192.in-addr.arpa</code>)
                    </label>
                    <input name="nombre"
                           placeholder="Ej. rayosx, laboratorio o 20.50.168.192.in-addr.arpa"
                           value="<?= htmlspecialchars((string) ($eForm['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           required
                           style="width:100%; min-height:40px; padding:8px 12px; border:1px solid #cbd8e8; border-radius:9px; font:inherit;">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--ca-text-sub); margin-bottom:4px;">
                            Tipo de Registro
                        </label>
                        <select name="tipo"
                                style="width:100%; min-height:40px; padding:8px 12px; border:1px solid #cbd8e8; border-radius:9px; font:inherit; background:#fff;">
                            <?php foreach (['A', 'CNAME', 'MX', 'PTR'] as $t): ?>
                                <option value="<?= $t ?>" <?= (($eForm['tipo'] ?? 'A') === $t) ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--ca-text-sub); margin-bottom:4px;">
                            Estado
                        </label>
                        <select name="estado"
                                style="width:100%; min-height:40px; padding:8px 12px; border:1px solid #cbd8e8; border-radius:9px; font:inherit; background:#fff;">
                            <?php foreach (['activo', 'inactivo'] as $s): ?>
                                <option value="<?= $s ?>" <?= (($eForm['estado'] ?? 'activo') === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--ca-text-sub); margin-bottom:4px;">
                        Valor (IPv4 para A, o FQDN con punto final para CNAME/MX)
                    </label>
                    <input name="valor"
                           placeholder="Ej. 192.168.50.20 o mail.clinicasanjose.lan."
                           value="<?= htmlspecialchars((string) ($eForm['valor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           required
                           style="width:100%; min-height:40px; padding:8px 12px; border:1px solid #cbd8e8; border-radius:9px; font:inherit;">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--ca-text-sub); margin-bottom:4px;">
                            Prioridad (Solo MX)
                        </label>
                        <input name="prioridad"
                               type="number"
                               placeholder="Ej. 10"
                               value="<?= htmlspecialchars((string) ($eForm['prioridad'] ?? $eForm['prio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               style="width:100%; min-height:40px; padding:8px 12px; border:1px solid #cbd8e8; border-radius:9px; font:inherit;">
                    </div>

                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--ca-text-sub); margin-bottom:4px;">
                            TTL (Segundos)
                        </label>
                        <input name="ttl"
                               type="number"
                               value="<?= (int) ($eForm['ttl'] ?? 3600) ?>"
                               style="width:100%; min-height:40px; padding:8px 12px; border:1px solid #cbd8e8; border-radius:9px; font:inherit;">
                    </div>
                </div>

                <button type="submit" class="btn-actualizar" style="justify-content:center; width:100%; margin-top:4px; min-height:42px;">
                    <?= !empty($eForm['id']) ? 'Guardar Cambios' : 'Registrar en Zona DNS' ?>
                </button>
            </form>
        </aside>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
