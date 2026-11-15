<?php
/**
 * editar-evento.php?id=N · Editar un evento.
 * Permiso: el organizador del evento o un administrador.
 */
require_once __DIR__ . '/includes/eventos.php';
requerir_login();

$id = (int) ($_GET['id'] ?? 0);
$evento = obtener_evento($id);

if (!$evento) {
    http_response_code(404);
    exit('Evento no encontrado.');
}
if (!puede_gestionar_evento($evento)) {
    http_response_code(403);
    exit('No tienes permiso para editar este evento.');
}
if ($evento['estado'] === 'cancelado') {
    flash('error', 'Un evento cancelado ya no se puede editar.');
    redirigir('mis-eventos.php');
}

$asistentes = contar_asistentes($id);
$datos = evento_a_formulario($evento);
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    [$datos, $errores] = validar_evento($_POST, $evento, $asistentes);

    if (!$errores) {
        $st = db()->prepare(
            'UPDATE eventos SET titulo = ?, slogan = ?, descripcion = ?, tematica_id = ?, imagen_url = ?,
                    lugar = ?, direccion = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, costo_cover = ?,
                    invitados_especiales = ?, politica_bebidas = ?, actividades = ?, codigo_vestimenta = ?,
                    edad_minima = ?, capacidad = ?
             WHERE id = ?'
        );
        $st->execute(array_merge(evento_para_bd($datos), [$id]));
        flash('exito', 'Los cambios se guardaron.');
        redirigir('mis-eventos.php');
    }
}

$titulo = 'Editar evento';
$scripts = ['eventos-form.js'];
$modo = 'editar';
$accion = 'editar-evento.php?id=' . $id;
$tematicas = obtener_tematicas();
require __DIR__ . '/includes/header.php';
?>
<div class="caja caja-ancha">
  <h1 style="font-size:2rem">Editar evento</h1>
  <?php if ($asistentes > 0): ?>
    <div class="alerta alerta-info">Este evento ya tiene <?= $asistentes ?> asistente(s) registrado(s).</div>
  <?php endif; ?>
  <?php if ($errores): ?><div class="alerta alerta-error" role="alert">Revisa los campos marcados.</div><?php endif; ?>
  <?php require __DIR__ . '/includes/form_evento.php'; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
