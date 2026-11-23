<?php
declare(strict_types=1);

$baseUrl = $baseUrl ?? '';
$tituloPagina = $tituloPagina ?? 'Panel de Control | ClinicAdmin';
$cssExtra = $cssExtra ?? [];
$rutaDashboardCss = $baseUrl . 'assets/css/dashboard.css';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) $tituloPagina, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($rutaDashboardCss, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($cssExtra as $hoja): ?>
        <?php if ($hoja !== $rutaDashboardCss && $hoja !== 'assets/css/dashboard.css' && $hoja !== '../assets/css/dashboard.css'): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars((string) $hoja, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body class="ca-body">
