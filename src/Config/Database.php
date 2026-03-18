<?php

namespace App\Config;

use mysqli;

class Database {
    private static ?mysqli $connection = null;
    private static string $host;
    private static string $db;
    private static string $user;
    private static string $password;
    private static int $port = 3306;

    public static function connect(): mysqli {
        if (self::$connection !== null) {
            return self::$connection;
        }

        // Get database configuration from environment or use defaults
        self::$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
        self::$db = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'crud_app';
        self::$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'crud_user';
        self::$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'crud_password';

        \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        self::$connection = new mysqli(
            self::$host,
            self::$user,
            self::$password,
            self::$db,
            self::$port
        );

        self::$connection->set_charset('utf8mb4');

        return self::$connection;
    }

    public static function getInstance(): mysqli {
        return self::connect();
    }

    public static function disconnect(): void {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}
