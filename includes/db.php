<?php
/**
 * db.php · Conexión a MySQL con PDO.
 * Uso:  $pdo = db();   y luego consultas preparadas, por ejemplo:
 *       $st = $pdo->prepare('SELECT * FROM eventos WHERE id = ?');
 *       $st->execute([$id]);
 * Las consultas preparadas evitan la inyección SQL.
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;          // se crea una sola vez por petición
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errores como excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // filas como arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                    // preparadas reales
        ]);
    }
    return $pdo;
}
