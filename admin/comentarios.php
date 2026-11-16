<?php
/**
 * admin/comentarios.php · Los 100 comentarios más recientes, con opción de borrarlos (solo admin).
 */
require_once __DIR__ . '/../includes/eventos.php';
require_once __DIR__ . '/../includes/social.php';
requerir_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $pdo->prepare('DELETE FROM comentarios WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
    flash('exito', 'Comentario eliminado.');
    redirigir('admin/comentarios.php');
}

$comentarios = $pdo->query(
    'SELECT c.id, c.texto, c.creado_en, c.evento_id, u.nombre AS autor, e.titulo AS evento
     FROM comentarios c
     JOIN usuarios u ON u.id = c.usuario_id
     JOIN eventos e  ON e.id = c.evento_id
     ORDER BY c.creado_en DESC, c.id DESC LIMIT 100'
)->fetchAll();

$titulo = 'Comentarios';
$seccion_admin = 'comentarios';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_menu.php';
?>
<?php if (!$comentarios): ?>
  <div class="caja"><p style="margin:0">No hay comentarios todavía.</p></div>
<?php else: ?>
<div class="tabla-envoltorio">
  <table class="tabla">
    <thead><tr><th>Autor</th><th>Comentario</th><th>Evento</th><th>Fecha</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($comentarios as $c): ?>
      <tr>
        <td><?= e($c['autor']) ?></td>
        <td style="max-width:320px;overflow-wrap:anywhere"><?= e($c['texto']) ?></td>
        <td><a href="<?= BASE_URL ?>/evento.php?id=<?= (int) $c['evento_id'] ?>"><?= e($c['evento']) ?></a></td>
        <td><?= e(fecha_comentario($c['creado_en'])) ?></td>
        <td>
          <form class="form-inline" method="post" action="comentarios.php" data-confirmar="¿Eliminar este comentario?">
            <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
