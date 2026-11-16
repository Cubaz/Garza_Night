<?php
/**
 * index.php · Página principal: feed de eventos con filtros y orden.
 * Los filtros viajan por la URL (GET), así que cada búsqueda tiene su propio enlace.
 * Los visitantes sin cuenta solo ven LIMITE_VISITANTES eventos.
 */
require_once __DIR__ . '/includes/eventos.php';
require_once __DIR__ . '/includes/feed.php';

$logueado = esta_logueado();
$filtros = leer_filtros();
[$eventos, $total] = buscar_eventos($filtros, $logueado ? null : LIMITE_VISITANTES);
$tematicas = obtener_tematicas();

$titulo = 'Inicio';
$scripts = ['feed.js'];
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Eventos y fiestas de la UAEH</h1>
  <p>Encuentra tu próximo plan o publica el tuyo. Solo para estudiantes con correo institucional.</p>
  <?php if ($logueado): ?>
    <a class="btn btn-rosa" href="crear-evento.php">Publicar evento</a>
    <a class="btn btn-borde" href="mis-eventos.php">Mis eventos</a>
  <?php else: ?>
    <a class="btn btn-rosa" href="registro.php">Crear cuenta</a>
    <a class="btn btn-borde" href="login.php">Iniciar sesión</a>
  <?php endif; ?>
</section>

<form method="get" action="index.php" class="filtros" id="form-filtros">
  <label class="filtro-busqueda"><span class="sr-only">Buscar</span>
    <input class="input" type="search" name="q" maxlength="100" placeholder="Buscar por título o lugar…" value="<?= e($filtros['q']) ?>">
  </label>
  <label><span class="sr-only">Temática</span>
    <select class="input" name="tematica">
      <option value="0">Todas las temáticas</option>
      <?php foreach ($tematicas as $t): ?>
        <option value="<?= (int) $t['id'] ?>" <?= $filtros['tematica'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label><span class="sr-only">Fecha</span>
    <input class="input" type="date" name="fecha" value="<?= e($filtros['fecha']) ?>">
  </label>
  <label><span class="sr-only">Costo</span>
    <select class="input" name="costo">
      <option value="">Cualquier costo</option>
      <option value="gratis" <?= $filtros['costo'] === 'gratis' ? 'selected' : '' ?>>Gratis</option>
      <option value="pago" <?= $filtros['costo'] === 'pago' ? 'selected' : '' ?>>De paga</option>
    </select>
  </label>
  <label><span class="sr-only">Ordenar</span>
    <select class="input" name="orden">
      <option value="fecha" <?= $filtros['orden'] === 'fecha' ? 'selected' : '' ?>>Ordenar: fecha</option>
      <option value="populares" <?= $filtros['orden'] === 'populares' ? 'selected' : '' ?>>Más populares</option>
      <option value="recientes" <?= $filtros['orden'] === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
    </select>
  </label>
  <button class="btn btn-sm" type="submit">Filtrar</button>
  <?php if (hay_filtros($filtros)): ?><a class="btn btn-borde btn-sm" href="index.php">Limpiar</a><?php endif; ?>
</form>

<h2 style="margin-bottom:18px">Próximos eventos</h2>

<?php if (!$eventos): ?>
  <div class="caja"><p style="margin:0">No encontramos eventos con esos filtros.</p></div>
<?php else: ?>
  <div class="rejilla">
    <?php foreach ($eventos as $ev) tarjeta_evento($ev, $logueado); ?>
  </div>
<?php endif; ?>

<?php if (!$logueado && $total > LIMITE_VISITANTES): ?>
  <div class="caja banner-login">
    <h3>¿Quieres ver más eventos?</h3>
    <p>Hay <?= $total - count($eventos) ?> evento(s) más. Inicia sesión con tu correo institucional para verlos todos y apuntarte.</p>
    <a class="btn" href="login.php">Iniciar sesión</a>
    <a class="btn btn-borde" href="registro.php">Crear cuenta</a>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
