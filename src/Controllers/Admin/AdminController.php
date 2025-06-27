<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminUser;
use App\Core\Application;

class AdminController extends BaseController
{
    public function dashboard(): void
    {
        $stats = $this->get_dashboard_stats();
        
        $this->render('admin/dashboard.twig', [
            'stats' => $stats,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function login(): void
    {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
            $this->redirect('/admin');
            return;
        }
        
        $this->render('admin/login.twig', [
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function authenticate(): void
    {
        $username = $this->sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $this->add_flash_message('error', 'Vul beide velden in');
            $this->redirect('/admin/login');
            return;
        }
        
        $admin_model = new AdminUser();
        
        if ($admin_model->verify_password($username, $password)) {
            $admin = $admin_model->find_by_username($username);
            
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            $this->add_flash_message('success', 'Succesvol ingelogd');
            $this->redirect('/admin');
        } else {
            $this->add_flash_message('error', 'Ongeldige inloggegevens');
            $this->redirect('/admin/login');
        }
    }

    public function logout(): void
    {
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username']);
        session_destroy();
        $this->redirect('/admin/login');
    }

    /**
     * @return array<string, mixed>
     */
    private function get_dashboard_stats(): array
    {
        $db = Application::getInstance()->get_database()->get_connection();
        
        $product_count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $category_count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $order_count = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $page_count = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
        
        $pending_orders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
        $low_stock_products = $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 5 AND is_active = 1")->fetchColumn();
        
        $total_revenue = $db->query("
            SELECT COALESCE(SUM(total_amount), 0) 
            FROM orders 
            WHERE status IN ('processing', 'shipped', 'delivered')
        ")->fetchColumn();
        
        $recent_orders = $db->query("
            SELECT id, customer_name, total_amount, status, created_at 
            FROM orders 
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll();
        
        return [
            'products' => (int) $product_count,
            'categories' => (int) $category_count,
            'orders' => (int) $order_count,
            'pages' => (int) $page_count,
            'pending_orders' => (int) $pending_orders,
            'low_stock_products' => (int) $low_stock_products,
            'total_revenue' => (float) $total_revenue,
            'recent_orders' => $recent_orders,
        ];
    }
}
