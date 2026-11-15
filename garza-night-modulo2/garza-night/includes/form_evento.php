<?php
/**
 * form_evento.php · Formulario compartido por "crear" y "editar".
 * Variables que debe definir la página antes de incluirlo:
 *   $modo ('crear' | 'editar'), $accion (URL del POST), $datos, $errores, $tematicas.
 */
$hoy = date('Y-m-d');

/** Imprime un campo <input> con su etiqueta, valor previo y mensaje de error. */
function campo_input(string $campo, string $etiqueta, array $d, array $err, string $tipo = 'text', string $attrs = '', string $ayuda = ''): void
{
    $inv = isset($err[$campo]) ? ' invalido' : '';
    echo '<div class="campo"><label for="' . $campo . '">' . $etiqueta . '</label>';
    echo '<input class="input' . $inv . '" id="' . $campo . '" name="' . $campo . '" type="' . $tipo . '" value="' . e($d[$campo]) . '" ' . $attrs . '>';
    if ($ayuda) echo '<small>' . $ayuda . '</small>';
    if (isset($err[$campo])) echo '<div class="error-campo">' . e($err[$campo]) . '</div>';
    echo '</div>';
}

function mensaje_error(array $err, string $campo): void
{
    if (isset($err[$campo])) echo '<div class="error-campo">' . e($err[$campo]) . '</div>';
}
?>
<form method="post" action="<?= e($accion) ?>" id="form-evento">
  <?= csrf_campo() ?>

  <h2 class="form-seccion">Lo básico</h2>
  <div class="form-grid">
    <div class="span-2"><?php campo_input('titulo', 'Título del evento', $datos, $errores, 'text', 'maxlength="100" required'); ?></div>
    <div class="span-2"><?php campo_input('slogan', 'Slogan <span class="opcional">(opcional)</span>', $datos, $errores, 'text', 'maxlength="120"', 'Una frase corta y atractiva.'); ?></div>

    <div class="campo span-2">
      <label for="descripcion">Descripción</label>
      <textarea class="input <?= isset($errores['descripcion']) ? 'invalido' : '' ?>" id="descripcion" name="descripcion" rows="4" maxlength="1500" required><?= e($datos['descripcion']) ?></textarea>
      <small><span data-contador="descripcion">0</span>/1500 caracteres</small>
      <?php mensaje_error($errores, 'descripcion'); ?>
    </div>

    <div class="campo">
      <label for="tematica_id">Temática</label>
      <select class="input <?= isset($errores['tematica_id']) ? 'invalido' : '' ?>" id="tematica_id" name="tematica_id" required>
        <option value="">Elige una…</option>
        <?php foreach ($tematicas as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= (string) $t['id'] === (string) $datos['tematica_id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php mensaje_error($errores, 'tematica_id'); ?>
    </div>

    <div class="campo">
      <label for="edad_minima">Edad mínima</label>
      <select class="input" id="edad_minima" name="edad_minima">
        <?php foreach (EDADES_MINIMAS as $valor => $texto): ?>
          <option value="<?= $valor ?>" <?= (string) $valor === (string) $datos['edad_minima'] ? 'selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
      </select>
      <?php mensaje_error($errores, 'edad_minima'); ?>
    </div>

    <div class="span-2">
      <?php campo_input('imagen_url', 'Imagen de portada (enlace)', $datos, $errores, 'url', 'maxlength="500" required placeholder="https://..."', 'Copia la dirección de una imagen de internet y pégala aquí.'); ?>
      <img id="preview" class="preview" alt="Vista previa de la portada" hidden>
    </div>
  </div>

  <h2 class="form-seccion">Cuándo y dónde</h2>
  <div class="form-grid">
    <?php campo_input('fecha', 'Fecha', $datos, $errores, 'date', $modo === 'crear' ? 'min="' . $hoy . '" required' : 'required'); ?>
    <div></div>
    <?php campo_input('hora_inicio', 'Hora de inicio', $datos, $errores, 'time', 'required'); ?>
    <?php campo_input('hora_fin', 'Hora de fin <span class="opcional">(opcional)</span>', $datos, $errores, 'time'); ?>
    <?php campo_input('lugar', 'Nombre del lugar', $datos, $errores, 'text', 'maxlength="100" required'); ?>
    <?php campo_input('direccion', 'Dirección', $datos, $errores, 'text', 'maxlength="200" required'); ?>
  </div>

  <h2 class="form-seccion">Entrada y detalles</h2>
  <div class="form-grid">
    <?php campo_input('costo_cover', 'Costo de cover (MXN)', $datos, $errores, 'number', 'min="0" max="9999" step="1" required', '0 significa gratis.'); ?>
    <?php campo_input('capacidad', 'Capacidad máxima <span class="opcional">(opcional)</span>', $datos, $errores, 'number', 'min="1" max="10000" step="1"', 'Vacío = sin límite. Al llenarse aparece SOLD OUT.'); ?>

    <div class="campo span-2">
      <label for="politica_bebidas">Bebidas</label>
      <select class="input <?= isset($errores['politica_bebidas']) ? 'invalido' : '' ?>" id="politica_bebidas" name="politica_bebidas" required>
        <option value="">Elige una…</option>
        <?php foreach (POLITICAS_BEBIDAS as $valor => $texto): ?>
          <option value="<?= $valor ?>" <?= $valor === $datos['politica_bebidas'] ? 'selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
      </select>
      <?php mensaje_error($errores, 'politica_bebidas'); ?>
    </div>

    <div class="span-2"><?php campo_input('invitados_especiales', 'Invitados especiales <span class="opcional">(opcional)</span>', $datos, $errores, 'text', 'maxlength="200"'); ?></div>
    <div class="span-2"><?php campo_input('actividades', 'Actividades y concursos <span class="opcional">(opcional)</span>', $datos, $errores, 'text', 'maxlength="300"'); ?></div>
    <div class="span-2"><?php campo_input('codigo_vestimenta', 'Código de vestimenta <span class="opcional">(opcional)</span>', $datos, $errores, 'text', 'maxlength="80"'); ?></div>
  </div>

  <div class="acciones-form">
    <button class="btn" type="submit"><?= $modo === 'crear' ? 'Publicar evento' : 'Guardar cambios' ?></button>
    <a class="btn btn-borde" href="mis-eventos.php">Cancelar</a>
  </div>
</form>
