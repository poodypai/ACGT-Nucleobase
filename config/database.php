<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'nucleotide_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never leak connection details to the browser.
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Please check config/database.php and that MySQL is running.');
        }
    }

    return $pdo;
}
