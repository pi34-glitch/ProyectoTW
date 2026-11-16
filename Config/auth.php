<?php
declare(strict_types=1);

const SESSION_IDLE_TIMEOUT_SECONDS = 1800; // 30 minutos de inactividad
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;

/**
 * Inicia o reanuda la sesión PHP con parámetros endurecidos de seguridad.
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('MED_INFRA_SESSID');
    session_start();

    enforce_session_timeout();
}

/**
 * Cierra la sesión si ha superado el tiempo máximo de inactividad.
 */
function enforce_session_timeout(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }

    $now = time();
    $lastActivity = (int) ($_SESSION['ultima_actividad'] ?? $now);

    if (($now - $lastActivity) > SESSION_IDLE_TIMEOUT_SECONDS) {
        destroy_user_session();
        start_secure_session();
        $_SESSION['flash_error'] = 'Tu sesión expiró por inactividad. Inicia sesión nuevamente.';
        return;
    }

    $_SESSION['ultima_actividad'] = $now;
}

/**
 * Genera o recupera el token CSRF de la sesión actual.
 */
function get_csrf_token(): string
{
    start_secure_session();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF recibido vía $_POST en tiempo constante.
 */
function verify_csrf_token(?string $token): bool
{
    start_secure_session();

    if (empty($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verifica si existe una sesión autenticada válida.
 */
function is_authenticated(): bool
{
    start_secure_session();

    return isset($_SESSION['usuario_id'], $_SESSION['usuario_rol']) && (int) $_SESSION['usuario_id'] > 0;
}

/**
 * Exige autenticación para acceder a rutas protegidas como el panel de control.
 */
function require_auth(): void
{
    if (!is_authenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Almacena los datos del usuario autenticado en $_SESSION y regenera el ID de sesión.
 *
 * @param array<string, mixed> $user
 */
function establish_user_session(array $user): void
{
    start_secure_session();
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $user['id'];
    $_SESSION['usuario_username'] = (string) $user['username'];
    $_SESSION['usuario_nombre'] = (string) $user['nombre_completo'];
    $_SESSION['usuario_email'] = (string) $user['email'];
    $_SESSION['usuario_rol'] = (string) $user['rol'];
    $_SESSION['inicio_sesion'] = time();
    $_SESSION['ultima_actividad'] = time();
    $_SESSION['ip_sesion'] = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

    // Rotar el token CSRF tras autenticarse
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Destruye por completo la sesión actual y elimina la cookie asociada.
 */
function destroy_user_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies') !== false && (bool) ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $sessionName = session_name();
        if ($sessionName !== false) {
            setcookie(
                $sessionName,
                '',
                [
                    'expires' => time() - 42000,
                    'path' => (string) ($params['path'] ?? '/'),
                    'domain' => (string) ($params['domain'] ?? ''),
                    'secure' => (bool) ($params['secure'] ?? false),
                    'httponly' => (bool) ($params['httponly'] ?? true),
                    'samesite' => (string) ($params['samesite'] ?? 'Lax'),
                ]
            );
        }
    }

    session_destroy();
}

/**
 * Escapa cadenas para salida segura en HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
