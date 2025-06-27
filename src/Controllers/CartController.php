<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;

class CartController extends BaseController
{
    public function add(): void
    {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        
        if ($product_id <= 0 || $quantity <= 0) {
            $this->json_response(['error' => 'Ongeldig product of aantal'], 400);
            return;
        }
        
        $product_model = new Product();
        $product = $product_model->find_by_id($product_id);
        
        if (!$product || !$product['is_active']) {
            $this->json_response(['error' => 'Product niet gevonden'], 404);
            return;
        }
        
        if ($product['stock_quantity'] < $quantity) {
            $this->json_response(['error' => 'Onvoldoende voorraad beschikbaar'], 400);
            return;
        }
        
        $cart = $this->get_cart();
        
        // Check if adding this quantity would exceed stock
        $current_cart_quantity = 0;
        foreach ($cart as $item) {
            if ($item['product_id'] == $product_id) {
                $current_cart_quantity = $item['quantity'];
                break;
            }
        }
        
        if (($current_cart_quantity + $quantity) > $product['stock_quantity']) {
            $this->json_response(['error' => 'Onvoldoende voorraad voor gewenste aantal'], 400);
            return;
        }
        
        $found = false;
        foreach ($cart as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $cart[] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image'] ?? '',
            ];
        }
        
        $this->set_cart($cart);
        $this->json_response(['success' => true, 'cart_count' => count($cart)]);
    }

    public function show(): void
    {
        $cart = $this->get_cart();
        $total = 0;
        $cart_with_details = [];
        
        if (!empty($cart)) {
            $product_model = new Product();
            
            foreach ($cart as $item) {
                $product = $product_model->find_by_id($item['product_id']);
                if ($product && $product['is_active']) {
                    $item_total = $item['price'] * $item['quantity'];
                    $total += $item_total;
                    
                    $cart_with_details[] = array_merge($item, [
                        'current_price' => $product['price'],
                        'stock_available' => $product['stock_quantity'],
                        'item_total' => $item_total,
                    ]);
                }
            }
        }
        
        $this->render('cart/show.twig', [
            'cart' => $cart_with_details,
            'total' => $total,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function update(): void
    {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        
        $cart = $this->get_cart();
        
        if ($quantity <= 0) {
            // Remove item from cart
            $cart = array_filter($cart, function($item) use ($product_id) {
                return $item['product_id'] !== $product_id;
            });
            $cart = array_values($cart); // Reset array keys
        } else {
            // Update quantity
            $product_model = new Product();
            $product = $product_model->find_by_id($product_id);
            
            if ($product && $product['stock_quantity'] >= $quantity) {
                foreach ($cart as &$item) {
                    if ($item['product_id'] == $product_id) {
                        $item['quantity'] = $quantity;
                        break;
                    }
                }
            } else {
                $this->add_flash_message('error', 'Onvoldoende voorraad beschikbaar');
            }
        }
        
        $this->set_cart($cart);
        $this->redirect('/cart');
    }

    public function remove(): void
    {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $cart = $this->get_cart();
        
        $cart = array_filter($cart, function($item) use ($product_id) {
            return $item['product_id'] !== $product_id;
        });
        $cart = array_values($cart); // Reset array keys
        
        $this->set_cart($cart);
        $this->add_flash_message('success', 'Product verwijderd uit winkelwagen');
        $this->redirect('/cart');
    }

    public function clear(): void
    {
        $this->set_cart([]);
        $this->add_flash_message('success', 'Winkelwagen geleegd');
        $this->redirect('/cart');
    }

    public function get_count(): void
    {
        $cart = $this->get_cart();
        $this->json_response(['count' => count($cart)]);
    }
}
