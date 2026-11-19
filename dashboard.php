<?php
declare(strict_types=1);

require_once __DIR__ . '/Config/auth.php';

// Protección: sin sesión válida se redirige a login.php y no se ejecuta nada más.
require_auth();

// Evita que el navegador muestre una copia en caché del panel tras cerrar sesión.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
$nombreUsuario = is_string($_SESSION['usuario_nombre'] ?? null) ? $_SESSION['usuario_nombre'] : 'Administrador TI';
$username = is_string($_SESSION['usuario_username'] ?? null) ? $_SESSION['usuario_username'] : '';
$email = is_string($_SESSION['usuario_email'] ?? null) ? $_SESSION['usuario_email'] : '';
$rol = is_string($_SESSION['usuario_rol'] ?? null) ? $_SESSION['usuario_rol'] : 'admin_ti';
$ipSesion = is_string($_SESSION['ip_sesion'] ?? null) ? $_SESSION['ip_sesion'] : 'desconocida';

$inicioSesion = isset($_SESSION['inicio_sesion']) && is_int($_SESSION['inicio_sesion'])
    ? date('d/m/Y H:i:s', $_SESSION['inicio_sesion'])
    : 'N/D';

$ultimaActividad = isset($_SESSION['ultima_actividad']) && is_int($_SESSION['ultima_actividad'])
    ? date('d/m/Y H:i:s', $_SESSION['ultima_actividad'])
    : 'N/D';

$csrfToken = get_csrf_token();

// Datos que usan los componentes comunes (header, navbar)
$tituloPagina = 'Panel de Control | ClinicAdmin';
$cssExtra = ['assets/css/dashboard.css'];
$paginaActiva = 'inicio';
$baseUrl = '';

include __DIR__ . '/Views/header.php';
?>
<div class="dash">

    
    <?php include __DIR__ . '/Views/partials/navbar.php'; ?>

    <section class="dash-banner">
        <div>
            <h1>Bienvenido, <?= e($nombreUsuario) ?></h1>
            <p>Panel de control de la infraestructura médica.</p>
        </div>
        <span class="dash-pill">
            <span class="dash-pill-dot" aria-hidden="true"></span>
            Sesión activa
        </span>
    </section>

    <h2 class="dash-section-title">Módulos</h2>
    <section class="dash-modules" aria-label="Módulos disponibles">
        <a class="dash-module" href="Controllers/Controllers_servidores.php">
            <h3>Estado de servidores</h3>
            <p>Monitoreo de servicios (web, base de datos, SSH, correo) mediante sockets.</p>
        </a>
        <a class="dash-module" href="Controllers/Controllers_dns.php">
            <h3>Registros DNS</h3>
            <p>Alta, edición y eliminación de registros A, CNAME y MX.</p>
        </a>
    </section>

    <h2 class="dash-section-title">Sesión actual</h2>
    <section class="dash-card" aria-label="Estado de la sesión">
        <div class="dash-card-head">
            <h2>Datos del usuario autenticado</h2>
            <span>Información de la sesión activa</span>
        </div>

        <div class="dash-grid">
            <div class="dash-item"><span class="dash-label">ID de usuario</span><span class="dash-value">#<?= e((string) $usuarioId) ?></span></div>
            <div class="dash-item"><span class="dash-label">Usuario</span><span class="dash-value"><?= e($username) ?></span></div>
            <div class="dash-item"><span class="dash-label">Correo electrónico</span><span class="dash-value"><?= e($email) ?></span></div>
            <div class="dash-item"><span class="dash-label">Rol asignado</span><span class="dash-value"><span class="dash-badge"><?= e($rol) ?></span></span></div>
            <div class="dash-item"><span class="dash-label">Inicio de sesión</span><span class="dash-value"><?= e($inicioSesion) ?></span></div>
            <div class="dash-item"><span class="dash-label">Última actividad</span><span class="dash-value"><?= e($ultimaActividad) ?></span></div>
            <div class="dash-item"><span class="dash-label">Dirección IP</span><span class="dash-value"><?= e($ipSesion) ?></span></div>
        </div>
    </section>
</div>
<?php include __DIR__ . '/Views/footer.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control | ClinicAdmin</title>
    <style>
        :root {
            --blue-primary: #1a56db;
            --blue-hover: #1447bd;
            --blue-deep: #0b2d7a;
            --blue-mid: #1648b8;
            --surface-bg: #f8fafc;
            --surface-white: #ffffff;
            --surface-soft: #eef4ff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-subtle: #e2e8f0;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --success-text: #047857;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #b91c1c;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--surface-bg);
            color: var(--text-dark);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .navbar {
            background: var(--surface-white);
            border-bottom: 1px solid var(--border-subtle);
            padding: 1rem 2rem;
        }

        .navbar-inner {
            max-width: 920px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--text-dark);
            letter-spacing: -0.01em;
        }

        .brand-plus-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--surface-soft);
            color: var(--blue-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 500;
            line-height: 1;
        }

        .btn-logout {
            height: 38px;
            padding: 0 1rem;
            border-radius: 9px;
            border: 1px solid var(--border-subtle);
            background: var(--surface-white);
            color: #334155;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .btn-logout:hover {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger-text);
        }

        .main-content {
            max-width: 920px;
            margin: 2.25rem auto;
            padding: 0 1.5rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--blue-deep) 0%, var(--blue-mid) 55%, var(--blue-primary) 100%);
            color: #ffffff;
            border-radius: 16px;
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .welcome-banner h1 {
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }

        .welcome-banner p {
            font-size: 0.92rem;
            color: rgba(239, 246, 255, 0.86);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.42rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-size: 0.82rem;
            font-weight: 600;
            color: #ffffff;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #34d399;
        }

        .session-card {
            background: var(--surface-white);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 1.75rem 2rem;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        }

        .session-card-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 1.1rem;
            margin-bottom: 1.1rem;
            border-bottom: 1px solid var(--border-subtle);
        }

        .session-card-header h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .session-card-header span {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .session-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem 1.5rem;
        }

        .session-item {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            background: var(--surface-bg);
            border: 1px solid var(--border-subtle);
        }

        .session-label {
            display: block;
            font-size: 0.76rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 0.3rem;
        }

        .session-value {
            font-size: 0.94rem;
            font-weight: 600;
            color: var(--text-dark);
            word-break: break-word;
        }

        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--surface-soft);
            color: var(--blue-primary);
        }

        .state-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .page-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 0.9rem 1.25rem;
            }

            .welcome-banner,
            .session-card {
                padding: 1.35rem;
            }
        }
    </style>
</head>
</html>
