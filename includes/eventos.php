<?php
/**
 * eventos.php · Lógica compartida del CRUD de eventos:
 * consultas básicas, permisos y validación del formulario.
 * Lo usan crear-evento.php, editar-evento.php, cancelar-evento.php,
 * eliminar-evento.php y mis-eventos.php.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/funciones.php';

// Opciones fijas (coinciden con los ENUM de la base de datos)
const POLITICAS_BEBIDAS = [
    'llevar_propias'   => 'Los asistentes deben llevar sus bebidas',
    'permitido_llevar' => 'Se pueden llevar bebidas propias',
    'no_permitido'     => 'No se permite llevar bebidas',
    'incluidas'        => 'Bebidas incluidas con el cover',
];
const EDADES_MINIMAS = [0 => 'Todo público', 18 => '+18', 21 => '+21'];

// ---------- Consultas ----------
function obtener_tematicas(): array
{
    return db()->query('SELECT id, nombre FROM tematicas ORDER BY nombre')->fetchAll();
}

function obtener_evento(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM eventos WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function contar_asistentes(int $evento_id): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM asistencias WHERE evento_id = ?');
    $st->execute([$evento_id]);
    return (int) $st->fetchColumn();
}

// ---------- Permisos ----------
/** El admin gestiona todo; un usuario normal solo sus propios eventos. */
function puede_gestionar_evento(array $evento): bool
{
    return es_admin() || (esta_logueado() && (int) $evento['organizador_id'] === usuario_actual()['id']);
}

/** Texto y estilo del estado de un evento: Cancelado, Finalizado o Activo. */
function estado_evento(array $ev): array
{
    if ($ev['estado'] === 'cancelado')     return ['Cancelado', 'etiqueta-cancelado'];
    if (evento_finalizado($ev['fecha']))   return ['Finalizado', 'etiqueta-fin'];
    return ['Activo', 'etiqueta-ok'];
}

// ---------- Datos del formulario ----------
/** Valores iniciales para el formulario de "crear". */
function evento_vacio(): array
{
    return [
        'titulo' => '', 'slogan' => '', 'descripcion' => '', 'tematica_id' => '', 'imagen_url' => '',
        'lugar' => '', 'direccion' => '', 'fecha' => '', 'hora_inicio' => '', 'hora_fin' => '',
        'costo_cover' => '0', 'invitados_especiales' => '', 'politica_bebidas' => '', 'actividades' => '',
        'codigo_vestimenta' => '', 'edad_minima' => '0', 'capacidad' => '',
    ];
}

/** Convierte una fila de la BD a los textos que se muestran en el formulario de "editar". */
function evento_a_formulario(array $ev): array
{
    $d = evento_vacio();
    foreach ($d as $campo => $_) {
        $d[$campo] = (string) ($ev[$campo] ?? '');
    }
    $d['hora_inicio'] = substr($d['hora_inicio'], 0, 5);          // 21:00:00 -> 21:00
    $d['hora_fin']    = substr($d['hora_fin'], 0, 5);
    $d['costo_cover'] = (string) (float) $d['costo_cover'];       // 80.00 -> 80
    return $d;
}

// ---------- Validación ----------
function hora_valida(string $h): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $h);
}

/**
 * Valida los datos del formulario (siempre en el servidor).
 * $original: el evento actual al editar (null al crear).
 * $asistentes: asistentes ya registrados (la capacidad no puede ser menor).
 * Devuelve [datos limpios como texto, errores por campo].
 */
