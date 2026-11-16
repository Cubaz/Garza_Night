<?php
/**
 * admin/usuarios.php · Lista de usuarios y eliminación (solo admin).
 * Eliminar un usuario borra también sus eventos, asistencias, favoritos y comentarios
 * (ON DELETE CASCADE). No se puede eliminar a un administrador, ni a uno mismo.
 */
require_once __DIR__ . '/../includes/eventos.php';
requerir_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $id = (int) ($_POST['id'] ?? 0);

    $st = $pdo->prepare('SELECT id, rol FROM usuarios WHERE id = ?');
    $st->execute([$id]);
    $objetivo = $st->fetch();

    if (!$objetivo) {
        flash('error', 'El usuario no existe.');
    } elseif ((int) $objetivo['id'] === usuario_actual()['id'] || $objetivo['rol'] === 'admin') {
        flash('error', 'No se puede eliminar una cuenta de administrador.');
    } else {
        $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        flash('exito', 'Usuario eliminado junto con su actividad.');
    }
    redirigir('admin/usuarios.php');
}

$usuarios = $pdo->query(
    'SELECT u.id, u.nombre, u.correo, u.carrera, u.rol, u.creado_en,
            (SELECT COUNT(*) FROM eventos e WHERE e.organizador_id = u.id) AS eventos
     FROM usuarios u ORDER BY u.creado_en DESC'
)->fetchAll();

$titulo = 'Usuarios';
$seccion_admin = 'usuarios';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_menu.php';
?>
<div class="tabla-envoltorio">
  <table class="tabla">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Carrera</th><th>Rol</th><th>Eventos</th><th>Registro</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $u2): ?>
      <tr>
        <td><strong><?= e($u2['nombre']) ?></strong></td>
        <td><?= e($u2['correo']) ?></td>
        <td><?= e($u2['carrera'] ?: '—') ?></td>
        <td><span class="etiqueta <?= $u2['rol'] === 'admin' ? '' : 'etiqueta-fin' ?>"><?= e($u2['rol']) ?></span></td>
        <td><?= (int) $u2['eventos'] ?></td>
        <td><?= e(fecha_es($u2['creado_en'])) ?></td>
        <td>
          <div class="acciones">
            <a class="btn btn-borde btn-sm" href="editar-usuario.php?id=<?= (int) $u2['id'] ?>">Editar</a>
            <?php if ($u2['rol'] !== 'admin'): ?>
              <form class="form-inline" method="post" action="usuarios.php"
                    data-confirmar="¿Eliminar a este usuario? Se borrarán también sus eventos, asistencias y comentarios.">
                <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $u2['id'] ?>">
                <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
