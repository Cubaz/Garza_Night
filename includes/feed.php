<?php
/**
 * feed.php · Lógica compartida de listados de eventos: filtros, consultas y tarjetas.
 * Lo usan index.php (próximos), historico.php (pasados) y perfil.php.
 */
require_once __DIR__ . '/funciones.php';

/** Lee y limpia los filtros que llegan por la URL (GET). Todo valor inválido se ignora. */
function leer_filtros(): array
{
    $fecha = trim($_GET['fecha'] ?? '');
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$f || $f->format('Y-m-d') !== $fecha) {
        $fecha = '';
    }
    $costo = $_GET['costo'] ?? '';
    $orden = $_GET['orden'] ?? 'fecha';

    return [
        'q'        => mb_substr(trim($_GET['q'] ?? ''), 0, 100),
        'tematica' => (int) ($_GET['tematica'] ?? 0),
        'fecha'    => $fecha,
        'costo'    => in_array($costo, ['gratis', 'pago'], true) ? $costo : '',
        'orden'    => in_array($orden, ['fecha', 'populares', 'recientes'], true) ? $orden : 'fecha',
    ];
}

/** true si hay algún filtro aplicado (para mostrar el enlace "Limpiar"). */
function hay_filtros(array $f): bool
{
    return $f['q'] !== '' || $f['tematica'] > 0 || $f['fecha'] !== '' || $f['costo'] !== '' || $f['orden'] !== 'fecha';
}

/** SELECT común: las columnas que necesita tarjeta_evento(). */
function sql_eventos_base(): string
{
    return "SELECT e.id, e.titulo, e.imagen_url, e.lugar, e.fecha, e.hora_inicio, e.hora_fin,
                   e.costo_cover, e.edad_minima, e.capacidad, e.estado, t.nombre AS tematica,
                   (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS asistentes
            FROM eventos e JOIN tematicas t ON t.id = e.tematica_id";
}

/**
 * Busca eventos aplicando los filtros.
 * $pasados = false: eventos de hoy en adelante (feed).
 * $pasados = true : eventos que ya terminaron y no fueron cancelados (histórico).
 * $limite: máximo de tarjetas (los visitantes ven solo unas cuantas).
 * Devuelve [eventos, total que cumple los filtros].
 */
function buscar_eventos(array $f, ?int $limite = null, bool $pasados = false): array
{
    $donde  = $pasados ? ['e.fecha < ?', "e.estado = 'activo'"] : ['e.fecha >= ?'];
    $params = [date('Y-m-d')];

    if ($f['q'] !== '') {
        // Se escapan % y _ para que el usuario no use comodines de LIKE
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $donde[] = '(e.titulo LIKE ? OR e.lugar LIKE ? OR e.direccion LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($f['tematica'] > 0) { $donde[] = 'e.tematica_id = ?'; $params[] = $f['tematica']; }
    if ($f['fecha'] !== '') { $donde[] = 'e.fecha = ?';       $params[] = $f['fecha']; }
    if ($f['costo'] === 'gratis') { $donde[] = 'e.costo_cover = 0'; }
    if ($f['costo'] === 'pago')   { $donde[] = 'e.costo_cover > 0'; }
    $where = implode(' AND ', $donde);

    // El orden sale de una lista fija, nunca directo del usuario
    $orden = [
        'fecha'     => $pasados ? 'e.fecha DESC, e.hora_inicio DESC' : 'e.fecha ASC, e.hora_inicio ASC',
        'populares' => 'asistentes DESC, e.fecha ASC',
        'recientes' => 'e.creado_en DESC, e.id DESC',
    ][$f['orden']];

    $sql = sql_eventos_base() . " WHERE $where ORDER BY (e.estado = 'cancelado') ASC, $orden"
         . ($limite ? ' LIMIT ' . (int) $limite : '');   // (int) -> seguro de concatenar

    $st = db()->prepare($sql);
    $st->execute($params);
    $eventos = $st->fetchAll();

    $st = db()->prepare("SELECT COUNT(*) FROM eventos e WHERE $where");
    $st->execute($params);
    return [$eventos, (int) $st->fetchColumn()];
}

/** Eventos de una sección del perfil: 'publicados', 'asistire' o 'favoritos'. */
function eventos_de_perfil(string $seccion, int $usuario_id): array
{
    switch ($seccion) {
        case 'asistire':      // próximos eventos a los que se apuntó
            $donde  = "e.fecha >= ? AND e.estado = 'activo'
                       AND EXISTS (SELECT 1 FROM asistencias x WHERE x.evento_id = e.id AND x.usuario_id = ?)";
            $params = [date('Y-m-d'), $usuario_id];
            $orden  = 'e.fecha ASC, e.hora_inicio ASC';
            break;
        case 'favoritos':
            $donde  = 'EXISTS (SELECT 1 FROM favoritos x WHERE x.evento_id = e.id AND x.usuario_id = ?)';
            $params = [$usuario_id];
            $orden  = 'e.fecha DESC';
            break;
        default:              // publicados por él
            $donde  = 'e.organizador_id = ?';
            $params = [$usuario_id];
            $orden  = 'e.fecha DESC';
    }
    $st = db()->prepare(sql_eventos_base() . " WHERE $donde ORDER BY $orden");
    $st->execute($params);
    return $st->fetchAll();
}

/** true si el evento tiene cupo limitado y ya se llenó. */
function evento_agotado(array $ev): bool
{
    return $ev['capacidad'] !== null && (int) $ev['asistentes'] >= (int) $ev['capacidad'];
}

/** Dibuja la tarjeta de un evento. Para visitantes el enlace lleva al login. */
function tarjeta_evento(array $ev, bool $logueado): void
{
    $href = $logueado ? 'evento.php?id=' . (int) $ev['id'] : 'login.php';
    $cancelado = $ev['estado'] === 'cancelado';
    $horario = hora_12($ev['hora_inicio']) . ($ev['hora_fin'] ? ' – ' . hora_12($ev['hora_fin']) : '');
    $n = (int) $ev['asistentes'];
    ?>
    <a class="tarjeta<?= $cancelado ? ' tarjeta-cancelada' : '' ?>" href="<?= e($href) ?>">
      <div class="tarjeta-img">
        <img src="<?= e($ev['imagen_url']) ?>" alt="" loading="lazy">
        <div class="tarjeta-etiquetas">
          <span class="etiqueta"><?= e($ev['tematica']) ?></span>
          <?php if ((int) $ev['edad_minima'] > 0): ?><span class="etiqueta">+<?= (int) $ev['edad_minima'] ?></span><?php endif; ?>
          <?php if ($cancelado): ?><span class="etiqueta etiqueta-cancelado">CANCELADO</span>
          <?php elseif (evento_finalizado($ev['fecha'])): ?><span class="etiqueta etiqueta-fin">FINALIZADO</span>
          <?php elseif (evento_agotado($ev)): ?><span class="etiqueta etiqueta-agotado">SOLD OUT</span><?php endif; ?>
        </div>
      </div>
      <div class="tarjeta-info">
        <h3><?= e($ev['titulo']) ?></h3>
        <p><?= e(fecha_es($ev['fecha'])) ?> · <?= e($horario) ?></p>
        <p><?= e($ev['lugar']) ?></p>
        <div class="tarjeta-pie">
          <strong><?= e(costo_texto((float) $ev['costo_cover'])) ?></strong>
          <span><?= $n ?> <?= $n === 1 ? 'confirmado' : 'confirmados' ?></span>
        </div>
      </div>
    </a>
    <?php
}
