/* app.js · JavaScript común a todas las páginas. */

// Menú desplegable en pantallas angostas
const botonMenu = document.getElementById('menu-btn');
const menu = document.getElementById('menu');
if (botonMenu && menu) {
  botonMenu.addEventListener('click', () => {
    const abierto = menu.classList.toggle('abierto');
    botonMenu.setAttribute('aria-expanded', abierto);
  });
}

// Token CSRF para usarlo en las peticiones fetch (módulos siguientes)
const csrfToken = document.querySelector('meta[name="csrf"]')?.content ?? '';

/**
 * Envía un POST con fetch y devuelve la respuesta JSON.
 * Ejemplo:  const r = await enviarPost('acciones/asistir.php', { evento_id: 3 });
 */
async function enviarPost(url, datos) {
  const respuesta = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(datos),
  });
  return respuesta.json();
}

// Confirmación antes de enviar formularios peligrosos:
// <form data-confirmar="¿Seguro?"> muestra un aviso y cancela el envío si el usuario dice que no.
document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
  formulario.addEventListener('submit', (evento) => {
    if (!confirm(formulario.dataset.confirmar)) {
      evento.preventDefault();
    }
  });
});
