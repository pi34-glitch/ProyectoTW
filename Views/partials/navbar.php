<?php
    declare(strict_types=1);

    /**
     * Barra de navegación del panel (solo para usuarios con sesión).
     *   $baseUrl       '' desde la raíz, '../' desde /Controllers.
     *   $paginaActiva  'inicio' | 'servidores' | 'dns'
     *   $nombreUsuario, $csrfToken
     */
    $baseUrl = $baseUrl ?? '';
    $paginaActiva = $paginaActiva ?? '';
    $nombreUsuario = $nombreUsuario ?? 'Administrador TI';
    $csrfToken = $csrfToken ?? '';

    $enlaces = [
        'inicio'     => ['Inicio', 'dashboard.php'],
        'servidores' => ['Servidores', 'Controllers/Controllers_servidores.php'],
        'dns'        => ['DNS', 'Controllers/Controllers_dns.php'],
        'historial'  => ['Historial', 'Controllers/Controllers_historial.php'],
    ];
?>
<nav class="dash-navbar" aria-label="Navegación principal">
    <div class="dash-brand">
        <span class="dash-brand-mark" aria-hidden="true">+</span>
        <span>ClinicAdmin</span>
    </div>

    <ul class="dash-links">
        <?php foreach ($enlaces as $clave => [$texto, $ruta]): ?>
            <li>
                <a href="<?= htmlspecialchars($baseUrl . $ruta, ENT_QUOTES, 'UTF-8') ?>"
                   class="dash-link<?= $paginaActiva === $clave ? ' is-active' : '' ?>"
                   <?= $paginaActiva === $clave ? 'aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <form action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>logout.php" method="POST" class="dash-logout-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="dash-logout">Cerrar sesión</button>
    </form>
</nav>