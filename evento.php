<?php
/**
 * evento.php?id=N · Detalle completo de un evento. Solo usuarios con sesión.
 * Incluye el botón de asistencia (lo maneja assets/js/evento.js) y, para el
 * organizador o el admin, la lista de asistentes. Comentarios: Módulo 5.
 */
require_once __DIR__ . '/includes/eventos.php';
require_once __DIR__ . '/includes/feed.php';
require_once __DIR__ . '/includes/social.php';
requerir_login();

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare(
    'SELECT e.*, t.nombre AS tematica, u.nombre AS organizador,
            (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS asistentes
     FROM eventos e
     JOIN tematicas t ON t.id = e.tematica_id
     JOIN usuarios u  ON u.id = e.organizador_id
     WHERE e.id = ?'
);
$st->execute([$id]);
$ev = $st->fetch();

if (!$ev) {
    http_response_code(404);
    exit('Evento no encontrado.');
}

$cancelado  = $ev['estado'] === 'cancelado';
$finalizado = evento_finalizado($ev['fecha']);
$agotado    = evento_agotado($ev);
$n          = (int) $ev['asistentes'];
$horario    = hora_12($ev['hora_inicio']) . ($ev['hora_fin'] ? ' a ' . hora_12($ev['hora_fin']) : '');

// ¿El usuario actual ya está apuntado? ¿Es el organizador?
$uid = usuario_actual()['id'];
$es_organizador = (int) $ev['organizador_id'] === $uid;
$st = db()->prepare('SELECT 1 FROM asistencias WHERE evento_id = ? AND usuario_id = ?');
$st->execute([$id, $uid]);
$yo_asisto = (bool) $st->fetchColumn();

// Lista de asistentes: solo para quien gestiona el evento (organizador o admin)
$lista = [];
if (puede_gestionar_evento($ev)) {
    $st = db()->prepare(
        'SELECT u.nombre, u.carrera, a.creado_en
         FROM asistencias a JOIN usuarios u ON u.id = a.usuario_id
         WHERE a.evento_id = ? ORDER BY a.creado_en'
    );
    $st->execute([$id]);
    $lista = $st->fetchAll();
}

$es_fav = es_favorito($id, $uid);
$comentarios = obtener_comentarios($id);

/** Imprime una fila "etiqueta: valor" solo si el valor existe. */
function fila_dato(string $etiqueta, ?string $valor): void
{
    if ($valor === null || $valor === '') return;
    echo '<dt>' . e($etiqueta) . '</dt><dd>' . e($valor) . '</dd>';
}

$titulo = $ev['titulo'];
$scripts = ['evento.js', 'social.js'];
require __DIR__ . '/includes/header.php';
?>
<a class="volver" href="index.php">← Volver a eventos</a>

<?php if ($cancelado): ?>
  <div class="alerta alerta-error">Este evento fue cancelado por el organizador.</div>
<?php elseif ($finalizado): ?>
  <div class="alerta alerta-info">Este evento ya terminó.</div>
<?php endif; ?>

<div class="detalle-grid">
  <div>
    <img class="detalle-img" src="<?= e($ev['imagen_url']) ?>" alt="Portada de <?= e($ev['titulo']) ?>">

    <div class="etiquetas">
      <span class="etiqueta"><?= e($ev['tematica']) ?></span>
      <?php if ((int) $ev['edad_minima'] > 0): ?><span class="etiqueta">+<?= (int) $ev['edad_minima'] ?></span><?php endif; ?>
      <?php if ($cancelado): ?>
        <span class="etiqueta etiqueta-cancelado">CANCELADO</span>
      <?php else: ?>
        <span id="etiqueta-agotado" class="etiqueta etiqueta-agotado" <?= $agotado ? '' : 'hidden' ?>>SOLD OUT</span>
      <?php endif; ?>
    </div>

    <h1><?= e($ev['titulo']) ?></h1>
    <?php if ($ev['slogan']): ?><p class="slogan">“<?= e($ev['slogan']) ?>”</p><?php endif; ?>

    <h2>Acerca del evento</h2>
    <p><?= nl2br(e($ev['descripcion'])) ?></p>

    <h2>Detalles</h2>
    <dl class="datos">
      <?php
      fila_dato('Fecha', fecha_es($ev['fecha']) . ' ' . date('Y', strtotime($ev['fecha'])));
      fila_dato('Horario', $horario);
      fila_dato('Lugar', $ev['lugar']);
      fila_dato('Dirección', $ev['direccion']);
      fila_dato('Bebidas', POLITICAS_BEBIDAS[$ev['politica_bebidas']] ?? '');
      fila_dato('Invitados especiales', $ev['invitados_especiales']);
      fila_dato('Actividades', $ev['actividades']);
      fila_dato('Vestimenta', $ev['codigo_vestimenta']);
      fila_dato('Edad mínima', EDADES_MINIMAS[(int) $ev['edad_minima']] ?? '');
      fila_dato('Organiza', $ev['organizador']);
      ?>
    </dl>
    <?php if ((int) $ev['edad_minima'] > 0 || $ev['politica_bebidas'] !== 'no_permitido'): ?>
      <p class="aviso-edad">Este evento puede incluir bebidas alcohólicas: solo para mayores de 18 años.</p>
    <?php endif; ?>

    <?php if (puede_gestionar_evento($ev)): ?>
      <h2>Asistentes confirmados (<?= count($lista) ?>)</h2>
      <?php if (!$lista): ?>
        <p class="suave">Todavía nadie se ha registrado.</p>
      <?php else: ?>
        <ul class="lista-asistentes">
          <?php foreach ($lista as $a): ?>
            <li>
              <?= avatar_html($a['nombre']) ?>
              <span><?= e($a['nombre']) ?>
                <small><?= e($a['carrera'] ?: 'Sin carrera registrada') ?></small></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php endif; ?>
    <h2 id="comentarios"><?= $finalizado ? 'Así se vivió' : 'Comentarios' ?> (<span id="num-comentarios"><?= count($comentarios) ?></span>)</h2>
    <?php if (!$cancelado): ?>
      <form id="form-comentario" class="form-comentario" data-evento="<?= (int) $ev['id'] ?>">
        <label class="sr-only" for="texto-comentario">Tu comentario</label>
        <textarea class="input" id="texto-comentario" rows="2" maxlength="<?= MAX_COMENTARIO ?>" required
                  placeholder="<?= $finalizado ? '¿Cómo te la pasaste? Cuéntalo en pocas palabras.' : 'Escribe un comentario…' ?>"></textarea>
        <div class="comentario-pie">
          <small class="suave"><span id="contador-comentario">0</span>/<?= MAX_COMENTARIO ?></small>
          <button class="btn btn-sm" type="submit">Publicar</button>
        </div>
        <p id="mensaje-comentario" class="mensaje-asistencia" role="status"></p>
      </form>
    <?php endif; ?>
    <ul id="lista-comentarios" class="comentarios">
      <?php foreach ($comentarios as $c): ?>
        <li class="comentario">
          <?= avatar_html($c['nombre']) ?>
          <div>
            <div class="comentario-cabecera"><strong><?= e($c['nombre']) ?></strong> <small><?= e(fecha_comentario($c['creado_en'])) ?></small></div>
            <p><?= e($c['texto']) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <p id="sin-comentarios" class="suave" <?= $comentarios ? 'hidden' : '' ?>>Aún no hay comentarios. ¡Sé el primero!</p>
  </div>

  <aside class="detalle-aside caja">
    <p class="precio"><?= e(costo_texto((float) $ev['costo_cover'])) ?></p>
    <p><strong id="num-asistentes"><?= $n ?></strong>
       <span id="texto-asistentes"><?= $n === 1 ? 'asistente confirmado' : 'asistentes confirmados' ?></span>
      <?php if ($ev['capacidad'] !== null): ?>
        <br><span class="suave">Cupo: <?= (int) $ev['capacidad'] ?> · Quedan
          <span id="quedan"><?= max(0, (int) $ev['capacidad'] - $n) ?></span></span>
      <?php endif; ?>
    </p>

    <?php if ($cancelado): ?>
      <p class="suave">Evento cancelado.</p>
    <?php elseif ($finalizado): ?>
      <p class="suave">Evento finalizado.</p>
    <?php elseif ($es_organizador): ?>
      <p class="suave">Eres el organizador de este evento.</p>
    <?php else:
      if ($yo_asisto)  { $accion = 'cancelar'; $texto_boton = 'Cancelar asistencia'; $clase = 'btn btn-borde btn-block'; $off = ''; }
      elseif ($agotado) { $accion = 'agotado';  $texto_boton = 'SOLD OUT';           $clase = 'btn btn-block';              $off = 'disabled'; }
      else              { $accion = 'apuntar';  $texto_boton = 'Apuntarme';          $clase = 'btn btn-block';              $off = ''; }
    ?>
      <button id="btn-asistencia" class="<?= $clase ?>" type="button"
              data-evento="<?= (int) $ev['id'] ?>" data-accion="<?= $accion ?>" <?= $off ?>><?= $texto_boton ?></button>
      <p id="mensaje-asistencia" class="mensaje-asistencia" role="status"></p>
    <?php endif; ?>

    <button id="btn-favorito" class="btn btn-borde btn-block" style="margin-top:12px" type="button"
            data-evento="<?= (int) $ev['id'] ?>" data-favorito="<?= $es_fav ? '1' : '0' ?>" aria-pressed="<?= $es_fav ? 'true' : 'false' ?>"><?= $es_fav ? '♥ Guardado en favoritos' : '♡ Guardar en favoritos' ?></button>

    <?php if (puede_gestionar_evento($ev) && !$cancelado): ?>
      <a class="btn btn-borde btn-block" style="margin-top:12px" href="editar-evento.php?id=<?= (int) $ev['id'] ?>">Editar evento</a>
    <?php endif; ?>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
