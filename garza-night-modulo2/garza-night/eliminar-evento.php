<?php
/**
 * eliminar-evento.php · Borra un evento definitivamente.
 * Gracias al ON DELETE CASCADE de la base de datos, también se borran
 * sus asistencias, favoritos y comentarios.
 */
require_once __DIR__ . '/includes/eventos.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('mis-eventos.php');
}
csrf_exigir();

$id = (int) ($_POST['id'] ?? 0);
$evento = obtener_evento($id);

if (!$evento || !puede_gestionar_evento($evento)) {
    http_response_code(403);
    exit('No tienes permiso para eliminar este evento.');
}

db()->prepare('DELETE FROM eventos WHERE id = ?')->execute([$id]);
flash('exito', 'El evento se eliminó.');
redirigir('mis-eventos.php');
