<?php
/**
 * auth.php · Sesiones, usuario actual y control de permisos.
 * Se incluye en todas las páginas (a través de header.php o directamente).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

// Cookies de sesión más seguras
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,       // JavaScript no puede leer la cookie
        'samesite' => 'Lax',      // no se envía desde otros sitios en POST
    ]);
    session_start();
}

function esta_logueado(): bool
{
    return isset($_SESSION['usuario']);
}

/** Datos básicos del usuario en sesión (id, nombre, correo, rol) o null. */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function es_admin(): bool
{
    return esta_logueado() && $_SESSION['usuario']['rol'] === 'admin';
}

/** Guarda al usuario en sesión tras un login correcto. */
function iniciar_sesion(array $u): void
{
    session_regenerate_id(true);   // evita la fijación de sesión
    $_SESSION['usuario'] = [
        'id'     => (int) $u['id'],
        'nombre' => $u['nombre'],
        'correo' => $u['correo'],
        'rol'    => $u['rol'],
    ];
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    session_destroy();
}

/** Si no hay sesión, manda al login. */
function requerir_login(): void
{
    if (!esta_logueado()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Solo administradores; los demás reciben 403. */
function requerir_admin(): void
{
    requerir_login();
    if (!es_admin()) {
        http_response_code(403);
        exit('Acceso restringido a administradores.');
    }
}
