<?php
/**
 * mis-eventos.php · PROVISIONAL. Lista los eventos del usuario (todos, si es admin)
 * con los botones Editar / Cancelar / Eliminar. En el Módulo 5 esta lista pasa al perfil.
 */
require_once __DIR__ . '/includes/eventos.php';
requerir_login();

$sql = 'SELECT e.id, e.titulo, e.fecha, e.estado, e.capacidad, u.nombre AS organizador,
               (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS asistentes
        FROM eventos e JOIN usuarios u ON u.id = e.organizador_id';
if (es_admin()) {
    $st = db()->query($sql . ' ORDER BY e.fecha DESC');
} else {
    $st = db()->prepare($sql . ' WHERE e.organizador_id = ? ORDER BY e.fecha DESC');
    $st->execute([usuario_actual()['id']]);
}
$eventos = $st->fetchAll();

$titulo = es_admin() ? 'Todos los eventos' : 'Mis eventos';
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px">
  <h1 style="font-size:2rem;margin:0"><?= e($titulo) ?></h1>
  <a class="btn" href="crear-evento.php">+ Crear evento</a>
</div>

<?php if (!$eventos): ?>
  <div class="caja"><p>Todavía no hay eventos. ¡Publica el primero!</p></div>
<?php else: ?>
<div class="tabla-envoltorio">
  <table class="tabla">
    <thead>
      <tr>
        <th>Evento</th><?php if (es_admin()): ?><th>Organizador</th><?php endif; ?>
        <th>Fecha</th><th>Estado</th><th>Asistentes</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($eventos as $ev): [$texto_estado, $clase_estado] = estado_evento($ev); ?>
      <tr>
        <td><strong><?= e($ev['titulo']) ?></strong></td>
        <?php if (es_admin()): ?><td><?= e($ev['organizador']) ?></td><?php endif; ?>
        <td><?= e(fecha_es($ev['fecha'])) ?></td>
        <td><span class="etiqueta <?= $clase_estado ?>"><?= $texto_estado ?></span></td>
        <td><?= (int) $ev['asistentes'] ?><?= $ev['capacidad'] ? ' / ' . (int) $ev['capacidad'] : '' ?></td>
        <td>
          <div class="acciones">
            <?php if ($ev['estado'] !== 'cancelado'): ?>
              <a class="btn btn-borde btn-sm" href="editar-evento.php?id=<?= (int) $ev['id'] ?>">Editar</a>
            <?php endif; ?>
            <?php if ($ev['estado'] === 'activo' && !evento_finalizado($ev['fecha'])): ?>
              <form class="form-inline" method="post" action="cancelar-evento.php"
                    data-confirmar="¿Cancelar este evento? Se quitarán las asistencias registradas.">
                <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                <button class="btn btn-borde btn-sm" type="submit">Cancelar</button>
              </form>
            <?php endif; ?>
            <form class="form-inline" method="post" action="eliminar-evento.php"
                  data-confirmar="¿Eliminar este evento definitivamente? No se puede deshacer.">
              <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
              <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
