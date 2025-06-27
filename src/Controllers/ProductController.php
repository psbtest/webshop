<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Models\Category;

class ProductController extends BaseController
{
    public function index(): void
    {
        $product_model = new Product();
        $category_model = new Category();
        
        $get_data = $this->get_get_data();
        $category_id = isset($get_data['category']) ? (int) $get_data['category'] : null;
        $search_query = $get_data['search'] ?? '';
        
        if (!empty($search_query)) {
            $products = $product_model->search($search_query);
            $page_title = "Zoekresultaten voor: {$search_query}";
        } elseif ($category_id) {
            $products = $product_model->find_by_category($category_id);
            $category = $category_model->find_by_id($category_id);
            $page_title = $category ? "Producten in: {$category['name']}" : "Producten";
        } else {
            $products = $product_model->find_active();
            $page_title = "Alle Producten";
        }
        
        $categories = $category_model->find_all();
        
        $this->render('products/index.twig', [
            'products' => $products,
            'categories' => $categories,
            'page_title' => $page_title,
            'current_category' => $category_id,
            'search_query' => $search_query,
        ]);
    }

    public function show(int $id): void
    {
        $product_model = new Product();
        $product = $product_model->find_with_category($id);
        
        if (!$product || !$product['is_active']) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }
        
        // Get related products from same category
        $related_products = [];
        if ($product['category_id']) {
            $all_related = $product_model->find_by_category((int) $product['category_id']);
            $related_products = array_filter($all_related, function($p) use ($id) {
                return $p['id'] !== $id;
            });
            $related_products = array_slice($related_products, 0, 4);
        }
        
        $this->render('products/show.twig', [
            'product' => $product,
            'related_products' => $related_products,
        ]);
    }
}
