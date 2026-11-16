<?php
/**
 * acciones/comentar.php · Publica un comentario en un evento (responde JSON).
 * Entrada: { "evento_id": 3, "texto": "..." }  ·  Máximo MAX_COMENTARIO caracteres.
 * Los comentarios no se moderan; el administrador puede borrarlos (Módulo 6).
 */
require_once __DIR__ . '/../includes/json.php';
require_once __DIR__ . '/../includes/social.php';

$entrada = entrada_json_post();
$eid   = (int) ($entrada['evento_id'] ?? 0);
$texto = trim((string) ($entrada['texto'] ?? ''));

$largo = mb_strlen($texto);
if ($largo < 1 || $largo > MAX_COMENTARIO) {
    responder(422, ['ok' => false, 'mensaje' => 'El comentario debe tener entre 1 y ' . MAX_COMENTARIO . ' caracteres.']);
}

$pdo = db();
$st = $pdo->prepare('SELECT estado FROM eventos WHERE id = ?');
$st->execute([$eid]);
$estado = $st->fetchColumn();
if ($estado === false) {
    responder(404, ['ok' => false, 'mensaje' => 'El evento no existe.']);
}
if ($estado === 'cancelado') {
    responder(409, ['ok' => false, 'mensaje' => 'No se puede comentar un evento cancelado.']);
}

$u = usuario_actual();
$pdo->prepare('INSERT INTO comentarios (evento_id, usuario_id, texto) VALUES (?, ?, ?)')->execute([$eid, $u['id'], $texto]);

$st = $pdo->prepare('SELECT COUNT(*) FROM comentarios WHERE evento_id = ?');
$st->execute([$eid]);

responder(200, [
    'ok' => true,
    'comentario' => [
        'nombre'    => $u['nombre'],
        'iniciales' => iniciales($u['nombre']),
        'color'     => avatar_color($u['nombre']),
        'texto'     => $texto,
        'fecha'     => fecha_comentario(date('Y-m-d H:i:s')),
    ],
    'total' => (int) $st->fetchColumn(),
]);
