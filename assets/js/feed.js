/**
 * feed.js · Filtros del feed: al cambiar un selector o la fecha se envía el formulario solo.
 * Sin JavaScript el formulario sigue funcionando con el botón "Filtrar".
 */
const formFiltros = document.getElementById('form-filtros');
if (formFiltros) {
  formFiltros.querySelectorAll('select, input[type="date"]').forEach((campo) => {
    campo.addEventListener('change', () => formFiltros.submit());
  });
}
