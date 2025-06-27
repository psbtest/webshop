<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Models\Product;

class OrderController extends BaseController
{
    public function checkout(): void
    {
        $cart = $this->get_cart();
        
        if (empty($cart)) {
            $this->add_flash_message('error', 'Je winkelwagen is leeg');
            $this->redirect('/cart');
            return;
        }
        
        $data = $this->get_post_data();
        
        // Validate required fields
        $required_fields = ['customer_name', 'customer_email', 'customer_address'];
        $errors = $this->validate_required_fields($data, $required_fields);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_flash_message('error', $error);
            }
            $this->redirect('/cart');
            return;
        }
        
        // Validate email format
        if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $this->add_flash_message('error', 'Ongeldig e-mailadres');
            $this->redirect('/cart');
            return;
        }
        
        // Calculate total and verify stock
        $total = 0;
        $validated_items = [];
        $product_model = new Product();
        
        foreach ($cart as $item) {
            $product = $product_model->find_by_id($item['product_id']);
            
            if (!$product || !$product['is_active']) {
                $this->add_flash_message('error', "Product '{$item['name']}' is niet meer beschikbaar");
                $this->redirect('/cart');
                return;
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                $this->add_flash_message('error', "Onvoldoende voorraad voor '{$item['name']}'");
                $this->redirect('/cart');
                return;
            }
            
            $item_total = $product['price'] * $item['quantity'];
            $total += $item_total;
            
            $validated_items[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $product['price'],
            ];
        }
        
        $order_data = [
            'customer_name' => $this->sanitize_input($data['customer_name']),
            'customer_email' => $this->sanitize_input($data['customer_email']),
            'customer_phone' => $this->sanitize_input($data['customer_phone'] ?? ''),
            'customer_address' => $this->sanitize_input($data['customer_address']),
            'total_amount' => $total,
            'status' => 'pending',
        ];
        
        try {
            $order_model = new Order();
            $order_id = $order_model->create_with_items($order_data, $validated_items);
            
            // Clear cart
            $this->set_cart([]);
            
            $this->redirect("/order/success/{$order_id}");
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het verwerken van je bestelling');
            $this->redirect('/cart');
        }
    }

    public function success(int $order_id): void
    {
        $order_model = new Order();
        $order = $order_model->find_by_id($order_id);
        $items = $order_model->find_items($order_id);
        
        if (!$order) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }
        
        $this->render('orders/success.twig', [
            'order' => $order,
            'items' => $items,
        ]);
    }

    public function track(): void
    {
        $get_data = $this->get_get_data();
        $order_id = isset($get_data['order_id']) ? (int) $get_data['order_id'] : 0;
        $email = $get_data['email'] ?? '';
        
        $order = null;
        $items = [];
        
        if ($order_id > 0 && !empty($email)) {
            $order_model = new Order();
            $order = $order_model->find_by_id($order_id);
            
            if ($order && strtolower($order['customer_email']) === strtolower($email)) {
                $items = $order_model->find_items($order_id);
            } else {
                $order = null;
                $this->add_flash_message('error', 'Bestelling niet gevonden of onjuist e-mailadres');
            }
        }
        
        $this->render('orders/track.twig', [
            'order' => $order,
            'items' => $items,
            'search_order_id' => $order_id,
            'search_email' => $email,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }
}
