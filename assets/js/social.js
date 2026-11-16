/**
 * social.js · Favoritos y comentarios en la página de detalle (sin recargar).
 * Usa enviarPost() de app.js. Va dentro de una función para no chocar con
 * las variables de evento.js (ambos scripts comparten el mismo ámbito global).
 */
(() => {
  // ---------- Favoritos ----------
  const btnFav = document.getElementById('btn-favorito');

  function pintarFavorito(esFav) {
    btnFav.dataset.favorito = esFav ? '1' : '0';
    btnFav.setAttribute('aria-pressed', esFav ? 'true' : 'false');
    btnFav.textContent = esFav ? '♥ Guardado en favoritos' : '♡ Guardar en favoritos';
  }

  if (btnFav) {
    btnFav.addEventListener('click', async () => {
      const esFav = btnFav.dataset.favorito === '1';
      btnFav.disabled = true;
      try {
        const r = await enviarPost('acciones/favorito.php', {
          evento_id: Number(btnFav.dataset.evento),
          accion: esFav ? 'quitar' : 'agregar',
        });
        if (r.ok) pintarFavorito(r.favorito);
      } catch (error) { /* si falla, el botón queda como estaba */ }
      btnFav.disabled = false;
    });
  }

  // ---------- Comentarios ----------
  const form    = document.getElementById('form-comentario');
  const texto   = document.getElementById('texto-comentario');
  const lista   = document.getElementById('lista-comentarios');
  const aviso   = document.getElementById('mensaje-comentario');
  const contador = document.getElementById('contador-comentario');

  function decir(mensaje, ok) {
    aviso.textContent = mensaje;
    aviso.className = 'mensaje-asistencia ' + (ok ? 'exito' : 'error');
  }

  /** Crea el <li> del comentario. Se usa textContent (no innerHTML) para evitar XSS. */
  function crearComentario(c) {
    const li = document.createElement('li');
    li.className = 'comentario';

    const avatar = document.createElement('span');
    avatar.className = 'avatar';
    avatar.style.background = c.color;
    avatar.textContent = c.iniciales;

    const cuerpo = document.createElement('div');
    const cabecera = document.createElement('div');
    cabecera.className = 'comentario-cabecera';
    const nombre = document.createElement('strong');
    nombre.textContent = c.nombre;
    const fecha = document.createElement('small');
    fecha.textContent = ' ' + c.fecha;
    cabecera.append(nombre, fecha);

    const parrafo = document.createElement('p');
    parrafo.textContent = c.texto;

    cuerpo.append(cabecera, parrafo);
    li.append(avatar, cuerpo);
    return li;
  }

  if (form && texto && lista) {
    const actualizarContador = () => { contador.textContent = texto.value.length; };
    texto.addEventListener('input', actualizarContador);

    form.addEventListener('submit', async (evento) => {
      evento.preventDefault();
      const botonEnviar = form.querySelector('button[type="submit"]');
      botonEnviar.disabled = true;
      try {
        const r = await enviarPost('acciones/comentar.php', {
          evento_id: Number(form.dataset.evento),
          texto: texto.value,
        });
        if (r.ok) {
          lista.prepend(crearComentario(r.comentario));       // el más nuevo arriba
          document.getElementById('num-comentarios').textContent = r.total;
          document.getElementById('sin-comentarios').hidden = true;
          texto.value = '';
          actualizarContador();
          decir('Comentario publicado.', true);
        } else {
          decir(r.mensaje, false);
        }
      } catch (error) {
        decir('No se pudo enviar el comentario. Intenta de nuevo.', false);
      }
      botonEnviar.disabled = false;
    });
  }
})();
