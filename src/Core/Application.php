<?php
declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Application
{
    private static ?Application $instance = null;
    private Database $database;
    private Environment $twig;

    public function __construct()
    {
        $this->load_environment();
        $this->setup_database();
        $this->setup_twig();
        self::$instance = $this;
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_environment(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    }

    private function setup_database(): void
    {
        $this->database = new Database();
    }

    private function setup_twig(): void
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, [
            'cache' => $_ENV['APP_ENV'] === 'production' ? __DIR__ . '/../../storage/cache/twig' : false,
            'debug' => $_ENV['APP_DEBUG'] === 'true',
        ]);
        
        $this->twig->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'Webshop');
        $this->twig->addGlobal('base_url', $this->get_base_url());
    }

    public function get_database(): Database
    {
        return $this->database;
    }

    public function get_twig(): Environment
    {
        return $this->twig;
    }

    private function get_base_url(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
