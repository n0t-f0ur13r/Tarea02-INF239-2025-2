<?php

/**
 * Conexión PDO única (singleton) a MySQL con UTF8MB4.
 */
if (!defined('DBH_INC_PHP')) {
    define('DBH_INC_PHP', 1);

    // === Configuración (ajusta a tu entorno) ===
    $DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
    $DB_NAME = getenv('DB_NAME') ?: 'example_database';
    $DB_USER = getenv('DB_USER') ?: 't2inf239';
    $DB_PASS = getenv('DB_PASS') ?: 't2inf239password%%';
    $DB_PORT = getenv('DB_PORT') ?: '3306';
    $DB_CHARSET = 'utf8mb4';

    /**
     * Retorna una instancia PDO compartida.
     */
    function db(): PDO {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        // Usa las variables definidas arriba dentro de la función
        $host = $GLOBALS['DB_HOST'];
        $name = $GLOBALS['DB_NAME'];
        $user = $GLOBALS['DB_USER'];
        $pass = $GLOBALS['DB_PASS'];
        $port = $GLOBALS['DB_PORT'];
        $charset = $GLOBALS['DB_CHARSET'];

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // usa prepared statements nativos
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Error claro para debug;
            http_response_code(500);
            exit('Error de conexión a la base de datos.');
        }

        return $pdo;
    }

}

