<?php

class Database {

    private $host = 'localhost';
    private $dbName = 'croin';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';

    private static $instance = null;

    public function connect() {

        // reutiliza conexão
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";

        try {

            self::$instance = new PDO(
                $dsn,
                $this->user,
                $this->pass,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );

        } catch (PDOException $e) {

            error_log('[CROIN] DB error: ' . $e->getMessage());

            return null;
        }

        return self::$instance;
    }
}