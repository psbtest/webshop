<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connect();
        $this->create_tables();
    }

    private function connect(): void
    {
        $host = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $username = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASS'];

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    public function get_connection(): PDO
    {
        return $this->connection;
    }

    private function create_tables(): void
    {
        // Tables will be created here - implement based on migrations
        $this->run_migrations();
    }

    private function run_migrations(): void
    {
        // TODO: Implement migration runner
        // For now, create basic tables
        $this->create_basic_tables();
    }

    private function create_basic_tables(): void
    {
        // Create admin_users table first
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert default admin user
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $this->connection->prepare("
                INSERT INTO admin_users (username, password, email) 
                VALUES ('admin', ?, 'admin@webshop.com')
            ")->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
        }

        // Add other tables as needed
    }
}
