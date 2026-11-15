<?php
/**
 * prueba.php · Página temporal para comprobar que el Módulo 0 funciona.
 * Se elimina cuando empecemos el feed (Módulo 3).
 */
$titulo = 'Prueba del Módulo 0';
require __DIR__ . '/includes/header.php';

// 1) Comprobar la conexión a la base de datos y contar filas
$conexion_ok = true;
$conteos = [];
try {
    foreach (['usuarios', 'tematicas', 'eventos', 'asistencias', 'favoritos', 'comentarios'] as $tabla) {
        $conteos[$tabla] = (int) db()->query("SELECT COUNT(*) FROM $tabla")->fetchColumn();
    }
} catch (PDOException $ex) {
    $conexion_ok = false;
    $error_bd = $ex->getMessage();
}
?>
<section class="hero">
  <h1>Encuentra tu próximo plan</h1>
  <p>Eventos y fiestas de la UAEH. Solo para estudiantes con correo institucional.</p>
  <a class="btn btn-rosa" href="#">Ver eventos</a>
  <a class="btn btn-borde" href="#">Publicar evento</a>
</section>

<div class="caja">
  <h2>Resultados de la prueba</h2>

  <?php if ($conexion_ok): ?>
    <div class="alerta alerta-exito">Conexión a MySQL correcta.</div>
    <ul>
      <?php foreach ($conteos as $tabla => $n): ?>
        <li>Tabla <strong><?= e($tabla) ?></strong>: <?= $n ?> registro(s)</li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <div class="alerta alerta-error">No se pudo conectar: <?= e($error_bd) ?></div>
  <?php endif; ?>

  <p><strong>Sesión:</strong> <?= esta_logueado() ? 'iniciada como ' . e(usuario_actual()['nombre']) : 'sin iniciar (visitante)' ?></p>
  <p><strong>Token CSRF:</strong> <?= e(substr(csrf_token(), 0, 12)) ?>… (se genera por sesión)</p>
  <p><strong>Correo válido?</strong>
     alumno@uaeh.edu.mx → <?= correo_institucional_valido('alumno@uaeh.edu.mx') ? 'sí' : 'no' ?> ·
     alumno@gmail.com → <?= correo_institucional_valido('alumno@gmail.com') ? 'sí' : 'no' ?></p>
  <p><strong>Formato de fecha:</strong> <?= e(fecha_es('2026-11-21')) ?> · <?= e(hora_12('21:00:00')) ?> · <?= e(costo_texto(80)) ?> · <?= e(costo_texto(0)) ?></p>

  <h3>Tarjeta de ejemplo</h3>
  <div class="rejilla">
    <article class="tarjeta">
      <div class="tarjeta-img"><span class="etiqueta">Halloween</span><span class="etiqueta">+18</span></div>
      <div class="tarjeta-info">
        <h3>Halloween en la Terraza</h3>
        <p>Sáb 21 Nov · 9:00 PM</p>
        <p>Terraza Los Pinos, Pachuca</p>
        <div class="tarjeta-pie"><strong>$80</strong><span>96 confirmados</span></div>
      </div>
    </article>
  </div>

  <h3>Formulario con CSRF</h3>
  <form method="post" action="prueba.php">
    <?= csrf_campo() ?>
    <div class="campo"><label for="x">Campo de ejemplo</label><input class="input" id="x" name="x"></div>
    <button class="btn" type="submit">Enviar</button>
  </form>
  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <?= csrf_valido()
        ? '<div class="alerta alerta-exito">Token CSRF válido.</div>'
        : '<div class="alerta alerta-error">Token CSRF inválido.</div>' ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
