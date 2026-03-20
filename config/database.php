<?php
// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'balance_score');
define('DB_USER', 'root');
define('DB_PASS', '');

// API Key de Claude (Anthropic)
define('CLAUDE_API_KEY', ''); // Configurar con tu API key

// Conexión PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    return $pdo;
}

// URL base de la aplicación
define('BASE_URL', '/balance_score/');
