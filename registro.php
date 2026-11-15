<?php
/**
 * registro.php · Alta de nuevos usuarios (solo correo institucional).
 * Flujo: el formulario envía por POST -> se valida en el servidor ->
 * se guarda con la contraseña cifrada -> se inicia sesión automáticamente.
 * Todo el procesamiento va ANTES de imprimir HTML para poder usar redirecciones.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

if (esta_logueado()) {
    redirigir('index.php');
}

$errores = [];
$v = ['nombre' => '', 'correo' => '', 'carrera' => '', 'descripcion' => ''];   // valores para rellenar el formulario

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();

    $v['nombre']      = trim($_POST['nombre'] ?? '');
    $v['correo']      = strtolower(trim($_POST['correo'] ?? ''));
    $v['carrera']     = trim($_POST['carrera'] ?? '');
    $v['descripcion'] = trim($_POST['descripcion'] ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    $mayor     = isset($_POST['mayor_edad']);

    // --- Validaciones del servidor (las del navegador se pueden saltar) ---
    $largo = mb_strlen($v['nombre']);
    if ($largo < 2 || $largo > 80 || !preg_match('/^[\p{L}][\p{L} .\'-]*$/u', $v['nombre'])) {
        $errores['nombre'] = 'Escribe tu nombre (2 a 80 caracteres, solo letras).';
    }

    if (!correo_institucional_valido($v['correo'])) {
        $errores['correo'] = 'Usa tu correo institucional, por ejemplo ab123456@uaeh.edu.mx.';
    } else {
        $st = db()->prepare('SELECT id FROM usuarios WHERE correo = ?');
        $st->execute([$v['correo']]);
        if ($st->fetch()) {
            $errores['correo'] = 'Ese correo ya tiene una cuenta.';
        }
    }

    if (mb_strlen($v['carrera']) > 80) {
        $errores['carrera'] = 'La carrera admite máximo 80 caracteres.';
    }
    if (mb_strlen($v['descripcion']) > 200) {
        $errores['descripcion'] = 'La descripción admite máximo 200 caracteres.';
    }

    if ($msg = password_error($password)) {
        $errores['password'] = $msg;
    } elseif ($password !== $password2) {
        $errores['password2'] = 'Las contraseñas no coinciden.';
    }

    if (!$mayor) {
        $errores['mayor_edad'] = 'Debes confirmar que eres mayor de 18 años.';
    }

    // --- Si todo está bien, guardar ---
    if (!$errores) {
        try {
            $st = db()->prepare(
                'INSERT INTO usuarios (nombre, correo, password_hash, carrera, descripcion)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $st->execute([
                $v['nombre'],
                $v['correo'],
                password_hash($password, PASSWORD_DEFAULT),   // nunca se guarda la contraseña en claro
                $v['carrera'] !== '' ? $v['carrera'] : null,
                $v['descripcion'] !== '' ? $v['descripcion'] : null,
            ]);
            iniciar_sesion([
                'id' => (int) db()->lastInsertId(),
                'nombre' => $v['nombre'],
                'correo' => $v['correo'],
                'rol' => 'usuario',
            ]);
            flash('exito', '¡Bienvenido a ' . APP_NOMBRE . ', ' . $v['nombre'] . '!');
            redirigir('index.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {            // violación del UNIQUE (carrera de dos registros a la vez)
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                throw $ex;
            }
        }
    }
}

$titulo = 'Crear cuenta';
$scripts = ['validaciones.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="caja caja-centrada">
  <h1 style="font-size:2rem">Crear cuenta</h1>
  <p style="color:var(--texto-suave)">Solo para la comunidad universitaria.</p>

  <?php if ($errores): ?>
    <div class="alerta alerta-error" role="alert">Revisa los campos marcados.</div>
  <?php endif; ?>

  <form method="post" action="registro.php" id="form-registro">
    <?= csrf_campo() ?>

    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input class="input <?= isset($errores['nombre']) ? 'invalido' : '' ?>" id="nombre" name="nombre" type="text"
             maxlength="80" required autocomplete="name" value="<?= e($v['nombre']) ?>">
      <?php if (isset($errores['nombre'])): ?><div class="error-campo"><?= e($errores['nombre']) ?></div><?php endif; ?>
    </div>

    <div class="campo">
      <label for="correo">Correo institucional</label>
      <input class="input <?= isset($errores['correo']) ? 'invalido' : '' ?>" id="correo" name="correo" type="email"
             required autocomplete="email" placeholder="ab123456@uaeh.edu.mx"
             pattern="<?= e(correo_patron()) ?>" title="Dos letras minúsculas, seis números y @uaeh.edu.mx"
             value="<?= e($v['correo']) ?>">
      <?php if (isset($errores['correo'])): ?><div class="error-campo"><?= e($errores['correo']) ?></div><?php endif; ?>
    </div>

    <div class="campo">
      <label for="carrera">Carrera <span class="opcional">(opcional)</span></label>
      <input class="input <?= isset($errores['carrera']) ? 'invalido' : '' ?>" id="carrera" name="carrera" type="text"
             maxlength="80" value="<?= e($v['carrera']) ?>">
      <?php if (isset($errores['carrera'])): ?><div class="error-campo"><?= e($errores['carrera']) ?></div><?php endif; ?>
    </div>

    <div class="campo">
      <label for="descripcion">Sobre ti <span class="opcional">(opcional)</span></label>
      <textarea class="input <?= isset($errores['descripcion']) ? 'invalido' : '' ?>" id="descripcion" name="descripcion"
                rows="2" maxlength="200"><?= e($v['descripcion']) ?></textarea>
      <small><span id="contador">0</span>/200 caracteres</small>
      <?php if (isset($errores['descripcion'])): ?><div class="error-campo"><?= e($errores['descripcion']) ?></div><?php endif; ?>
    </div>

    <div class="campo">
      <label for="password">Contraseña</label>
      <input class="input <?= isset($errores['password']) ? 'invalido' : '' ?>" id="password" name="password" type="password"
             required minlength="8" autocomplete="new-password">
      <small>Mínimo 8 caracteres, con letras y números.</small>
      <?php if (isset($errores['password'])): ?><div class="error-campo"><?= e($errores['password']) ?></div><?php endif; ?>
    </div>

    <div class="campo">
      <label for="password2">Confirmar contraseña</label>
      <input class="input <?= isset($errores['password2']) ? 'invalido' : '' ?>" id="password2" name="password2" type="password"
             required autocomplete="new-password">
      <?php if (isset($errores['password2'])): ?><div class="error-campo"><?= e($errores['password2']) ?></div><?php endif; ?>
    </div>

    <div class="campo-check">
      <input type="checkbox" id="mayor_edad" name="mayor_edad" required>
      <label for="mayor_edad">Confirmo que soy mayor de 18 años. Algunos eventos incluyen bebidas alcohólicas.</label>
    </div>
    <?php if (isset($errores['mayor_edad'])): ?><div class="error-campo" style="margin-top:-12px;margin-bottom:14px"><?= e($errores['mayor_edad']) ?></div><?php endif; ?>

    <button class="btn btn-block" type="submit">Crear cuenta</button>
  </form>

  <p class="pie-form">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
