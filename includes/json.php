<?php
/**
 * json.php · Utilidades para los endpoints que responden JSON (carpeta acciones/).
 * entrada_json_post() hace las comprobaciones comunes: método POST, sesión iniciada,
 * token CSRF y lectura del cuerpo JSON. Si algo falla, responde con un código HTTP y termina.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/funciones.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function entrada_json_post(): array
{
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
    return is_array($entrada) ? $entrada : [];
}
