<?php
/**
 * historico.php · Eventos que ya pasaron: datos finales (asistentes confirmados)
 * y, al abrir uno, los comentarios "Así se vivió". Los visitantes ven solo unos cuantos.
 */
require_once __DIR__ . '/includes/eventos.php';
require_once __DIR__ . '/includes/feed.php';

$logueado = esta_logueado();
$filtros = leer_filtros();
[$eventos, $total] = buscar_eventos($filtros, $logueado ? null : LIMITE_VISITANTES, true);
$tematicas = obtener_tematicas();

$titulo = 'Histórico';
$scripts = ['feed.js'];
require __DIR__ . '/includes/header.php';
?>
<h1 style="font-size:2.2rem">Histórico de eventos</h1>
<p style="color:var(--texto-suave);margin-bottom:24px">Mira cuántas personas asistieron y cómo se vivió cada fiesta.</p>

<form method="get" action="historico.php" class="filtros" id="form-filtros">
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
  <label><span class="sr-only">Ordenar</span>
    <select class="input" name="orden">
      <option value="fecha" <?= $filtros['orden'] === 'fecha' ? 'selected' : '' ?>>Más recientes primero</option>
      <option value="populares" <?= $filtros['orden'] === 'populares' ? 'selected' : '' ?>>Más asistentes</option>
    </select>
  </label>
  <button class="btn btn-sm" type="submit">Filtrar</button>
  <?php if (hay_filtros($filtros)): ?><a class="btn btn-borde btn-sm" href="historico.php">Limpiar</a><?php endif; ?>
</form>

<?php if (!$eventos): ?>
  <div class="caja"><p style="margin:0">Todavía no hay eventos finalizados.</p></div>
<?php else: ?>
  <div class="rejilla">
    <?php foreach ($eventos as $ev) tarjeta_evento($ev, $logueado); ?>
  </div>
<?php endif; ?>

<?php if (!$logueado && $total > LIMITE_VISITANTES): ?>
  <div class="caja banner-login">
    <h3>¿Quieres ver más?</h3>
    <p>Hay <?= $total - count($eventos) ?> evento(s) más. Inicia sesión para verlos y leer cómo se vivieron.</p>
    <a class="btn" href="login.php">Iniciar sesión</a>
    <a class="btn btn-borde" href="registro.php">Crear cuenta</a>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
