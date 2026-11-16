<?php
/**
 * admin/index.php · Resumen del panel de administración (solo admin).
 * requerir_admin() se llama al inicio de TODAS las páginas de esta carpeta.
 */
require_once __DIR__ . '/../includes/eventos.php';
requerir_admin();

$pdo = db();
$contar = fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();
$estadisticas = [
    ' Usuarios'          => $contar('SELECT COUNT(*) FROM usuarios'),
    ' Eventos activos'   => $contar("SELECT COUNT(*) FROM eventos WHERE estado = 'activo'"),
    ' Eventos cancelados'=> $contar("SELECT COUNT(*) FROM eventos WHERE estado = 'cancelado'"),
    ' Asistencias'       => $contar('SELECT COUNT(*) FROM asistencias'),
    ' Comentarios'       => $contar('SELECT COUNT(*) FROM comentarios'),
    'Temáticas'         => $contar('SELECT COUNT(*) FROM tematicas'),
];

$titulo = 'Administración';
$seccion_admin = 'index';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_menu.php';
?>
<div class="estadisticas">
  <?php foreach ($estadisticas as $nombre => $valor): ?>
    <div class="caja estadistica"><strong><?= $valor ?></strong><span><?= e($nombre) ?></span></div>
  <?php endforeach; ?>
</div>
<p class="suave">Desde aquí puedes administrar las temáticas, los usuarios, los comentarios y todos los eventos.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
