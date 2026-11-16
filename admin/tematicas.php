<?php
/**
 * admin/tematicas.php · Agregar, renombrar y eliminar temáticas (solo admin).
 * Una temática en uso por algún evento no se puede eliminar.
 * Patrón: se procesa el POST, se guarda un mensaje flash y se redirige (así recargar no repite la acción).
 */
require_once __DIR__ . '/../includes/eventos.php';
requerir_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');

    try {
        if ($accion === 'crear' || $accion === 'renombrar') {
            $largo = mb_strlen($nombre);
            if ($largo < 2 || $largo > 50) {
                flash('error', 'El nombre debe tener de 2 a 50 caracteres.');
            } elseif ($accion === 'crear') {
                $pdo->prepare('INSERT INTO tematicas (nombre) VALUES (?)')->execute([$nombre]);
                flash('exito', 'Temática agregada.');
            } else {
                $pdo->prepare('UPDATE tematicas SET nombre = ? WHERE id = ?')->execute([$nombre, $id]);
                flash('exito', 'Temática actualizada.');
            }
        } elseif ($accion === 'eliminar') {
            $st = $pdo->prepare('SELECT COUNT(*) FROM eventos WHERE tematica_id = ?');
            $st->execute([$id]);
            if ((int) $st->fetchColumn() > 0) {
                flash('error', 'No se puede eliminar: hay eventos que usan esa temática.');
            } else {
                $pdo->prepare('DELETE FROM tematicas WHERE id = ?')->execute([$id]);
                flash('exito', 'Temática eliminada.');
            }
        }
    } catch (PDOException $ex) {
        if ($ex->getCode() === '23000') {      // el nombre ya existe (UNIQUE)
            flash('error', 'Ya existe una temática con ese nombre.');
        } else {
            throw $ex;
        }
    }
    redirigir('admin/tematicas.php');
}

$tematicas = $pdo->query(
    'SELECT t.id, t.nombre, (SELECT COUNT(*) FROM eventos e WHERE e.tematica_id = t.id) AS eventos
     FROM tematicas t ORDER BY t.nombre'
)->fetchAll();

$titulo = 'Temáticas';
$seccion_admin = 'tematicas';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_menu.php';
?>
<div class="caja" style="margin-bottom:20px">
  <h2 style="font-size:1.2rem">Nueva temática</h2>
  <form method="post" action="tematicas.php" class="form-linea">
    <?= csrf_campo() ?><input type="hidden" name="accion" value="crear">
    <label class="sr-only" for="nueva">Nombre</label>
    <input class="input" id="nueva" name="nombre" maxlength="50" required placeholder="Ej. Noche mexicana">
    <button class="btn" type="submit">Agregar</button>
  </form>
</div>

<div class="tabla-envoltorio">
  <table class="tabla">
    <thead><tr><th>Nombre</th><th>Eventos</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($tematicas as $t): ?>
      <tr>
        <td>
          <form method="post" action="tematicas.php" class="form-linea">
            <?= csrf_campo() ?><input type="hidden" name="accion" value="renombrar">
            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
            <label class="sr-only" for="t<?= (int) $t['id'] ?>">Nombre</label>
            <input class="input" id="t<?= (int) $t['id'] ?>" name="nombre" maxlength="50" required value="<?= e($t['nombre']) ?>">
            <button class="btn btn-borde btn-sm" type="submit">Guardar</button>
          </form>
        </td>
        <td><?= (int) $t['eventos'] ?></td>
        <td>
          <form class="form-inline" method="post" action="tematicas.php" data-confirmar="¿Eliminar la temática «<?= e($t['nombre']) ?>»?">
            <?= csrf_campo() ?><input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
            <button class="btn btn-peligro btn-sm" type="submit" <?= $t['eventos'] > 0 ? 'disabled title="En uso por eventos"' : '' ?>>Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
