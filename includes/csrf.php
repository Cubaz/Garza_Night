<?php
/**
 * csrf.php · Protección contra CSRF (peticiones falsas desde otros sitios).
 * Cada sesión tiene un token secreto. Los formularios lo envían oculto y el
 * servidor comprueba que coincida antes de procesar cualquier POST.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Devuelve el <input> oculto para pegarlo dentro de un formulario. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/** true si el token recibido (campo "csrf" o cabecera X-CSRF-Token) es válido. */
function csrf_valido(): bool
{
    $recibido = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($recibido) && hash_equals($_SESSION['csrf'] ?? '', $recibido);
}

/** Corta la ejecución si el token no es válido. Se llama al inicio de cada POST. */
function csrf_exigir(): void
{
    if (!csrf_valido()) {
        http_response_code(403);
        exit('Solicitud no válida (token CSRF).');
    }
}
