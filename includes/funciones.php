<?php
/**
 * funciones.php · Utilidades compartidas (escape, validaciones, mensajes, fechas).
 */
require_once __DIR__ . '/config.php';

/** Escapa texto antes de imprimirlo en HTML (protege contra XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function redirigir(string $ruta): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($ruta, '/'));
    exit;
}

/**
 * Patrón del correo institucional: 2 letras minúsculas + 6 números + dominio.
 * Ejemplo válido: ab123456@uaeh.edu.mx
 * Se arma con la lista DOMINIOS_PERMITIDOS de config.php.
 * Sin ^ ni $ para poder usarlo también en el atributo HTML "pattern".
 */
function correo_patron(): string
{
    $dominios = implode('|', array_map(fn($d) => preg_quote($d, '/'), DOMINIOS_PERMITIDOS));
    return '[a-z]{2}[0-9]{6}@(' . $dominios . ')';
}

/** true si el correo cumple el patrón institucional (se acepta con mayúsculas). */
function correo_institucional_valido(string $correo): bool
{
    return (bool) preg_match('/^' . correo_patron() . '$/D', strtolower(trim($correo)));
}

/** Devuelve un mensaje de error si la contraseña no cumple las reglas, o null si es válida. */
function password_error(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'La contraseña debe incluir letras y números.';
    }
    return null;
}

// ---------- Mensajes "flash": se muestran una vez y desaparecen ----------
function flash(string $tipo, string $texto): void   // tipo: exito | error | info
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function flash_obtener(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

// ---------- Fechas y costo ----------
function fecha_es(string $fecha): string   // '2026-11-21' -> 'Sáb 21 Nov'
{
    $dias  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $t = strtotime($fecha);
    return $dias[(int) date('w', $t)] . ' ' . date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1];
}

function hora_12(string $hora): string      // '21:00:00' -> '9:00 PM'
{
    return date('g:i A', strtotime($hora));
}

function costo_texto(float $costo): string
{
    return $costo > 0 ? '$' . number_format($costo, 0) : 'Gratis';
}

/** Un evento está finalizado cuando su fecha ya pasó (no se guarda en la BD). */
function evento_finalizado(string $fecha): bool
{
    return $fecha < date('Y-m-d');
}

/** Iniciales para el avatar: 'Ana López' -> 'AL'. */
function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre));
    $ini = mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) {
        $ini .= mb_substr(end($partes), 0, 1);
    }
    return mb_strtoupper($ini);
}
