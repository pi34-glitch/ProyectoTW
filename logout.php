<?php
declare(strict_types=1);

require_once __DIR__ . '/Config/auth.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
if (!verify_csrf_token($csrfToken)) {
    $_SESSION['flash_error'] = 'No se pudo validar el cierre de sesión.';
    header('Location: dashboard.php');
    exit;
}

destroy_user_session();
start_secure_session();
$_SESSION['flash_success'] = 'Has cerrado sesión correctamente.';

header('Location: login.php');
exit;
