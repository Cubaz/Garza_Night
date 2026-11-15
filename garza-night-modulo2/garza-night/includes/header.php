<?php
/**
 * header.php · Cabecera común: <head>, barra de navegación y mensajes flash.
 * Cada página hace, antes de incluirlo:
 *     $titulo = 'Nombre de la página';      (opcional)
 *     require __DIR__ . '/includes/header.php';
 * y al final incluye footer.php.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/funciones.php';

$titulo_completo = isset($titulo) ? $titulo . ' · ' . APP_NOMBRE : APP_NOMBRE;
$u = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo_completo) ?></title>
  <meta name="csrf" content="<?= csrf_token() ?>"><!-- lo leerá JavaScript para los fetch -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/variables.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/componentes.css">
</head>
<body>
<header class="navbar">
  <a class="logo" href="<?= BASE_URL ?>/index.php" aria-label="<?= e(APP_NOMBRE) ?>">
    <svg width="30" height="30" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M31 6c-5-2-10 1-10 7 0 6 6 7 6 13 0 4-4 7-9 7"/><path d="M31 6l6 2-6 2"/><path d="M18 33v5M23 33v5"/>
    </svg>
    <span><?= e(APP_NOMBRE) ?></span>
  </a>

  <button class="menu-btn" id="menu-btn" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu">☰</button>

  <nav class="menu" id="menu">
    <a href="<?= BASE_URL ?>/index.php">Inicio</a>
    <a href="<?= BASE_URL ?>/historico.php">Histórico</a>
    <?php if ($u): ?>
      <a href="<?= BASE_URL ?>/crear-evento.php">Crear evento</a>
      <a href="<?= BASE_URL ?>/mis-eventos.php"><?= es_admin() ? 'Todos los eventos' : 'Mis eventos' ?></a>
      <a href="<?= BASE_URL ?>/perfil.php">Perfil</a>
      <?php if (es_admin()): ?><a href="<?= BASE_URL ?>/admin/index.php">Admin</a><?php endif; ?>
      <span class="menu-usuario">
        <span class="avatar" aria-hidden="true"><?= e(iniciales($u['nombre'])) ?></span>
        <?= e($u['nombre']) ?>
      </span>
      <form method="post" action="<?= BASE_URL ?>/logout.php" class="form-inline">
        <?= csrf_campo() ?>
        <button class="btn btn-borde btn-sm" type="submit">Cerrar sesión</button>
      </form>
    <?php else: ?>
      <a class="btn btn-borde btn-sm" href="<?= BASE_URL ?>/login.php">Iniciar sesión</a>
      <a class="btn btn-sm" href="<?= BASE_URL ?>/registro.php">Registrarse</a>
    <?php endif; ?>
  </nav>
</header>

<main class="contenedor-principal">
<?php foreach (flash_obtener() as $m): ?>
  <div class="alerta alerta-<?= e($m['tipo']) ?>" role="status"><?= e($m['texto']) ?></div>
<?php endforeach; ?>
