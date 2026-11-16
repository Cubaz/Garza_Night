/**
 * evento.js · Botón de asistencia de la página de detalle.
 * Envía la acción con fetch (enviarPost, definida en app.js) y actualiza
 * contador, cupo, etiqueta SOLD OUT y botón sin recargar la página.
 */
const boton    = document.getElementById('btn-asistencia');
const mensaje  = document.getElementById('mensaje-asistencia');

const numero   = document.getElementById('num-asistentes');
const textoNum = document.getElementById('texto-asistentes');
const quedan   = document.getElementById('quedan');
const etiquetaAgotado = document.getElementById('etiqueta-agotado');

function decir(texto, ok) {
  mensaje.textContent = texto;
  mensaje.className = 'mensaje-asistencia ' + (ok ? 'exito' : 'error');
}

/** Refleja en pantalla el estado que devuelve el servidor. */
function actualizar(r) {
  if (typeof r.asistentes === 'number') {
    numero.textContent = r.asistentes;
    textoNum.textContent = r.asistentes === 1 ? 'asistente confirmado' : 'asistentes confirmados';
    if (quedan && r.capacidad !== null) quedan.textContent = Math.max(0, r.capacidad - r.asistentes);
  }
  if (etiquetaAgotado) etiquetaAgotado.hidden = !r.agotado;

  if (r.asisto) {                       // ya está apuntado: puede cancelar
    boton.textContent = 'Cancelar asistencia';
    boton.dataset.accion = 'cancelar';
    boton.className = 'btn btn-borde btn-block';
    boton.disabled = false;
  } else if (r.agotado) {               // sin lugares
    boton.textContent = 'SOLD OUT';
    boton.dataset.accion = 'agotado';
    boton.className = 'btn btn-block';
    boton.disabled = true;
  } else if (typeof r.asisto === 'boolean') {
    boton.textContent = 'Apuntarme';
    boton.dataset.accion = 'apuntar';
    boton.className = 'btn btn-block';
    boton.disabled = false;
  }
}

if (boton) {
  boton.addEventListener('click', async () => {
    const accion = boton.dataset.accion;
    if (accion === 'agotado') return;

    boton.disabled = true;              // evita doble clic mientras se espera
    try {
      const r = await enviarPost('acciones/asistir.php', {
        evento_id: Number(boton.dataset.evento),
        accion: accion,
      });
      decir(r.mensaje, r.ok);
      actualizar(r);
    } catch (error) {
      decir('No se pudo conectar con el servidor. Intenta de nuevo.', false);
      boton.disabled = false;
    }
  });
}
