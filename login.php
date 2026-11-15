<?php
/**
 * login.php · Inicio de sesión.
 * Busca al usuario por correo y compara la contraseña con su hash (password_verify).
 * El mensaje de error es el mismo si falla el correo o la contraseña,
 * para no revelar qué correos están registrados.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

if (esta_logueado()) {
    redirigir('index.php');
}

$error = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();
    $correo   = strtolower(trim($_POST['correo'] ?? ''));
    $password = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT id, nombre, correo, password_hash, rol FROM usuarios WHERE correo = ?');
    $st->execute([$correo]);
    $usuario = $st->fetch();

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        iniciar_sesion($usuario);
        flash('exito', 'Hola de nuevo, ' . $usuario['nombre'] . '.');
        redirigir('index.php');
    }
    $error = 'Correo o contraseña incorrectos.';
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<div class="caja caja-centrada">
  <h1 style="font-size:2rem">Iniciar sesión</h1>

  <?php if ($error): ?><div class="alerta alerta-error" role="alert"><?= e($error) ?></div><?php endif; ?>

  <form method="post" action="login.php">
    <?= csrf_campo() ?>
    <div class="campo">
      <label for="correo">Correo institucional</label>
      <input class="input" id="correo" name="correo" type="email" required autocomplete="email"
             placeholder="ab123456@uaeh.edu.mx" value="<?= e($correo) ?>">
    </div>
    <div class="campo">
      <label for="password">Contraseña</label>
      <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <button class="btn btn-block" type="submit">Entrar</button>
  </form>

  <p class="pie-form">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
