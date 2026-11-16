<?php
/**
 * acciones/asistir.php · Apuntarse o cancelar la asistencia a un evento.
 * Lo llama JavaScript con fetch (assets/js/evento.js) y SIEMPRE responde JSON.
 * Entrada (JSON): { "evento_id": 3, "accion": "apuntar" | "cancelar" }
 * Salida: { ok, mensaje, asisto, asistentes, capacidad, agotado }
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Estado actual del evento para este usuario: cuántos asisten, cupo y si ya está apuntado. */
function estado_asistencia(PDO $pdo, int $evento_id, int $usuario_id): array
{
    $st = $pdo->prepare('SELECT capacidad FROM eventos WHERE id = ?');
    $st->execute([$evento_id]);
    $cap = $st->fetchColumn();
    $cap = ($cap === null || $cap === false) ? null : (int) $cap;

    $st = $pdo->prepare('SELECT COUNT(*) FROM asistencias WHERE evento_id = ?');
    $st->execute([$evento_id]);
    $n = (int) $st->fetchColumn();

    $st = $pdo->prepare('SELECT 1 FROM asistencias WHERE evento_id = ? AND usuario_id = ?');
    $st->execute([$evento_id, $usuario_id]);

    return [
        'asisto'     => (bool) $st->fetchColumn(),
        'asistentes' => $n,
        'capacidad'  => $cap,
        'agotado'    => $cap !== null && $n >= $cap,
    ];
}

/** Aplica la acción dentro de una transacción. Devuelve [código HTTP, datos de la respuesta]. */
function procesar(PDO $pdo, int $uid, int $eid, string $accion): array
{
    // FOR UPDATE bloquea la fila del evento: si dos personas se apuntan al mismo tiempo,
    // la segunda espera a que termine la primera y no se rebasa el cupo.
    $st = $pdo->prepare('SELECT id, organizador_id, fecha, estado, capacidad FROM eventos WHERE id = ? FOR UPDATE');
    $st->execute([$eid]);
    $ev = $st->fetch();

    if (!$ev) {
        return [404, ['ok' => false, 'mensaje' => 'El evento no existe.']];
    }
    if ($ev['estado'] === 'cancelado') {
        return [409, ['ok' => false, 'mensaje' => 'Este evento fue cancelado.'] + estado_asistencia($pdo, $eid, $uid)];
    }
    if (evento_finalizado($ev['fecha'])) {
        return [409, ['ok' => false, 'mensaje' => 'Este evento ya terminó.'] + estado_asistencia($pdo, $eid, $uid)];
    }
    if ((int) $ev['organizador_id'] === $uid) {
        return [403, ['ok' => false, 'mensaje' => 'Eres el organizador de este evento.']];
    }

    $estado = estado_asistencia($pdo, $eid, $uid);

    if ($accion === 'apuntar') {
        if ($estado['asisto']) {
            return [200, ['ok' => true, 'mensaje' => 'Ya estabas registrado.'] + $estado];
        }
        if ($estado['agotado']) {
            return [409, ['ok' => false, 'mensaje' => 'SOLD OUT: ya no hay lugares disponibles.'] + $estado];
        }
        $pdo->prepare('INSERT INTO asistencias (evento_id, usuario_id) VALUES (?, ?)')->execute([$eid, $uid]);
        return [200, ['ok' => true, 'mensaje' => '¡Listo! Quedaste registrado.'] + estado_asistencia($pdo, $eid, $uid)];
    }

    // accion === 'cancelar'
    $pdo->prepare('DELETE FROM asistencias WHERE evento_id = ? AND usuario_id = ?')->execute([$eid, $uid]);
    return [200, ['ok' => true, 'mensaje' => 'Cancelaste tu asistencia.'] + estado_asistencia($pdo, $eid, $uid)];
}

// ---------- Validaciones previas ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}
if (!esta_logueado()) {
    responder(401, ['ok' => false, 'mensaje' => 'Inicia sesión para continuar.']);
}
if (!csrf_valido()) {
    responder(403, ['ok' => false, 'mensaje' => 'Sesión expirada. Recarga la página.']);
}

$entrada = json_decode(file_get_contents('php://input'), true);
$eid    = (int) ($entrada['evento_id'] ?? 0);
$accion = $entrada['accion'] ?? '';
if ($eid <= 0 || !in_array($accion, ['apuntar', 'cancelar'], true)) {
    responder(400, ['ok' => false, 'mensaje' => 'Solicitud no válida.']);
}

// ---------- Ejecutar ----------
$pdo = db();
$pdo->beginTransaction();
try {
    [$codigo, $datos] = procesar($pdo, usuario_actual()['id'], $eid, $accion);
    $pdo->commit();
} catch (Throwable $t) {
    $pdo->rollBack();
    responder(500, ['ok' => false, 'mensaje' => 'Ocurrió un error. Intenta de nuevo.']);
}
responder($codigo, $datos);
