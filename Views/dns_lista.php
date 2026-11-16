<?php include __DIR__ . '/header.php'; ?>
<h2>Registros DNS  clinicasanjose.local</h2>

<form method="get">
  <label>Filtrar por tipo:
    <select name="tipo" onchange="this.form.submit()">
      <option value="">Todos</option>
      <?php foreach (['A', 'CNAME', 'MX'] as $t): ?>
        <option value="<?= $t ?>" <?= $filtro === $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</form>

<?php foreach ($errores as $err): ?>
  <p style="color:#b00020"><?= htmlspecialchars($err) ?></p>
<?php endforeach; ?>

<table>
  <tr><th>Nombre</th><th>Tipo</th><th>Valor</th><th>Prioridad</th><th>TTL</th><th>Estado</th><th>Acciones</th></tr>
  <?php foreach ($registros as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['nombre']) ?></td>
    <td><?= htmlspecialchars($r['tipo']) ?></td>
    <td><?= htmlspecialchars($r['valor']) ?></td>
    <td><?= htmlspecialchars((string)($r['prioridad'] ?? '-')) ?></td>
    <td><?= (int)$r['ttl'] ?></td>
    <td><?= htmlspecialchars($r['estado']) ?></td>
    <td>
      <a href="?editar=<?= (int)$r['id'] ?>">Editar</a>
      <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este registro?')">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button>Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<?php $e = $editando ?? []; ?>
<h3><?= !empty($e['id']) ? 'Editar registro' : 'Nuevo registro' ?></h3>
<form method="post">
  <input type="hidden" name="accion" value="<?= !empty($e['id']) ? 'editar' : 'crear' ?>">
  <input type="hidden" name="id" value="<?= (int)($e['id'] ?? 0) ?>">
  <input name="nombre" placeholder="nombre (@, rayosx)" value="<?= htmlspecialchars((string)($e['nombre'] ?? '')) ?>" required>
  <select name="tipo">
    <?php foreach (['A', 'CNAME', 'MX'] as $t): ?>
      <option <?= (($e['tipo'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <input name="valor" placeholder="IP o host con punto final" value="<?= htmlspecialchars((string)($e['valor'] ?? '')) ?>" required>
  <input name="prioridad" type="number" placeholder="prioridad (solo MX)" value="<?= htmlspecialchars((string)($e['prioridad'] ?? $e['prio'] ?? '')) ?>">
  <input name="ttl" type="number" value="<?= (int)($e['ttl'] ?? 3600) ?>">
  <select name="estado">
    <?php foreach (['activo', 'inactivo'] as $s): ?>
      <option <?= (($e['estado'] ?? 'activo') === $s) ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <button>Guardar</button>
</form>
<?php include __DIR__ . '/footer.php'; ?>