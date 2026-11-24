<?php
declare(strict_types=1);

$tituloPagina = $tituloPagina ?? 'Historial y Bitácora | ClinicAdmin';
$cssExtra = ['../assets/css/dashboard.css', '../assets/css/servidores.css', '../assets/css/historial.css'];
$paginaActiva = 'historial';
$baseUrl = $baseUrl ?? '../';

require __DIR__ . '/header.php';
require __DIR__ . '/partials/navbar.php';

$filtros = $filtros ?? ['tipo' => '', 'evento' => '', 'fecha_desde' => '', 'fecha_hasta' => '', 'buscar' => ''];
$registros = $registros ?? [];
$resumenHistorial = $resumenHistorial ?? ['total' => 0, 'ok' => 0, 'info' => 0, 'alerta' => 0];
$eventosDisponibles = $eventosDisponibles ?? [];
$bdDisponible = $bdDisponible ?? false;
?>
<main class="servidores-page">
    <section class="hist-modulo" aria-label="Historial y trazabilidad técnica">
        <div class="hist-header">
            <div class="hist-title-wrap">
                <span class="hist-title-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <polyline points="12 7 12 12 15 15"/>
                    </svg>
                </span>
                <div>
                    <h1>Historial y Trazabilidad Técnica</h1>
                    <p class="hist-subtitle">
                        Bitácora de monitoreo, alertas SMTP, control SSH y eventos del sistema — Clínica Don Juan
                    </p>
                </div>
            </div>

            <div class="hist-header-actions">
                <span class="hist-pill-count">
                    Mostrando <?= (int) $resumenHistorial['total'] ?> registro(s)
                </span>
                <a href="Controllers_historial.php" class="btn-actualizar">
                    &#x21bb; Actualizar
                </a>
            </div>
        </div>

        <?php if (!$bdDisponible): ?>
            <div class="hist-aviso" role="alert">
                No se pudo consultar la tabla <code>bitacora_eventos</code> en la base de datos.
            </div>
        <?php endif; ?>

        <section class="hist-kpis" aria-label="Resumen de bitácora">
            <div class="hist-kpi">
                <div>
                    <span class="hist-kpi-label">Total mostrados</span>
                    <span class="hist-kpi-val"><?= (int) $resumenHistorial['total'] ?></span>
                </div>
                <span class="hist-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h10"/>
                    </svg>
                </span>
            </div>

            <div class="hist-kpi hist-kpi--ok">
                <div>
                    <span class="hist-kpi-label">Correctos (OK)</span>
                    <span class="hist-kpi-val hist-kpi-val--ok"><?= (int) $resumenHistorial['ok'] ?></span>
                </div>
                <span class="hist-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </span>
            </div>

            <div class="hist-kpi hist-kpi--info">
                <div>
                    <span class="hist-kpi-label">Informativos</span>
                    <span class="hist-kpi-val hist-kpi-val--info"><?= (int) $resumenHistorial['info'] ?></span>
                </div>
                <span class="hist-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="12" y1="10" x2="12" y2="16"/>
                        <circle cx="12" cy="7" r="1" fill="currentColor"/>
                    </svg>
                </span>
            </div>

            <div class="hist-kpi hist-kpi--alerta">
                <div>
                    <span class="hist-kpi-label">Alertas</span>
                    <span class="hist-kpi-val hist-kpi-val--alerta"><?= (int) $resumenHistorial['alerta'] ?></span>
                </div>
                <span class="hist-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <circle cx="12" cy="17" r="1" fill="currentColor"/>
                    </svg>
                </span>
            </div>
        </section>

        <section class="hist-panel" aria-label="Filtros de búsqueda">
            <div class="hist-panel-top">
                <h2 class="hist-panel-title">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    <span>Filtros de búsqueda</span>
                </h2>

                <div class="hist-quick-pills">
                    <a href="Controllers_historial.php" class="hist-quick-pill<?= $filtros['tipo'] === '' ? ' is-active' : '' ?>">Todos</a>
                    <a href="Controllers_historial.php?tipo=ok" class="hist-quick-pill<?= $filtros['tipo'] === 'ok' ? ' is-active' : '' ?>">Solo OK</a>
                    <a href="Controllers_historial.php?tipo=info" class="hist-quick-pill<?= $filtros['tipo'] === 'info' ? ' is-active' : '' ?>">Solo Info</a>
                    <a href="Controllers_historial.php?tipo=alerta" class="hist-quick-pill<?= $filtros['tipo'] === 'alerta' ? ' is-active' : '' ?>">Solo Alertas</a>
                </div>
            </div>

            <form method="GET" action="Controllers_historial.php" class="hist-filtros">
                <div class="hist-campo">
                    <label for="filtro-tipo">Tipo</label>
                    <select id="filtro-tipo" name="tipo">
                        <option value="" <?= $filtros['tipo'] === '' ? 'selected' : '' ?>>Todos</option>
                        <option value="ok" <?= $filtros['tipo'] === 'ok' ? 'selected' : '' ?>>OK</option>
                        <option value="info" <?= $filtros['tipo'] === 'info' ? 'selected' : '' ?>>Info</option>
                        <option value="alerta" <?= $filtros['tipo'] === 'alerta' ? 'selected' : '' ?>>Alerta</option>
                    </select>
                </div>

                <div class="hist-campo">
                    <label for="filtro-evento">Evento / Módulo</label>
                    <select id="filtro-evento" name="evento">
                        <option value="" <?= $filtros['evento'] === '' ? 'selected' : '' ?>>Todos</option>
                        <?php foreach ($eventosDisponibles as $evNombre): ?>
                            <option value="<?= htmlspecialchars($evNombre, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $filtros['evento'] === $evNombre ? 'selected' : '' ?>>
                                <?= htmlspecialchars($evNombre, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="hist-campo">
                    <label for="filtro-desde">Desde</label>
                    <input id="filtro-desde" type="date" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="hist-campo">
                    <label for="filtro-hasta">Hasta</label>
                    <input id="filtro-hasta" type="date" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="hist-campo">
                    <label for="filtro-buscar">Buscar detalle</label>
                    <input id="filtro-buscar" type="text" name="buscar" placeholder="Palabra clave..." value="<?= htmlspecialchars($filtros['buscar'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="hist-acciones">
                    <button type="submit" class="hist-btn-filtrar">Filtrar</button>
                    <a href="Controllers_historial.php" class="hist-btn-limpiar">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="hist-panel" aria-label="Listado de eventos">
            <div class="hist-panel-top">
                <h2 class="hist-panel-title">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <span>Registro cronológico de eventos</span>
                </h2>
            </div>

            <div class="hist-tabla-wrap">
                <?php if ($registros === []): ?>
                    <p class="hist-vacio">No hay registros en la bitácora que coincidan con los filtros seleccionados.</p>
                <?php else: ?>
                    <table class="hist-tabla">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha y hora</th>
                                <th>Evento</th>
                                <th>Tipo</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $reg): ?>
                                <?php
                                $tipo = (string) ($reg['tipo'] ?? 'info');
                                $claseTipo = in_array($tipo, ['ok', 'info', 'alerta'], true) ? 'hist-badge--' . $tipo : 'hist-badge--info';
                                ?>
                                <tr>
                                    <td class="hist-id">#<?= (int) $reg['id'] ?></td>
                                    <td class="hist-fecha"><?= htmlspecialchars((string) $reg['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="hist-evento-tag">
                                            <?= htmlspecialchars((string) $reg['evento'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="hist-badge <?= $claseTipo ?>">
                                            <span class="hist-dot" aria-hidden="true"></span>
                                            <?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="hist-detalle"><?= htmlspecialchars((string) $reg['detalle'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </section>
</main>
<?php require __DIR__ . '/footer.php'; ?>