function validar_evento(array $in, ?array $original = null, int $asistentes = 0): array
{
    $campos = array_keys(evento_vacio());
    $d = [];
    foreach ($campos as $c) {
        $d[$c] = trim((string) ($in[$c] ?? ''));
    }
    $err = [];
    $len = fn(string $c) => mb_strlen($d[$c]);

    if ($len('titulo') < 3 || $len('titulo') > 100)         $err['titulo'] = 'El título debe tener de 3 a 100 caracteres.';
    if ($len('slogan') > 120)                               $err['slogan'] = 'El slogan admite máximo 120 caracteres.';
    if ($len('descripcion') < 10 || $len('descripcion') > 1500) $err['descripcion'] = 'La descripción debe tener de 10 a 1500 caracteres.';

    // Temática: debe existir en la tabla
    $st = db()->prepare('SELECT 1 FROM tematicas WHERE id = ?');
    $st->execute([(int) $d['tematica_id']]);
    if (!$st->fetch())                                      $err['tematica_id'] = 'Elige una temática de la lista.';

    // Imagen: URL http(s) válida
    if ($len('imagen_url') > 500 || !preg_match('#^https?://#i', $d['imagen_url']) || !filter_var($d['imagen_url'], FILTER_VALIDATE_URL)) {
        $err['imagen_url'] = 'Pega un enlace de imagen válido que empiece con http:// o https://.';
    }

    if ($len('lugar') < 2 || $len('lugar') > 100)           $err['lugar'] = 'El lugar debe tener de 2 a 100 caracteres.';
    if ($len('direccion') < 5 || $len('direccion') > 200)   $err['direccion'] = 'La dirección debe tener de 5 a 200 caracteres.';

    // Fecha: formato válido y no pasada (al editar se permite dejar la misma fecha)
    $f = DateTime::createFromFormat('Y-m-d', $d['fecha']);
    if (!$f || $f->format('Y-m-d') !== $d['fecha']) {
        $err['fecha'] = 'Elige una fecha válida.';
    } elseif ($d['fecha'] < date('Y-m-d') && !($original && $original['fecha'] === $d['fecha'])) {
        $err['fecha'] = 'La fecha no puede ser anterior a hoy.';
    }

    if (!hora_valida($d['hora_inicio']))                    $err['hora_inicio'] = 'Elige la hora de inicio.';
    if ($d['hora_fin'] !== '' && !hora_valida($d['hora_fin'])) $err['hora_fin'] = 'La hora de fin no es válida.';

    // Costo: número entre 0 y 9999 (0 = gratis)
    if ($d['costo_cover'] === '') $d['costo_cover'] = '0';
    if (!is_numeric($d['costo_cover']) || $d['costo_cover'] < 0 || $d['costo_cover'] > 9999) {
        $err['costo_cover'] = 'El costo debe ser un número entre 0 y 9999 (0 = gratis).';
    }

    if ($len('invitados_especiales') > 200)                  $err['invitados_especiales'] = 'Máximo 200 caracteres.';
    if (!array_key_exists($d['politica_bebidas'], POLITICAS_BEBIDAS)) $err['politica_bebidas'] = 'Elige una opción.';
    if ($len('actividades') > 300)                          $err['actividades'] = 'Máximo 300 caracteres.';
    if ($len('codigo_vestimenta') > 80)                     $err['codigo_vestimenta'] = 'Máximo 80 caracteres.';
    if (!in_array($d['edad_minima'], ['0', '18', '21'], true)) $err['edad_minima'] = 'Elige una opción.';

    // Capacidad: vacía = sin límite; si hay valor, entero positivo y no menor a los asistentes actuales
    if ($d['capacidad'] !== '') {
        if (!ctype_digit($d['capacidad']) || (int) $d['capacidad'] < 1 || (int) $d['capacidad'] > 10000) {
            $err['capacidad'] = 'La capacidad debe ser un entero entre 1 y 10000, o déjala vacía.';
        } elseif ((int) $d['capacidad'] < $asistentes) {
            $err['capacidad'] = "Ya hay $asistentes asistentes registrados; la capacidad no puede ser menor.";
        }
    }
    return [$d, $err];
}

/** Convierte los textos validados a los valores que se guardan en la BD ('' -> NULL, números reales). */
function evento_para_bd(array $d): array
{
    $nulo = fn(string $v) => $v === '' ? null : $v;
    return [
        $d['titulo'], $nulo($d['slogan']), $d['descripcion'], (int) $d['tematica_id'], $d['imagen_url'],
        $d['lugar'], $d['direccion'], $d['fecha'], $d['hora_inicio'], $nulo($d['hora_fin']),
        (float) $d['costo_cover'], $nulo($d['invitados_especiales']), $d['politica_bebidas'],
        $nulo($d['actividades']), $nulo($d['codigo_vestimenta']), (int) $d['edad_minima'],
        $d['capacidad'] === '' ? null : (int) $d['capacidad'],
    ];
}
