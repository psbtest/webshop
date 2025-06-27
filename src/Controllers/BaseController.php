<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use Twig\Environment;

abstract class BaseController
{
    protected Environment $twig;

    public function __construct()
    {
        $this->twig = Application::getInstance()->get_twig();
    }

    protected function render(string $template, array $data = []): void
    {
        // Add global template variables
        $data['cart_count'] = count($this->get_cart());
        $data['admin_logged_in'] = $_SESSION['admin_logged_in'] ?? false;
        $data['admin_username'] = $_SESSION['admin_username'] ?? null;
        
        echo $this->twig->render($template, $data);
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    protected function json_response(array $data, int $status_code = 200): void
    {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    protected function get_post_data(): array
    {
        return $_POST;
    }

    /**
     * @return array<string, mixed>
     */
    protected function get_get_data(): array
    {
        return $_GET;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function get_cart(): array
    {
        return $_SESSION['cart'] ?? [];
    }

    /**
     * @param array<int, array<string, mixed>> $cart
     */
    protected function set_cart(array $cart): void
    {
        $_SESSION['cart'] = $cart;
    }

    protected function add_flash_message(string $type, string $message): void
    {
        $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function get_flash_messages(): array
    {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }

    protected function generate_slug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }

    protected function validate_required_fields(array $data, array $required_fields): array
    {
        $errors = [];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Het veld '{$field}' is verplicht.";
            }
        }
        return $errors;
    }

    protected function sanitize_input(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
