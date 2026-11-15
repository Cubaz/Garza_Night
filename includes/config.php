<?php
/**
 * config.php · Configuración general del sitio.
 * Aquí se cambian los datos de conexión y las reglas básicas.
 * Todos los demás archivos lo incluyen, directa o indirectamente.
 */

// --- Base de datos (valores por defecto de XAMPP) ---
const DB_HOST = 'localhost';
const DB_NAME = 'garza_night';
const DB_USER = 'root';
const DB_PASS = '';          // XAMPP trae el usuario root sin contraseña

// --- Sitio ---
const APP_NOMBRE = 'Garza Night';
const BASE_URL   = '/garza-night';   // carpeta dentro de htdocs (sin diagonal final)

// --- Reglas de negocio ---
const DOMINIOS_PERMITIDOS = ['uaeh.edu.mx'];  // correos válidos para registrarse
const LIMITE_VISITANTES   = 6;                // eventos visibles sin iniciar sesión
const MAX_COMENTARIO      = 200;              // caracteres por comentario

// --- Zona horaria (para comparar fechas de eventos) ---
date_default_timezone_set('America/Mexico_City');

// --- Errores: mostrarlos mientras desarrollamos ---
// En una entrega "real" se pondría en 0 para no exponer detalles internos.
ini_set('display_errors', '1');
error_reporting(E_ALL);
