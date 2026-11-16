<?php
/**
 * admin_menu.php · Pestañas del panel de administración.
 * Cada página define antes $seccion_admin ('index', 'tematicas', 'usuarios', 'comentarios').
 */
$opciones_admin = [
    'index'       => ['Resumen',     '/admin/index.php'],
    'tematicas'   => ['Temáticas',   '/admin/tematicas.php'],
    'usuarios'    => ['Usuarios',    '/admin/usuarios.php'],
    'comentarios' => ['Comentarios', '/admin/comentarios.php'],
    'eventos'     => ['Eventos',     '/mis-eventos.php'],   // ya existe: el admin ve y gestiona todos
];
?>
<h1 style="font-size:2rem">Panel de administración</h1>
<nav class="pestanas" aria-label="Administración">
  <?php foreach ($opciones_admin as $clave => [$nombre, $ruta]): ?>
    <a class="pestana <?= ($seccion_admin ?? '') === $clave ? 'activa' : '' ?>" href="<?= BASE_URL . $ruta ?>"><?= e($nombre) ?></a>
  <?php endforeach; ?>
</nav>
