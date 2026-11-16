<?php
/**
 * perfil.php · Perfil del usuario con sesión: sus datos y tres secciones
 * (publicados, a los que asistirá, favoritos). La sección viaja en la URL: ?seccion=...
 */
require_once __DIR__ . '/includes/eventos.php';
require_once __DIR__ . '/includes/feed.php';
require_once __DIR__ . '/includes/social.php';
requerir_login();

$uid = usuario_actual()['id'];
$secciones = [
    'publicados' => 'Publicados',
    'asistire'   => 'Asistiré',
    'favoritos'  => 'Favoritos',
];
$seccion = $_GET['seccion'] ?? 'publicados';
if (!isset($secciones[$seccion])) {
    $seccion = 'publicados';
}

$st = db()->prepare('SELECT nombre, correo, carrera, descripcion, creado_en FROM usuarios WHERE id = ?');
$st->execute([$uid]);
$perfil = $st->fetch();

$eventos = eventos_de_perfil($seccion, $uid);
$vacio = [
    'publicados' => 'Todavía no has publicado eventos.',
    'asistire'   => 'No estás apuntado a ningún evento próximo.',
    'favoritos'  => 'Aún no tienes eventos favoritos.',
][$seccion];

$titulo = 'Mi perfil';
require __DIR__ . '/includes/header.php';
?>
<div class="caja perfil-cabecera">
  <?= avatar_html($perfil['nombre'], 'avatar-grande') ?>
  <div>
    <h1 style="font-size:1.8rem;margin-bottom:4px"><?= e($perfil['nombre']) ?></h1>
    <p class="suave" style="margin:0"><?= e($perfil['carrera'] ?: 'Carrera no especificada') ?> · <?= e($perfil['correo']) ?></p>
    <?php if ($perfil['descripcion']): ?><p style="margin:8px 0 0"><?= e($perfil['descripcion']) ?></p><?php endif; ?>
    <p class="suave" style="margin:8px 0 0">Miembro desde <?= e(fecha_es($perfil['creado_en'])) ?> <?= date('Y', strtotime($perfil['creado_en'])) ?></p>
  </div>
</div>

<nav class="pestanas" aria-label="Secciones del perfil">
  <?php foreach ($secciones as $clave => $nombre): ?>
    <a class="pestana <?= $clave === $seccion ? 'activa' : '' ?>" href="perfil.php?seccion=<?= $clave ?>"
       <?= $clave === $seccion ? 'aria-current="page"' : '' ?>><?= e($nombre) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($seccion === 'publicados'): ?>
  <p><a class="btn btn-sm" href="crear-evento.php">+ Crear evento</a>
     <a class="btn btn-borde btn-sm" href="mis-eventos.php">Gestionar mis eventos</a></p>
<?php endif; ?>

<?php if (!$eventos): ?>
  <div class="caja"><p style="margin:0"><?= e($vacio) ?> <a href="index.php">Explorar eventos</a></p></div>
<?php else: ?>
  <div class="rejilla">
    <?php foreach ($eventos as $ev) tarjeta_evento($ev, true); ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
