<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;

class CategoryController extends BaseController
{
    public function index(): void
    {
        $category_model = new Category();
        $categories = $category_model->get_with_product_counts();
        
        $this->render('categories/index.twig', [
            'categories' => $categories,
        ]);
    }

    public function show(int $id): void
    {
        $category_model = new Category();
        $product_model = new Product();
        
        $category = $category_model->find_by_id($id);
        
        if (!$category) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }
        
        $products = $product_model->find_by_category($id);
        $product_count = $category_model->get_product_count($id);
        
        $this->render('categories/show.twig', [
            'category' => $category,
            'products' => $products,
            'product_count' => $product_count,
        ]);
    }
}
