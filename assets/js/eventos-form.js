/**
 * eventos-form.js · Ayudas del formulario de eventos (crear y editar):
 * contadores de caracteres y vista previa de la imagen de portada.
 * El servidor valida todo de nuevo; esto solo mejora la experiencia.
 */

// Contadores: <span data-contador="id_del_campo">
document.querySelectorAll('[data-contador]').forEach((span) => {
  const campo = document.getElementById(span.dataset.contador);
  if (!campo) return;
  const actualizar = () => { span.textContent = campo.value.length; };
  campo.addEventListener('input', actualizar);
  actualizar();
});

// Vista previa de la portada
const urlImagen = document.getElementById('imagen_url');
const preview = document.getElementById('preview');

function mostrarPreview() {
  const url = urlImagen.value.trim();
  if (/^https?:\/\//i.test(url)) {
    preview.src = url;          // si la URL no es una imagen, se dispara "error" y se oculta
    preview.hidden = false;
  } else {
    preview.hidden = true;
  }
}

if (urlImagen && preview) {
  urlImagen.addEventListener('change', mostrarPreview);
  preview.addEventListener('error', () => { preview.hidden = true; });
  mostrarPreview();
}
