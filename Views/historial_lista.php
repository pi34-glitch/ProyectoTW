<?php
declare(strict_types=1);

require __DIR__ . '/header.php';
require __DIR__ . '/partials/navbar.php';

$filtros = $filtros ?? ['tipo' => '', 'estado' => '', 'fecha_desde' => '', 'fecha_hasta' => '', 'buscar' => ''];
$eventos = $eventos ?? [];
$resumenHistorial = $resumenHistorial ?? ['total' => 0, 'monitoreo' => 0, 'incidente' => 0, 'alerta' => 0, 'comando' => 0];
$bdDisponible = $bdDisponible ?? false;
?>
<main class="hist-page">
    <div class="hist-header">
        <div>
            <h1>Historial y Trazabilidad Técnica</h1>
            <p class="hist-subtitle">
                Eventos de monitoreo, incidentes, alertas y comandos ejecutados — Clínica Don Juan
            </p>
        </div>
    </div>

    <?php if (!$bdDisponible): ?>
        <div class="hist-aviso" role="alert">
            No se pudo conectar con la base de datos para consultar el historial.
        </div>
    <?php endif; ?>

    <section class="hist-kpis" aria-label="Resumen de eventos">
        <div class="hist-kpi">
            <span class="hist-kpi-label">Eventos mostrados</span>
            <span class="hist-kpi-val"><?= (int) $resumenHistorial['total'] ?></span>
        </div>
        <div class="hist-kpi">
            <span class="hist-kpi-label">Monitoreos</span>
            <span class="hist-kpi-val"><?= (int) $resumenHistorial['monitoreo'] ?></span>
        </div>
        <div class="hist-kpi">
            <span class="hist-kpi-label">Incidentes</span>
            <span class="hist-kpi-val"><?= (int) $resumenHistorial['incidente'] ?></span>
        </div>
        <div class="hist-kpi">
            <span class="hist-kpi-label">Alertas</span>
            <span class="hist-kpi-val"><?= (int) $resumenHistorial['alerta'] ?></span>
        </div>
        <div class="hist-kpi">
            <span class="hist-kpi-label">Comandos SSH</span>
            <span class="hist-kpi-val"><?= (int) $resumenHistorial['comando'] ?></span>
        </div>
    </section>

    <form method="GET" action="Controllers_historial.php" class="hist-filtros">
        <div class="hist-campo">
            <label for="filtro-tipo">Tipo de evento</label>
            <select id="filtro-tipo" name="tipo">
                <option value="" <?= $filtros['tipo'] === '' ? 'selected' : '' ?>>Todos</option>
                <option value="monitoreo" <?= $filtros['tipo'] === 'monitoreo' ? 'selected' : '' ?>>Monitoreo</option>
                <option value="incidente" <?= $filtros['tipo'] === 'incidente' ? 'selected' : '' ?>>Incidentes</option>
                <option value="alerta" <?= $filtros['tipo'] === 'alerta' ? 'selected' : '' ?>>Alertas</option>
                <option value="comando" <?= $filtros['tipo'] === 'comando' ? 'selected' : '' ?>>Comandos SSH</option>
            </select>
        </div>

        <div class="hist-campo">
            <label for="filtro-estado">Estado</label>
            <select id="filtro-estado" name="estado">
                <option value="" <?= $filtros['estado'] === '' ? 'selected' : '' ?>>Todos</option>
                <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="caido" <?= $filtros['estado'] === 'caido' ? 'selected' : '' ?>>Caído</option>
                <option value="abierto" <?= $filtros['estado'] === 'abierto' ? 'selected' : '' ?>>Abierto</option>
                <option value="resuelto" <?= $filtros['estado'] === 'resuelto' ? 'selected' : '' ?>>Resuelto</option>
                <option value="enviado" <?= $filtros['estado'] === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                <option value="fallido" <?= $filtros['estado'] === 'fallido' ? 'selected' : '' ?>>Fallido</option>
                <option value="exitoso" <?= $filtros['estado'] === 'exitoso' ? 'selected' : '' ?>>Exitoso</option>
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
            <label for="filtro-buscar">Buscar</label>
            <input id="filtro-buscar" type="text" name="buscar" placeholder="Servicio, host o detalle..." value="<?= htmlspecialchars($filtros['buscar'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="hist-acciones">
            <button type="submit">Filtrar</button>
            <a href="Controllers_historial.php" class="hist-btn-limpiar">Limpiar</a>
        </div>
    </form>

    <div class="hist-tabla-wrap">
        <?php if ($eventos === []): ?>
            <p class="hist-vacio">No hay eventos registrados que coincidan con los filtros seleccionados.</p>
        <?php else: ?>
            <table class="hist-tabla">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Tipo</th>
                        <th>Servicio / Origen</th>
                        <th>Destino</th>
                        <th>Detalle</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $ev): ?>
                        <?php
                        $est = (string) ($ev['estado'] ?? '');
                        $claseEstado = 'hist-estado--warn';
                        if (in_array($est, ['activo', 'resuelto', 'cerrado', 'enviado', 'exitoso'], true)) {
                            $claseEstado = 'hist-estado--ok';
                        } elseif (in_array($est, ['caido', 'error', 'fallido'], true)) {
                            $claseEstado = 'hist-estado--down';
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $ev['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="hist-badge hist-badge--<?= htmlspecialchars((string) $ev['tipo'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) $ev['tipo_label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string) $ev['origen'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $ev['destino'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $ev['detalle'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="hist-badge <?= $claseEstado ?>">
                                    <?= htmlspecialchars($est, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
