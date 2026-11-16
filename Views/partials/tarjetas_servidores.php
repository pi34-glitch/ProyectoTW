<?php
declare(strict_types=1);

$servidores = $servidores ?? [];
$resumenServidores = $resumenServidores ?? ['total' => 0, 'activos' => 0, 'caidos' => 0];
$modoDemoServidores = $modoDemoServidores ?? false;
$fechaConsulta = $fechaConsulta ?? date('d/m/Y H:i:s');
?>
<section class="servidores-modulo" aria-label="Estado de servidores y servicios">
    <div class="servidores-header">
        <div class="servidores-title-group">
            <h1>Estado de Servidores y Servicios</h1>
            <p class="servidores-subtitle">
                Monitoreo en tiempo real mediante sockets PHP (<code>fsockopen</code>) — Clínica Don Juan
            </p>
        </div>

        <div class="servidores-actions">
            <span class="ultima-actualizacion">
                Última comprobación: <?= htmlspecialchars((string) $fechaConsulta, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <a href="?actualizar=1" class="btn-actualizar">
                &#x21bb; Actualizar estado
            </a>
        </div>
    </div>

    <?php if ($modoDemoServidores): ?>
        <div class="aviso-demo" role="status">
            <strong>Modo vista previa (sin BD conectada):</strong> Se están consultando los servicios locales de ejemplo mediante <code>fsockopen</code> real. Cuando la tabla <code>servicios</code> esté en MySQL, se leerá automáticamente desde allí.
        </div>
    <?php endif; ?>

    <div class="resumen-kpi">
        <div class="kpi-box">
            <span class="kpi-label">Total servicios</span>
            <div class="kpi-valor"><?= (int) $resumenServidores['total'] ?></div>
        </div>
        <div class="kpi-box">
            <span class="kpi-label">Activos</span>
            <div class="kpi-valor kpi-valor--ok"><?= (int) $resumenServidores['activos'] ?></div>
        </div>
        <div class="kpi-box">
            <span class="kpi-label">Caídos</span>
            <div class="kpi-valor kpi-valor--down"><?= (int) $resumenServidores['caidos'] ?></div>
        </div>
    </div>

    <div class="servidores-grid">
        <?php foreach ($servidores as $srv): ?>
            <?php
            $esActivo = ($srv['estado'] ?? '') === 'activo';
            $claseCard = $esActivo ? 'servicio-card--activo' : 'servicio-card--caido';
            $claseBadge = $esActivo ? 'estado-badge--activo' : 'estado-badge--caido';
            $textoEstado = $esActivo ? 'Activo' : 'Caído';
            $latencia = isset($srv['tiempo_ms']) && $srv['tiempo_ms'] !== null
                ? ((int) $srv['tiempo_ms']) . ' ms'
                : 'N/D';
            ?>
            <article class="servicio-card <?= $claseCard ?>">
                <div class="servicio-card-top">
                    <div>
                        <span class="servicio-categoria">
                            <?= htmlspecialchars((string) $srv['categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <h2 class="servicio-nombre">
                            <?= htmlspecialchars((string) $srv['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                    </div>

                    <span class="estado-badge <?= $claseBadge ?>">
                        <span class="estado-dot" aria-hidden="true"></span>
                        <?= $textoEstado ?>
                    </span>
                </div>

                <div class="servicio-meta">
                    <div class="meta-item">
                        <span class="meta-label">IP / Host y Puerto</span>
                        <span class="meta-valor">
                            <?= htmlspecialchars((string) $srv['host'], ENT_QUOTES, 'UTF-8') ?>:<?= (int) $srv['puerto'] ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Protocolo</span>
                        <span class="meta-valor">
                            <?= htmlspecialchars((string) $srv['protocolo'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Tiempo de respuesta</span>
                        <span class="meta-valor"><?= htmlspecialchars($latencia, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">ID Servicio</span>
                        <span class="meta-valor">#<?= (int) $srv['id'] ?></span>
                    </div>
                </div>

                <div class="servicio-detalle">
                    <?= htmlspecialchars((string) $srv['detalle'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
