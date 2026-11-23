<?php
declare(strict_types=1);

$tituloPagina = $tituloPagina ?? 'Estado de Servidores y Nodos | ClinicAdmin';
$cssExtra = ['../assets/css/dashboard.css', '../assets/css/servidores.css'];
$paginaActiva = 'servidores';
$baseUrl = $baseUrl ?? '../';

require __DIR__ . '/header.php';
require __DIR__ . '/partials/navbar.php';
?>
<main class="servidores-page">
    <?php require __DIR__ . '/partials/tarjetas_servidores.php'; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
