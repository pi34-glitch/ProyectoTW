<?php
declare(strict_types=1);

/**
 * Sidebar lateral izquierdo + Barra superior + Modales de Configuración y Acerca de.
 * Variables esperadas:
 *   $baseUrl       '' desde la raíz, '../' desde /Controllers.
 *   $paginaActiva  'inicio' | 'servidores' | 'dns'
 *   $nombreUsuario, $csrfToken
 */
$baseUrl = $baseUrl ?? '';
$paginaActiva = $paginaActiva ?? 'inicio';
$nombreUsuario = is_string($nombreUsuario ?? null) && trim($nombreUsuario) !== ''
    ? trim($nombreUsuario)
    : (is_string($_SESSION['usuario_nombre'] ?? null) ? $_SESSION['usuario_nombre'] : 'Bianca Morales');
$csrfToken = $csrfToken ?? (function_exists('get_csrf_token') ? get_csrf_token() : '');

// Obtener iniciales del usuario (ej. "Bianca Morales" -> "BM")
$palabrasNombre = preg_split('/\s+/', $nombreUsuario) ?: [];
$iniciales = '';
foreach ($palabrasNombre as $p) {
    if ($p !== '' && mb_strlen($iniciales) < 2) {
        $char = mb_strtoupper(mb_substr($p, 0, 1));
        if (preg_match('/[A-ZÁÉÍÓÚÑ]/u', $char)) {
            $iniciales .= $char;
        }
    }
}
if ($iniciales === '') {
    $iniciales = 'TI';
}

