<?php
/**
 * acciones/favorito.php · Agrega o quita un evento de favoritos (responde JSON).
 * Entrada: { "evento_id": 3, "accion": "agregar" | "quitar" }
 */
require_once __DIR__ . '/../includes/json.php';

$entrada = entrada_json_post();
$eid     = (int) ($entrada['evento_id'] ?? 0);
$accion  = $entrada['accion'] ?? '';
if ($eid <= 0 || !in_array($accion, ['agregar', 'quitar'], true)) {
    responder(400, ['ok' => false, 'mensaje' => 'Solicitud no válida.']);
}

$pdo = db();
$st = $pdo->prepare('SELECT 1 FROM eventos WHERE id = ?');
$st->execute([$eid]);
if (!$st->fetchColumn()) {
    responder(404, ['ok' => false, 'mensaje' => 'El evento no existe.']);
}

$uid = usuario_actual()['id'];
if ($accion === 'agregar') {
    // INSERT IGNORE: si ya era favorito, el UNIQUE lo rechaza en silencio y no pasa nada
    $pdo->prepare('INSERT IGNORE INTO favoritos (evento_id, usuario_id) VALUES (?, ?)')->execute([$eid, $uid]);
} else {
    $pdo->prepare('DELETE FROM favoritos WHERE evento_id = ? AND usuario_id = ?')->execute([$eid, $uid]);
}
responder(200, ['ok' => true, 'favorito' => $accion === 'agregar']);
