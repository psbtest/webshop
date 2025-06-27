<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Models\Category;

class HomeController extends BaseController
{
    public function index(): void
    {
        $product_model = new Product();
        $category_model = new Category();
        
        $featured_products = array_slice($product_model->find_active(), 0, 8);
        $categories = $category_model->get_with_product_counts();
        
        $this->render('home.twig', [
            'featured_products' => $featured_products,
            'categories' => $categories,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }
}