// Datos de sesión para el modal de Configuración / Sesión actual y cabecera PTR (HU-02)
$infoClientePtr = function_exists('obtener_cliente_ptr')
    ? obtener_cliente_ptr()
    : [
        'ip'       => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'host'     => @gethostbyaddr($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?: '127.0.0.1',
        'etiqueta' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ];
$sessIdUsuario = (int) ($_SESSION['usuario_id'] ?? 1);
$sessUsername  = is_string($_SESSION['usuario_username'] ?? null) ? $_SESSION['usuario_username'] : 'admin.ti';
$sessEmail     = is_string($_SESSION['usuario_email'] ?? null) ? $_SESSION['usuario_email'] : 'admin@clinicadonjuan.bo';
$sessRol       = is_string($_SESSION['usuario_rol'] ?? null) ? $_SESSION['usuario_rol'] : 'admin_ti';
$sessIp        = $infoClientePtr['ip'];
$sessHostPtr   = $infoClientePtr['host'];
$sessInicio    = isset($_SESSION['inicio_sesion']) && is_int($_SESSION['inicio_sesion'])
    ? date('d/m/Y H:i:s', $_SESSION['inicio_sesion'])
    : date('d/m/Y H:i:s');
$sessActividad = isset($_SESSION['ultima_actividad']) && is_int($_SESSION['ultima_actividad'])
    ? date('d/m/Y H:i:s', $_SESSION['ultima_actividad'])
    : date('d/m/Y H:i:s');
?>
<!-- Sidebar Lateral Izquierdo (Diseño guía ClinicAdmin) -->
<aside class="ca-sidebar" id="caSidebar" aria-label="Menú lateral principal">
    <div class="ca-sidebar-top">
        <a href="<?= htmlspecialchars($baseUrl . 'dashboard.php', ENT_QUOTES, 'UTF-8') ?>" class="ca-brand-box">
            <div class="ca-cross-logo" aria-hidden="true">
                <svg viewBox="0 0 64 64" width="54" height="54" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="crossGrad" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#4da3ff"/>
                            <stop offset="100%" stop-color="#1e62d0"/>
                        </linearGradient>
                    </defs>
                    <path d="M23 8C23 5.79086 24.7909 4 27 4H37C39.2091 4 41 5.79086 41 8V23H56C58.2091 23 60 24.7909 60 27V37C60 39.2091 58.2091 41 56 41H41V56C41 58.2091 39.2091 60 37 60H27C24.7909 60 23 58.2091 23 56V41H8C5.79086 41 4 39.2091 4 37V27C4 24.7909 5.79086 23 8 23H23V8Z" fill="url(#crossGrad)" stroke="#7cc0ff" stroke-width="1.5"/>
                    <path d="M11 33H21L25 23L30 42L35 28L38 33H53" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="ca-brand-title">ClinicAdmin</div>
            <div class="ca-brand-subtitle">TI Clínica Don Juan</div>
        </a>

        <nav class="ca-nav" aria-label="Navegación principal">
            <ul class="ca-nav-list">
                <li>
                    <a href="<?= htmlspecialchars($baseUrl . 'dashboard.php', ENT_QUOTES, 'UTF-8') ?>"
                       class="ca-nav-link<?= $paginaActiva === 'inicio' ? ' is-active' : '' ?>"
                       <?= $paginaActiva === 'inicio' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">
                            <path d="M12 3.2L3 10.5V20a1 1 0 0 0 1 1h5.5v-6h5v6H20a1 1 0 0 0 1-1v-9.5L12 3.2z"/>
                        </svg>
                        <span>Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($baseUrl . 'Controllers/Controllers_servidores.php', ENT_QUOTES, 'UTF-8') ?>"
                       class="ca-nav-link<?= $paginaActiva === 'servidores' ? ' is-active' : '' ?>"
                       <?= $paginaActiva === 'servidores' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="6" rx="2"/>
                            <rect x="3" y="14" width="18" height="6" rx="2"/>
                            <circle cx="7" cy="7" r="1" fill="currentColor"/>
                            <circle cx="10" cy="7" r="1" fill="currentColor"/>
                            <circle cx="7" cy="17" r="1" fill="currentColor"/>
                            <circle cx="10" cy="17" r="1" fill="currentColor"/>
                        </svg>
                        <span>Servidores</span>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($baseUrl . 'Controllers/Controllers_dns.php', ENT_QUOTES, 'UTF-8') ?>"
                       class="ca-nav-link<?= $paginaActiva === 'dns' ? ' is-active' : '' ?>"
                       <?= $paginaActiva === 'dns' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18"/>
                        </svg>
                        <span>DNS</span>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($baseUrl . 'Controllers/Controllers_historial.php', ENT_QUOTES, 'UTF-8') ?>"
                       class="ca-nav-link<?= $paginaActiva === 'historial' ? ' is-active' : '' ?>"
                       <?= $paginaActiva === 'historial' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <polyline points="12 7 12 12 15 15"/>
                        </svg>
                        <span>Historial</span>
                    </a>
                </li>
            </ul>

            <div class="ca-nav-divider" role="separator"></div>

            <ul class="ca-nav-list ca-nav-secondary">
                <li>
                    <button type="button" class="ca-nav-link ca-nav-btn" data-open-modal="caModalConfig">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <span>Configuración</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="ca-nav-link ca-nav-btn" data-open-modal="caModalAcerca">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <span>Acerca de</span>
                    </button>
                </li>
                <li>
                    <form action="<?= htmlspecialchars($baseUrl . 'logout.php', ENT_QUOTES, 'UTF-8') ?>" method="POST" class="ca-logout-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="ca-nav-link ca-nav-btn ca-logout-btn">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Cerrar sesión</span>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>

    <div class="ca-sidebar-footer">
        <div class="ca-sidebar-building" aria-hidden="true">
            <svg viewBox="0 0 240 120" width="100%" height="100%" preserveAspectRatio="xMidYMax slice">
                <defs>
                    <linearGradient id="skySidebar" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#102a49" stop-opacity="0"/>
                        <stop offset="40%" stop-color="#2c5e9e" stop-opacity="0.35"/>
                        <stop offset="100%" stop-color="#0b1f38" stop-opacity="0.95"/>
                    </linearGradient>
                </defs>
                <rect width="240" height="120" fill="url(#skySidebar)"/>
                <!-- Silueta arquitectónica de la clínica -->
                <g opacity="0.42">
                    <polygon points="20,95 95,48 165,68 165,115 20,115" fill="#9bc5f2"/>
                    <polygon points="95,48 165,68 165,115 95,115" fill="#5e96d4"/>
                    <rect x="165" y="60" width="55" height="55" fill="#78aee6"/>
                    <!-- Ventanas de edificio médico -->
                    <rect x="42" y="68" width="14" height="20" fill="#163b68"/>
                    <rect x="64" y="60" width="16" height="26" fill="#163b68"/>
                    <rect x="110" y="62" width="18" height="28" fill="#123055"/>
                    <rect x="136" y="70" width="16" height="24" fill="#123055"/>
                    <rect x="178" y="72" width="14" height="22" fill="#163b68"/>
                    <rect x="198" y="72" width="12" height="22" fill="#163b68"/>
                </g>
            </svg>
        </div>
        <div class="ca-sidebar-location">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>
            </svg>
            <span>Cochabamba, Bolivia</span>
        </div>
        <p class="ca-sidebar-motto">Infraestructura estable,<br>servicios siempre activos.</p>
    </div>
</aside>

<!-- Barra Superior (Buscador + Estado en línea + Perfil de usuario) -->
<header class="ca-topbar">
    <div class="ca-topbar-left">
        <button type="button" class="ca-mobile-toggle" id="caMobileToggle" aria-label="Abrir menú lateral">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        <div class="ca-search-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <line x1="20" y1="20" x2="16.2" y2="16.2"/>
            </svg>
            <input type="search" id="caSearchInput" placeholder="Buscar en ClinicAdmin..." aria-label="Buscar en ClinicAdmin">
        </div>
    </div>

    <div class="ca-topbar-right">
        <span class="ca-ptr-badge" title="Auditoría de acceso por resolución DNS inversa (PTR - gethostbyaddr)">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            <span>PTR: <strong><?= htmlspecialchars($sessHostPtr, ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($sessIp, ENT_QUOTES, 'UTF-8') ?>)</span>
        </span>

        <span class="ca-topbar-divider" aria-hidden="true"></span>

        <span class="ca-online-indicator">
            <span class="ca-online-dot" aria-hidden="true"></span>
            <span>En línea</span>
        </span>

        <span class="ca-topbar-divider" aria-hidden="true"></span>

        <button type="button" class="ca-user-chip" data-open-modal="caModalConfig" title="Ver detalles de la sesión activa">
            <span class="ca-user-avatar"><?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="ca-user-name"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?></span>
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
    </div>
</header>

<!-- Modal: Configuración y Sesión Actual -->
<div class="ca-modal-backdrop" id="caModalConfig" hidden>
    <div class="ca-modal" role="dialog" aria-modal="true" aria-labelledby="caModalConfigTitle">
        <div class="ca-modal-header">
            <div>
                <h2 id="caModalConfigTitle">Configuración y Sesión Activa</h2>
                <p>Parámetros de autenticación y red ZeroTier de ClinicAdmin</p>
            </div>
            <button type="button" class="ca-modal-close" data-close-modal="caModalConfig" aria-label="Cerrar">&times;</button>
        </div>
        <div class="ca-modal-body">
            <h3 class="ca-modal-subtitle">Datos del Usuario Autenticado (<code>$_SESSION</code>)</h3>
            <div class="ca-meta-grid">
                <div class="ca-meta-box">
                    <span class="ca-meta-label">ID de usuario</span>
                    <span class="ca-meta-value">#<?= htmlspecialchars((string) $sessIdUsuario, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Usuario</span>
                    <span class="ca-meta-value"><?= htmlspecialchars($sessUsername, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Nombre completo</span>
                    <span class="ca-meta-value"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Correo electrónico</span>
                    <span class="ca-meta-value"><?= htmlspecialchars($sessEmail, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Rol asignado</span>
                    <span class="ca-meta-value"><span class="ca-pill-badge ca-pill-badge--blue"><?= htmlspecialchars($sessRol, ENT_QUOTES, 'UTF-8') ?></span></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Dirección IP Cliente</span>
                    <span class="ca-meta-value"><?= htmlspecialchars($sessIp, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Host Cliente (DNS Inversa PTR)</span>
                    <span class="ca-meta-value"><?= htmlspecialchars($sessHostPtr, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Inicio de sesión</span>
                    <span class="ca-meta-value"><?= htmlspecialchars($sessInicio, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Última actividad</span>
                    <span class="ca-meta-value"><?= htmlspecialchars($sessActividad, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <h3 class="ca-modal-subtitle" style="margin-top:18px;">Parámetros Operativos de Red</h3>
            <div class="ca-meta-grid">
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Nodo 1 (Central)</span>
                    <span class="ca-meta-value">admin.clinicasanjose.lan (192.168.50.10:8080)</span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">Nodo 2 (Rayos X)</span>
                    <span class="ca-meta-value">rayosx.clinicasanjose.lan (192.168.50.20:8080)</span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">DNS Primario (Technitium)</span>
                    <span class="ca-meta-value">192.168.50.10:53 (Web :5380)</span>
                </div>
                <div class="ca-meta-box">
                    <span class="ca-meta-label">SMTP / Alertas (Mailpit)</span>
                    <span class="ca-meta-value">127.0.0.1:25 (Visor :37410)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Acerca del Sistema -->
<div class="ca-modal-backdrop" id="caModalAcerca" hidden>
    <div class="ca-modal" role="dialog" aria-modal="true" aria-labelledby="caModalAcercaTitle">
        <div class="ca-modal-header">
            <div>
                <h2 id="caModalAcercaTitle">Acerca de ClinicAdmin</h2>
                <p>Plataforma de Gestión y Monitoreo de Infraestructura Médica — Clínica Don Juan</p>
            </div>
            <button type="button" class="ca-modal-close" data-close-modal="caModalAcerca" aria-label="Cerrar">&times;</button>
        </div>
        <div class="ca-modal-body">
            <p class="ca-modal-text">
                <strong>ClinicAdmin</strong> supervisa en tiempo real la infraestructura distribuida sobre dos servidores
                <strong>Rocky Linux 9</strong> interconectados mediante una red privada <strong>ZeroTier (192.168.50.0/24)</strong>.
            </p>
            <ul class="ca-feature-list">
                <li><strong>Monitoreo Pasivo Multi-Nodo (7 servicios):</strong> Comprobación por sockets TCP (<code>fsockopen</code>) con medición de latencia en milisegundos y timeout de 1.5–2s.</li>
                <li><strong>Resolución DNS Activa:</strong> Validación en vivo de la zona <code>clinicasanjose.lan</code> en Technitium DNS mediante <code>gethostbyname()</code>.</li>
                <li><strong>Control Remoto SSH (VM 2):</strong> Reinicio, detención e inicio remoto de <code>httpd</code> y <code>mariadb</code> en <code>rayosx.clinicasanjose.lan</code> con lista blanca estricta.</li>
                <li><strong>Telemetría y Salud SQL:</strong> Supervisión de hardware (Uptime, RAM, Disco) e integridad PDO de la base de datos médica <code>rayosx_db</code>.</li>
                <li><strong>Alertas Automáticas SMTP:</strong> Notificación inmediata a Mailpit (puerto 25) ante caídas de servicios y registro en bitácora.</li>
            </ul>
            <div class="ca-modal-links">
                <a href="http://admin.clinicasanjose.lan:5380" target="_blank" rel="noopener" class="ca-ext-btn">Panel Technitium DNS (:5380) ↗</a>
                <a href="http://mail.clinicasanjose.lan:37410" target="_blank" rel="noopener" class="ca-ext-btn">Visor Web Mailpit (:37410) ↗</a>
                <a href="http://rayosx.clinicasanjose.lan:8080" target="_blank" rel="noopener" class="ca-ext-btn">Portal Rayos X VM 2 (:8080) ↗</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Apertura y cierre de modales
    document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-open-modal');
            var modal = document.getElementById(id);
            if (modal) {
                modal.hidden = false;
            }
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-close-modal');
            var modal = document.getElementById(id);
            if (modal) {
                modal.hidden = true;
            }
        });
    });

    document.querySelectorAll('.ca-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) {
                backdrop.hidden = true;
            }
        });
    });

    // Menú lateral en móvil
    var mobileBtn = document.getElementById('caMobileToggle');
    var sidebar = document.getElementById('caSidebar');
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', function () {
            sidebar.classList.toggle('is-open');
        });
    }

    // Buscador en vivo en ClinicAdmin
    var searchInput = document.getElementById('caSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = searchInput.value.trim().toLowerCase();
            var items = document.querySelectorAll('[data-searchable]');
            items.forEach(function (el) {
                var text = (el.textContent || '').toLowerCase();
                el.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }
});
</script>
