<?php
declare(strict_types=1);

require_once __DIR__ . '/Config/database.php';
require_once __DIR__ . '/Config/auth.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

/**
 * Redirige al formulario de inicio de sesión guardando el error y el último usuario ingresado.
 */
function redirect_with_error(string $message, string $identificador = ''): never
{
    $_SESSION['flash_error'] = $message;
    $_SESSION['old_identificador'] = $identificador;
    header('Location: login.php');
    exit;
}

// 1. Verificar token CSRF recibido por $_POST
$csrfToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
if (!verify_csrf_token($csrfToken)) {
    redirect_with_error('Solicitud inválida o expirada. Por favor, recarga la página e intenta nuevamente.');
}

// 2. Leer y sanitizar variables $_POST
$identificador = isset($_POST['identificador']) && is_string($_POST['identificador'])
    ? trim($_POST['identificador'])
    : '';
$password = isset($_POST['password']) && is_string($_POST['password'])
    ? $_POST['password']
    : '';

if ($identificador === '' || $password === '') {
    redirect_with_error('Debes ingresar tu usuario o correo electrónico y tu contraseña.', $identificador);
}

try {
    $pdo = get_db_connection();

    // 3. Buscar usuario por username o email usando consulta preparada
    $stmt = $pdo->prepare(
        'SELECT id, username, email, password_hash, nombre_completo, rol, activo, intentos_fallidos, bloqueado_hasta
         FROM usuarios
         WHERE username = :identificador_user OR email = :identificador_email
         LIMIT 1'
    );
    $stmt->execute([
        'identificador_user' => $identificador,
        'identificador_email' => $identificador,
    ]);
    $user = $stmt->fetch();

    if ($user === false) {
        redirect_with_error('Credenciales inválidas. Verifica tu usuario y contraseña.', $identificador);
    }

    // 4. Verificar si la cuenta está activa
    if ((int) $user['activo'] !== 1) {
        redirect_with_error('Esta cuenta se encuentra deshabilitada. Contacta al Administrador Principal.', $identificador);
    }

    // 5. Verificar si existe un bloqueo temporal por intentos fallidos
    if (!empty($user['bloqueado_hasta'])) {
        $lockedUntil = strtotime((string) $user['bloqueado_hasta']);
        if ($lockedUntil !== false && $lockedUntil > time()) {
            $minutesRemaining = (int) ceil(($lockedUntil - time()) / 60);
            redirect_with_error(
                "Cuenta bloqueada temporalmente por seguridad. Intenta nuevamente en {$minutesRemaining} minuto(s).",
                $identificador
            );
        }
    }

    // 6. Verificar contraseña con password_verify
    if (!password_verify($password, (string) $user['password_hash'])) {
        $failedAttempts = (int) $user['intentos_fallidos'] + 1;
        $lockUntilSql = null;

        if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
            $lockUntilSql = date('Y-m-d H:i:s', time() + (LOCKOUT_MINUTES * 60));
        }

        $updateFailStmt = $pdo->prepare(
            'UPDATE usuarios
             SET intentos_fallidos = :intentos,
                 bloqueado_hasta = :bloqueado_hasta
             WHERE id = :id'
        );
        $updateFailStmt->execute([
            'intentos' => $failedAttempts,
            'bloqueado_hasta' => $lockUntilSql,
            'id' => (int) $user['id'],
        ]);

        if ($lockUntilSql !== null) {
            redirect_with_error(
                'Se superó el máximo de intentos fallidos. Tu cuenta fue bloqueada por ' . LOCKOUT_MINUTES . ' minutos.',
                $identificador
            );
        }

        redirect_with_error('Credenciales inválidas. Verifica tu usuario y contraseña.', $identificador);
    }

    // 7. Rehash automático si el algoritmo por defecto de PHP fue actualizado
    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $rehashStmt = $pdo->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $rehashStmt->execute([
            'hash' => $newHash,
            'id' => (int) $user['id'],
        ]);
    }

    // 8. Restablecer intentos fallidos y registrar último acceso
    $updateSuccessStmt = $pdo->prepare(
        'UPDATE usuarios
         SET intentos_fallidos = 0,
             bloqueado_hasta = NULL,
             ultimo_acceso = NOW()
         WHERE id = :id'
    );
    $updateSuccessStmt->execute(['id' => (int) $user['id']]);

    // 9. Establecer sesión segura ($_SESSION) y redirigir al panel de control
    unset($_SESSION['flash_error'], $_SESSION['old_identificador']);
    establish_user_session($user);

    try {
        $infoPtr = obtener_cliente_ptr();
        $stmtBitacora = $pdo->prepare(
            'INSERT INTO bitacora_eventos (fecha, evento, tipo, detalle)
             VALUES (NOW(), :evento, :tipo, :detalle)'
        );
        $stmtBitacora->execute([
            'evento'  => 'Sistema',
            'tipo'    => 'info',
            'detalle' => sprintf(
                'Sesión iniciada por %s desde host PTR: %s (%s).',
                (string) $user['nombre_completo'],
                $infoPtr['host'],
                $infoPtr['ip']
            ),
        ]);
    } catch (Throwable $ignored) {
    }

    header('Location: dashboard.php');
    exit;
} catch (PDOException $exception) {
    error_log('Error de autenticación en procesar_login.php: ' . $exception->getMessage());
    redirect_with_error('No se pudo conectar con la base de datos. Verifica la configuración del servidor.', $identificador);
}
