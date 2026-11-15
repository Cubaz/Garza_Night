<?php
/**
 * cancelar-evento.php · Marca un evento como "cancelado" y borra sus asistencias,
 * para que deje de aparecer entre los eventos de quienes iban a asistir.
 * Solo POST con token CSRF. Las dos operaciones van en una transacción:
 * o se hacen las dos, o no se hace ninguna.
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
    exit('No tienes permiso para cancelar este evento.');
}
if ($evento['estado'] === 'cancelado') {
    flash('info', 'Ese evento ya estaba cancelado.');
    redirigir('mis-eventos.php');
}

$pdo = db();
$pdo->beginTransaction();
$pdo->prepare("UPDATE eventos SET estado = 'cancelado' WHERE id = ?")->execute([$id]);
$pdo->prepare('DELETE FROM asistencias WHERE evento_id = ?')->execute([$id]);
$pdo->commit();

flash('exito', 'El evento se canceló y se quitaron sus asistencias.');
redirigir('mis-eventos.php');
