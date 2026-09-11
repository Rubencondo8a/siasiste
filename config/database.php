<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'siasiste');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $maxRetries = 3;
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxRetries) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                return;
            } catch (\PDOException $e) {
                $lastError = $e;
                $attempt++;
                if ($attempt < $maxRetries) {
                    usleep(200000 * $attempt);
                }
            }
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            die("Error de conexión: " . $lastError->getMessage());
        }
        die("Error de conexión a la base de datos.");
    }

    public function reconnect()
    {
        $this->conn = null;
        $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
