<?php
/**
 * crear-evento.php · Publicar un evento nuevo (solo usuarios con sesión).
 * La validación y el formulario viven en includes/eventos.php y includes/form_evento.php.
 */
require_once __DIR__ . '/includes/eventos.php';
requerir_login();

$datos = evento_vacio();
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    [$datos, $errores] = validar_evento($_POST);

    if (!$errores) {
        $st = db()->prepare(
            'INSERT INTO eventos (organizador_id, titulo, slogan, descripcion, tematica_id, imagen_url,
                                  lugar, direccion, fecha, hora_inicio, hora_fin, costo_cover,
                                  invitados_especiales, politica_bebidas, actividades, codigo_vestimenta,
                                  edad_minima, capacidad)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute(array_merge([usuario_actual()['id']], evento_para_bd($datos)));
        flash('exito', 'Tu evento se publicó correctamente.');
        redirigir('mis-eventos.php');
    }
}

$titulo = 'Crear evento';
$scripts = ['eventos-form.js'];
$modo = 'crear';
$accion = 'crear-evento.php';
$tematicas = obtener_tematicas();
require __DIR__ . '/includes/header.php';
?>
<div class="caja caja-ancha">
  <h1 style="font-size:2rem">Crear evento</h1>
  <?php if ($errores): ?><div class="alerta alerta-error" role="alert">Revisa los campos marcados.</div><?php endif; ?>
  <?php require __DIR__ . '/includes/form_evento.php'; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
