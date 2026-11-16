<?php
/**
 * admin/editar-usuario.php?id=N · El admin corrige nombre, carrera y descripción de un usuario.
 * (El correo, la contraseña y el rol no se modifican desde aquí.)
 */
require_once __DIR__ . '/../includes/eventos.php';
requerir_admin();

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare('SELECT id, nombre, correo, carrera, descripcion FROM usuarios WHERE id = ?');
$st->execute([$id]);
$usuario = $st->fetch();
if (!$usuario) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}

$v = [
    'nombre'      => $usuario['nombre'],
    'carrera'     => (string) $usuario['carrera'],
    'descripcion' => (string) $usuario['descripcion'],
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $v['nombre']      = trim($_POST['nombre'] ?? '');
    $v['carrera']     = trim($_POST['carrera'] ?? '');
    $v['descripcion'] = trim($_POST['descripcion'] ?? '');

    $largo = mb_strlen($v['nombre']);
    if ($largo < 2 || $largo > 80 || !preg_match('/^[\p{L}][\p{L} .\'-]*$/u', $v['nombre'])) {
        $errores['nombre'] = 'Nombre de 2 a 80 caracteres, solo letras.';
    }
    if (mb_strlen($v['carrera']) > 80)      $errores['carrera'] = 'Máximo 80 caracteres.';
    if (mb_strlen($v['descripcion']) > 200) $errores['descripcion'] = 'Máximo 200 caracteres.';

    if (!$errores) {
        db()->prepare('UPDATE usuarios SET nombre = ?, carrera = ?, descripcion = ? WHERE id = ?')->execute([
            $v['nombre'],
            $v['carrera'] !== '' ? $v['carrera'] : null,
            $v['descripcion'] !== '' ? $v['descripcion'] : null,
            $id,
        ]);
        flash('exito', 'Usuario actualizado.');
        redirigir('admin/usuarios.php');
    }
}

$titulo = 'Editar usuario';
$seccion_admin = 'usuarios';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_menu.php';
?>
<div class="caja caja-centrada" style="margin-top:0">
  <h2>Editar usuario</h2>
  <p class="suave"><?= e($usuario['correo']) ?></p>
  <form method="post" action="editar-usuario.php?id=<?= $id ?>">
    <?= csrf_campo() ?>
    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input class="input <?= isset($errores['nombre']) ? 'invalido' : '' ?>" id="nombre" name="nombre" maxlength="80" required value="<?= e($v['nombre']) ?>">
      <?php if (isset($errores['nombre'])): ?><div class="error-campo"><?= e($errores['nombre']) ?></div><?php endif; ?>
    </div>
    <div class="campo">
      <label for="carrera">Carrera <span class="opcional">(opcional)</span></label>
      <input class="input" id="carrera" name="carrera" maxlength="80" value="<?= e($v['carrera']) ?>">
      <?php if (isset($errores['carrera'])): ?><div class="error-campo"><?= e($errores['carrera']) ?></div><?php endif; ?>
    </div>
    <div class="campo">
      <label for="descripcion">Descripción <span class="opcional">(opcional)</span></label>
      <textarea class="input" id="descripcion" name="descripcion" rows="3" maxlength="200"><?= e($v['descripcion']) ?></textarea>
      <?php if (isset($errores['descripcion'])): ?><div class="error-campo"><?= e($errores['descripcion']) ?></div><?php endif; ?>
    </div>
    <div class="acciones-form">
      <button class="btn" type="submit">Guardar</button>
      <a class="btn btn-borde" href="usuarios.php">Cancelar</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
