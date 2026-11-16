<?php
declare(strict_types=1);

require_once __DIR__ . '/Config/auth.php';

start_secure_session();

if (is_authenticated()) {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = get_csrf_token();
$flashError = isset($_SESSION['flash_error']) && is_string($_SESSION['flash_error']) ? $_SESSION['flash_error'] : null;
$flashSuccess = isset($_SESSION['flash_success']) && is_string($_SESSION['flash_success']) ? $_SESSION['flash_success'] : null;
$oldIdentificador = isset($_SESSION['old_identificador']) && is_string($_SESSION['old_identificador'])
    ? $_SESSION['old_identificador']
    : '';

unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['old_identificador']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | ClinicAdmin</title>
    <style>
        :root {
            --blue-primary: #1a56db;
            --blue-hover: #1447bd;
            --blue-deep: #0b2d7a;
            --blue-mid: #1648b8;
            --blue-bright: #2563eb;
            --surface-white: #ffffff;
            --surface-soft: #eef4ff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-input: #dbe2ea;
            --border-focus: #1a56db;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
            --error-text: #b91c1c;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --success-text: #047857;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--surface-white);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        .split-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Panel izquierdo azul */
        .hero-panel {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 82% 18%, rgba(59, 130, 246, 0.45) 0%, transparent 45%),
                radial-gradient(circle at 18% 82%, rgba(30, 64, 175, 0.55) 0%, transparent 50%),
                linear-gradient(145deg, var(--blue-deep) 0%, var(--blue-mid) 48%, #1d57d6 100%);
            color: #ffffff;
            padding: 2.5rem 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Anillos decorativos sutiles */
        .hero-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.09);
            pointer-events: none;
        }

        .hero-ring-1 {
            width: 520px;
            height: 520px;
            top: 50%;
            left: 55%;
            transform: translate(-50%, -50%);
        }

        .hero-ring-2 {
            width: 760px;
            height: 760px;
            top: 50%;
            left: 55%;
            transform: translate(-50%, -50%);
        }

        /* Tarjeta decorativa inclinada de fondo */
        .hero-mock-card {
            position: absolute;
            top: 22%;
            right: 12%;
            width: 300px;
            padding: 1.25rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(4px);
            transform: rotate(6deg);
            pointer-events: none;
            z-index: 1;
        }

        .mock-line {
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            margin-bottom: 0.55rem;
        }

        .mock-line.short {
            width: 45%;
        }

        .mock-line.medium {
            width: 72%;
            margin-bottom: 1rem;
        }

        .mock-tiles {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.6rem;
            margin-bottom: 1rem;
        }

        .mock-tile {
            height: 54px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.11);
            border: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.02em;
        }

        .brand-mark {
            position: relative;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: -0.01em;
        }

        .brand-plus-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 500;
            line-height: 1;
        }

        .hero-panel .brand-plus-box {
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 460px;
            margin: auto 0;
        }

        .hero-content h1 {
            font-size: clamp(2.15rem, 3.4vw, 3rem);
            font-weight: 700;
            line-height: 1.08;
            letter-spacing: -0.03em;
            margin-bottom: 1.25rem;
        }

        .hero-content p {
            font-size: 0.98rem;
            line-height: 1.6;
            color: rgba(239, 246, 255, 0.84);
            max-width: 410px;
        }

        .hero-footer {
            position: relative;
            z-index: 2;
            font-size: 0.8rem;
            color: rgba(219, 234, 254, 0.72);
        }

        /* Panel derecho blanco */
        .form-panel {
            background: var(--surface-white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
        }

        .form-container {
            width: 100%;
            max-width: 375px;
        }

        .form-panel .brand-mark {
            margin-bottom: 2.25rem;
            color: var(--text-dark);
        }

        .form-panel .brand-plus-box {
            background: var(--surface-soft);
            color: var(--blue-primary);
        }

        .form-header {
            margin-bottom: 1.75rem;
        }

        .form-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--text-dark);
            margin-bottom: 0.35rem;
        }

        .form-header p {
            font-size: 0.92rem;
            color: var(--text-muted);
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            padding: 0.8rem 0.95rem;
            border-radius: 10px;
            font-size: 0.86rem;
            line-height: 1.4;
            margin-bottom: 1.25rem;
        }

        .alert svg {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.84rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.45rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            height: 44px;
            padding: 0.65rem 0.95rem;
            font-size: 0.94rem;
            color: var(--text-dark);
            background: var(--surface-white);
            border: 1px solid var(--border-input);
            border-radius: 10px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .form-control.has-toggle {
            padding-right: 2.75rem;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12);
        }

        .toggle-password {
            position: absolute;
            right: 0.55rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.35rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s ease, background-color 0.15s ease;
        }

        .toggle-password:hover {
            color: #475569;
            background-color: #f1f5f9;
        }

        .btn-submit {
            width: 100%;
            height: 46px;
            margin-top: 0.45rem;
            border: none;
            border-radius: 10px;
            background: var(--blue-primary);
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            transition: background-color 0.18s ease, transform 0.1s ease;
        }

        .btn-submit:hover {
            background: var(--blue-hover);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .btn-submit:disabled {
            opacity: 0.72;
            cursor: not-allowed;
        }

        .security-note {
            margin-top: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            color: #94a3b8;
        }

        @media (max-width: 920px) {
            .split-shell {
                grid-template-columns: 1fr;
            }

            .hero-panel {
                padding: 2rem 1.75rem;
                min-height: 250px;
            }

            .hero-mock-card {
                display: none;
            }

            .hero-content {
                margin: 1.75rem 0 1.25rem;
            }

            .form-panel {
                padding: 2.5rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <main class="split-shell">
        <section class="hero-panel" aria-label="Presentación ClinicAdmin">
            <div class="hero-ring hero-ring-1" aria-hidden="true"></div>
            <div class="hero-ring hero-ring-2" aria-hidden="true"></div>

            <div class="hero-mock-card" aria-hidden="true">
                <div class="mock-line short"></div>
                <div class="mock-line medium"></div>
                <div class="mock-tiles">
                    <div class="mock-tile">99.8%</div>
                    <div class="mock-tile">7/7</div>
                    <div class="mock-tile">SSH</div>
                </div>
                <div class="mock-line"></div>
                <div class="mock-line short"></div>
            </div>

            <div class="brand-mark">
                <span class="brand-plus-box" aria-hidden="true">+</span>
                <span>ClinicAdmin</span>
            </div>

            <div class="hero-content">
                <h1>La clínica conectada.<br>El cuidado, sin pausas.</h1>
                <p>
                    Infraestructura saludable para que cada equipo de la Clínica Don Juan pueda concentrarse
                    en lo más importante: sus pacientes.
                </p>
            </div>

            <footer class="hero-footer">
                Clínica Don Juan · Cochabamba, Bolivia
            </footer>
        </section>

        <section class="form-panel" aria-label="Formulario de inicio de sesión">
            <div class="form-container">
                <div class="brand-mark">
                    <span class="brand-plus-box" aria-hidden="true">+</span>
                    <span>ClinicAdmin</span>
                </div>

                <header class="form-header">
                    <h2>Bienvenido de nuevo</h2>
                    <p>Ingresa a tu espacio de administración TI.</p>
                </header>

                <?php if ($flashError !== null && $flashError !== ''): ?>
                    <div class="alert alert-error" role="alert">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span><?= e($flashError) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($flashSuccess !== null && $flashSuccess !== ''): ?>
                    <div class="alert alert-success" role="status">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span><?= e($flashSuccess) ?></span>
                    </div>
                <?php endif; ?>

                <form action="procesar_login.php" method="POST" id="loginForm" autocomplete="on" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                    <div class="form-group">
                        <label for="identificador">Correo electrónico</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="identificador"
                                name="identificador"
                                class="form-control"
                                placeholder="admin@clinicadonjuan.bo"
                                value="<?= e($oldIdentificador) ?>"
                                autocomplete="username"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control has-toggle"
                                placeholder="••••••••••••"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="toggle-password"
                                id="togglePassword"
                                aria-label="Mostrar u ocultar contraseña"
                                aria-pressed="false"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span>Ingresar</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <div class="security-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span>Conexión segura · Acceso exclusivo para TI</span>
                </div>
            </div>
        </section>
    </main>

    <script>
        (function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const loginForm = document.getElementById('loginForm');
            const submitButton = document.getElementById('submitBtn');

            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    toggleButton.setAttribute('aria-pressed', String(isHidden));
                });
            }

            if (loginForm && submitButton) {
                loginForm.addEventListener('submit', function () {
                    submitButton.disabled = true;
                    const label = submitButton.querySelector('span');
                    if (label) {
                        label.textContent = 'Ingresando...';
                    }
                });
            }
        })();
    </script>
</body>
</html>
