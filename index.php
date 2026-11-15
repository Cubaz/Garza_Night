<?php
/**
 * index.php · PROVISIONAL. Solo sirve para comprobar el login y el logout.
 * En el Módulo 3 se reemplaza por el feed real de eventos.
 */
$titulo = 'Inicio';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Eventos y fiestas de la UAEH</h1>
  <p>Encuentra tu próximo plan o publica el tuyo. Solo para estudiantes con correo institucional.</p>
  <?php if (!esta_logueado()): ?>
    <a class="btn btn-rosa" href="registro.php">Crear cuenta</a>
    <a class="btn btn-borde" href="login.php">Iniciar sesión</a>
  <?php endif; ?>
</section>

<div class="caja">
  <?php if ($u): ?>
    <h2>Sesión activa</h2>
    <p>Nombre: <strong><?= e($u['nombre']) ?></strong><br>
       Correo: <?= e($u['correo']) ?><br>
       Rol: <?= e($u['rol']) ?></p>
  <?php else: ?>
    <h2>Eres visitante</h2>
    <p>Aquí aparecerá el feed de eventos (Módulo 3).</p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
