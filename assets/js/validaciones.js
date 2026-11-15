/**
 * validaciones.js · Validación en el navegador para el formulario de registro.
 * Es solo una ayuda para el usuario: el servidor (registro.php) vuelve a validar todo.
 * Usa la API de validación de HTML5: setCustomValidity('') = campo válido.
 */
const correo = document.getElementById('correo');
const pass   = document.getElementById('password');
const pass2  = document.getElementById('password2');
const desc   = document.getElementById('descripcion');
const contador = document.getElementById('contador');

// El correo siempre en minúsculas (el patrón exige minúsculas)
if (correo) {
  correo.addEventListener('input', () => { correo.value = correo.value.toLowerCase(); });
}

// Regla de contraseña: mínimo 8 caracteres, con letras y números
function validarPassword() {
  const v = pass.value;
  const ok = v.length >= 8 && /[A-Za-z]/.test(v) && /\d/.test(v);
  pass.setCustomValidity(ok ? '' : 'Mínimo 8 caracteres, con letras y números.');
}

// Las dos contraseñas deben coincidir
function validarCoincidencia() {
  pass2.setCustomValidity(pass2.value === pass.value ? '' : 'Las contraseñas no coinciden.');
}

if (pass && pass2) {
  pass.addEventListener('input', () => { validarPassword(); validarCoincidencia(); });
  pass2.addEventListener('input', validarCoincidencia);
}

// Contador de caracteres de la descripción
if (desc && contador) {
  const actualizar = () => { contador.textContent = desc.value.length; };
  desc.addEventListener('input', actualizar);
  actualizar();
}
