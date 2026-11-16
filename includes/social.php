<?php
/**
 * social.php · Funciones de la parte social: avatares, favoritos y comentarios.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funciones.php';

/** Color del avatar: siempre el mismo para el mismo nombre (se calcula desde el nombre). */
function avatar_color(string $nombre): string
{
    return 'hsl(' . (crc32($nombre) % 360) . ', 45%, 38%)';
}

/** Círculo con las iniciales del usuario. */
function avatar_html(string $nombre, string $extra = ''): string
{
    return '<span class="avatar ' . e($extra) . '" style="background:' . avatar_color($nombre) . '" aria-hidden="true">'
         . e(iniciales($nombre)) . '</span>';
}

function es_favorito(int $evento_id, int $usuario_id): bool
{
    $st = db()->prepare('SELECT 1 FROM favoritos WHERE evento_id = ? AND usuario_id = ?');
    $st->execute([$evento_id, $usuario_id]);
    return (bool) $st->fetchColumn();
}

/** Comentarios de un evento, los más recientes primero. */
function obtener_comentarios(int $evento_id): array
{
    $st = db()->prepare(
        'SELECT c.id, c.texto, c.creado_en, u.nombre
         FROM comentarios c JOIN usuarios u ON u.id = c.usuario_id
         WHERE c.evento_id = ? ORDER BY c.creado_en DESC, c.id DESC'
    );
    $st->execute([$evento_id]);
    return $st->fetchAll();
}

/** '2026-11-21 22:30:00' -> 'Sáb 21 Nov · 10:30 PM' */
function fecha_comentario(string $fecha_hora): string
{
    $t = strtotime($fecha_hora);
    return fecha_es(date('Y-m-d', $t)) . ' · ' . hora_12(date('H:i:s', $t));
}
