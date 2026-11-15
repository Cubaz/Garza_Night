<?php
/**
 * logout.php · Cierra la sesión. Solo acepta POST con token CSRF
 * (el botón "Cerrar sesión" del menú es un pequeño formulario).
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}
csrf_exigir();

cerrar_sesion();
session_start();                         // sesión nueva y vacía, solo para mostrar el aviso
flash('info', 'Cerraste sesión.');
redirigir('login.php');
